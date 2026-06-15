<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Genera el CARNET PVC imprimible de un abono.
 *
 * El cliente imprime en una impresora de tarjetas PVC que recorta la esquina
 * superior izquierda de una hoja A4 vertical. Por eso el contenido del carnet
 * vive SOLO en esa esquina (posiciones absolutas en mm clavadas para coincidir
 * con el PDF de referencia del proveedor anterior — ver carnet-abono.blade.php).
 *
 * El QR codifica `Ticket::qrContent()` (token plano legacy si existe, o el
 * payload propio uuid.qr_token), de modo que tanto el carnet impreso como la
 * validación en la puerta usan el MISMO contenido.
 *
 * Ruta: GET /admin/carnet/{ticket}  (protegida con auth del panel).
 */
class CarnetAbonoController extends Controller
{
    public function show(Ticket $ticket): Response
    {
        $ticket->loadMissing(['customer', 'season', 'product', 'seat.sector', 'zone']);

        $customer = $ticket->customer;
        $seat     = $ticket->seat;
        $sector   = $seat?->sector;
        $season   = $ticket->season;

        // --- Nombre completo del abonado ---
        $nombre = $customer
            ? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
            : ($ticket->holder_name ?? '');

        // --- Tipo de precio (negrita). De customer.notes['tipo_precio'] guardado
        //     en el import; fallback al nombre del producto del abono. ---
        $tipoPrecio = null;
        $notes = $customer?->notes;
        if (is_array($notes) && !empty($notes['tipo_precio'])) {
            $tipoPrecio = (string) $notes['tipo_precio'];
        }
        if (!$tipoPrecio) {
            $tipoPrecio = $ticket->product
                ? (is_array($ticket->product->name)
                    ? ($ticket->product->name['es'] ?? '')
                    : (string) $ticket->product->getTranslation('name', 'es'))
                : '';
        }
        $tipoPrecio = strtoupper(trim($tipoPrecio));

        // --- Fila / Nº asiento (AMBOS a 3 dígitos) / Puerta (PTA) ---
        $fila    = $seat?->row;
        $asiento = $seat?->number;

        // PUERTA física del estadio. VERIFICADO contra el carnet real del
        // proveedor (socio #132, Preferente Par 6 → PTA 28; socio #3, Tribuna
        // Alta Par 4 → PTA "4, 51"): NO es derivable del nº de sector ni del nº
        // de abonado. Es una asignación de torno propia del proveedor anterior
        // que NO viajó en el Excel del padrón. Orígenes reales, por prioridad:
        //   1) notes['puerta'] del customer (si el export del proveedor la trae)
        //   2) mapa sector->puerta en config('club.puertas') (plano de accesos)
        // Si no hay dato fiable, se OMITE: mejor sin puerta que con una puerta
        // equivocada que mande al abonado a un torno que no es el suyo.
        $puerta = null;
        if (is_array($notes) && !empty($notes['puerta'])) {
            $puerta = trim((string) $notes['puerta']);
        } elseif ($sector && is_array($map = config('club.puertas'))) {
            // La puerta es por GRANDSTAND (Tribuna / Preferencia / Fondo Norte /
            // Fondo Sur / Palco) — confirmado que par e impar la comparten. Por
            // eso casamos por PREFIJO del nombre de la grada (insensible a
            // mayúsculas/espacios): "Fondo Norte Par 2" empieza por "FONDO NORTE".
            $needle = preg_replace('/\s+/', ' ', mb_strtoupper(trim($sector->name)));
            foreach ($map as $grada => $pta) {
                $pref = preg_replace('/\s+/', ' ', mb_strtoupper(trim($grada)));
                if ($pta !== '' && str_starts_with($needle, $pref)) {
                    $puerta = trim((string) $pta);
                    break;
                }
            }
        }

        $filaTxt    = $fila    !== null ? str_pad((string) $fila, 3, '0', STR_PAD_LEFT) : '—';
        $asientoTxt = $asiento !== null ? str_pad((string) $asiento, 3, '0', STR_PAD_LEFT) : '—';
        $lineaAsiento = 'FILA: ' . $filaTxt . '   Nº: ' . $asientoTxt;
        if ($puerta !== null && $puerta !== '') {
            $lineaAsiento .= '   PTA: ' . $puerta;
        }

        // --- Columna derecha ---
        // Temporada "2026-27" -> "26/27".
        $tempCorta = $this->temporadaCorta($season?->name);
        $numSocio  = $customer?->socio_number ?? $customer?->legacy_socio_id ?? '';
        $sectorNombre = strtoupper((string) ($sector?->name ?? ($ticket->zone?->name ?? '')));

        // --- QR (token plano = qrContent) ---
        $qrDataUri = $this->qrDataUri($ticket->qrContent());

        $pdf = Pdf::loadView('pdfs.carnet-abono', [
            'nombre'        => $nombre,
            'tipoPrecio'    => $tipoPrecio,
            'lineaAsiento'  => $lineaAsiento,
            'tempCorta'     => $tempCorta,
            'numSocio'      => $numSocio,
            'sectorNombre'  => $sectorNombre,
            'qrDataUri'     => $qrDataUri,
        ])->setPaper('a4', 'portrait');

        $filename = 'carnet-abono-' . ($numSocio !== '' ? $numSocio : $ticket->id) . '.pdf';

        // Inline en el navegador: el operador imprime directamente (Ctrl+P).
        return $pdf->stream($filename);
    }

    /**
     * Recibo del abono (PDF) — réplica de "Imprimir recibo" del proveedor.
     * Justificante de pago con datos del club + abonado + abono.
     *
     * Ruta: GET /admin/recibo/{ticket}
     */
    public function recibo(Ticket $ticket): Response
    {
        $ticket->loadMissing(['customer', 'season', 'product', 'seat.sector', 'zone']);

        $customer = $ticket->customer;
        $seat     = $ticket->seat;
        $sector   = $seat?->sector;

        $nombre = $customer
            ? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
            : ($ticket->holder_name ?? '');

        $tipo = null;
        $notes = $customer?->notes;
        if (is_array($notes) && !empty($notes['tipo_precio'])) {
            $tipo = (string) $notes['tipo_precio'];
        }
        $tipo = strtoupper(trim((string) $tipo)) ?: 'ABONO';

        $pdf = Pdf::loadView('pdfs.recibo-abono', [
            'club'         => config('club'),
            'nombre'       => $nombre,
            'dni'          => $customer?->dni ?? ($ticket->holder_dni ?? '—'),
            'numAbonado'   => $customer?->socio_number ?? '—',
            'tipo'         => $tipo,
            'precio'       => $ticket->price_paid !== null ? number_format((float) $ticket->price_paid, 2, ',', '.') . ' €' : '—',
            'temporada'    => $ticket->season?->name ?? '—',
            'sector'       => $sector?->name ?? ($ticket->zone?->name ?? '—'),
            'fila'         => $seat?->row ?? '—',
            'butaca'       => $seat?->number ?? '—',
            'numRecibo'    => 'AB-' . str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT),
            'fecha'        => optional($ticket->created_at)->format('d/m/Y') ?? now()->format('d/m/Y'),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('recibo-abono-' . ($customer?->socio_number ?? $ticket->id) . '.pdf');
    }

    /**
     * "2026-27" -> "26/27". Tolera "2026/27", "2026-2027" y similares; si no
     * casa el patrón devuelve el nombre tal cual.
     */
    private function temporadaCorta(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }
        if (preg_match('/(\d{4}).*?(\d{2,4})$/', $name, $m)) {
            $ini = substr($m[1], -2);
            $fin = substr($m[2], -2);
            return $ini . '/' . $fin;
        }
        return $name;
    }

    /**
     * Genera un PNG del QR y lo devuelve como data URI (para incrustar en el
     * blade con <img src>). Reutiliza el writer endroid/qr-code que ya usa el
     * proyecto (mismo que QrService). Color negro / fondo blanco para máxima
     * legibilidad en impresión PVC.
     */
    private function qrDataUri(string $content): string
    {
        $qr = new QrCode(
            data: $content,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 600,
            margin: 0,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );

        $result = (new PngWriter())->write($qr);

        return 'data:image/png;base64,' . base64_encode($result->getString());
    }
}
