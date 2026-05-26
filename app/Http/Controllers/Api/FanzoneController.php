<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FootballMatch;
use App\Models\MvpVote;
use App\Models\Player;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FanzoneController extends Controller
{
    /**
     * GET /api/fanzone/historial-mvp
     * Últimos N partidos pasados con su MVP más votado.
     */
    public function historial(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 6);

        // Partidos finalizados (más recientes primero)
        $partidos = FootballMatch::query()
            ->where('status', 'finished')
            ->orderByDesc('kickoff_at')
            ->limit($limit)
            ->get();

        $historial = [];

        foreach ($partidos as $partido) {
            // Ganador: player_id con más votos para este partido
            $row = DB::table('mvp_votes')
                ->select('player_id', DB::raw('COUNT(*) as total'))
                ->where('match_id', $partido->id)
                ->groupBy('player_id')
                ->orderByDesc('total')
                ->first();

            if (!$row) {
                // partido sin votos → lo saltamos del historial
                continue;
            }

            $player = Player::find($row->player_id);
            if (!$player) continue;

            $historial[] = [
                'partido' => [
                    'id'         => $partido->id,
                    'fecha'      => $partido->kickoff_at?->toIso8601String(),
                    'rival'      => $partido->opponent,
                    'venue'      => $partido->venue,
                    'home_score' => $partido->home_score,
                    'away_score' => $partido->away_score,
                ],
                'mvp' => [
                    'id'     => $player->id,
                    'slug'   => $player->slug,
                    'nombre' => $player->display_name,
                    'dorsal' => $player->dorsal,
                    'foto'   => $player->photo ? url($player->photo) : null,
                    'position' => $player->position,
                ],
                'votos' => (int) $row->total,
            ];
        }

        return response()->json($historial);
    }

    /**
     * GET /api/fanzone/{matchId}/votos
     * Conteo de votos agrupado por jugador para un partido.
     */
    public function votos(int $matchId): JsonResponse
    {
        // Validamos que el partido existe
        $match = FootballMatch::findOrFail($matchId);

        $filas = DB::table('mvp_votes')
            ->select('player_id', DB::raw('COUNT(*) as votos'))
            ->where('match_id', $matchId)
            ->groupBy('player_id')
            ->orderByDesc('votos')
            ->get();

        $playerIds = $filas->pluck('player_id')->all();
        $players = Player::whereIn('id', $playerIds)->get()->keyBy('id');

        $total = (int) $filas->sum('votos');

        $resultado = $filas->map(function ($fila) use ($players, $total) {
            $p = $players->get($fila->player_id);
            if (!$p) return null;
            return [
                'jugador' => [
                    'id'     => $p->id,
                    'slug'   => $p->slug,
                    'nombre' => $p->display_name,
                    'dorsal' => $p->dorsal,
                    'foto'   => $p->photo ? url($p->photo) : null,
                    'position' => $p->position,
                ],
                'votos'      => (int) $fila->votos,
                'porcentaje' => $total > 0 ? round(($fila->votos / $total) * 100, 1) : 0,
            ];
        })->filter()->values();

        return response()->json([
            'match_id'  => $match->id,
            'total'     => $total,
            'resultado' => $resultado,
        ]);
    }

    /**
     * GET /api/fanzone/{matchId}/mi-voto
     * Voto del usuario autenticado para ese partido.
     */
    public function miVoto(Request $request, int $matchId): JsonResponse
    {
        $user = $request->user();
        $customer = $user?->customer;

        if (!$customer) {
            return response()->json(['voto' => null, 'jugador' => null]);
        }

        $voto = MvpVote::with('player')
            ->where('match_id', $matchId)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$voto || !$voto->player) {
            return response()->json(['voto' => null, 'jugador' => null]);
        }

        $p = $voto->player;
        return response()->json([
            'voto'    => $p->id,
            'jugador' => [
                'id'     => $p->id,
                'slug'   => $p->slug,
                'nombre' => $p->display_name,
                'dorsal' => $p->dorsal,
                'foto'   => $p->photo ? url($p->photo) : null,
                'position' => $p->position,
            ],
        ]);
    }

    /**
     * POST /api/fanzone/{matchId}/votar  body: { jugador: <player_id> }
     * Solo se permite votar si la "ventana" está abierta:
     *   - partido programado (próximo) o
     *   - partido terminado hace menos de 7 días
     */
    public function votar(Request $request, int $matchId): JsonResponse
    {
        $data = $request->validate([
            'jugador' => ['required', 'integer', 'exists:players,id'],
        ]);

        $match = FootballMatch::findOrFail($matchId);

        // Validar ventana de votación
        if (!$this->ventanaAbierta($match)) {
            return response()->json([
                'ok'  => false,
                'msg' => 'La votación para este partido no está abierta.',
            ], 422);
        }

        $user = $request->user();
        $customer = $user?->customer;

        if (!$customer) {
            return response()->json([
                'ok'  => false,
                'msg' => 'Necesitas tener una ficha de socio asociada para votar.',
            ], 403);
        }

        // Upsert: un socio = un voto por partido
        $voto = MvpVote::updateOrCreate(
            [
                'match_id'    => $match->id,
                'customer_id' => $customer->id,
            ],
            [
                'player_id' => (int) $data['jugador'],
                'voter_ip'  => $request->ip(),
            ]
        );

        return response()->json([
            'ok'    => true,
            'voto'  => $voto->player_id,
        ]);
    }

    /** Determina si la votación está abierta para un partido. */
    protected function ventanaAbierta(FootballMatch $match): bool
    {
        $status = $match->status;
        $kickoff = $match->kickoff_at ? Carbon::parse($match->kickoff_at) : null;

        // Próximo (programado) o en vivo → abierta
        if (in_array($status, ['scheduled', 'live'], true)) {
            return true;
        }

        // Terminado hace menos de 7 días → abierta
        if ($status === 'finished' && $kickoff && $kickoff->gte(now()->subDays(7))) {
            return true;
        }

        return false;
    }
}
