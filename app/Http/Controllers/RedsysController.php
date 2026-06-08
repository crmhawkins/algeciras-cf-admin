<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\RedsysPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endpoints de Redsys:
 *  - startPayment (GET)  → muestra el form auto-submitting al banco
 *  - notify (POST)       → server-to-server. Aquí marcamos el Order como
 *                          paid y disparamos generación de tickets QR.
 *  - returnOk (GET)      → redirección del usuario tras pago OK
 *  - returnKo (GET)      → redirección del usuario tras pago KO
 */
class RedsysController extends Controller
{
    public function __construct(
        protected RedsysPaymentService $redsys,
        protected CheckoutService $checkout,
    ) {}

    /** Inicia el pago — devuelve la vista que auto-postea a Redsys. */
    public function startPayment(Order $order)
    {
        // Si ya está pagado, redirigir al detalle del pedido
        if (in_array($order->status, ['paid', 'fulfilled'], true)) {
            return redirect()->route('pedido', $order->reference);
        }

        $payload = $this->redsys->buildFormPayload($order);

        return view('pago.redsys', [
            'order'     => $order,
            'actionUrl' => $this->redsys->url(),
            'fields'    => $payload,
        ]);
    }

    /**
     * Webhook server-to-server de Redsys. Debe responder 200 OK siempre
     * (incluso si firma falla) para que Redsys no reintente eternamente.
     */
    public function notify(Request $request)
    {
        $params    = $request->input('Ds_MerchantParameters');
        $signature = $request->input('Ds_Signature');

        if (! $params || ! $signature) {
            Log::warning('Redsys notify sin params/signature');
            return response('', 200);
        }

        $verified = $this->redsys->verifyNotification($params, $signature);
        if (! $verified) {
            Log::warning('Redsys notify firma inválida');
            return response('', 200);
        }

        $upper        = array_change_key_case($verified, CASE_UPPER);
        $merchantOrd  = (string) ($upper['DS_ORDER'] ?? '');
        $response     = (int) ($upper['DS_RESPONSE'] ?? -1);

        $order = Order::where('payment_intent_id', $merchantOrd)->first();
        if (! $order) {
            Log::warning('Redsys notify order no encontrada', ['ds_order' => $merchantOrd]);
            return response('', 200);
        }

        if ($this->redsys->isResponseOk($verified)) {
            // Idempotente: si ya está pagado no duplicamos
            if ($order->status !== 'paid' && $order->status !== 'fulfilled') {
                $this->checkout->markOrderPaid($order, 'redsys:' . $merchantOrd);
                Log::info('Redsys pago OK', ['order' => $order->id, 'response' => $response]);
            }
        } else {
            $this->checkout->markOrderFailed($order, 'redsys_response_' . $response);
            Log::info('Redsys pago KO', ['order' => $order->id, 'response' => $response]);
        }

        return response('', 200);
    }

    /** Redirect URL tras pago OK desde Redsys (el usuario llega aquí). */
    public function returnOk(Request $request)
    {
        $merchantOrd = (string) $request->query('o', '');
        $order = Order::where('payment_intent_id', $merchantOrd)->first();

        if (! $order) {
            return view('pago.exito')->with('order', null);
        }

        return view('pago.exito', ['order' => $order]);
    }

    /** Redirect URL tras pago KO desde Redsys. */
    public function returnKo(Request $request)
    {
        $merchantOrd = (string) $request->query('o', '');
        $order = Order::where('payment_intent_id', $merchantOrd)->first();

        return view('pago.fallo', [
            'order'  => $order,
            'retry'  => $order ? route('redsys.start', $order) : url('/'),
        ]);
    }
}
