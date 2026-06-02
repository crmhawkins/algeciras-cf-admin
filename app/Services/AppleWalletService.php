<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Genera ficheros .pkpass para Apple Wallet a partir de un Ticket (abono).
 *
 * Requiere los siguientes assets en storage/app/wallet/:
 *   - passcert.p12             (cert + privkey del Pass Type ID)
 *   - AppleWWDRCA.pem          (Apple WWDR intermediate certificate)
 *
 * Y los iconos del pass en storage/app/wallet/assets/ (icon.png, icon@2x.png,
 * logo.png, logo@2x.png). Si no existen, se generan iconos placeholder 29x29.
 *
 * Estructura .pkpass final = zip con:
 *   - pass.json
 *   - icon.png + icon@2x.png
 *   - logo.png + logo@2x.png  (opcional pero recomendado)
 *   - manifest.json   (SHA1 hashes de cada fichero)
 *   - signature       (firma PKCS#7 detached de manifest.json)
 *
 * NOTA: NO usamos lib externa de Composer. La firma se hace con openssl_pkcs7_sign
 * (incluido en PHP). El proceso es:
 *
 *   1. Construir pass.json
 *   2. Volcar pass.json + assets a un directorio temporal
 *   3. Generar manifest.json con sha1 de cada fichero
 *   4. openssl_pkcs7_sign(manifest.json) usando .p12 + WWDR como extracerts
 *   5. Convertir el PEM resultante a DER (pkcs7 binario) que es lo que Apple espera
 *   6. Empaquetar todo en un .zip con extensión .pkpass
 */
class AppleWalletService
{
    private string $passTypeIdentifier;
    private string $teamIdentifier;
    private string $organizationName;
    private string $certificatePath;
    private string $certificatePassword;
    private string $wwdrPath;

    public function __construct()
    {
        $cfg = config('services.apple_wallet', []);

        $this->passTypeIdentifier  = $cfg['pass_type_identifier']  ?? '';
        $this->teamIdentifier      = $cfg['team_identifier']       ?? '';
        $this->organizationName    = $cfg['organization_name']     ?? 'Algeciras CF';
        $this->certificatePath     = $cfg['certificate_path']      ?? storage_path('app/wallet/passcert.p12');
        $this->certificatePassword = $cfg['certificate_password']  ?? '';
        $this->wwdrPath            = $cfg['wwdr_path']             ?? storage_path('app/wallet/AppleWWDRCA.pem');
    }

    /**
     * Genera el .pkpass para un Ticket y devuelve la ruta absoluta al fichero.
     *
     * @throws RuntimeException si faltan certificados o el ticket no es abono.
     */
    public function generateForTicket(Ticket $ticket): string
    {
        if (! is_file($this->certificatePath)) {
            throw new RuntimeException(
                'Apple Wallet no configurado. Falta cert en storage/app/wallet/passcert.p12. '
                . 'Sigue las instrucciones en docs/WALLET_SETUP.md.'
            );
        }
        if (! is_file($this->wwdrPath)) {
            throw new RuntimeException(
                'Apple Wallet no configurado. Falta AppleWWDRCA.pem en storage/app/wallet/. '
                . 'Descárgalo de https://www.apple.com/certificateauthority/.'
            );
        }
        if (empty($this->passTypeIdentifier) || empty($this->teamIdentifier)) {
            throw new RuntimeException(
                'Apple Wallet no configurado. Faltan APPLE_WALLET_PASS_TYPE_IDENTIFIER '
                . 'y/o APPLE_WALLET_TEAM_IDENTIFIER en .env.'
            );
        }

        $ticket->loadMissing(['customer', 'season', 'product', 'zone', 'orderItem', 'match']);

        // URL pública que escaneará el lector de puerta (igual que la del QR existente).
        $publicUrl = rtrim(config('app.url', 'https://algecirascf.hawkins.es'), '/')
            . '/v/' . $this->buildQrToken($ticket);

        $customer = $ticket->customer;
        $holderName = $ticket->holder_name
            ?: trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
            ?: 'Abonado';

        $seasonLabel = $ticket->season?->name ?? config('app.season', 'Temporada');
        $sectorLabel = $ticket->zone?->name ?? '';
        $itemMeta = $ticket->orderItem?->meta ?? [];
        $asiento = $ticket->seat_number
            ?? ($itemMeta['asiento'] ?? ($itemMeta['seat'] ?? '—'));
        $fila = $itemMeta['fila'] ?? ($itemMeta['row'] ?? '—');

        // Estadio Nuevo Mirador (Algeciras).
        $latitude  = 36.1285;
        $longitude = -5.4537;

        $passJson = [
            'formatVersion'        => 1,
            'passTypeIdentifier'   => $this->passTypeIdentifier,
            'serialNumber'         => (string) $ticket->id,
            'teamIdentifier'       => $this->teamIdentifier,
            'organizationName'     => $this->organizationName,
            'description'          => 'Abono Algeciras CF ' . $seasonLabel,
            'logoText'             => 'Algeciras CF',
            'foregroundColor'      => 'rgb(255, 255, 255)',
            'backgroundColor'      => 'rgb(207, 46, 46)',
            'labelColor'           => 'rgb(255, 255, 255)',
            'barcodes' => [[
                'format'          => 'PKBarcodeFormatQR',
                'message'         => $publicUrl,
                'messageEncoding' => 'iso-8859-1',
                'altText'         => 'Abono ' . $ticket->id,
            ]],
            // Apple deja `barcode` (singular) como fallback iOS 8.
            'barcode' => [
                'format'          => 'PKBarcodeFormatQR',
                'message'         => $publicUrl,
                'messageEncoding' => 'iso-8859-1',
                'altText'         => 'Abono ' . $ticket->id,
            ],
            'locations' => [[
                'latitude'     => $latitude,
                'longitude'    => $longitude,
                'relevantText' => 'Estadio Nuevo Mirador',
            ]],
            'eventTicket' => [
                'primaryFields' => [[
                    'key'   => 'name',
                    'label' => 'TITULAR',
                    'value' => $holderName,
                ]],
                'secondaryFields' => [
                    [
                        'key'   => 'season',
                        'label' => 'TEMPORADA',
                        'value' => $seasonLabel,
                    ],
                    [
                        'key'   => 'sector',
                        'label' => 'SECTOR',
                        'value' => $sectorLabel ?: '—',
                    ],
                ],
                'auxiliaryFields' => [
                    [
                        'key'   => 'seat',
                        'label' => 'ASIENTO',
                        'value' => (string) $asiento,
                    ],
                    [
                        'key'   => 'row',
                        'label' => 'FILA',
                        'value' => (string) $fila,
                    ],
                ],
                'backFields' => [
                    [
                        'key'   => 'terms',
                        'label' => 'Condiciones',
                        'value' => 'Abono personal e intransferible. La presentación de este pase implica la aceptación de las normas del estadio.',
                    ],
                    [
                        'key'   => 'url',
                        'label' => 'Validación',
                        'value' => $publicUrl,
                    ],
                ],
            ],
        ];

        if ($ticket->match && $ticket->match->kickoff_at) {
            $passJson['relevantDate'] = $ticket->match->kickoff_at->toIso8601String();
        }

        return $this->buildPkpass($ticket, $passJson);
    }

    /**
     * Construye el archivo .pkpass empaquetando JSON + assets + manifest + firma.
     */
    private function buildPkpass(Ticket $ticket, array $passJson): string
    {
        $tmpDir = storage_path('app/passes/build_' . $ticket->id . '_' . uniqid());
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        try {
            // 1. pass.json
            file_put_contents(
                $tmpDir . '/pass.json',
                json_encode($passJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            // 2. Iconos. Si el cliente subió iconos custom a storage/app/wallet/assets/
            //    los usamos; si no, generamos placeholders 29x29 / 58x58 transparentes
            //    con el logo del club (placeholder PNG 1x1 si no hay GD).
            $this->copyOrPlaceholder($tmpDir . '/icon.png',     storage_path('app/wallet/assets/icon.png'),     29);
            $this->copyOrPlaceholder($tmpDir . '/icon@2x.png',  storage_path('app/wallet/assets/icon@2x.png'),  58);
            $this->copyOrPlaceholder($tmpDir . '/logo.png',     storage_path('app/wallet/assets/logo.png'),     160);
            $this->copyOrPlaceholder($tmpDir . '/logo@2x.png',  storage_path('app/wallet/assets/logo@2x.png'),  320);

            // 3. manifest.json (sha1 de cada fichero del bundle)
            $manifest = [];
            foreach (glob($tmpDir . '/*') as $f) {
                $manifest[basename($f)] = sha1_file($f);
            }
            file_put_contents(
                $tmpDir . '/manifest.json',
                json_encode($manifest, JSON_UNESCAPED_SLASHES)
            );

            // 4. signature: PKCS7 detached firmada con el .p12 + WWDR como extracert
            $this->signManifest($tmpDir . '/manifest.json', $tmpDir . '/signature');

            // 5. Empaquetar todo en .pkpass (zip).
            $outDir = storage_path('app/passes');
            if (! is_dir($outDir)) {
                mkdir($outDir, 0775, true);
            }
            $outPath = $outDir . '/' . $ticket->id . '.pkpass';
            if (is_file($outPath)) {
                @unlink($outPath);
            }

            $zip = new ZipArchive();
            if ($zip->open($outPath, ZipArchive::CREATE) !== true) {
                throw new RuntimeException('No se pudo crear el .pkpass zip');
            }
            foreach (glob($tmpDir . '/*') as $f) {
                $zip->addFile($f, basename($f));
            }
            $zip->close();

            return $outPath;
        } finally {
            // Cleanup tmpDir
            foreach (glob($tmpDir . '/*') as $f) {
                @unlink($f);
            }
            @rmdir($tmpDir);
        }
    }

    /**
     * Copia un fichero de assets si existe; si no, genera un placeholder PNG.
     */
    private function copyOrPlaceholder(string $destPath, string $sourcePath, int $size): void
    {
        if (is_file($sourcePath)) {
            copy($sourcePath, $destPath);
            return;
        }

        // Placeholder: PNG cuadrado rojo con el escudo de tamaño solicitado.
        if (function_exists('imagecreatetruecolor')) {
            $img = imagecreatetruecolor($size, $size);
            $red = imagecolorallocate($img, 207, 46, 46);
            imagefilledrectangle($img, 0, 0, $size, $size, $red);
            imagepng($img, $destPath);
            imagedestroy($img);
            return;
        }

        // Sin GD: copiamos un PNG 1x1 rojo binario mínimo.
        $png1x1Red = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGP8z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg=='
        );
        file_put_contents($destPath, $png1x1Red);
    }

    /**
     * Firma el manifest.json con el .p12 + WWDR usando openssl_pkcs7_sign.
     * Apple espera la firma PKCS#7 en formato DER (binario), no PEM.
     */
    private function signManifest(string $manifestPath, string $signatureOutPath): void
    {
        // Cargar .p12
        $p12Content = file_get_contents($this->certificatePath);
        $certs = [];
        if (! openssl_pkcs12_read($p12Content, $certs, $this->certificatePassword)) {
            throw new RuntimeException(
                'No se pudo leer el .p12 de Apple Wallet. Password incorrecta o fichero corrupto.'
            );
        }

        // Volcamos cert y privkey a archivos temporales (openssl_pkcs7_sign los requiere así).
        $certFile = tempnam(sys_get_temp_dir(), 'wcert_');
        $keyFile  = tempnam(sys_get_temp_dir(), 'wkey_');
        file_put_contents($certFile, $certs['cert']);
        file_put_contents($keyFile,  $certs['pkey']);

        $signedPemPath = tempnam(sys_get_temp_dir(), 'wsig_');

        try {
            $ok = openssl_pkcs7_sign(
                $manifestPath,
                $signedPemPath,
                'file://' . $certFile,
                ['file://' . $keyFile, $this->certificatePassword],
                [],
                PKCS7_BINARY | PKCS7_DETACHED,
                $this->wwdrPath
            );
            if (! $ok) {
                throw new RuntimeException('openssl_pkcs7_sign falló para el manifest.json');
            }

            // openssl_pkcs7_sign produce un PEM con headers MIME y la firma en base64.
            // Apple necesita los bytes DER puros entre las cabeceras PKCS7.
            $pem = file_get_contents($signedPemPath);

            // Extraemos el bloque base64 entre las cabeceras MIME.
            $parts = explode("\n\n", $pem, 2);
            if (count($parts) < 2) {
                throw new RuntimeException('Formato inesperado en firma PKCS#7 generada.');
            }
            $body = preg_replace('/-----.*?-----/', '', $parts[1]);
            $body = preg_replace('/\s+/', '', $body);
            $der = base64_decode($body, true);
            if ($der === false || strlen($der) < 10) {
                throw new RuntimeException('No se pudo decodificar la firma a DER.');
            }

            file_put_contents($signatureOutPath, $der);
        } finally {
            @unlink($certFile);
            @unlink($keyFile);
            @unlink($signedPemPath);
        }
    }

    /**
     * Reproduce el cálculo de token público usado por QrService (ABONO v1).
     * Lo replicamos aquí en vez de regenerar QR para no tocar el ticket.
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
