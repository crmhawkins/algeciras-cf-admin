<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\QrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Validador de QRs en la puerta del estadio.
 *
 * POST /api/validar-qr  -> JSON con resultado (lector de la app/PWA)
 * GET  /v/{token}       -> landing pública (alguien escanea con cámara genérica)
 */
class ValidatorController extends Controller
{
    public function __construct(private QrService $qrService) {}

    public function validate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'    => ['required', 'string'],
            'match_id' => ['nullable', 'integer'],
        ]);

        $result = $this->qrService->verifyToken($data['token']);

        if (! $result || ! ($result['valid'] ?? false)) {
            return response()->json([
                'valid'   => false,
                'message' => 'QR no válido ('.($result['reason'] ?? 'desconocido').').',
            ], 422);
        }

        $ticket = Ticket::with(['customer', 'zone', 'product'])
            ->find($result['ticket_id']);

        if (! $ticket) {
            return response()->json([
                'valid'   => false,
                'message' => 'Ticket no encontrado.',
            ], 404);
        }

        if (! in_array($ticket->status, ['issued', 'used'], true)) {
            return response()->json([
                'valid'   => false,
                'message' => 'Ticket en estado no admitido: '.$ticket->status,
            ], 422);
        }

        if ($ticket->status === 'used') {
            return response()->json([
                'valid'   => false,
                'message' => 'QR ya usado el '.optional($ticket->used_at)->format('d/m/Y H:i').'.',
                'ticket'  => $this->ticketPayload($ticket),
            ], 409);
        }

        // Para entradas exigimos que el match_id del QR coincida con el del partido
        // que se está validando (si el lector lo manda). Esto evita reentradas con
        // QR de un partido anterior si el cliente no rotó el secret.
        if ($result['type'] === 'entrada') {
            if (! empty($data['match_id']) && (int) $data['match_id'] !== (int) $result['match_id']) {
                return response()->json([
                    'valid'   => false,
                    'message' => 'Este QR no corresponde al partido de hoy.',
                ], 422);
            }
            if ($ticket->match_id && (int) $ticket->match_id !== (int) $result['match_id']) {
                return response()->json([
                    'valid'   => false,
                    'message' => 'El QR está caducado (partido distinto).',
                ], 422);
            }
        }

        // Marca como usado. NOTA: el campo used_at ya está en la migración
        // original de tickets; si en algún entorno no existiera, hay que
        // añadirlo manualmente (no se intenta crear desde aquí).
        if (Schema::hasColumn('tickets', 'used_at')) {
            $ticket->used_at = now();
        }
        $ticket->status = 'used';
        if (Schema::hasColumn('tickets', 'used_by_admin_id') && $request->user()) {
            $ticket->used_by_admin_id = $request->user()->id;
        }
        $ticket->save();

        return response()->json([
            'valid'   => true,
            'message' => 'Acceso autorizado.',
            'ticket'  => $this->ticketPayload($ticket),
        ]);
    }

    /**
     * Landing pública para cuando alguien escanea el QR con una cámara
     * cualquiera (sin la app de control). Muestra OK/KO informativo,
     * NO marca el ticket como usado para no quemarlo por error.
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

    private function ticketPayload(Ticket $ticket): array
    {
        $customer = $ticket->customer;
        $zone     = $ticket->zone;

        return [
            'customer_name' => $customer
                ? trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''))
                : ($ticket->holder_name ?? null),
            'zone'    => $zone?->name,
            'fila'    => $ticket->row    ?? null,
            'asiento' => $ticket->number ?? $ticket->seat_number ?? null,
        ];
    }
}
