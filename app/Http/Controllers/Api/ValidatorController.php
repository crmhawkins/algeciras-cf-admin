<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\FootballMatch;
use App\Models\Sector;
use App\Models\Season;
use App\Models\Ticket;
use App\Services\QrService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Validador de QRs en la puerta del estadio.
 *
 * POST /api/validar-qr           -> JSON con resultado (lector de la app/PWA)
 * GET  /v/{token}                -> landing pública (cámara genérica del móvil)
 * GET  /api/admin/matches/{m}/stats -> aforo live para dashboard de control
 *
 * Auth: los endpoints `validate` y `matchStats` exigen Bearer token Sanctum
 * con ability `scope:operator` (emitida por `OperatorAuthController::login`).
 * Se comprueba dentro del método para no tener que envolver la ruta en un
 * middleware extra. La landing pública `showPublic` queda abierta a propósito.
 */
class ValidatorController extends Controller
{
    public function __construct(private QrService $qrService) {}

    /**
     * Comprueba que la request lleva token Sanctum válido con scope:operator.
     * Devuelve `null` si todo OK, o un JsonResponse con el error si no.
     *
     * Resuelve el usuario explícitamente via guard `sanctum` porque la ruta
     * NO usa el middleware `auth:sanctum` (queremos controlar el formato
     * de error desde aquí, no el default de Laravel). Después fija el
     * usuario en la request para que el resto del controller pueda usar
     * `$request->user()` con normalidad.
     */
    private function ensureOperatorScope(Request $request): ?JsonResponse
    {
        $user = Auth::guard('sanctum')->user();
        if (! $user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }
        if (! $user->tokenCan('scope:operator')) {
            return response()->json(['message' => 'Sin permiso'], 403);
        }
        $request->setUserResolver(fn () => $user);
        return null;
    }

    public function validate(Request $request): JsonResponse
    {
        if ($deny = $this->ensureOperatorScope($request)) {
            return $deny;
        }

        $data = $request->validate([
            'token'    => ['required', 'string'],
            'match_id' => ['required', 'integer'],
            'gate_id'  => ['nullable', 'string', 'max:32'],
        ]);

        // ---------------- QR ANTIGUO DEL CLUB (legacy_qr) ----------------
        // El sistema anterior del club emitía un QR con un token plano de 12
        // chars (sin URL ni firma). Esos carnets PVC ya están impresos y la
        // app antigua sigue mostrándolos, así que el validador DEBE aceptarlos.
        // Se intenta SIEMPRE primero: si el escaneo coincide con un legacy_qr
        // resolvemos el ticket por ahí; si no, caemos al formato propio
        // (uuid.qr_token firmado) sin tocar su lógica.
        $legacyTicket = $this->resolveLegacyTicket($data['token']);
        if ($legacyTicket) {
            // Construimos un resultado equivalente al de un abono válido para
            // que el resto del flujo (temporada, pago, attendance) sea idéntico.
            $r = [
                'valid'               => true,
                'type'                => 'abono',
                'payload_version'     => 'legacy',
                'ticket_id'           => $legacyTicket->id,
                'customer_id'         => (int) $legacyTicket->customer_id,
                'season_id'           => (int) $legacyTicket->season_id,
                'match_id_from_token' => null,
            ];
            $ticket = Ticket::with(['customer', 'product', 'zone', 'match', 'orderItem.order'])
                ->find($legacyTicket->id);

            return $this->resolveAndRegister($request, $r, $ticket, $data);
        }

        $r = $this->qrService->verifyToken($data['token']);
        if (! $r || ! ($r['valid'] ?? false)) {
            return response()->json([
                'valid'   => false,
                'reason'  => $r['reason'] ?? 'invalid',
                'message' => 'QR no válido',
            ], 422);
        }

        $ticket = Ticket::with(['customer', 'product', 'zone', 'match', 'orderItem.order'])
            ->find($r['ticket_id']);

        return $this->resolveAndRegister($request, $r, $ticket, $data);
    }

    /**
     * Resuelve el Ticket de un QR escaneado por `legacy_qr` (token plano del
     * sistema antiguo). Devuelve null si el escaneo no es un legacy QR conocido.
     *
     * Un mismo abonado renovado tiene su legacy_qr replicado en el ticket de la
     * temporada anterior Y en el de la actual (mismo token). Priorizamos el de
     * la temporada actual para que el flujo de validación (que comprueba la
     * temporada) lo acepte directamente.
     */
    private function resolveLegacyTicket(string $scanned): ?Ticket
    {
        $scanned = trim($scanned);
        if ($scanned === '') {
            return null;
        }

        $base = Ticket::where('legacy_qr', $scanned);
        if (! (clone $base)->exists()) {
            return null;
        }

        $currentSeasonId = Season::current()?->id;

        // Preferimos el ticket de la temporada actual si existe; si no, el más
        // reciente con ese legacy_qr.
        return (clone $base)
            ->when($currentSeasonId, fn ($q) => $q->orderByRaw('season_id = ? DESC', [$currentSeasonId]))
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Tramo común tras resolver el ticket (legacy o propio): comprueba que
     * exista, esté pagado, no cancelado, y aplica la lógica de abono/entrada.
     * Centraliza lo que antes vivía inline en `validate()` para reutilizarlo
     * en ambos caminos sin duplicar reglas de seguridad.
     */
    private function resolveAndRegister(Request $request, array $r, ?Ticket $ticket, array $data): JsonResponse
    {
        if (! $ticket) {
            return response()->json([
                'valid'   => false,
                'reason'  => 'ticket_not_found',
                'message' => 'Ticket no encontrado',
            ], 404);
        }

        $order = $ticket->orderItem?->order;
        if (! $order || $order->status !== 'paid') {
            return response()->json([
                'valid'   => false,
                'reason'  => 'not_paid',
                'message' => 'Ticket no pagado',
            ], 422);
        }

        if (in_array($ticket->status, ['cancelled', 'refunded'], true)) {
            return response()->json([
                'valid'   => false,
                'reason'  => 'cancelled',
                'message' => 'Ticket cancelado o reembolsado',
            ], 422);
        }

        $matchId = (int) $data['match_id'];
        $gateId  = $data['gate_id'] ?? null;
        $userId  = $request->user()?->id;
        $type    = $ticket->product?->type;

        // ---------------- ABONO ----------------
        if ($type === 'abono') {
            $currentSeason = Season::current();
            if ($currentSeason && $ticket->season_id && (int) $ticket->season_id !== (int) $currentSeason->id) {
                return response()->json([
                    'valid'   => false,
                    'reason'  => 'wrong_season',
                    'message' => 'Abono de otra temporada',
                ], 422);
            }

            // Si el QR es v2 (rotativo) el match_id va firmado dentro del token
            // y debe coincidir con el partido que el operador está marcando.
            if (($r['payload_version'] ?? null) === 'v2'
                && isset($r['match_id_from_token'])
                && (int) $r['match_id_from_token'] !== $matchId) {
                return response()->json([
                    'valid'   => false,
                    'reason'  => 'wrong_match_token',
                    'message' => 'Este QR es de otro partido',
                ], 422);
            }

            return $this->registerAttendance($ticket, $matchId, $userId, $gateId, 'abono');
        }

        // ---------------- ENTRADA ----------------
        if ($type === 'entrada') {
            if ($ticket->match_id && (int) $ticket->match_id !== $matchId) {
                return response()->json([
                    'valid'   => false,
                    'reason'  => 'wrong_match',
                    'message' => 'Esta entrada es de otro partido',
                ], 422);
            }
            if (isset($r['match_id_from_token']) && (int) $r['match_id_from_token'] !== $matchId) {
                return response()->json([
                    'valid'   => false,
                    'reason'  => 'wrong_match_token',
                    'message' => 'Este QR es de otro partido',
                ], 422);
            }
            if ($ticket->status === 'used') {
                return response()->json([
                    'valid'   => false,
                    'reason'  => 'already_used',
                    'message' => 'QR ya usado',
                ], 409);
            }

            return DB::transaction(function () use ($ticket, $matchId, $userId, $gateId) {
                $ticket->status  = 'used';
                $ticket->used_at = now();
                if ($userId) {
                    $ticket->used_by_admin_id = $userId;
                }
                $ticket->save();

                return $this->registerAttendance($ticket, $matchId, $userId, $gateId, 'entrada');
            });
        }

        return response()->json([
            'valid'   => false,
            'reason'  => 'unsupported_product_type',
            'message' => 'Tipo de producto no admite acceso',
        ], 422);
    }

    /**
     * Inserta el Attendance respetando la UNIQUE(ticket_id, match_id):
     * si ya existía, devuelve "already_entered_match" (no permite reentrada).
     */
    private function registerAttendance(Ticket $ticket, int $matchId, ?int $userId, ?string $gateId, string $type): JsonResponse
    {
        try {
            $att = Attendance::create([
                'ticket_id'          => $ticket->id,
                'match_id'           => $matchId,
                'scanned_at'         => now(),
                'scanned_by_user_id' => $userId,
                'gate_id'            => $gateId,
            ]);
        } catch (QueryException $e) {
            // 23000 = integrity constraint violation (UNIQUE en ticket+match).
            if ($e->getCode() === '23000') {
                return response()->json([
                    'valid'   => false,
                    'reason'  => 'already_entered_match',
                    'message' => 'Ya entró a este partido',
                    'ticket'  => $this->ticketPayload($ticket),
                ], 409);
            }
            throw $e;
        }

        return response()->json([
            'valid'         => true,
            'type'          => $type,
            'attendance_id' => $att->id,
            'ticket'        => $this->ticketPayload($ticket),
            'message'       => 'Acceso autorizado',
        ]);
    }

    /**
     * Landing pública para cuando alguien escanea el QR con cámara
     * genérica. Muestra OK/KO informativo, NO marca como usado ni
     * registra Attendance.
     */
    public function showPublic(string $token)
    {
        $result = $this->qrService->verifyToken($token);
        $ticket = null;

        if ($result && ($result['valid'] ?? false)) {
            $ticket = Ticket::with(['customer', 'zone', 'product'])
                ->find($result['ticket_id']);
        }

        return view('pages.qr-resultado', [
            'result' => $result,
            'ticket' => $ticket,
        ]);
    }

    /**
     * Stats LIVE de aforo de un partido, agrupadas por sector.
     *
     * Auth: requiere Bearer Sanctum con ability `scope:operator`.
     * El admin del CRM también puede consultar estas stats (admin emite
     * tokens con el mismo scope via OperatorAuthController).
     */
    /**
     * Devuelve los próximos partidos (los últimos 14 días + futuros) para
     * que el operador elija sobre cuál está validando QRs.
     * Auth: scope:operator (mismo que validate).
     */
    public function upcomingMatches(Request $request): JsonResponse
    {
        if ($deny = $this->ensureOperatorScope($request)) {
            return $deny;
        }

        // Ventana razonable: -3 días (re-validaciones post-partido) hasta
        // +120 días (todo el calendario relevante: pretemporada + 4 meses).
        // Si la BD aún no tiene partidos en ese rango (caso pre-temporada),
        // devolvemos los próximos 20 partidos futuros para que el operador
        // pueda elegir algo siempre.
        $desde = now()->subDays(3);
        $hasta = now()->addDays(120);

        $q = FootballMatch::whereBetween('kickoff_at', [$desde, $hasta])
            ->orderBy('kickoff_at')
            ->limit(20)
            ->get();

        if ($q->isEmpty()) {
            $q = FootballMatch::where('kickoff_at', '>=', now()->subYear())
                ->orderBy('kickoff_at')
                ->limit(20)
                ->get();
        }

        $partidos = $q
            ->map(fn ($m) => [
                'id'         => $m->id,
                'matchday'   => $m->matchday,
                'opponent'   => $m->opponent,
                'home'       => (bool) ($m->home ?? false),
                'kickoff_at' => $m->kickoff_at?->toIso8601String(),
                'label'      => sprintf(
                    'J%s · %s%s',
                    $m->matchday ?? '?',
                    $m->opponent ?? '?',
                    $m->kickoff_at ? ' · '.$m->kickoff_at->format('d/m H:i') : ''
                ),
            ]);

        return response()->json([
            'matches' => $partidos,
        ]);
    }

    public function matchStats(Request $request, FootballMatch $match): JsonResponse
    {
        if ($deny = $this->ensureOperatorScope($request)) {
            return $deny;
        }

        $totalCapacity = (int) Sector::sum('capacity');
        $entered       = (int) Attendance::where('match_id', $match->id)->count();

        // Por sector: cruzamos attendance -> ticket -> zone -> sector (por zone slug/name).
        // Como en este proyecto la relación canónica es ticket.zone (Zone),
        // y sectors.zone es un string (tribuna_baja, fondo_norte...), agrupamos
        // por sector.zone y sumamos capacity por grupo para tener cifras
        // comparables con `entered`.
        $bySector = DB::table('attendances')
            ->join('tickets', 'tickets.id', '=', 'attendances.ticket_id')
            ->leftJoin('zones', 'zones.id', '=', 'tickets.zone_id')
            ->where('attendances.match_id', $match->id)
            ->select('zones.slug as zone_slug', 'zones.name as zone_name', DB::raw('COUNT(*) as entered'))
            ->groupBy('zones.slug', 'zones.name')
            ->get();

        // Capacidad por sector.zone (string). Usamos suma de capacity por
        // grupo para una vista "zona del estadio -> aforo".
        $capacityByZone = Sector::select('zone', DB::raw('SUM(capacity) as capacity'))
            ->groupBy('zone')
            ->pluck('capacity', 'zone')
            ->toArray();

        $bySectorOut = $bySector->map(function ($row) use ($capacityByZone) {
            $slug = $row->zone_slug ?? 'desconocida';
            $cap  = (int) ($capacityByZone[$slug] ?? 0);
            $ent  = (int) $row->entered;
            return [
                'sector_name' => $row->zone_name ?? $slug,
                'sector_slug' => $slug,
                'capacity'    => $cap,
                'entered'     => $ent,
                'percent'     => $cap > 0 ? round($ent * 100 / $cap, 1) : null,
            ];
        })->values();

        return response()->json([
            'match_id'        => $match->id,
            'kickoff_at'      => $match->kickoff_at?->toIso8601String(),
            'total_capacity'  => $totalCapacity,
            'entered'         => $entered,
            'percent'         => $totalCapacity > 0 ? round($entered * 100 / $totalCapacity, 1) : null,
            'by_sector'       => $bySectorOut,
            'last_updated_at' => now()->toIso8601String(),
        ]);
    }

    private function ticketPayload(Ticket $ticket): array
    {
        $customer = $ticket->customer;
        $zone     = $ticket->zone;
        $product  = $ticket->product;
        $match    = $ticket->match;
        $season   = $ticket->season;
        $itemMeta = $ticket->orderItem?->meta ?? [];

        $fila    = $ticket->row    ?? ($itemMeta['fila'] ?? ($itemMeta['row'] ?? null));
        $asiento = $ticket->number ?? $ticket->seat_number ?? ($itemMeta['asiento'] ?? ($itemMeta['seat'] ?? null));

        $matchLabel = null;
        if ($match) {
            $matchLabel = sprintf(
                'J%s vs %s',
                $match->matchday ?? '?',
                $match->opponent ?? '?',
            );
        }

        $typeLabel = match ($product?->type) {
            'abono'   => 'Abono',
            'entrada' => 'Entrada',
            default   => $product?->type ?? null,
        };

        // Foto del socio para que el operador la vea en el resultado:
        // el modelo Customer cuelga del User a través de user_id, y el
        // campo se llama `profile_image` (path relativo en storage/public).
        // Devolvemos URL absoluta lista para <Image source={{uri}}> en RN.
        $photoUrl = null;
        $rawPhoto = $customer?->user?->profile_image;
        if (is_string($rawPhoto) && $rawPhoto !== '') {
            if (preg_match('#^https?://#i', $rawPhoto)) {
                $photoUrl = $rawPhoto;
            } else {
                $photoUrl = asset('storage/'.ltrim($rawPhoto, '/'));
            }
        }

        // Tier (aficionado / abonado / abonado_vip / peñista) para que el
        // operador vea de un vistazo si es socio "premium".
        $tierLabel = null;
        $tier      = $customer?->tier ?? null;
        if ($tier) {
            $tierLabel = match ($tier) {
                'abonado_vip' => 'Abonado VIP',
                'abonado'     => 'Abonado',
                'peñista', 'penista' => 'Peñista',
                'aficionado'  => 'Aficionado',
                default       => ucfirst((string) $tier),
            };
        }

        return [
            'id'                 => $ticket->id,
            'customer_name'      => $customer
                ? trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''))
                : ($ticket->holder_name ?? null),
            'customer_photo_url' => $photoUrl,
            'tier_label'         => $tierLabel,
            'holder_dni'         => $ticket->holder_dni,
            'zone'               => $zone ? ['name' => $zone->name] : null,
            'fila'               => $fila,
            'asiento'            => $asiento,
            'match_label'        => $matchLabel,
            'type_label'         => $typeLabel,
            'season_name'        => $season?->name,
        ];
    }
}
