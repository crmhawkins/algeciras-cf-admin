<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Wrapper sobre la SDK oficial de Stripe.
 *
 * Cashier ya trae `stripe/stripe-php` como dependencia transitiva, pero
 * preferimos un client propio en vez de los helpers de Cashier porque sólo
 * usamos PaymentIntents one-off (sin Cashier Billable, sin suscripciones por
 * ahora — eso vendrá con los abonos recurrentes en una fase posterior).
 *
 * Credenciales esperadas en .env:
 *   STRIPE_KEY=pk_test_...           (pública, para Stripe.js / PaymentSheet)
 *   STRIPE_SECRET=sk_test_...        (privada, sólo backend)
 *   STRIPE_WEBHOOK_SECRET=whsec_...  (verificación firma webhook)
 *
 * En cuanto el club tenga Redsys, este servicio se sustituye por otro
 * equivalente y `Order::payment_gateway` pasa a guardar "redsys" en vez de
 * "stripe". Mantenemos la abstracción para facilitar el swap.
 */
class StripePaymentService
{
    private ?StripeClient $stripe = null;

    /**
     * Constructor sin side-effects: NO bombea aunque STRIPE_SECRET esté vacío.
     * El error sólo se lanza cuando alguien intenta usar de verdad la SDK
     * (createIntent, ensureCustomer, verifyWebhook). Así el controlador
     * puede inyectar el servicio sin romper rutas ajenas al checkout.
     */
    public function __construct() {}

    /**
     * Cancela un PaymentIntent existente (uso: al aplicar cupón sobre una
     * Order pending, hay que invalidar el PI viejo antes de crear uno con
     * el nuevo importe). Silencioso si el PI ya no es cancelable.
     */
    public function cancelPaymentIntent(string $intentId): void
    {
        if (! str_starts_with($intentId, 'pi_')) {
            return;
        }
        try {
            $this->client()->paymentIntents->cancel($intentId);
        } catch (\Throwable $e) {
            \Log::info('cancelPaymentIntent ignorado', ['err' => $e->getMessage()]);
        }
    }

    private function client(): StripeClient
    {
        if ($this->stripe instanceof StripeClient) {
            return $this->stripe;
        }
        $secret = (string) config('services.stripe.secret');
        if ($secret === '') {
            throw new \RuntimeException(
                'STRIPE_SECRET no configurado en .env — checkout no operativo.'
            );
        }
        Stripe::setApiKey($secret);
        return $this->stripe = new StripeClient($secret);
    }

    /** Stripe SDK crudo, sólo si hace falta. */
    public function rawClient(): StripeClient
    {
        return $this->client();
    }

    public function isConfigured(): bool
    {
        return (string) config('services.stripe.secret') !== '';
    }

    /**
     * Crea un PaymentIntent para una Order que está en estado `pending`.
     * Devuelve el PI ya guardado contra la orden vía `order.payment_intent_id`.
     *
     * Importes en céntimos (Stripe trabaja en unidades indivisibles).
     */
    public function createIntentForOrder(Order $order): PaymentIntent
    {
        $amountCents = (int) round(((float) $order->total) * 100);
        if ($amountCents < 50) {
            // Stripe mínimo €0.50 para EUR
            throw new \RuntimeException("Importe inválido para Stripe: {$amountCents}c");
        }

        // Si la orden ya tenía un PI (reintento), lo reutilizamos
        if ($order->payment_intent_id && str_starts_with($order->payment_intent_id, 'pi_')) {
            try {
                $existing = $this->client()->paymentIntents->retrieve($order->payment_intent_id);
                // Sólo reutilizamos si está en un estado todavía pagable.
                if (in_array($existing->status, ['requires_payment_method','requires_confirmation','requires_action'], true)) {
                    return $existing;
                }
            } catch (\Throwable $e) {
                // PI no existe o no es accesible — caemos a crear uno nuevo.
            }
        }

        $intent = $this->client()->paymentIntents->create([
            'amount'              => $amountCents,
            'currency'            => strtolower($order->currency ?: 'eur'),
            'description'         => "Algeciras CF · pedido {$order->reference}",
            'receipt_email'       => $order->customer?->email ?? $order->guest_email,
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'order_id'        => (string) $order->id,
                'order_reference' => $order->reference,
                'customer_id'     => (string) ($order->customer_id ?? ''),
                'channel'         => $order->channel ?: 'web',
            ],
        ]);

        $order->update([
            'payment_gateway'   => 'stripe',
            'payment_intent_id' => $intent->id,
        ]);

        return $intent;
    }

    /**
     * Crea Customer Stripe para un cliente nuestro y devuelve su ID.
     * Idempotente: si el cliente ya tiene stripe_id, lo reusa.
     *
     * Sólo se usa para el flujo de PaymentSheet de la app móvil — la web
     * no lo necesita, va con guest checkout.
     */
    public function ensureStripeCustomer(Customer $customer): string
    {
        if ($customer->stripe_id ?? null) {
            return $customer->stripe_id;
        }

        $sc = $this->client()->customers->create([
            'email' => $customer->email,
            'name'  => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
            'phone' => $customer->phone,
            'metadata' => ['customer_id' => (string) $customer->id],
        ]);

        // Cashier almacena stripe_id en la tabla customers (migración del 25/05).
        $customer->forceFill(['stripe_id' => $sc->id])->save();
        return $sc->id;
    }

    /**
     * Crea un ephemeralKey para usar con PaymentSheet en la app RN.
     * Devuelve { customer, ephemeralKey, paymentIntent, publishableKey }.
     */
    public function paymentSheetParamsFor(Order $order): array
    {
        if (!$order->customer) {
            throw new \RuntimeException('Order sin customer — no se puede crear PaymentSheet');
        }

        $stripeCustomerId = $this->ensureStripeCustomer($order->customer);
        $intent           = $this->createIntentForOrder($order);

        $ephemeralKey = $this->client()->ephemeralKeys->create(
            ['customer' => $stripeCustomerId],
            ['stripe_version' => '2024-09-30.acacia'],
        );

        return [
            'paymentIntent'  => $intent->client_secret,
            'ephemeralKey'   => $ephemeralKey->secret,
            'customer'       => $stripeCustomerId,
            'publishableKey' => (string) config('services.stripe.key'),
            'orderReference' => $order->reference,
        ];
    }

    /**
     * Verifica una request de webhook y devuelve el evento parseado.
     * Lanza excepción si la firma es inválida.
     */
    public function verifyWebhook(string $rawPayload, string $sigHeader): \Stripe\Event
    {
        $secret = (string) config('services.stripe.webhook.secret');
        if ($secret === '') {
            throw new \RuntimeException('STRIPE_WEBHOOK_SECRET no configurado.');
        }
        return Webhook::constructEvent($rawPayload, $sigHeader, $secret);
    }
}
