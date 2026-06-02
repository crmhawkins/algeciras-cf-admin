<?php

namespace App\Services;

use App\Models\Ticket;
use RuntimeException;

/**
 * Genera la URL "Save to Google Wallet" para un abono.
 *
 * El flujo Google es distinto al de Apple:
 *
 *  - No empaquetamos un fichero. Construimos un JWT firmado con la
 *    cuenta de servicio del Issuer en Google Wallet API.
 *  - El JWT contiene el `eventTicketObject` con los datos del abono.
 *  - El usuario abre `https://pay.google.com/gp/v/save/{jwt}` y Google
 *    importa el pass en su Google Wallet.
 *
 * Requiere:
 *  - storage/app/wallet/google-service-account.json  (service account con
 *    rol "Wallet Object Issuer Admin")
 *  - Issuer ID registrado en https://pay.google.com/business/console/
 *  - Un class ID creado previamente (se referencia desde el object).
 *    El formato es {issuerId}.{classSuffix}, p.ej. `3388000000022xxx.abonos_2526`.
 *
 * Si las claves no están configuradas, se lanza RuntimeException con
 * instrucciones para que el usuario sepa qué falta.
 */
class GoogleWalletService
{
    private string $serviceAccountJsonPath;
    private string $issuerId;
    private string $classSuffix;
    private string $origin;

    public function __construct()
    {
        $cfg = config('services.google_wallet', []);

        $this->serviceAccountJsonPath = $cfg['service_account_json_path']
            ?? storage_path('app/wallet/google-service-account.json');
        $this->issuerId    = $cfg['issuer_id']    ?? '';
        $this->classSuffix = $cfg['class_suffix'] ?? 'abonos_generic';
        $this->origin      = $cfg['origin']       ?? config('app.url', 'https://algecirascf.hawkins.es');
    }

    /**
     * Devuelve la URL "Save to Google Wallet" lista para abrir desde la app.
     *
     * @throws RuntimeException si faltan credenciales
     */
    public function generateSaveUrl(Ticket $ticket): string
    {
        if (! is_file($this->serviceAccountJsonPath)) {
            throw new RuntimeException(
                'Google Wallet no configurado. Falta service account en '
                . 'storage/app/wallet/google-service-account.json. '
                . 'Sigue las instrucciones en docs/WALLET_SETUP.md.'
            );
        }
        if (empty($this->issuerId)) {
            throw new RuntimeException(
                'Google Wallet no configurado. Falta GOOGLE_WALLET_ISSUER_ID en .env.'
            );
        }

        $sa = json_decode(file_get_contents($this->serviceAccountJsonPath), true);
        if (! is_array($sa) || empty($sa['private_key']) || empty($sa['client_email'])) {
            throw new RuntimeException('JSON de service account de Google Wallet inválido.');
        }

        $ticket->loadMissing(['customer', 'season', 'zone', 'orderItem', 'match']);

        $customer = $ticket->customer;
        $holderName = $ticket->holder_name
            ?: trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
            ?: 'Abonado';

        $itemMeta = $ticket->orderItem?->meta ?? [];
        $sectorLabel = $ticket->zone?->name ?? '';
        $asiento = (string) ($ticket->seat_number
            ?? ($itemMeta['asiento'] ?? ($itemMeta['seat'] ?? '')));
        $fila = (string) ($itemMeta['fila'] ?? ($itemMeta['row'] ?? ''));

        $classId  = $this->issuerId . '.' . $this->classSuffix;
        $objectId = $this->issuerId . '.' . 'abono_' . $ticket->id;

        // Token público (mismo formato que el QR de la PWA).
        $publicUrl = rtrim(config('app.url', 'https://algecirascf.hawkins.es'), '/')
            . '/v/' . $this->buildQrToken($ticket);

        $eventTicketObject = [
            'id'              => $objectId,
            'classId'         => $classId,
            'state'           => 'ACTIVE',
            'ticketHolderName'=> $holderName,
            'ticketNumber'    => (string) $ticket->id,
            'seatInfo' => [
                'seat'   => ['defaultValue' => ['language' => 'es', 'value' => $asiento]],
                'row'    => ['defaultValue' => ['language' => 'es', 'value' => $fila]],
                'section'=> ['defaultValue' => ['language' => 'es', 'value' => $sectorLabel]],
            ],
            'barcode' => [
                'type'         => 'QR_CODE',
                'value'        => $publicUrl,
                'alternateText'=> 'Abono ' . $ticket->id,
            ],
            'hexBackgroundColor' => '#cf2e2e',
        ];

        if ($ticket->match && $ticket->match->kickoff_at) {
            $eventTicketObject['validTimeInterval'] = [
                'start' => ['date' => $ticket->match->kickoff_at->copy()->subHours(3)->toIso8601String()],
                'end'   => ['date' => $ticket->match->kickoff_at->copy()->addHours(6)->toIso8601String()],
            ];
        }

        $claims = [
            'iss'     => $sa['client_email'],
            'aud'     => 'google',
            'typ'     => 'savetowallet',
            'iat'     => time(),
            'origins' => [$this->origin],
            'payload' => [
                'eventTicketObjects' => [$eventTicketObject],
            ],
        ];

        $jwt = $this->signJwtRS256($claims, $sa['private_key']);
        return 'https://pay.google.com/gp/v/save/' . $jwt;
    }

    /**
     * Firma JWT RS256 manualmente (sin lib externa).
     * Sólo dependemos de openssl_sign, que viene con PHP por defecto.
     */
    private function signJwtRS256(array $claims, string $privateKeyPem): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];

        $segments = [
            $this->b64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->b64UrlEncode(json_encode($claims, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
        ];

        $signingInput = implode('.', $segments);

        $signature = '';
        $key = openssl_pkey_get_private($privateKeyPem);
        if (! $key) {
            throw new RuntimeException('Private key Google Wallet inválida.');
        }
        if (! openssl_sign($signingInput, $signature, $key, 'sha256WithRSAEncryption')) {
            throw new RuntimeException('No se pudo firmar el JWT Google Wallet.');
        }

        $segments[] = $this->b64UrlEncode($signature);
        return implode('.', $segments);
    }

    private function b64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Idem QrService::generate para abono v1, replicado aquí para no
     * regenerar el QR de disco.
     */
    private function buildQrToken(Ticket $ticket): string
    {
        $payload = sprintf(
            'ABONO|v1|%d|%d|%d',
            $ticket->id,
            (int) $ticket->customer_id,
            (int) $ticket->season_id,
        );
        $sig = hash_hmac('sha256', $payload, config('app.key'));
        $raw = $payload . '|' . $sig;
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
