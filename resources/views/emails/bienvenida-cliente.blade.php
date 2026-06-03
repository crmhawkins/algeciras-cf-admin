@php
    $titulo = match($tipoCompra) {
        'abono'    => '¡Bienvenido al Algeciras CF, abonado!',
        'entrada'  => '¡Gracias por venir al Nuevo Mirador!',
        default    => '¡Bienvenido al Algeciras CF!',
    };
    $primario = '#E30A2C';
    $negro    = '#1A1A1A';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:#f4f4f5;color:{{ $negro }};">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;">
        <tr><td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;margin:24px 0;">

                <tr>
                    <td style="background:{{ $negro }};padding:24px;text-align:center;">
                        <h1 style="color:#ffffff;margin:0;font-size:22px;letter-spacing:2px;">ALGECIRAS C.F.</h1>
                        <p style="color:{{ $primario }};margin:4px 0 0;font-size:11px;letter-spacing:3px;">PRIMERA FEDERACIÓN</p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:32px;">
                        <h2 style="margin:0 0 8px;color:{{ $primario }};">{{ $titulo }}</h2>
                        <p style="margin:0 0 16px;font-size:15px;">Hola <strong>{{ $customer->first_name }}</strong>,</p>
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                            @if($tipoCompra === 'abono')
                                Acabamos de registrar tu abono para la temporada. ¡Bienvenido a la familia rojiblanca!
                                A continuación tienes los datos para acceder a tu cuenta del club desde la web o la app.
                            @elseif($tipoCompra === 'entrada')
                                Acabamos de registrar tu compra de entrada. Hemos creado además una cuenta para que
                                puedas descargar tu QR y acceder al estadio sin imprimir nada.
                            @else
                                Hemos creado tu cuenta en el club. A continuación tienes los datos para acceder.
                            @endif
                        </p>

                        <h3 style="margin:24px 0 8px;color:{{ $negro }};font-size:16px;">🔐 Tus credenciales de acceso</h3>
                        <table cellpadding="8" cellspacing="0" style="background:#f9fafb;border-radius:8px;width:100%;font-size:14px;">
                            <tr>
                                <td style="font-weight:bold;width:30%;">Email:</td>
                                <td>{{ $customer->email }}</td>
                            </tr>
                            <tr>
                                <td style="font-weight:bold;">Contraseña temporal:</td>
                                <td><code style="background:#fff;padding:4px 8px;border:1px solid #e5e7eb;border-radius:4px;">{{ $password }}</code></td>
                            </tr>
                        </table>
                        <p style="margin:8px 0 24px;font-size:12px;color:#6b7280;">
                            <em>Por seguridad, cámbiala la primera vez que entres en
                            <a href="{{ $resetUrl }}" style="color:{{ $primario }};">{{ $resetUrl }}</a>.</em>
                        </p>

                        <h3 style="margin:24px 0 8px;color:{{ $negro }};font-size:16px;">📱 Cómo usar tu cuenta</h3>
                        <ol style="font-size:14px;line-height:1.7;padding-left:18px;margin:0 0 16px;">
                            <li>Descarga la app oficial del Algeciras CF
                                (TestFlight en iPhone, próximamente Android).</li>
                            <li>Inicia sesión con el email y contraseña de arriba.</li>
                            <li>En la pestaña <strong>Mi Cuenta</strong> verás tu carnet de socio con el QR
                                listo para entrar al Nuevo Mirador.</li>
                            <li>O entra en la web:
                                <a href="{{ $loginUrl }}" style="color:{{ $primario }};">{{ $loginUrl }}</a></li>
                        </ol>

                        @if($tipoCompra === 'abono')
                            <p style="margin:24px 0 0;font-size:14px;background:#fef3c7;padding:12px;border-radius:8px;border-left:4px solid #f59e0b;">
                                <strong>Tu abono ya está vinculado a esta cuenta.</strong>
                                Recibirás otro email aparte con el QR específico para escanear en taquilla
                                y los detalles de tu butaca.
                            </p>
                        @endif

                        <p style="margin:32px 0 0;font-size:14px;color:#6b7280;">
                            ¡Nos vemos en el Mirador! 🔴⚪<br>
                            <strong style="color:{{ $primario }};">Algeciras C.F.</strong> · #CrecemosContigo
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="background:#f9fafb;padding:16px;text-align:center;font-size:11px;color:#6b7280;">
                        Algeciras Club de Fútbol · Estadio Nuevo Mirador, Algeciras (Cádiz) ·
                        <a href="https://algecirasclubdefutbol.com" style="color:{{ $primario }};">algecirasclubdefutbol.com</a>
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
