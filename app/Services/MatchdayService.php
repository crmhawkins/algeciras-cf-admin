<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\FootballMatch;
use App\Models\Ticket;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Matchday Mode — detecta si HOY hay partido en casa y prepara el banner
 * para el área personal (web) y el campo `matchday` del payload /api/me.
 *
 * El modelo se llama FootballMatch (la tabla 'matches' es palabra reservada
 * de PHP). Aquí se respeta ese alias.
 */
class MatchdayService
{
    /**
     * Devuelve el partido EN CASA de hoy (00:00–23:59) si existe y está
     * en estado 'scheduled' o 'live'. Null si no hay nada.
     */
    public function todaysHomeMatch(): ?FootballMatch
    {
        return FootballMatch::whereDate('kickoff_at', today())
            ->where('venue', 'home')
            ->whereIn('status', ['scheduled', 'live'])
            ->orderBy('kickoff_at')
            ->first();
    }

    /** ¿Hay partido en casa hoy? */
    public function isMatchday(): bool
    {
        return $this->todaysHomeMatch() !== null;
    }

    /**
     * Próximos partidos en casa (scope home + upcoming).
     * Por defecto sólo 1, pasar null para sin límite.
     */
    public function nextHomeMatch(?int $limit = 1): Collection
    {
        $q = FootballMatch::query()
            ->home()
            ->where('status', 'scheduled')
            ->where('kickoff_at', '>=', now())
            ->orderBy('kickoff_at');

        if ($limit !== null) {
            $q->limit($limit);
        }

        return $q->get();
    }

    /**
     * Devuelve los datos para pintar la vista matchday para un Customer
     * concreto. Si no hay partido hoy → null.
     *
     * Estructura:
     *   [
     *     'match'     => FootballMatch,
     *     'ticket'    => Ticket|null,     // el ticket válido del user para este partido
     *     'isAbono'   => bool,            // true si el ticket es de tipo 'abono'
     *     'hasTicket' => bool,            // true si tiene algo válido (abono o entrada)
     *     'qr'        => string|null,     // <svg> inline del QR (o null si no hay ticket)
     *     'qrPayload' => string|null,     // texto crudo del QR (para depurar/app móvil)
     *     'sector'    => string|null,
     *     'row'       => string|null,
     *     'seat'      => string|null,
     *     'gatesOpenAt' => Carbon|null,   // kickoff - 1.5h
     *   ]
     */
    public function matchdayBannerFor(Customer $customer): ?array
    {
        $match = $this->todaysHomeMatch();
        if (! $match) {
            return null;
        }

        // 1) Buscar abono activo del customer (cualquier ticket de producto type=abono)
        $ticket = $customer->tickets()
            ->whereHas('product', fn ($q) => $q->where('type', 'abono'))
            ->where(function ($q) {
                $q->whereIn('status', ['issued', 'used'])
                  ->orWhereNull('status');
            })
            ->with(['product', 'zone'])
            ->first();
        $isAbono = (bool) $ticket;

        // 2) Si no hay abono, buscar entrada para ESTE match en concreto
        if (! $ticket) {
            $ticket = $customer->tickets()
                ->where('match_id', $match->id)
                ->whereHas('product', fn ($q) => $q->where('type', 'entrada'))
                ->where(function ($q) {
                    $q->whereIn('status', ['issued', 'used'])
                      ->orWhereNull('status');
                })
                ->with(['product', 'zone'])
                ->first();
        }

        $hasTicket = (bool) $ticket;
        $qrSvg = null;
        $qrPayload = null;
        $sector = null;
        $row = null;
        $seat = null;

        if ($ticket) {
            $qrPayload = url("/validar/{$ticket->uuid}.{$ticket->qr_token}");
            $qrSvg = $this->renderQrSvg($qrPayload);

            // Datos físicos del asiento — el modelo Ticket no tiene una
            // relación 'sector' directa, sí 'zone'. Filtramos defensivamente.
            $sector = optional($ticket->zone)->name
                ?? ($ticket->getAttribute('sector_name') ?? null);
            $row = $ticket->getAttribute('row');
            $seat = $ticket->getAttribute('seat_number') ?? $ticket->getAttribute('seat');
        }

        return [
            'match'       => $match,
            'ticket'      => $ticket,
            'isAbono'     => $isAbono,
            'hasTicket'   => $hasTicket,
            'qr'          => $qrSvg,
            'qrPayload'   => $qrPayload,
            'sector'      => $sector,
            'row'         => $row,
            'seat'        => $seat,
            'gatesOpenAt' => $match->kickoff_at?->copy()->subMinutes(90),
        ];
    }

    /**
     * Genera el SVG inline del QR con endroid/qr-code v6.
     * Si la librería falla por cualquier motivo, devuelve un placeholder
     * SVG con texto "QR DEL ABONO" para que la vista no se rompa.
     */
    private function renderQrSvg(string $payload): string
    {
        try {
            $builder = new Builder(
                writer: new SvgWriter(),
                writerOptions: [],
                validateResult: false,
                data: $payload,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 480,
                margin: 16,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                foregroundColor: new Color(10, 10, 10),
                backgroundColor: new Color(255, 255, 255),
            );

            $result = $builder->build();

            return $result->getString();
        } catch (\Throwable $e) {
            Log::warning('MatchdayService: fallo generando QR SVG', [
                'error' => $e->getMessage(),
            ]);

            return $this->placeholderQrSvg();
        }
    }

    private function placeholderQrSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 480" width="480" height="480">
  <rect width="480" height="480" fill="#ffffff"/>
  <rect x="20" y="20" width="440" height="440" fill="none" stroke="#0a0a0a" stroke-width="8" stroke-dasharray="14,10"/>
  <text x="240" y="240" text-anchor="middle" dominant-baseline="middle"
        font-family="monospace" font-size="32" font-weight="bold" fill="#0a0a0a">
    QR DEL ABONO
  </text>
  <text x="240" y="290" text-anchor="middle" dominant-baseline="middle"
        font-family="monospace" font-size="14" fill="#888888">
    (placeholder)
  </text>
</svg>
SVG;
    }
}
