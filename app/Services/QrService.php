<?php

namespace App\Services;

use App\Models\FootballMatch;
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
 * Tres variantes de QR conviven:
 *   - ABONO v1 (estático, toda la temporada). Payload "ABONO|v1|ticket|customer|season".
 *   - ABONO v2 (rotativo por partido, efímero). Payload "ABONO|v2|ticket|match|nonce|customer".
 *     NO se persiste en disco — se devuelve como data URI per-request.
 *   - ENTRADA v1 (rotativa por partido apoyándose en qr_secret).
 *     Payload "ENTRADA|v1|ticket|customer|match|secret".
 *
 * El QR codifica la URL pública /v/{token} donde token es base64url(payload.sig).
 */
class QrService
{
    private const VERSION = 'v1';
    private const VERSION_ABONO_ROTATIVO = 'v2';

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

        $writer = new PngWriter();
        $result = $writer->write($this->makeQr($url));

        $relativePath = "qr/{$ticket->id}.png";
        Storage::disk('public')->put($relativePath, $result->getString());

        $ticket->qr_image_path = $relativePath;
        $ticket->save();

        return $relativePath;
    }

    /**
     * Genera un QR ROTATIVO de abono para un partido concreto.
     *
     * Caso de uso: abonado abre la app/PWA antes de un partido y le
     * mostramos un QR único para ESE partido. El nonce aleatorio hace
     * que cada generación produzca un token distinto, y la validación
     * comprobará que el `match_id` del token coincide con el que está
     * marcando el operador en la puerta.
     *
     * NO se guarda PNG en disco: el data URI se manda en la respuesta
     * y vive solo en la sesión del cliente.
     */
    public function generateForMatch(Ticket $ticket, FootballMatch $match): array
    {
        $nonce = Str::random(24);

        $payload = sprintf(
            'ABONO|%s|%d|%d|%s|%d',
            self::VERSION_ABONO_ROTATIVO,
            $ticket->id,
            $match->id,
            $nonce,
            (int) $ticket->customer_id,
        );

        $sig   = $this->sign($payload);
        $token = $this->base64UrlEncode($payload.'|'.$sig);
        $url   = rtrim(config('app.url', 'https://algecirascf.hawkins.es'), '/').'/v/'.$token;

        $writer = new PngWriter();
        $result = $writer->write($this->makeQr($url));

        $pngDataUri = 'data:image/png;base64,'.base64_encode($result->getString());

        // Caducidad funcional: 6 horas tras el kickoff es ventana de sobra
        // para entrar al estadio (partidos duran ~2h + lobby pre-partido).
        $expiresAt = $match->kickoff_at
            ? $match->kickoff_at->copy()->addHours(6)
            : now()->addHours(12);

        return [
            'token'        => $token,
            'png_data_uri' => $pngDataUri,
            'expires_at'   => $expiresAt,
        ];
    }

    /**
     * Decodifica un token base64url, valida HMAC y devuelve los componentes.
     *
     * Estructura del retorno:
     *  - valid: bool
     *  - reason: string (solo si !valid)
     *  - type: 'abono' | 'entrada'
     *  - ticket_id: int
     *  - match_id_from_token: ?int  (presente en abono v2 + entrada v1; null en abono v1)
     *  - payload_version: 'v1' | 'v2'
     *  - customer_id / season_id según corresponda
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
        $ver   = $parts[1] ?? null;

        if ($kind === 'ABONO' && $ver === self::VERSION) {
            // ABONO|v1|ticket_id|customer_id|season_id
            return [
                'valid'               => true,
                'type'                => 'abono',
                'payload_version'     => 'v1',
                'ticket_id'           => (int) ($parts[2] ?? 0),
                'customer_id'         => (int) ($parts[3] ?? 0),
                'season_id'           => (int) ($parts[4] ?? 0),
                'match_id_from_token' => null,
            ];
        }

        if ($kind === 'ABONO' && $ver === self::VERSION_ABONO_ROTATIVO) {
            // ABONO|v2|ticket_id|match_id|nonce|customer_id
            return [
                'valid'               => true,
                'type'                => 'abono',
                'payload_version'     => 'v2',
                'ticket_id'           => (int) ($parts[2] ?? 0),
                'match_id_from_token' => (int) ($parts[3] ?? 0),
                'customer_id'         => (int) ($parts[5] ?? 0),
                'season_id'           => null,
            ];
        }

        if ($kind === 'ENTRADA' && $ver === self::VERSION) {
            // ENTRADA|v1|ticket_id|customer_id|match_id|secret
            return [
                'valid'               => true,
                'type'                => 'entrada',
                'payload_version'     => 'v1',
                'ticket_id'           => (int) ($parts[2] ?? 0),
                'customer_id'         => (int) ($parts[3] ?? 0),
                'match_id_from_token' => (int) ($parts[4] ?? 0),
                'season_id'           => null,
            ];
        }

        return ['valid' => false, 'reason' => 'unknown_kind'];
    }

    private function makeQr(string $url): QrCode
    {
        return new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 600,
            margin: 20,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(207, 46, 46),
            backgroundColor: new Color(255, 255, 255),
        );
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
