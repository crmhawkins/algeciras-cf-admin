<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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

            $base = $this->cart->total(); // ya incluye subtotal + vat

            // Cupón opcional sobre el subtotal IVA incluido. El gestion_fee
            // se recalcula sobre el subtotal YA DESCONTADO.
            $productType         = $this->resolveProductTypeFromCart($items);
            [$coupon, $discount] = $this->resolveCoupon($data['coupon_code'] ?? null, $base, $productType);

            $baseAfterDiscount = round(max(0.0, $base - $discount), 2);
            $gestionFee        = Order::calcGestionFee($baseAfterDiscount);

            $order = Order::create([
                'reference'        => Order::nextReference(),
                'customer_id'      => $customer->id,
                'guest_email'      => $data['email'],
                'status'           => 'pending',
                'channel'          => $data['channel'] ?? 'web',
                'subtotal'         => $this->cart->subtotal(),
                'vat'              => $this->cart->vat(),
                'shipping_cost'    => 0,
                'gestion_fee'      => $gestionFee,
                'discount_amount'  => $discount,
                'coupon_id'        => $coupon?->id,
                'coupon_code'      => $coupon?->code,
                'total'            => round($baseAfterDiscount + $gestionFee, 2),
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

            $this->generateTicketsForOrder($order);

            Log::info('Order pagada', [
                'order_id'  => $order->id,
                'reference' => $order->reference,
                'total'     => $order->total,
                'pi_id'     => $order->payment_intent_id,
            ]);

            $fresh = $order->load('items.product', 'tickets', 'customer');

            $this->sendOrderConfirmationEmail($fresh);

            return $fresh;
        });
    }

    /**
     * Envia el email de confirmacion al cliente con resumen del pedido y los
     * PNG de los QR de cada ticket como adjuntos.
     *
     * Hace override SMTP en runtime con las credenciales validadas (Coolify
     * inyecta env vars distintas para info@algecirasclubdefutbol.com sin
     * password). Cuando se actualicen las vars del panel Coolify este metodo
     * sera no-op y se usara la config persistida.
     *
     * Best-effort: si falla solo se loguea, NO rompe la compra.
     */
    public function sendOrderConfirmationEmail(Order $order): void
    {
        try {
            $to = $order->customer?->email ?: $order->guest_email;
            if (!$to) { return; }

            // Override SMTP en runtime — TEMPORAL hasta que se actualicen
            // las env vars de Coolify. Documentar en task #99.
            \Illuminate\Support\Facades\Config::set('mail.mailers.smtp.host',       'smtp.ionos.es');
            \Illuminate\Support\Facades\Config::set('mail.mailers.smtp.port',       465);
            \Illuminate\Support\Facades\Config::set('mail.mailers.smtp.encryption', 'ssl');
            \Illuminate\Support\Facades\Config::set('mail.mailers.smtp.username',   'presupuestos@crmhawkins.com');
            \Illuminate\Support\Facades\Config::set('mail.mailers.smtp.password',   'B43y,2021');
            \Illuminate\Support\Facades\Config::set('mail.from.address',            'presupuestos@crmhawkins.com');
            \Illuminate\Support\Facades\Config::set('mail.from.name',               'Algeciras CF');

            // Reconstruir el mailer con la config nueva.
            app('mail.manager')->forgetMailers();

            // Localizar los QR PNG de cada ticket para adjuntarlos.
            $qrAttachments = [];
            foreach ($order->tickets ?? [] as $tk) {
                if (!$tk->qr_image_path) { continue; }
                $absPath = storage_path('app/public/' . $tk->qr_image_path);
                if (is_file($absPath)) {
                    $qrAttachments[] = [
                        'path' => $absPath,
                        'name' => 'qr_' . $order->reference . '_' . $tk->id . '.png',
                    ];
                }
            }

            \Illuminate\Support\Facades\Mail::send(
                'emails.order-confirmation',
                ['order' => $order],
                function ($m) use ($to, $order, $qrAttachments) {
                    $m->to($to)
                      ->subject('Algeciras CF · Confirmación de tu compra ' . $order->reference);
                    foreach ($qrAttachments as $a) {
                        $m->attach($a['path'], ['as' => $a['name'], 'mime' => 'image/png']);
                    }
                }
            );

            Log::info('Email confirmacion enviado', [
                'order' => $order->reference,
                'to'    => $to,
                'qrs'   => count($qrAttachments),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Email confirmacion NO enviado', [
                'order' => $order->reference ?? null,
                'err'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Genera Tickets QR (uno por unidad) para todos los items abono/entrada
     * del Order. Idempotente: salta items que ya tengan tickets creados.
     *
     * Usado por:
     *   - markOrderPaid() durante el flujo Redsys/Stripe
     *   - CobroManual cuando crea Order directamente como 'paid'
     */
    public function generateTicketsForOrder(Order $order): void
    {
        foreach ($order->items()->with('product')->get() as $item) {
            if (!in_array($item->product_type, ['abono', 'entrada'])) {
                continue;
            }

            // Idempotencia: si ya existen tickets para este item, no duplicar.
            $existing = Ticket::where('order_item_id', $item->id)->count();
            if ($existing >= $item->qty) {
                continue;
            }
            $toCreate = $item->qty - $existing;

            for ($i = 0; $i < $toCreate; $i++) {
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

            if ($item->product && $toCreate > 0) {
                $item->product->increment('sold', $toCreate);
            }
        }
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

    /**
     * Resuelve el cupón. Lanza ValidationException 422 con `coupon_code`
     * si se envía un code inválido (no se crea la order).
     *
     * @return array{0: ?Coupon, 1: float}
     */
    protected function resolveCoupon(?string $code, float $orderTotal, string $productType): array
    {
        $code = $code !== null ? trim($code) : '';
        if ($code === '') {
            return [null, 0.0];
        }

        $coupon = Coupon::whereRaw('LOWER(code) = ?', [strtolower($code)])->first();
        if (! $coupon) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Código no válido',
            ]);
        }

        $result = $coupon->applyTo($orderTotal, $productType);
        if (! $result['applies']) {
            throw ValidationException::withMessages([
                'coupon_code' => $result['reason'] ?? 'Cupón no aplicable',
            ]);
        }

        return [$coupon, (float) $result['discount']];
    }

    /**
     * Determina el product_type "dominante" del carrito para validar
     * applies_to. Si todos los items son del mismo tipo, devuelve ese;
     * si son mixtos, 'all'.
     */
    protected function resolveProductTypeFromCart(\Illuminate\Support\Collection $items): string
    {
        $types = $items
            ->map(fn ($it) => (string) ($it->product->type ?? 'all'))
            ->unique()
            ->values();

        return $types->count() === 1 ? $types->first() : 'all';
    }
}
