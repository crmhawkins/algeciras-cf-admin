<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        private readonly Cart $cart,
        private readonly QrService $qrService,
    ) {}

    /**
     * Procesa un pedido completo desde el carrito + datos del cliente.
     * Por ahora marca el pedido como 'paid' simulado (sin Stripe).
     * Cuando tengamos las claves Stripe, este service cambia a 'pending' + webhook.
     *
     * @param array{first_name:string,last_name:string,email:string,phone?:string,dni?:string,address:string,city:string,province?:string,postal_code:string,country?:string} $data
     */
    public function placeOrder(array $data): Order
    {
        $items = $this->cart->items();
        if ($items->isEmpty()) {
            throw new \RuntimeException('Tu carrito está vacío.');
        }

        return DB::transaction(function () use ($data, $items) {
            // 1) Cliente: lo creamos o reusamos por DNI/email
            $customer = Customer::firstOrCreate(
                ['email' => $data['email']],
                [
                    'first_name'   => $data['first_name'],
                    'last_name'    => $data['last_name'],
                    'phone'        => $data['phone'] ?? null,
                    'dni'          => $data['dni'] ?? null,
                    'address'      => $data['address'],
                    'city'         => $data['city'],
                    'province'     => $data['province'] ?? null,
                    'postal_code'  => $data['postal_code'],
                    'country'      => $data['country'] ?? 'España',
                ]
            );

            // 2) Order
            $order = Order::create([
                'reference'        => Order::nextReference(),
                'customer_id'      => $customer->id,
                'status'           => 'paid',  // simulado — cuando Stripe esté, pasa por webhook
                'channel'          => 'web',
                'subtotal'         => $this->cart->subtotal(),
                'vat'              => $this->cart->vat(),
                'shipping_cost'    => 0,
                'total'            => $this->cart->total(),
                'currency'         => 'EUR',
                'payment_gateway'  => 'simulated',
                'payment_intent_id'=> 'sim_' . uniqid(),
                'shipping_address' => array_intersect_key($data, array_flip([
                    'first_name','last_name','address','city','province','postal_code','country','phone',
                ])),
                'billing_address'  => array_intersect_key($data, array_flip([
                    'first_name','last_name','address','city','province','postal_code','country','dni',
                ])),
                'paid_at'          => now(),
            ]);

            // 3) Items + Tickets QR si aplica
            foreach ($items as $item) {
                $orderItem = OrderItem::create([
                    'order_id'           => $order->id,
                    'product_id'         => $item->product->id,
                    'product_variant_id' => $item->variant?->id,
                    'product_type'       => $item->product->type,
                    'name'               => $item->product->getTranslation('name', 'es'),
                    'sku'                => $item->variant?->sku ?? $item->product->sku,
                    'qty'                => $item->qty,
                    'unit_price'         => $item->unit_price,
                    'vat_rate'           => $item->product->vat_rate,
                    'subtotal'           => $item->subtotal,
                    'vat_amount'         => $item->vat_amount,
                    'total'              => $item->total,
                    'meta'               => [
                        'size'  => $item->variant?->size,
                        'color' => $item->variant?->color,
                    ],
                ]);

                // 4) Si es abono o entrada, emitir 1 ticket por unidad con QR
                if (in_array($item->product->type, ['abono', 'entrada'])) {
                    for ($i = 0; $i < $item->qty; $i++) {
                        $ticket = Ticket::create([
                            'order_item_id' => $orderItem->id,
                            'customer_id'   => $customer->id,
                            'product_id'    => $item->product->id,
                            'match_id'      => $item->product->match_id,
                            'season_id'     => $item->product->season_id,
                            'zone_id'       => $item->product->zone_id,
                            'holder_name'   => $customer->full_name,
                            'holder_dni'    => $customer->dni,
                            'status'        => 'issued',
                        ]);
                        $this->qrService->generate($ticket);
                    }

                    // Actualizar contador de vendidas
                    $item->product->increment('sold', $item->qty);
                }
            }

            // 5) Vaciar el carrito
            $this->cart->clear();

            return $order->load('items.product', 'tickets', 'customer');
        });
    }
}
