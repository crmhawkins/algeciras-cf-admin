<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Checkout endpoints para la app móvil (React Native).
 *
 *   POST /api/checkout/payment-sheet
 *       Body: { items: [{ productId, qty, variantId? }], customer: {...} }
 *       Resp: { paymentIntent, ephemeralKey, customer, publishableKey, orderReference }
 *
 *   POST /api/checkout/sync
 *       Body: { orderReference, paymentIntentId }
 *       Resp: { status: paid|pending|failed, order: {...} }
 *
 * Notas:
 *   - La app no usa el `Cart` de la sesión web (porque no hay sesión).
 *     Construimos los OrderItems directamente desde `items` recibidos.
 *   - El servidor RECALCULA precios contra Product.price para evitar que
 *     el cliente envíe un precio inventado.
 *   - El status final se confirma vía webhook (cuando llega payment_intent.succeeded).
 *     /sync es un atajo para que la app sepa el estado sin esperar webhook.
 */
class CheckoutController extends Controller
{
    public function __construct(private readonly StripePaymentService $stripe) {}

    /**
     * Crea Order pending + PaymentIntent + devuelve params para PaymentSheet.
     */
    public function paymentSheet(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items'                  => 'required|array|min:1|max:20',
            'items.*.productId'      => 'required|integer|exists:products,id',
            'items.*.qty'            => 'required|integer|min:1|max:50',
            'items.*.variantId'      => 'nullable|integer|exists:product_variants,id',

            'customer.first_name'    => 'required|string|min:2|max:80',
            'customer.last_name'     => 'required|string|min:2|max:80',
            'customer.email'         => 'required|email|max:160',
            'customer.phone'         => 'nullable|string|max:32',
            'customer.dni'           => 'nullable|string|max:24',
            'customer.address'       => 'required|string|min:5|max:200',
            'customer.city'          => 'required|string|min:2|max:80',
            'customer.province'      => 'nullable|string|max:80',
            'customer.postal_code'   => 'required|string|max:12',
            'customer.country'       => 'nullable|string|max:80',

            'channel'                => ['nullable', Rule::in(['app','web','admin'])],
        ]);

        $order = DB::transaction(function () use ($data) {
            // 1) Cliente — reusamos por email si ya existía.
            $customer = Customer::firstOrCreate(
                ['email' => $data['customer']['email']],
                array_merge($data['customer'], ['country' => $data['customer']['country'] ?? 'España']),
            );

            // 2) Calcular precios server-side (NUNCA confiar en el cliente).
            $subtotal = 0.0; $vat = 0.0;
            $itemsResolved = [];
            foreach ($data['items'] as $row) {
                $product = Product::findOrFail($row['productId']);
                $unit    = (float) $product->price;
                $qty     = (int) $row['qty'];
                $itemSub = $unit * $qty;
                $itemVat = $itemSub * ((float) ($product->vat_rate ?? 21) / 100);
                $subtotal += $itemSub;
                $vat      += $itemVat;
                $itemsResolved[] = [
                    'product'   => $product,
                    'variantId' => $row['variantId'] ?? null,
                    'qty'       => $qty,
                    'unit'      => $unit,
                    'subtotal'  => $itemSub,
                    'vat'       => $itemVat,
                ];
            }

            // 3) Order pending
            $order = Order::create([
                'reference'        => Order::nextReference(),
                'customer_id'      => $customer->id,
                'guest_email'      => $data['customer']['email'],
                'status'           => 'pending',
                'channel'          => $data['channel'] ?? 'app',
                'subtotal'         => round($subtotal, 2),
                'vat'              => round($vat, 2),
                'shipping_cost'    => 0,
                'total'            => round($subtotal + $vat, 2),
                'currency'         => 'EUR',
                'payment_gateway'  => 'stripe',
                'payment_intent_id'=> null,
                'shipping_address' => array_intersect_key($data['customer'], array_flip([
                    'first_name','last_name','address','city','province','postal_code','country','phone',
                ])),
                'billing_address'  => array_intersect_key($data['customer'], array_flip([
                    'first_name','last_name','address','city','province','postal_code','country','dni',
                ])),
            ]);

            // 4) Order items
            foreach ($itemsResolved as $r) {
                OrderItem::create([
                    'order_id'           => $order->id,
                    'product_id'         => $r['product']->id,
                    'product_variant_id' => $r['variantId'],
                    'product_type'       => $r['product']->type,
                    'name'               => $r['product']->getTranslation('name', 'es'),
                    'sku'                => $r['product']->sku,
                    'qty'                => $r['qty'],
                    'unit_price'         => $r['unit'],
                    'vat_rate'           => $r['product']->vat_rate ?? 21,
                    'subtotal'           => $r['subtotal'],
                    'vat_amount'         => $r['vat'],
                    'total'              => $r['subtotal'] + $r['vat'],
                ]);
            }

            return $order->load('customer','items.product');
        });

        // 5) Stripe params para PaymentSheet
        $params = $this->stripe->paymentSheetParamsFor($order);

        return response()->json($params);
    }

    /**
     * GET/POST /api/checkout/web-redirect
     *
     * Endpoint usado por la app móvil: crea Order pending con un único item
     * (asiento/abono) y devuelve la URL del checkout web. La app abre esa
     * URL en un browser (expo-web-browser), el usuario paga ahí vía Stripe
     * Elements y al volver, la app llama a /api/checkout/sync para conocer
     * el estado real.
     *
     * Body (todos opcionales — flexible para distintos flujos app):
     *   { sectorId, asientoId, precio, dni, type: 'abono'|'entrada' }
     *
     * Resp: { orderReference, checkoutUrl }
     */
    public function webRedirect(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sectorId'   => 'nullable|integer',
            'asientoId'  => 'nullable|integer',
            'precio'     => 'required|numeric|min:0.5|max:9999',
            'dni'        => 'nullable|string|max:24',
            'type'       => ['nullable', Rule::in(['abono','entrada','merch'])],
        ]);

        $precio = (float) $data['precio'];

        // Crear Order pending mínima sin OrderItem (la creamos como ad-hoc).
        // Cuando exista Product->id para el asiento (mapping seat→product),
        // este endpoint lo enlazará. De momento dejamos la order con un
        // OrderItem "abono ad-hoc" para que el total cuadre.
        $order = DB::transaction(function () use ($precio, $data) {
            $order = Order::create([
                'reference'        => Order::nextReference(),
                'guest_email'      => 'app@algecirascf.es', // TODO: usar email del usuario auth si llega Bearer
                'status'           => 'pending',
                'channel'          => 'web', // schema enum no incluye 'app'; usamos web (es lo más cercano)
                'subtotal'         => round($precio / 1.21, 2),
                'vat'              => round($precio - ($precio / 1.21), 2),
                'shipping_cost'    => 0,
                'total'            => $precio,
                'currency'         => 'EUR',
                'payment_gateway'  => 'stripe',
                'payment_intent_id'=> null,
                'admin_notes'      => sprintf(
                    'App mobile: type=%s sectorId=%s asientoId=%s dni=%s',
                    $data['type']  ?? 'abono',
                    $data['sectorId']  ?? '?',
                    $data['asientoId'] ?? '?',
                    $data['dni']  ?? '-',
                ),
            ]);

            \App\Models\OrderItem::create([
                'order_id'      => $order->id,
                'product_id'    => null,
                'product_type'  => $data['type'] ?? 'abono',
                'name'          => sprintf(
                    'Abono Algeciras CF · Sector %s · Asiento %s',
                    $data['sectorId']  ?? '?',
                    $data['asientoId'] ?? '?',
                ),
                'sku'           => 'APP-' . ($data['asientoId'] ?? 'X'),
                'qty'           => 1,
                'unit_price'    => $precio,
                'vat_rate'      => 21,
                'subtotal'      => round($precio / 1.21, 2),
                'vat_amount'    => round($precio - ($precio / 1.21), 2),
                'total'         => $precio,
            ]);

            return $order;
        });

        $url = url('/pago-app/' . $order->reference);

        return response()->json([
            'orderReference' => $order->reference,
            'checkoutUrl'    => $url,
        ]);
    }

    /**
     * La app llama después de cerrar el PaymentSheet o el browser para
     * conocer el estado real. Útil cuando el webhook todavía no ha llegado.
     */
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'orderReference'   => 'required|string|exists:orders,reference',
            'paymentIntentId'  => 'nullable|string',
        ]);

        $order = Order::with('items.product','tickets','customer')
            ->where('reference', $data['orderReference'])
            ->firstOrFail();

        // Si todavía está pending pero conocemos el PI, consultamos a Stripe.
        if ($order->status === 'pending' && $data['paymentIntentId']) {
            try {
                $pi = $this->stripe->rawClient()->paymentIntents->retrieve($data['paymentIntentId']);
                if ($pi->status === 'succeeded') {
                    app(\App\Services\CheckoutService::class)->markOrderPaid($order, $pi->id);
                    $order->refresh()->load('items.product','tickets','customer');
                } elseif ($pi->status === 'requires_payment_method' && $pi->last_payment_error) {
                    // pagó y rebotó la tarjeta
                    $reason = $pi->last_payment_error->message ?? 'card declined';
                    app(\App\Services\CheckoutService::class)->markOrderFailed($order, $reason);
                    $order->refresh();
                }
            } catch (\Throwable $e) {
                // si Stripe está caído, devolvemos el estado actual y ya
            }
        }

        return response()->json([
            'status'    => $order->status,
            'reference' => $order->reference,
            'total'     => (float) $order->total,
            'tickets'   => $order->tickets->map(fn ($t) => [
                'id' => $t->id, 'status' => $t->status, 'qr_url' => $t->qr_url ?? null,
            ])->values(),
        ]);
    }
}
