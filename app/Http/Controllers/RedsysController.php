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

        $this->processSignedResult($params, $signature, 'notify');

        return response('', 200);
    }

    /** Redirect URL tras pago OK desde Redsys (el usuario llega aquí). */
    public function returnOk(Request $request)
    {
        $order = $this->orderFromReturn($request);

        if (! $order) {
            return view('pago.exito')->with('order', null);
        }

        return view('pago.exito', ['order' => $order]);
    }

    /** Redirect URL tras pago KO desde Redsys. */
    public function returnKo(Request $request)
    {
        $order = $this->orderFromReturn($request);

        return view('pago.fallo', [
            'order'  => $order,
            'retry'  => $order ? route('redsys.start', $order) : url('/'),
        ]);
    }

    /**
     * Redsys añade a las URL OK/KO los mismos parámetros firmados que manda
     * al notify. Si llegan y la firma es válida los procesamos también aquí:
     * el pedido no se queda "pending" si el notify server-to-server se retrasa
     * o no llega (p. ej. en local, donde Redsys no alcanza el notify).
     * markOrderPaid es idempotente, así que notify + retorno no duplican nada.
     */
    protected function orderFromReturn(Request $request): ?Order
    {
        $params    = (string) $request->query('Ds_MerchantParameters', '');
        $signature = (string) $request->query('Ds_Signature', '');

        if ($params !== '' && $signature !== '') {
            $order = $this->processSignedResult($params, $signature, 'retorno');
            if ($order) {
                return $order;
            }
        }

        // Sin parámetros firmados: solo mostramos el pedido, sin cambiar su estado.
        return $this->findOrder((string) $request->query('o', ''));
    }

    /**
     * Localiza el pedido por su Ds_Order. Al pagarse, markOrderPaid guarda
     * payment_intent_id como "redsys:{Ds_Order}", así que hay que buscar por
     * las dos formas: si el notify llega antes que el usuario vuelva del banco,
     * el retorno ya no encontraría el pedido por el Ds_Order a secas.
     */
    protected function findOrder(string $merchantOrd): ?Order
    {
        if ($merchantOrd === '') {
            return null;
        }

        return Order::whereIn('payment_intent_id', [$merchantOrd, 'redsys:' . $merchantOrd])->first();
    }

    /**
     * Verifica la firma y aplica el resultado al pedido. El pedido se localiza
     * por el DS_ORDER firmado (nunca por parámetros sin firmar) y solo se marca
     * pagado si el importe cobrado coincide con el total del pedido.
     */
    protected function processSignedResult(string $params, string $signature, string $via): ?Order
    {
        $verified = $this->redsys->verifyNotification($params, $signature);
        if (! $verified) {
            Log::warning("Redsys {$via}: firma inválida");
            return null;
        }

        $upper       = array_change_key_case($verified, CASE_UPPER);
        $merchantOrd = (string) ($upper['DS_ORDER'] ?? '');
        $response    = (int) ($upper['DS_RESPONSE'] ?? -1);

        $order = $this->findOrder($merchantOrd);
        if (! $order) {
            Log::warning("Redsys {$via}: order no encontrada", ['ds_order' => $merchantOrd]);
            return null;
        }

        if ($this->redsys->isResponseOk($verified)) {
            $cobrado  = (int) ($upper['DS_AMOUNT'] ?? -1);
            $esperado = (int) round((float) $order->total * 100);
            if ($cobrado !== $esperado) {
                Log::warning("Redsys {$via}: importe cobrado distinto del pedido — revisar a mano", [
                    'order' => $order->id, 'cobrado' => $cobrado, 'esperado' => $esperado,
                ]);
                return $order;
            }

            // Idempotente: si ya está pagado no duplicamos
            if ($order->status !== 'paid' && $order->status !== 'fulfilled') {
                $this->checkout->markOrderPaid($order, 'redsys:' . $merchantOrd);
                Log::info("Redsys pago OK ({$via})", ['order' => $order->id, 'response' => $response]);
            }
        } else {
            $this->checkout->markOrderFailed($order, 'redsys_response_' . $response);
            Log::info("Redsys pago KO ({$via})", ['order' => $order->id, 'response' => $response]);
        }

        return $order->refresh();
    }
}
