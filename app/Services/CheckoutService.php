<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de checkout.
 *
 * Refactorizado el 28/05/2026 para separar:
 *   - createPendingOrder()  → crea Customer + Order(status=pending) + OrderItems
 *                              SIN emitir tickets ni vaciar carrito.
 *   - markOrderPaid()       → al recibir webhook payment_intent.succeeded:
 *                              status=paid, emite Tickets+QR, incrementa sold.
 *
 * El flujo simulado de antes (status=paid directo sin pasar por Stripe)
 * se mantiene como `placeOrderSimulated()` por si hace falta debugging.
 */
class CheckoutService
{
    public function __construct(
        private readonly Cart $cart,
        private readonly QrService $qrService,
    ) {}

    /**
     * FASE 1: crea Customer + Order(pending) + OrderItems.
     * NO emite tickets, NO vacía el carrito.
     * Devuelve la Order recién creada para que el caller arranque el PaymentIntent.
     */
    public function createPendingOrder(array $data): Order
    {
        $items = $this->cart->items();
        if ($items->isEmpty()) {
            throw new \RuntimeException('Tu carrito está vacío.');
        }

        return DB::transaction(function () use ($data, $items) {
            $customer = Customer::firstOrCreate(
                ['email' => $data['email']],
                [
                    'first_name'  => $data['first_name'],
                    'last_name'   => $data['last_name'],
                    'phone'       => $data['phone'] ?? null,
                    'dni'         => $data['dni'] ?? null,
                    'address'     => $data['address'],
                    'city'        => $data['city'],
                    'province'    => $data['province'] ?? null,
                    'postal_code' => $data['postal_code'],
                    'country'     => $data['country'] ?? 'España',
                ]
            );

            $order = Order::create([
                'reference'        => Order::nextReference(),
                'customer_id'      => $customer->id,
                'guest_email'      => $data['email'],
                'status'           => 'pending',
                'channel'          => $data['channel'] ?? 'web',
                'subtotal'         => $this->cart->subtotal(),
                'vat'              => $this->cart->vat(),
                'shipping_cost'    => 0,
                'total'            => $this->cart->total(),
                'currency'         => 'EUR',
                'payment_gateway'  => 'stripe',     // pre-marcado, se confirma en webhook
                'payment_intent_id'=> null,         // lo escribe StripePaymentService
                'shipping_address' => array_intersect_key($data, array_flip([
                    'first_name','last_name','address','city','province','postal_code','country','phone',
                ])),
                'billing_address'  => array_intersect_key($data, array_flip([
                    'first_name','last_name','address','city','province','postal_code','country','dni',
                ])),
            ]);

            foreach ($items as $item) {
                OrderItem::create([
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
            }

            return $order->load('items.product', 'customer');
        });
    }

    /**
     * FASE 2: llamada desde el webhook de Stripe cuando el PI succeeded.
     * Marca la orden como pagada, emite Tickets+QR e incrementa sold.
     * Idempotente: si la orden ya está paid, no hace nada y devuelve la orden.
     */
    public function markOrderPaid(Order $order, ?string $paymentIntentId = null): Order
    {
        if ($order->status === 'paid') {
            return $order; // idempotencia
        }

        return DB::transaction(function () use ($order, $paymentIntentId) {
            $order->refresh();
            if ($order->status === 'paid') {
                return $order;
            }

            $order->update([
                'status'            => 'paid',
                'paid_at'           => now(),
                'payment_intent_id' => $paymentIntentId ?: $order->payment_intent_id,
            ]);

            foreach ($order->items()->with('product')->get() as $item) {
                if (!in_array($item->product_type, ['abono', 'entrada'])) {
                    continue;
                }

                for ($i = 0; $i < $item->qty; $i++) {
                    $ticket = Ticket::create([
                        'order_item_id' => $item->id,
                        'customer_id'   => $order->customer_id,
                        'product_id'    => $item->product_id,
                        'match_id'      => $item->product->match_id ?? null,
                        'season_id'     => $item->product->season_id ?? null,
                        'zone_id'       => $item->product->zone_id ?? null,
                        'holder_name'   => trim(
                            ($order->customer?->first_name ?? '') . ' ' .
                            ($order->customer?->last_name ?? '')
                        ),
                        'holder_dni'    => $order->customer?->dni,
                        'status'        => 'issued',
                    ]);
                    $this->qrService->generate($ticket);
                }

                if ($item->product) {
                    $item->product->increment('sold', $item->qty);
                }
            }

            Log::info('Order pagada', [
                'order_id'  => $order->id,
                'reference' => $order->reference,
                'total'     => $order->total,
                'pi_id'     => $order->payment_intent_id,
            ]);

            return $order->load('items.product', 'tickets', 'customer');
        });
    }

    /**
     * FASE 3: marca orden como fallida cuando llega payment_intent.payment_failed.
     */
    public function markOrderFailed(Order $order, ?string $reason = null): Order
    {
        if (in_array($order->status, ['paid','refunded'], true)) {
            return $order; // no degradamos órdenes ya cobradas
        }
        $order->update([
            'status'      => 'failed',
            'admin_notes' => trim(($order->admin_notes ?? '') . "\nStripe fail: " . ($reason ?? '?')),
        ]);
        return $order;
    }

    /**
     * Marca refund (charge.refunded del webhook).
     */
    public function markOrderRefunded(Order $order, ?string $reason = null): Order
    {
        $order->update([
            'status'      => 'refunded',
            'admin_notes' => trim(($order->admin_notes ?? '') . "\nStripe refund: " . ($reason ?? '?')),
        ]);
        return $order;
    }

    /**
     * Legacy: flujo simulado de antes (status=paid directo, emite tickets, vacía carrito).
     * Sigue disponible vía Artisan/tinker o feature flag por si hace falta debug,
     * pero no se llama desde el flujo normal cuando hay STRIPE_SECRET configurado.
     */
    public function placeOrderSimulated(array $data): Order
    {
        $order = $this->createPendingOrder($data);
        $order = $this->markOrderPaid($order, 'sim_' . uniqid());
        $order->update(['payment_gateway' => 'simulated']);
        $this->cart->clear();
        return $order;
    }
}
