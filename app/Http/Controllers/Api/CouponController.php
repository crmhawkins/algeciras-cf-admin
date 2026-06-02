<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint(s) de cupones para el checkout (web + app móvil).
 *
 *   POST /api/checkout/coupon/preview
 *       Body: { code: string, subtotal: number, product_type: 'abono'|'entrada'|'merch'|'all' }
 *       Resp OK:    { valid: true,  coupon: {...}, discount_amount, new_subtotal, message }
 *       Resp KO:    { valid: false, message }
 *
 * No modifica nada en BD: sólo valida y devuelve cifras para que el frontend
 * pinte el descuento antes de confirmar la compra. La aplicación efectiva del
 * cupón sobre la Order ocurre en los controllers de checkout/renovación
 * cuando llega `coupon_code` en el body.
 */
class CouponController extends Controller
{
    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'         => 'required|string|max:64',
            'subtotal'     => 'required|numeric|min:0',
            'product_type' => 'nullable|string|in:abono,abonos,entrada,entradas,merch,merchandising,all',
        ]);

        $code     = trim($data['code']);
        $subtotal = (float) $data['subtotal'];
        $type     = (string) ($data['product_type'] ?? 'all');

        $coupon = Coupon::whereRaw('LOWER(code) = ?', [strtolower($code)])->first();

        if (! $coupon) {
            return response()->json([
                'valid'   => false,
                'message' => 'Código no válido',
            ]);
        }

        $result = $coupon->applyTo($subtotal, $type);

        if (! $result['applies']) {
            return response()->json([
                'valid'   => false,
                'message' => $result['reason'] ?? 'Cupón no aplicable',
            ]);
        }

        $discount    = (float) $result['discount'];
        $newSubtotal = round(max(0.0, $subtotal - $discount), 2);

        return response()->json([
            'valid'           => true,
            'coupon' => [
                'code'          => $coupon->code,
                'title'         => $coupon->title,
                'type'          => $coupon->type,
                'value'         => (float) $coupon->value,
                'display_value' => $coupon->display_value,
            ],
            'discount_amount' => $discount,
            'new_subtotal'    => $newSubtotal,
            'message'         => 'Cupón aplicado: -' . number_format($discount, 2, ',', '.') . '€',
        ]);
    }
}
