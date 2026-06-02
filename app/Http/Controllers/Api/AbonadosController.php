<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Season;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Endpoints de renovación de abono para la app móvil.
 *
 *   POST /api/abonados/lookup
 *       Body: { numero_abonado, dni }
 *       Resp: { found: true,  abonado: {...} }  |  { found: false, message: '...' }
 *
 *   POST /api/abonados/renovar
 *       Body: { numero_abonado, dni, items: [{product_id, qty}], stripe_payment_method_id? }
 *       Resp: { orderReference, checkoutUrl }
 *
 * El "número de abonado" es el `id` del Ticket de abono (no hay campo
 * `abonado_numero` en la tabla). El DNI se compara case-insensitive y sin
 * espacios. Sólo cuentan los abonos `status = 'issued'`.
 */
class AbonadosController extends Controller
{
    /**
     * Busca un abono por número (Ticket.id) + DNI.
     */
    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'numero_abonado' => 'required|integer|min:1',
            'dni'            => 'required|string|max:24',
        ]);

        $dni = $this->normalizeDni($data['dni']);

        $ticket = Ticket::with(['product', 'customer', 'season', 'zone', 'orderItem'])
            ->where('id', $data['numero_abonado'])
            ->where('status', 'issued')
            ->whereHas('product', fn ($q) => $q->where('type', Product::TYPE_ABONO))
            ->first();

        if (! $ticket) {
            return response()->json([
                'found'   => false,
                'message' => 'No encontramos un abono con esos datos',
            ]);
        }

        // Comparar DNI normalizado contra Ticket.holder_dni y, como fallback,
        // contra el DNI del Customer asociado (algunos tickets viejos no
        // rellenaron holder_dni en su día).
        $ticketDni   = $this->normalizeDni((string) ($ticket->holder_dni ?? ''));
        $customerDni = $this->normalizeDni((string) ($ticket->customer->dni ?? ''));

        if ($ticketDni !== $dni && $customerDni !== $dni) {
            return response()->json([
                'found'   => false,
                'message' => 'No encontramos un abono con esos datos',
            ]);
        }

        // Sector / fila / asiento — el modelo Ticket no tiene esos campos;
        // los datos quedaron en OrderItem.meta o en OrderItem.name cuando
        // se compró desde la app (`webRedirect`). Intentamos sacarlos.
        $sectorNombre = null;
        $fila         = null;
        $asiento      = null;

        if ($ticket->zone) {
            $sectorNombre = $ticket->zone->name;
        }

        if ($ticket->orderItem) {
            $meta = is_array($ticket->orderItem->meta) ? $ticket->orderItem->meta : [];
            $sectorNombre = $meta['sector_nombre'] ?? $meta['sectorNombre'] ?? $sectorNombre;
            $fila         = $meta['fila']          ?? $meta['row']          ?? null;
            $asiento      = $meta['asiento']       ?? $meta['numero']       ?? $meta['number'] ?? null;

            // Fallback: parsear OrderItem.name si tiene formato
            //   "Abono Algeciras CF · Sector X · Asiento Y"
            if (! $sectorNombre || ! $asiento) {
                $name = (string) $ticket->orderItem->name;
                if (preg_match('/Sector\s+([^·]+?)\s*·/u', $name, $m)) {
                    $sectorNombre = $sectorNombre ?: trim($m[1]);
                }
                if (preg_match('/Asiento\s+(\S+)/u', $name, $m)) {
                    $asiento = $asiento ?: trim($m[1]);
                }
            }
        }

        $customer = $ticket->customer;

        // Precio de renovación: el abono de la temporada actual más cercano
        // (mismo zone_id si tenemos uno), o el primero disponible.
        $currentSeason = Season::current();
        $precioRenovacion = $this->resolveRenovacionPrice($ticket, $currentSeason);

        return response()->json([
            'found'   => true,
            'abonado' => [
                'numero_abonado'    => $ticket->id,
                'nombre'            => $customer->first_name ?? $this->splitName($ticket->holder_name, 0),
                'apellidos'         => $customer->last_name  ?? $this->splitName($ticket->holder_name, 1),
                'email'             => $customer->email ?? null,
                'telefono'          => $customer->phone ?? null,
                'sector_nombre'     => $sectorNombre,
                'fila'              => $fila,
                'asiento'           => $asiento,
                'season_name'       => $ticket->season->name ?? null,
                'precio_renovacion' => $precioRenovacion,
                'renovacion_season' => $currentSeason?->name,
                'renovacion_product_id' => $this->resolveRenovacionProductId($ticket, $currentSeason),
            ],
        ]);
    }

    /**
     * Crea una Order pending para la NUEVA temporada y devuelve la URL del
     * checkout web. La app abrirá esta URL en un browser y, al volver,
     * llamará a /api/checkout/sync.
     */
    public function renovar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'numero_abonado'           => 'required|integer|min:1',
            'dni'                      => 'required|string|max:24',
            'items'                    => 'required|array|min:1|max:5',
            'items.*.product_id'       => 'required|integer|exists:products,id',
            'items.*.qty'              => 'required|integer|min:1|max:5',
            'stripe_payment_method_id' => 'nullable|string|max:120',
        ]);

        $dni = $this->normalizeDni($data['dni']);

        $ticket = Ticket::with('customer')
            ->where('id', $data['numero_abonado'])
            ->where('status', 'issued')
            ->whereHas('product', fn ($q) => $q->where('type', Product::TYPE_ABONO))
            ->first();

        if (! $ticket) {
            return response()->json([
                'message' => 'No encontramos un abono con esos datos',
            ], 404);
        }

        $ticketDni   = $this->normalizeDni((string) ($ticket->holder_dni ?? ''));
        $customerDni = $this->normalizeDni((string) ($ticket->customer->dni ?? ''));
        if ($ticketDni !== $dni && $customerDni !== $dni) {
            return response()->json([
                'message' => 'No encontramos un abono con esos datos',
            ], 404);
        }

        $order = DB::transaction(function () use ($data, $ticket) {
            // Reusar el Customer del abono original — es la persona que
            // está renovando. Si por lo que sea no existe, lo creamos a
            // partir de los datos del holder.
            $customer = $ticket->customer;
            if (! $customer) {
                $customer = Customer::firstOrCreate(
                    ['email' => 'renovacion-'.$ticket->id.'@algecirascf.es'],
                    [
                        'first_name' => $this->splitName($ticket->holder_name, 0) ?: 'Abonado',
                        'last_name'  => $this->splitName($ticket->holder_name, 1) ?: (string) $ticket->id,
                        'dni'        => $ticket->holder_dni,
                        'country'    => 'España',
                    ],
                );
            }

            // Calcular subtotal/IVA con precios server-side.
            $subtotal      = 0.0;
            $vat           = 0.0;
            $itemsResolved = [];
            foreach ($data['items'] as $row) {
                $product = Product::findOrFail($row['product_id']);
                $unit    = (float) $product->price;
                $qty     = (int) $row['qty'];
                $itemSub = $unit * $qty;
                $itemVat = $itemSub * ((float) ($product->vat_rate ?? 21) / 100);
                $subtotal += $itemSub;
                $vat      += $itemVat;
                $itemsResolved[] = compact('product', 'qty', 'unit', 'itemSub', 'itemVat');
            }

            $total = round($subtotal + $vat, 2);

            $order = Order::create([
                'reference'        => Order::nextReference(),
                'customer_id'      => $customer->id,
                'guest_email'      => $customer->email,
                'status'           => 'pending',
                'channel'          => 'web', // enum no incluye 'app'
                'subtotal'         => round($subtotal, 2),
                'vat'              => round($vat, 2),
                'shipping_cost'    => 0,
                'total'            => $total,
                'currency'         => 'EUR',
                'payment_gateway'  => 'stripe',
                'payment_intent_id'=> null,
                'shipping_address' => [
                    'first_name' => $customer->first_name,
                    'last_name'  => $customer->last_name,
                    'address'    => $customer->address,
                    'city'       => $customer->city,
                    'province'   => $customer->province,
                    'postal_code'=> $customer->postal_code,
                    'country'    => $customer->country ?? 'España',
                    'phone'      => $customer->phone,
                ],
                'billing_address'  => [
                    'first_name' => $customer->first_name,
                    'last_name'  => $customer->last_name,
                    'dni'        => $customer->dni ?? $ticket->holder_dni,
                    'address'    => $customer->address,
                    'city'       => $customer->city,
                    'province'   => $customer->province,
                    'postal_code'=> $customer->postal_code,
                    'country'    => $customer->country ?? 'España',
                ],
                'admin_notes'      => sprintf(
                    'Renovación abono #%d (DNI %s)',
                    $ticket->id,
                    $ticket->holder_dni ?? '-',
                ),
            ]);

            foreach ($itemsResolved as $r) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $r['product']->id,
                    'product_type' => $r['product']->type,
                    'name'         => $r['product']->getTranslation('name', 'es'),
                    'sku'          => $r['product']->sku,
                    'qty'          => $r['qty'],
                    'unit_price'   => $r['unit'],
                    'vat_rate'     => $r['product']->vat_rate ?? 21,
                    'subtotal'     => $r['itemSub'],
                    'vat_amount'   => $r['itemVat'],
                    'total'        => $r['itemSub'] + $r['itemVat'],
                    'meta'         => [
                        'renovacion_de_ticket_id' => $ticket->id,
                        'holder_dni'              => $ticket->holder_dni,
                    ],
                ]);
            }

            return $order;
        });

        $url = url('/pago-app/'.$order->reference);

        return response()->json([
            'orderReference' => $order->reference,
            'checkoutUrl'    => $url,
        ]);
    }

    /**
     * Quita espacios, guiones y pasa a mayúsculas. Útil para comparar DNIs
     * entre "12345678 Z" y "12345678z".
     */
    private function normalizeDni(string $dni): string
    {
        return strtoupper(preg_replace('/[\s\-]+/', '', $dni));
    }

    private function splitName(?string $full, int $index): ?string
    {
        if (! $full) return null;
        $parts = preg_split('/\s+/', trim($full));
        if ($index === 0) return $parts[0] ?? null;
        if (count($parts) <= 1) return null;
        return implode(' ', array_slice($parts, 1));
    }

    /**
     * Producto de la temporada actual al que renovar este abono. Si hay un
     * abono en la misma zona, ése; si no, el más caro de la temporada
     * (asumiendo que es la categoría "general").
     */
    private function resolveRenovacionProductId(Ticket $ticket, ?Season $season): ?int
    {
        if (! $season) return null;

        $q = Product::where('type', Product::TYPE_ABONO)
            ->where('season_id', $season->id)
            ->where('active', true);

        if ($ticket->zone_id) {
            $sameZone = (clone $q)->where('zone_id', $ticket->zone_id)->orderBy('price', 'desc')->first();
            if ($sameZone) return $sameZone->id;
        }

        return $q->orderBy('price', 'desc')->value('id');
    }

    private function resolveRenovacionPrice(Ticket $ticket, ?Season $season): float
    {
        $productId = $this->resolveRenovacionProductId($ticket, $season);
        if ($productId) {
            return (float) Product::where('id', $productId)->value('price');
        }
        // Fallback: precio del abono original
        return (float) ($ticket->product->price ?? 0);
    }
}
