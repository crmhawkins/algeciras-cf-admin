<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

            'coupon_code'            => 'nullable|string|max:64',
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

            // 3) Cupón opcional — calcula descuento sobre el subtotal IVA incluido.
            //    Si el cliente envía un code inválido, abortamos la creación de
            //    la order para no cobrar más de la cuenta sin avisar.
            $base = round($subtotal + $vat, 2);

            $productTypeForCoupon = $this->resolveProductTypeFromItems($itemsResolved);
            [$coupon, $discount] = $this->resolveCoupon($data['coupon_code'] ?? null, $base, $productTypeForCoupon);

            $baseAfterDiscount = round(max(0.0, $base - $discount), 2);
            $gestionFee        = Order::calcGestionFee($baseAfterDiscount);
            $totalFinal        = round($baseAfterDiscount + $gestionFee, 2);

            $order = Order::create([
                'reference'        => Order::nextReference(),
                'customer_id'      => $customer->id,
                'guest_email'      => $data['customer']['email'],
                'status'           => 'pending',
                'channel'          => $data['channel'] ?? 'app',
                'subtotal'         => round($subtotal, 2),
                'vat'              => round($vat, 2),
                'shipping_cost'    => 0,
                'gestion_fee'      => $gestionFee,
                'discount_amount'  => $discount,
                'coupon_id'        => $coupon?->id,
                'coupon_code'      => $coupon?->code,
                'total'            => $totalFinal,
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
            'sectorId'    => 'nullable|integer',
            'asientoId'   => 'nullable|integer',
            'precio'      => 'required|numeric|min:0.5|max:9999',
            'dni'         => 'nullable|string|max:24',
            'type'        => ['nullable', Rule::in(['abono','entrada','merch'])],
            'coupon_code' => 'nullable|string|max:64',
        ]);

        // El parámetro `precio` es el PRECIO BASE del producto (lo que verá
        // el cliente en la pantalla del plano del estadio). El total final
        // que se cobra incluye un 5% de gastos de gestión (igual que
        // Ticketmaster / Compralaentrada / ServiCaixa).
        $precioBase   = (float) $data['precio'];
        $subtotal     = round($precioBase / 1.21, 2);
        $vat          = round($precioBase - $subtotal, 2);

        // Cupón opcional sobre el precio base IVA incluido.
        [$coupon, $discount] = $this->resolveCoupon(
            $data['coupon_code'] ?? null,
            $precioBase,
            $data['type'] ?? 'all'
        );

        $baseAfterDiscount = round(max(0.0, $precioBase - $discount), 2);
        $gestionFee        = Order::calcGestionFee($baseAfterDiscount);
        $totalFinal        = round($baseAfterDiscount + $gestionFee, 2);

        // Crear Order pending mínima sin OrderItem (la creamos como ad-hoc).
        // Cuando exista Product->id para el asiento (mapping seat→product),
        // este endpoint lo enlazará. De momento dejamos la order con un
        // OrderItem "abono ad-hoc" para que el total cuadre.
        $order = DB::transaction(function () use ($precioBase, $subtotal, $vat, $gestionFee, $totalFinal, $data, $coupon, $discount) {
            $order = Order::create([
                'reference'        => Order::nextReference(),
                'guest_email'      => 'app@algecirascf.es', // TODO: usar email del usuario auth si llega Bearer
                'status'           => 'pending',
                'channel'          => 'web', // schema enum no incluye 'app'; usamos web (es lo más cercano)
                'subtotal'         => $subtotal,
                'vat'              => $vat,
                'shipping_cost'    => 0,
                'gestion_fee'      => $gestionFee,
                'discount_amount'  => $discount,
                'coupon_id'        => $coupon?->id,
                'coupon_code'      => $coupon?->code,
                'total'            => $totalFinal,
                'currency'         => 'EUR',
                'payment_gateway'  => 'stripe',
                'payment_intent_id'=> null,
                'admin_notes'      => sprintf(
                    'App mobile: type=%s sectorId=%s asientoId=%s dni=%s | precio_base=%.2f gestion_5pct=%.2f coupon=%s discount=%.2f',
                    $data['type']  ?? 'abono',
                    $data['sectorId']  ?? '?',
                    $data['asientoId'] ?? '?',
                    $data['dni']  ?? '-',
                    $precioBase,
                    $gestionFee,
                    $coupon?->code ?? '-',
                    $discount,
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
                'unit_price'    => $precioBase,
                'vat_rate'      => 21,
                'subtotal'      => $subtotal,
                'vat_amount'    => $vat,
                'total'         => $precioBase,  // OrderItem.total = precio base sin gestion
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

    /**
     * Resuelve el cupón a aplicar a partir del code enviado. Si llega code
     * pero no es válido, lanza ValidationException 422 con mensaje en
     * `coupon_code` para que el frontend lo muestre debajo del input.
     *
     * @return array{0: ?Coupon, 1: float}  [cupón o null, descuento aplicado en €]
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
     * Reduce el tipo de producto de un carrito multi-item a un solo string
     * para validar contra applies_to. Si todos los items son del mismo type
     * devuelve ese type; si son mixtos, 'all'.
     *
     * @param array<int, array{product: Product}> $itemsResolved
     */
    protected function resolveProductTypeFromItems(array $itemsResolved): string
    {
        $types = collect($itemsResolved)
            ->map(fn ($r) => (string) ($r['product']->type ?? 'all'))
            ->unique()
            ->values();

        return $types->count() === 1 ? $types->first() : 'all';
    }
}
