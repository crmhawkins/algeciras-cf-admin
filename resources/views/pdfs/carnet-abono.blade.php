{{--
    CARNET PVC del abono — réplica EXACTA del formato del proveedor anterior.

    Página A4 vertical (210×297 mm). El contenido va SOLO en la esquina superior
    izquierda (la impresora de tarjetas PVC recorta esa zona). Posiciones en mm
    absolutos, medidas del PDF de referencia `abono.pdf` (socio #3) y convertidas
    de puntos PDF a mm:

      QR        bbox (38,39)-(100,102) pt  ->  left 13.4mm · top 13.8mm · 22×22mm
      Texto col izquierda  x≈36pt   -> 12.7mm
      Texto col derecha    borde dcho x≈276pt -> 97.4mm
      Línea 1 (nombre / Temp.)  y≈143pt -> 50.5mm desde arriba

    DomPDF soporta position:absolute con unidades mm, que es como clavamos cada
    elemento. Fuente DejaVu Sans (sans-serif por defecto de DomPDF).
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            width: 210mm;
            height: 297mm;
            background: #ffffff;
            font-family: 'DejaVu Sans', sans-serif;
            color: #000000;
        }
        .abs { position: absolute; }

        /* QR — esquina superior izquierda (bbox ref: 13.2mm,13.8mm · 22.2mm) */
        .qr {
            left: 13.2mm;
            top: 13.8mm;
            width: 22.2mm;
            height: 22.2mm;
        }
        .qr img { width: 22.2mm; height: 22.2mm; display: block; }

        /* ---- Columna izquierda (debajo del QR) ----
           Tamaños y tops CLAVADOS al abono.pdf de referencia (socio #3):
           regular = 8.2pt, negrita = 9.8pt; solo "TIPO" va en negrita.        */
        .col-izq { left: 12.7mm; width: 70mm; }
        .l-nombre { top: 49.0mm; font-size: 8.2pt; font-weight: normal; }
        .l-tipo   { top: 53.1mm; font-size: 9.8pt; font-weight: bold;   }
        .l-asiento{ top: 57.4mm; font-size: 8.2pt; font-weight: normal; }

        /* ---- Columna derecha (alineada a la derecha, borde x≈97.4mm) ---- */
        .col-der {
            left: 12.7mm;
            width: 84.7mm;     /* 97.4mm - 12.7mm */
            text-align: right;
        }
        .r-temp   { top: 49.0mm; font-size: 8.2pt; font-weight: normal; }
        .r-socio  { top: 53.7mm; font-size: 8.2pt; font-weight: normal; }
        .r-sector { top: 57.4mm; font-size: 8.2pt; font-weight: normal; }
    </style>
</head>
<body>
    {{-- QR --}}
    <div class="abs qr">
        <img src="{{ $qrDataUri }}" alt="QR">
    </div>

    {{-- Columna izquierda --}}
    <div class="abs col-izq l-nombre">{{ $nombre }}</div>
    <div class="abs col-izq l-tipo">{{ $tipoPrecio }}</div>
    <div class="abs col-izq l-asiento">{{ $lineaAsiento }}</div>

    {{-- Columna derecha --}}
    <div class="abs col-der r-temp">Temp. {{ $tempCorta }}</div>
    <div class="abs col-der r-socio">Nº.{{ $numSocio }}</div>
    <div class="abs col-der r-sector">{{ $sectorNombre }}</div>
</body>
</html>
