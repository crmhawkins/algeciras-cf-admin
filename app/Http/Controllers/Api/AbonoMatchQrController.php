<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FootballMatch;
use App\Models\Ticket;
use App\Services\QrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Genera QR ROTATIVO por partido para abonos.
 *
 * GET /api/me/abonos/{ticket}/qr?match=X  (auth:sanctum)
 *
 * El cliente (PWA / app móvil) lo pinta justo antes de pasar por la
 * puerta. El QR es efímero — no se persiste. Cada llamada produce un
 * nonce distinto que invalida los QR previos firmados con otros nonces
 * (en realidad el unique attendance bloquea reentrada igualmente).
 */
class AbonoMatchQrController extends Controller
{
    public function __construct(private QrService $qrService) {}

    public function forMatch(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        // El user debe ser dueño del ticket (ownership por customer).
        $myCustomerId = $user->customer?->id;
        if (! $myCustomerId || (int) $ticket->customer_id !== (int) $myCustomerId) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $ticket->loadMissing(['product', 'zone', 'customer', 'orderItem']);

        if ($ticket->product?->type !== 'abono') {
            return response()->json([
                'message' => 'Este ticket no es un abono',
            ], 422);
        }

        $matchId = $request->query('match');
        $match   = null;
        if ($matchId) {
            $match = FootballMatch::find((int) $matchId);
            if (! $match) {
                return response()->json(['message' => 'Partido no encontrado'], 404);
            }
        } else {
            // Próximo partido en casa por defecto.
            $match = FootballMatch::upcoming()->where('venue', 'home')->first();
            if (! $match) {
                return response()->json(['message' => 'No hay próximos partidos en casa'], 404);
            }
        }

        $qr = $this->qrService->generateForMatch($ticket, $match);

        $customer = $ticket->customer;
        $itemMeta = $ticket->orderItem?->meta ?? [];

        return response()->json([
            'qr_data_uri'   => $qr['png_data_uri'],
            'token'         => $qr['token'],
            'match' => [
                'id'         => $match->id,
                'opponent'   => $match->opponent,
                'kickoff_at' => $match->kickoff_at?->toIso8601String(),
                'label'      => sprintf('J%s vs %s', $match->matchday ?? '?', $match->opponent ?? '?'),
            ],
            'expires_at'    => $qr['expires_at']?->toIso8601String(),
            'customer_name' => $customer
                ? trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''))
                : ($ticket->holder_name ?? null),
            'zone'          => $ticket->zone?->name,
            'asiento'       => $ticket->number
                ?? $ticket->seat_number
                ?? ($itemMeta['asiento'] ?? ($itemMeta['seat'] ?? null)),
        ]);
    }
}
