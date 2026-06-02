<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Webhook receiver de Stripe.
 *
 * URL pública: POST /webhooks/stripe
 * Eventos manejados:
 *   - payment_intent.succeeded         → markOrderPaid (emite tickets+QR)
 *   - payment_intent.payment_failed    → markOrderFailed
 *   - charge.refunded                  → markOrderRefunded
 *
 * Firma verificada con STRIPE_WEBHOOK_SECRET en cabecera Stripe-Signature.
 * Devolvemos 200 SIEMPRE que la firma sea válida (incluso si no manejamos
 * el tipo de evento) — Stripe reintenta cualquier no-2xx con backoff.
 */
class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly StripePaymentService $stripe,
        private readonly CheckoutService $checkout,
    ) {}

    public function handle(Request $request): Response
    {
        $payload   = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');

        try {
            $event = $this->stripe->verifyWebhook($payload, $signature);
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook firma inválida', ['err' => $e->getMessage()]);
            return response('Bad signature', 400);
        }

        $type   = $event->type;
        $object = $event->data->object;

        try {
            match ($type) {
                'payment_intent.succeeded'      => $this->onPaymentIntentSucceeded($object),
                'payment_intent.payment_failed' => $this->onPaymentIntentFailed($object),
                'charge.refunded'               => $this->onChargeRefunded($object),
                default                         => Log::info("Stripe event ignorado: {$type}"),
            };
        } catch (\Throwable $e) {
            Log::error('Stripe webhook handler error', [
                'type' => $type,
                'err'  => $e->getMessage(),
                'trace'=> $e->getTraceAsString(),
            ]);
            // 500 → Stripe reintenta (con backoff). Si lo recibimos repetido,
            // markOrderPaid es idempotente.
            return response('Handler error', 500);
        }

        return response('OK', 200);
    }

    private function onPaymentIntentSucceeded(\Stripe\PaymentIntent $pi): void
    {
        $order = $this->orderFromPaymentIntent($pi);
        if (!$order) {
            Log::warning('PI succeeded sin order asociada', ['pi' => $pi->id]);
            return;
        }
        $this->checkout->markOrderPaid($order, $pi->id);
    }

    private function onPaymentIntentFailed(\Stripe\PaymentIntent $pi): void
    {
        $order = $this->orderFromPaymentIntent($pi);
        if (!$order) return;

        $reason = $pi->last_payment_error?->message ?? 'unknown';
        $this->checkout->markOrderFailed($order, $reason);
    }

    private function onChargeRefunded(\Stripe\Charge $charge): void
    {
        $piId = $charge->payment_intent;
        if (!$piId) return;

        $order = Order::where('payment_intent_id', $piId)->first();
        if (!$order) return;

        $this->checkout->markOrderRefunded($order, "charge {$charge->id}");
    }

    /**
     * Busca la Order asociada a un PaymentIntent. Primero por metadata.order_id
     * (más fiable), si no por payment_intent_id (fallback).
     */
    private function orderFromPaymentIntent(\Stripe\PaymentIntent $pi): ?Order
    {
        $orderId = $pi->metadata['order_id'] ?? null;
        if ($orderId) {
            $order = Order::find((int) $orderId);
            if ($order) return $order;
        }
        return Order::where('payment_intent_id', $pi->id)->first();
    }
}
