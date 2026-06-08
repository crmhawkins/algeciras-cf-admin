<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * TPV Virtual Redsys / Banco Sabadell.
 *
 * Flujo:
 *  1. buildFormPayload(Order) → array con Ds_SignatureVersion + Ds_MerchantParameters + Ds_Signature
 *     Esos 3 campos se envían POST al URL de Redsys mediante un form HTML
 *     que se auto-submitea (lo hace la vista pago-redsys.blade).
 *  2. Redsys procesa el pago en su entorno.
 *  3. Redsys hace POST server-to-server a /pago/redsys/notify (verifyNotification).
 *  4. Redsys redirecciona al usuario a /pago/ok o /pago/ko.
 *
 * Documentación: https://bsdev.es/es/venta-a-distancia/pasarela-pagos/tpv-virtual-banco-redsys/introduccion
 *
 * Firma SHA256:
 *   1. Genera clave derivada = AES-256-CBC(SHA256_KEY_BASE64, Ds_Merchant_Order_string)
 *   2. firma_HMAC = HMAC-SHA256(clave_derivada, base64(merchant_parameters_json))
 *   3. Ds_Signature = base64(firma_HMAC)
 */
class RedsysPaymentService
{
    public function url(): string
    {
        return config('redsys.env') === 'prod'
            ? config('redsys.url.prod')
            : config('redsys.url.test');
    }

    /**
     * Construye los 3 parámetros que necesita el form POST a Redsys.
     *
     * @return array{Ds_SignatureVersion: string, Ds_MerchantParameters: string, Ds_Signature: string}
     */
    public function buildFormPayload(Order $order): array
    {
        // Ds_Merchant_Order debe ser único por operación, 4-12 chars,
        // los primeros 4 numéricos. Usamos timestamp + id.
        $merchantOrder = $this->buildMerchantOrderId($order);

        // Importe en céntimos (sin decimales). Total Stripe-style.
        $amountCents = (int) round((float) $order->total * 100);

        $payload = [
            'DS_MERCHANT_AMOUNT'           => (string) $amountCents,
            'DS_MERCHANT_ORDER'            => $merchantOrder,
            'DS_MERCHANT_MERCHANTCODE'     => (string) config('redsys.merchant_code'),
            'DS_MERCHANT_CURRENCY'         => (string) config('redsys.currency'),
            'DS_MERCHANT_TRANSACTIONTYPE'  => '0',   // 0 = Autorización
            'DS_MERCHANT_TERMINAL'         => (string) config('redsys.terminal'),
            'DS_MERCHANT_MERCHANTURL'      => url(config('redsys.urls.notify')),  // server-to-server
            'DS_MERCHANT_URLOK'            => url(config('redsys.urls.ok') . '?o=' . $merchantOrder),
            'DS_MERCHANT_URLKO'            => url(config('redsys.urls.ko') . '?o=' . $merchantOrder),
            'DS_MERCHANT_CONSUMERLANGUAGE' => (string) config('redsys.language'),
            'DS_MERCHANT_PRODUCTDESCRIPTION'=> 'Algeciras CF - Pedido ' . $order->reference,
            'DS_MERCHANT_TITULAR'          => substr((string) ($order->customer?->fullName ?? $order->guest_email ?? 'Cliente'), 0, 60),
        ];

        // Guardamos la relación order ↔ merchantOrder en BD
        $order->update([
            'payment_gateway'  => 'redsys',
            'payment_intent_id'=> $merchantOrder,
        ]);

        // Modo mock — no llama a Redsys de verdad
        if (config('redsys.mock')) {
            Log::info('Redsys MOCK mode — no se envía', ['order' => $order->id, 'payload' => $payload]);
        }

        // Codificar parámetros
        $parametersJson   = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $parametersBase64 = $this->base64UrlEncode($parametersJson);

        // Firma
        $signature = $this->createSignature($parametersBase64, $merchantOrder);

        return [
            'Ds_SignatureVersion'   => 'HMAC_SHA256_V1',
            'Ds_MerchantParameters' => $parametersBase64,
            'Ds_Signature'          => $signature,
        ];
    }

    /**
     * Verifica una notificación entrante de Redsys. Si OK, devuelve los
     * parámetros decodificados; si la firma falla, devuelve null.
     *
     * @return array<string, mixed>|null
     */
    public function verifyNotification(string $merchantParameters, string $signature): ?array
    {
        try {
            $decoded = $this->base64UrlDecode($merchantParameters);
            $params  = json_decode($decoded, true);
            if (! is_array($params)) {
                return null;
            }

            // Redsys envía las claves a veces en mayúscula, a veces minúscula.
            // Normalizamos a uppercase para acceder al order.
            $upper = array_change_key_case($params, CASE_UPPER);
            $order = $upper['DS_ORDER'] ?? null;
            if (! $order) {
                return null;
            }

            $expected = $this->createSignature($merchantParameters, (string) $order);

            // Tiempo constante para evitar timing attacks
            if (! hash_equals($expected, $signature)) {
                Log::warning('Redsys signature mismatch', ['expected' => substr($expected, 0, 12), 'received' => substr($signature, 0, 12)]);
                return null;
            }

            return $params;
        } catch (\Throwable $e) {
            Log::error('Redsys verifyNotification error', ['err' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Decide si una notificación corresponde a un PAGO EXITOSO según
     * Ds_Response (0-99 = OK, 1000-9999 = OK con autenticación reforzada).
     */
    public function isResponseOk(array $params): bool
    {
        $upper    = array_change_key_case($params, CASE_UPPER);
        $response = (int) ($upper['DS_RESPONSE'] ?? -1);
        return ($response >= 0 && $response < 100) || ($response >= 900 && $response < 1000);
    }

    // === Helpers internos ===

    /** Genera Ds_Order único, máx 12 chars, primeros 4 numéricos. */
    protected function buildMerchantOrderId(Order $order): string
    {
        // 4 dígitos del id + timestamp 8 chars = 12 total. Cumple ambas restricciones.
        $prefix = str_pad((string) ($order->id % 10000), 4, '0', STR_PAD_LEFT);
        return $prefix . substr((string) time(), -8);
    }

    /**
     * HMAC SHA256 firmando los Ds_MerchantParameters base64, usando como
     * clave la SHA256_KEY cifrada con 3DES sobre el Ds_Order.
     * Esto es lo que Redsys llama "HMAC_SHA256_V1".
     */
    protected function createSignature(string $merchantParametersBase64, string $order): string
    {
        $key = base64_decode((string) config('redsys.sha256_key'), true);
        if ($key === false) {
            throw new \RuntimeException('Redsys SHA256_KEY no es base64 válido');
        }

        // 3DES-CBC con IV cero
        $derivedKey = openssl_encrypt(
            $this->zeroPad($order, 8),
            'des-ede3-cbc',
            $key,
            OPENSSL_RAW_DATA | OPENSSL_NO_PADDING,
            "\0\0\0\0\0\0\0\0"
        );

        if ($derivedKey === false) {
            throw new \RuntimeException('Redsys 3DES derive key failed');
        }

        $hmac = hash_hmac('sha256', $merchantParametersBase64, $derivedKey, true);
        return $this->base64UrlEncode($hmac);
    }

    /** Padding con null bytes hasta múltiplo de $blocksize. */
    protected function zeroPad(string $data, int $blocksize): string
    {
        $pad = $blocksize - (strlen($data) % $blocksize);
        if ($pad === $blocksize) {
            $pad = 0;
        }
        return $data . str_repeat("\0", $pad);
    }

    protected function base64UrlEncode(string $data): string
    {
        return strtr(base64_encode($data), '+/', '-_');
    }

    protected function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
