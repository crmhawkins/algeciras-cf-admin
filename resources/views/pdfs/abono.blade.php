<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Abono Digital · Algeciras CF</title>
<style>
    @page { margin: 24px; }
    body { font-family: DejaVu Sans, sans-serif; color: #111; margin: 0; padding: 0; font-size: 12px; }
    .header { background: #CF2E2E; color: #fff; padding: 18px 24px; }
    .header h1 { margin: 0; font-size: 22px; letter-spacing: 2px; }
    .header .sub { font-size: 11px; opacity: .9; margin-top: 4px; letter-spacing: 1px; }
    .badge { display: inline-block; background: #0A0A0A; color: #fff; padding: 4px 10px; font-size: 10px; letter-spacing: 2px; margin-top: 6px; }
    .wrapper { padding: 20px 24px; }
    h2 { font-size: 13px; color: #CF2E2E; border-bottom: 2px solid #CF2E2E; padding-bottom: 4px; margin: 18px 0 10px; letter-spacing: 1px; text-transform: uppercase; }
    table.data { width: 100%; border-collapse: collapse; margin: 4px 0 8px; }
    table.data td { padding: 6px 0; font-size: 12px; vertical-align: top; }
    table.data td.label { color: #666; width: 35%; }
    table.data td.value { color: #111; font-weight: bold; }
    .qr-box { text-align: center; margin: 20px 0 10px; }
    .qr-box img { width: 250px; height: 250px; border: 6px solid #0A0A0A; padding: 4px; background: #fff; }
    .qr-box .uuid { font-family: monospace; font-size: 9px; color: #888; margin-top: 8px; word-break: break-all; }
    .footer { background: #0A0A0A; color: #aaa; padding: 14px 24px; font-size: 10px; text-align: center; margin-top: 22px; }
    .nota { background: #fff8e1; border-left: 4px solid #f59e0b; padding: 8px 12px; font-size: 11px; margin: 12px 0; }
</style>
</head>
<body>

<div class="header">
    <h1>ALGECIRAS CF</h1>
    <div class="sub">ESTADIO NUEVO MIRADOR · ALGECIRAS</div>
    <span class="badge">ABONO DIGITAL · TEMPORADA {{ $temporada }}</span>
</div>

<div class="wrapper">

    <h2>Titular del abono</h2>
    <table class="data">
        <tr>
            <td class="label">Nombre y apellidos</td>
            <td class="value">{{ $titular }}</td>
        </tr>
        @if ($dni)
        <tr>
            <td class="label">DNI / NIE</td>
            <td class="value">{{ $dni }}</td>
        </tr>
        @endif
        @if ($email)
        <tr>
            <td class="label">Email</td>
            <td class="value">{{ $email }}</td>
        </tr>
        @endif
    </table>

    <h2>Detalles del abono</h2>
    <table class="data">
        <tr>
            <td class="label">Producto</td>
            <td class="value">{{ $producto }}</td>
        </tr>
        <tr>
            <td class="label">Zona</td>
            <td class="value">{{ $zona }}</td>
        </tr>
        @if ($asiento)
        <tr>
            <td class="label">Asiento</td>
            <td class="value">{{ $asiento }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Referencia pedido</td>
            <td class="value">{{ $referencia }}</td>
        </tr>
        <tr>
            <td class="label">Emitido</td>
            <td class="value">{{ $emitido }}</td>
        </tr>
    </table>

    <div class="qr-box">
        @if ($qrBase64)
            <img src="{{ $qrBase64 }}" alt="QR de acceso">
        @endif
        <div class="uuid">{{ $uuid }}</div>
    </div>

    <div class="nota">
        <strong>Acceso al estadio:</strong> presenta este código QR (en el móvil o impreso) en la puerta el día del partido. No lo compartas con terceros.
    </div>

</div>

<div class="footer">
    Algeciras Club de Fútbol · Estadio Nuevo Mirador · Algeciras (Cádiz)<br>
    Documento generado automáticamente. Conserva el QR íntegro y legible.
</div>

</body>
</html>
