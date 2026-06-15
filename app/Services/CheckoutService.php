<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

            // Consumir el cupón aplicado (control de un-solo-uso / stock).
            // markOrderPaid es idempotente, así que este incremento ocurre
            // exactamente una vez por pedido pagado. Sin esto, un cupón con
            // total_stock=1 nunca llegaba a agotarse en el flujo por código.
            if ($order->coupon_id) {
                Coupon::whereKey($order->coupon_id)->increment('used_count');
            }

            Log::info('Order pagada', [
                'order_id'  => $order->id,
                'reference' => $order->reference,
                'total'     => $order->total,
                'pi_id'     => $order->payment_intent_id,
            ]);

            $fresh = $order->load('items.product', 'tickets', 'customer');

            // Asegurar que el cliente tiene cuenta de app con credenciales.
            // Devuelve la contraseña EN CLARO solo si se ha generado una nueva
            // (cuenta nueva o reseteada). Se pasa al email para que el cliente
            // pueda entrar en la app. Nunca se loguea ni se persiste en claro.
            $appPassword = $fresh->customer
                ? $this->ensureAppCredentials($fresh->customer)
                : null;

            $this->sendOrderConfirmationEmail($fresh, $appPassword);

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
     *
     * @param ?string $appPassword Contraseña EN CLARO de la cuenta de la app
     *                             del cliente (solo si se acaba de generar).
     *                             Se incluye en el email para el primer acceso.
     *                             Nunca se loguea.
     */
    public function sendOrderConfirmationEmail(Order $order, ?string $appPassword = null): void
    {
        // ─────────────────────────────────────────────────────────────────
        // KILL SWITCH DE CORREO — TEMPORAL. Mientras se migra la BD de
        // abonados NO se envía ningún email a nadie. Este método tiene
        // credenciales SMTP reales, así que se corta aquí ANTES de conectar.
        // Reactivar: MAIL_KILL_SWITCH=false en Coolify (ver AppServiceProvider).
        // ─────────────────────────────────────────────────────────────────
        if (filter_var(env('MAIL_KILL_SWITCH', true), FILTER_VALIDATE_BOOLEAN)) {
            \Log::info('[MAIL KILL SWITCH] Email de confirmación NO enviado (correo desactivado).', [
                'order'    => $order->id,
                'customer' => $order->customer?->email ?: $order->guest_email,
            ]);
            return;
        }

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

            // Para cada ticket: localizar el QR PNG y generar PDF "Abono digital"
            // con datos del titular + QR embebido. Ambos van adjuntos al email.
            $attachments = [];
            foreach ($order->tickets ?? [] as $tk) {
                // 1) QR como PNG suelto (escaneable directo desde el movil)
                $absPath = $tk->qr_image_path ? storage_path('app/public/' . $tk->qr_image_path) : null;
                if ($absPath && is_file($absPath)) {
                    $attachments[] = [
                        'path' => $absPath,
                        'name' => 'qr_' . $order->reference . '_' . $tk->id . '.png',
                        'mime' => 'image/png',
                    ];
                }

                // 2) PDF "Abono digital" con QR embebido (base64) + datos titular
                try {
                    $pdfPath = $this->generateTicketPdf($order, $tk);
                    if ($pdfPath) {
                        $attachments[] = [
                            'path' => $pdfPath,
                            'name' => 'abono_' . $order->reference . '_' . $tk->id . '.pdf',
                            'mime' => 'application/pdf',
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning('No se pudo generar PDF abono', [
                        'order'  => $order->reference,
                        'ticket' => $tk->id,
                        'err'    => $e->getMessage(),
                    ]);
                }
            }

            // URLs de descarga de la app (placeholders si no hay reales).
            $appStoreUrl = (string) (config('club.app_store_url')   ?? 'https://apps.apple.com/app/algeciras-cf');
            $playStoreUrl = (string) (config('club.play_store_url') ?? 'https://play.google.com/store/apps/details?id=com.algecirascf.app');

            \Illuminate\Support\Facades\Mail::send(
                'emails.order-confirmation',
                [
                    'order'        => $order,
                    'appEmail'     => $to,
                    'appPassword'  => $appPassword,
                    'appStoreUrl'  => $appStoreUrl,
                    'playStoreUrl' => $playStoreUrl,
                ],
                function ($m) use ($to, $order, $attachments) {
                    $m->to($to)
                      ->subject('Algeciras CF · Confirmación de tu compra ' . $order->reference);
                    foreach ($attachments as $a) {
                        $m->attach($a['path'], ['as' => $a['name'], 'mime' => $a['mime']]);
                    }
                }
            );

            // Limpiar PDFs temporales tras enviar
            foreach ($attachments as $a) {
                if ($a['mime'] === 'application/pdf' && is_file($a['path']) && str_starts_with($a['path'], sys_get_temp_dir())) {
                    @unlink($a['path']);
                }
            }

            Log::info('Email confirmacion enviado', [
                'order'       => $order->reference,
                'to'          => $to,
                'attachments' => count($attachments),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Email confirmacion NO enviado', [
                'order' => $order->reference ?? null,
                'err'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Garantiza que el Customer tiene una cuenta de la app (User vinculado)
     * con credenciales utilizables, y devuelve la contraseña EN CLARO recién
     * generada para incluirla en el email de confirmación.
     *
     * Casos:
     *   - Customer SIN user_id (abonado importado, alta de taquilla): crea un
     *     User nuevo con contraseña generada, lo vincula (customer.user_id) y
     *     devuelve la contraseña en claro.
     *   - Customer CON user_id pero el User no tiene contraseña usable
     *     (importados sin password): resetea con una contraseña nueva y la
     *     devuelve.
     *   - Customer con cuenta ya operativa: NO toca nada y devuelve null
     *     (no exponemos ni reseteamos la contraseña de quien ya entra a la app).
     *
     * La contraseña en claro solo vive en memoria; se hashea con Hash::make()
     * antes de persistirla y NUNCA se escribe en logs.
     *
     * Público: lo llama también CobroManual (taquilla) para asegurar las
     * credenciales del abonado antes de enviar el email.
     */
    public function ensureAppCredentials(Customer $customer): ?string
    {
        try {
            $email = $customer->email;
            if (!$email) {
                return null;
            }

            // 1) ¿Ya tiene User vinculado y con contraseña? -> no tocar.
            $user = $customer->user_id ? User::find($customer->user_id) : null;

            // Fallback: buscar User por email (puede existir sin estar vinculado).
            if (!$user) {
                $user = User::where('email', $email)->first();
            }

            if ($user) {
                // Si el User ya tiene contraseña, lo dejamos como está: el cliente
                // ya tiene acceso y no reseteamos su clave por una compra.
                if (!empty($user->getAttribute('password'))) {
                    // Asegurar el vínculo customer->user por si faltaba.
                    if (!$customer->user_id) {
                        $customer->forceFill(['user_id' => $user->id])->save();
                    }
                    return null;
                }

                // User existente SIN contraseña (importado): asignar una nueva.
                $plain = $this->generarPasswordLegible();
                $user->forceFill(['password' => Hash::make($plain)])->save();
                if (!$customer->user_id) {
                    $customer->forceFill(['user_id' => $user->id])->save();
                }
                return $plain;
            }

            // 2) No existe User: crear uno nuevo con contraseña generada.
            $plain = $this->generarPasswordLegible();
            $user = User::create([
                'name'     => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: $email,
                'email'    => $email,
                'password' => Hash::make($plain),
            ]);

            $customer->forceFill(['user_id' => $user->id])->save();

            return $plain;
        } catch (\Throwable $e) {
            // Nunca rompemos la compra por las credenciales; el email irá sin
            // bloque de acceso (la vista lo omite con @if).
            Log::warning('No se pudieron generar credenciales de app', [
                'customer' => $customer->id ?? null,
                'err'      => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Genera una contraseña legible (sin caracteres ambiguos) de 10 chars.
     * Pensada para escribir a mano en la app desde el email.
     */
    protected function generarPasswordLegible(): string
    {
        return Str::password(10, letters: true, numbers: true, symbols: false, spaces: false);
    }

    /**
     * Genera un PDF "Abono Digital" del ticket con QR embebido en base64,
     * datos del titular, zona, sector/asiento, ref pedido. Guarda en tmp
     * y devuelve la ruta absoluta (el caller borra tras adjuntar).
     *
     * Devuelve null si falla o si no hay barryvdh/laravel-dompdf instalado.
     */
    protected function generateTicketPdf(Order $order, $ticket): ?string
    {
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return null;
        }

        // Embeber el QR como data URI para que dompdf lo renderice sin red.
        $qrBase64 = null;
        if ($ticket->qr_image_path) {
            $absPath = storage_path('app/public/' . $ticket->qr_image_path);
            if (is_file($absPath)) {
                $qrBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($absPath));
            }
        }

        // Resolver datos de sector/asiento sin usar relacion eloquent (no existe
        // 'seat' en Ticket — el seat_id apunta directamente a seats.id).
        $asiento = null;
        $zona    = 'General';
        if ($ticket->seat_id) {
            $seat = \App\Models\Seat::with('sector')->find($ticket->seat_id);
            if ($seat) {
                $sectorName = $seat->sector?->name ?? ('Sector ' . $seat->sector_id);
                $asiento    = $sectorName . ' · Fila ' . $seat->row . ' · Butaca ' . $seat->number;
                $zona       = $seat->sector?->zone_label ?? $sectorName;
            }
        } elseif ($ticket->product) {
            // Sin asiento concreto (abono Fondo, abono Joven, etc.) -> zona del producto
            $zona = $ticket->product->zone?->name ?? $ticket->product->name;
        }

        $data = [
            'titular'    => $ticket->holder_name ?: trim(($order->customer?->first_name ?? '') . ' ' . ($order->customer?->last_name ?? '')),
            'dni'        => $ticket->holder_dni ?: ($order->customer?->dni ?? ''),
            'email'      => $order->customer?->email ?: $order->guest_email,
            'producto'   => $ticket->product?->name ?? 'Abono Algeciras CF',
            'zona'       => $zona,
            'asiento'    => $asiento,
            'temporada'  => '2026-27',
            'referencia' => $order->reference,
            'emitido'    => optional($order->paid_at ?? $order->created_at)->format('d/m/Y H:i'),
            'qrBase64'   => $qrBase64,
            'uuid'       => $ticket->uuid,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.abono', $data)
            ->setPaper('a4', 'portrait');

        $tmpPath = tempnam(sys_get_temp_dir(), 'abono_') . '.pdf';
        file_put_contents($tmpPath, $pdf->output());

        return $tmpPath;
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

                // QR unificado para ABONOS: código corto de 12 chars en
                // legacy_qr (misma forma/densidad que los carnets legacy del
                // club → imprimible y escaneable en PVC, y legible por el
                // futuro lector móvil que consulta la BD). Renovación: reutiliza
                // el código del abonado; alta nueva: genera uno único.
                if ($item->product_type === 'abono') {
                    $code = Ticket::where('customer_id', $order->customer_id)
                        ->whereNotNull('legacy_qr')->where('legacy_qr', '!=', '')
                        ->where('id', '!=', $ticket->id)->value('legacy_qr');
                    if (! $code) {
                        do { $code = Str::random(12); } while (Ticket::where('legacy_qr', $code)->exists());
                    }
                    $ticket->update(['legacy_qr' => $code]);
                }
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
