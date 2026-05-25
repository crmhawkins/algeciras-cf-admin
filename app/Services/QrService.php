<?php

namespace App\Services;

use App\Models\Ticket;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

class QrService
{
    /**
     * Genera el PNG del QR para un ticket. Guarda en storage/app/public/tickets/{uuid}.png.
     * Devuelve la ruta relativa (sin /storage/).
     */
    public function generate(Ticket $ticket): string
    {
        $payload = url("/validar/{$ticket->uuid}.{$ticket->qr_token}");

        $qr = new QrCode(
            data: $payload,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 600,
            margin: 20,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(207, 46, 46),     // #CF2E2E
            backgroundColor: new Color(255, 255, 255),
        );

        $writer = new PngWriter();
        $result = $writer->write($qr);

        $relativePath = "tickets/{$ticket->uuid}.png";
        Storage::disk('public')->put($relativePath, $result->getString());

        $ticket->update(['qr_image_path' => "storage/{$relativePath}"]);
        return $relativePath;
    }
}
