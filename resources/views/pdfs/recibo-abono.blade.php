<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #111; font-size: 11pt; }
        .wrap { padding: 22mm 20mm; }
        .head { border-bottom: 2px solid #CF2E2E; padding-bottom: 8mm; margin-bottom: 8mm; }
        .head h1 { font-size: 15pt; color: #CF2E2E; }
        .head .sub { font-size: 9pt; color: #555; margin-top: 2mm; }
        h2 { font-size: 12pt; margin: 6mm 0 3mm; border-bottom: 1px solid #ddd; padding-bottom: 1mm; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2mm 0; vertical-align: top; }
        td.lbl { color: #666; width: 38%; font-size: 9.5pt; text-transform: uppercase; }
        td.val { font-weight: bold; }
        .total { margin-top: 8mm; padding: 5mm; background: #f5f5f5; border: 1px solid #e2e2e2; }
        .total .lbl { font-size: 9.5pt; color: #666; text-transform: uppercase; }
        .total .amt { font-size: 18pt; font-weight: bold; color: #CF2E2E; }
        .foot { margin-top: 14mm; font-size: 8pt; color: #888; border-top: 1px solid #eee; padding-top: 4mm; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="head">
        <h1>{{ $club['nombre_comercial'] ?? 'Algeciras C.F.' }} — RECIBO DE ABONO</h1>
        <div class="sub">
            {{ $club['razon_social'] ?? '' }} · CIF {{ $club['cif'] ?? '' }}<br>
            {{ ($club['direccion']['calle'] ?? '') }}, {{ ($club['direccion']['codigo_postal'] ?? '') }}
            {{ ($club['direccion']['localidad'] ?? '') }} ({{ ($club['direccion']['provincia'] ?? '') }})
        </div>
    </div>

    <table>
        <tr><td class="lbl">Nº de recibo</td><td class="val">{{ $numRecibo }}</td></tr>
        <tr><td class="lbl">Fecha</td><td class="val">{{ $fecha }}</td></tr>
        <tr><td class="lbl">Temporada</td><td class="val">{{ $temporada }}</td></tr>
    </table>

    <h2>Datos del abonado</h2>
    <table>
        <tr><td class="lbl">Nombre</td><td class="val">{{ $nombre }}</td></tr>
        <tr><td class="lbl">DNI</td><td class="val">{{ $dni }}</td></tr>
        <tr><td class="lbl">Nº de abonado</td><td class="val">{{ $numAbonado }}</td></tr>
    </table>

    <h2>Datos del abono</h2>
    <table>
        <tr><td class="lbl">Tipo de abono</td><td class="val">{{ $tipo }}</td></tr>
        <tr><td class="lbl">Localidad</td><td class="val">{{ $sector }} · Fila {{ $fila }} · Butaca {{ $butaca }}</td></tr>
    </table>

    <div class="total">
        <table>
            <tr>
                <td class="lbl" style="vertical-align: middle;">Importe abonado</td>
                <td class="amt" style="text-align: right;">{{ $precio }}</td>
            </tr>
        </table>
    </div>

    <div class="foot">
        Documento justificante del pago del abono de temporada. Conserve este recibo.
        Para cualquier incidencia contacte con {{ $club['email'] ?? '' }}.
    </div>
</div>
</body>
</html>
