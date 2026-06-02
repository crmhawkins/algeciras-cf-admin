<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Ticket;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Genera y verifica los QR firmados de los tickets.
 *
 * - Abono: QR FIJO toda la temporada. Payload "ABONO|{id}|{customer}|{season}".
 * - Entrada: QR ROTATIVO por partido (se regenera cuando cambia match_id).
 *   Se apoya en qr_secret aleatorio por ticket para que el HMAC mute.
 *
 * El QR codifica la URL pública /v/{token} donde token es base64url(payload.sig).
 */
class QrService
{
    private const VERSION = 'v1';

    public function generate(Ticket $ticket): string
    {
        $ticket->loadMissing('product');
        $type = $ticket->product?->type ?? Product::TYPE_ENTRADA;

        if ($type === Product::TYPE_ABONO) {
            $payload = sprintf(
                'ABONO|%s|%d|%d|%d',
                self::VERSION,
                $ticket->id,
                (int) $ticket->customer_id,
                (int) $ticket->season_id,
            );
        } else {
            // Para entradas, si no hay qr_secret aún o el partido cambió,
            // generamos una semilla nueva. Esto hace que el HMAC y por
            // tanto la URL del QR sea distinta para cada partido.
            if (empty($ticket->qr_secret)) {
                $ticket->qr_secret = Str::random(40);
            }

            $payload = sprintf(
                'ENTRADA|%s|%d|%d|%d|%s',
                self::VERSION,
                $ticket->id,
                (int) $ticket->customer_id,
                (int) ($ticket->match_id ?? 0),
                $ticket->qr_secret,
            );
        }

        $sig   = $this->sign($payload);
        $token = $this->base64UrlEncode($payload.'|'.$sig);
        $url   = rtrim(config('app.url', 'https://algecirascf.hawkins.es'), '/').'/v/'.$token;

        // Persistimos token + secret juntos para no perder uno y dejar
        // medio ticket roto.
        $ticket->qr_token = substr(hash_hmac('sha256', $payload, config('app.key')), 0, 64);
        $ticket->save();

        $qr = new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 600,
            margin: 20,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(207, 46, 46),
            backgroundColor: new Color(255, 255, 255),
        );

        $writer = new PngWriter();
        $result = $writer->write($qr);

        $relativePath = "qr/{$ticket->id}.png";
        Storage::disk('public')->put($relativePath, $result->getString());

        $ticket->qr_image_path = $relativePath;
        $ticket->save();

        return $relativePath;
    }

    /**
     * Decodifica un token base64url, valida HMAC y devuelve los componentes.
     */
    public function verifyToken(string $token): ?array
    {
        $raw = $this->base64UrlDecode($token);
        if ($raw === null || ! str_contains($raw, '|')) {
            return ['valid' => false, 'reason' => 'malformed'];
        }

        $pos = strrpos($raw, '|');
        $payload = substr($raw, 0, $pos);
        $sig     = substr($raw, $pos + 1);

        $expected = $this->sign($payload);
        if (! hash_equals($expected, $sig)) {
            return ['valid' => false, 'reason' => 'bad_signature'];
        }

        $parts = explode('|', $payload);
        $kind  = $parts[0] ?? null;

        if ($kind === 'ABONO') {
            // ABONO|version|ticket_id|customer_id|season_id
            return [
                'valid'       => true,
                'type'        => 'abono',
                'ticket_id'   => (int) ($parts[2] ?? 0),
                'customer_id' => (int) ($parts[3] ?? 0),
                'season_id'   => (int) ($parts[4] ?? 0),
                'match_id'    => null,
            ];
        }

        if ($kind === 'ENTRADA') {
            // ENTRADA|version|ticket_id|customer_id|match_id|secret
            return [
                'valid'       => true,
                'type'        => 'entrada',
                'ticket_id'   => (int) ($parts[2] ?? 0),
                'customer_id' => (int) ($parts[3] ?? 0),
                'match_id'    => (int) ($parts[4] ?? 0),
                'season_id'   => null,
            ];
        }

        return ['valid' => false, 'reason' => 'unknown_kind'];
    }

    private function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, config('app.key'));
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): ?string
    {
        $pad = strlen($data) % 4;
        if ($pad) {
            $data .= str_repeat('=', 4 - $pad);
        }
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }
}
