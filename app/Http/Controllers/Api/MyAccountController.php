<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Http\Request;

/**
 * Endpoints "mi cuenta" para web y app móvil (Algeciras CF).
 *
 * Compatibles con la app React Native:
 *   GET  /api/abonos/usuario/{id}     → abonos del usuario (tickets de tipo abono)
 *   GET  /api/entradas/usuario/{id}   → entradas del usuario (tickets de tipo entrada)
 *   POST /api/abonos/liberar          → liberar plaza de abono (no-show)
 *
 * Todas requieren auth:sanctum y validan que {id} sea el del usuario logueado
 * (un usuario no puede consultar abonos de otro).
 */
class MyAccountController extends Controller
{
    /** GET /api/abonos/usuario/{id} — abonos del socio */
    public function abonosUsuario(Request $request, int $userId)
    {
        $this->ensureSameUser($request, $userId);

        $customer = $request->user()->customer;
        if (! $customer) {
            return response()->json([]);
        }

        $abonos = Ticket::query()
            ->where('customer_id', $customer->id)
            ->whereHas('product', fn ($q) => $q->where('type', 'abono'))
            ->with(['product', 'zone'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Ticket $t) => $this->serializeTicket($t, 'abono'));

        return response()->json($abonos);
    }

    /** GET /api/entradas/usuario/{id} — entradas del usuario */
    public function entradasUsuario(Request $request, int $userId)
    {
        $this->ensureSameUser($request, $userId);

        $customer = $request->user()->customer;
        if (! $customer) {
            return response()->json(['entradas' => []]);
        }

        $entradas = Ticket::query()
            ->where('customer_id', $customer->id)
            ->whereHas('product', fn ($q) => $q->where('type', 'entrada'))
            ->with(['product', 'zone'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Ticket $t) => $this->serializeTicket($t, 'entrada'));

        return response()->json(['entradas' => $entradas]);
    }

    /** POST /api/abonos/liberar — liberar plaza para un partido (no-show) */
    public function liberarAbono(Request $request)
    {
        $data = $request->validate([
            'abonoId' => 'required|integer',
            'matchId' => 'nullable|integer',
        ]);

        $customer = $request->user()->customer;
        $ticket = Ticket::where('id', $data['abonoId'])
            ->where('customer_id', $customer?->id)
            ->first();

        if (! $ticket) {
            return response()->json(['error' => 'Abono no encontrado.'], 404);
        }

        // Marcar liberación en meta (TODO: implementar tabla seat_releases si se necesita)
        $meta = $ticket->meta ?? [];
        $meta['released_for_matches'] = array_unique(array_merge(
            $meta['released_for_matches'] ?? [],
            $data['matchId'] ? [$data['matchId']] : ['next']
        ));
        $ticket->update(['meta' => $meta]);

        return response()->json(['ok' => true, 'message' => 'Plaza liberada.']);
    }

    /** GET /api/me/orders — pedidos completos del usuario */
    public function misPedidos(Request $request)
    {
        $customer = $request->user()->customer;
        if (! $customer) {
            return response()->json([]);
        }

        $orders = Order::where('customer_id', $customer->id)
            ->with(['items.product'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($orders);
    }

    private function ensureSameUser(Request $request, int $userId): void
    {
        abort_unless($request->user()->id === $userId, 403, 'No tienes permiso para ver estos datos.');
    }

    private function serializeTicket(Ticket $t, string $tipo): array
    {
        return [
            'id'        => $t->id,
            'tipo'      => $tipo,
            'producto'  => [
                'id'    => $t->product?->id,
                'name'  => $t->product?->name,
                'price' => $t->product?->price,
            ],
            'zona'      => $t->zone ? ['id' => $t->zone->id, 'name' => $t->zone->name] : null,
            'fila'      => $t->row ?? null,
            'butaca'    => $t->seat_number ?? $t->seat ?? null,
            'qr'        => $t->qr_token ?? $t->qr_code ?? $t->uuid ?? null,
            'createdAt' => $t->created_at?->toIso8601String(),
            'orderRef'  => null, // Ticket no tiene relación order directa
        ];
    }
}
