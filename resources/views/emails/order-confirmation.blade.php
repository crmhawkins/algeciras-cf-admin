<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmación de pedido — Algeciras CF</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:24px; color:#1f1f1f;">
    <div style="max-width:600px; margin:0 auto; background:#fff; border-top:6px solid #C8102E; padding:32px;">
        <h1 style="margin:0 0 4px; font-size:24px; color:#C8102E;">¡Gracias por tu compra!</h1>
        <p style="margin:0 0 24px; color:#666; font-size:14px;">
            Pedido <strong>{{ $order->reference }}</strong>
            · {{ optional($order->paid_at)->format('d/m/Y H:i') }}
        </p>

        <h2 style="font-size:16px; border-bottom:2px solid #1f1f1f; padding-bottom:6px;">Tu pedido</h2>
        <table width="100%" cellspacing="0" cellpadding="6" style="border-collapse:collapse; font-size:14px;">
            @foreach ($order->items as $it)
                <tr style="border-bottom:1px solid #eee;">
                    <td>{{ $it->qty }} × {{ $it->name }}</td>
                    <td align="right">{{ number_format((float)$it->total, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
            <tr>
                <td>Subtotal</td>
                <td align="right">{{ number_format((float)$order->subtotal + (float)$order->vat, 2, ',', '.') }} €</td>
            </tr>
            @if ((float)$order->discount_amount > 0)
                <tr style="color:#0a7;">
                    <td>Descuento {{ $order->coupon_code }}</td>
                    <td align="right">−{{ number_format((float)$order->discount_amount, 2, ',', '.') }} €</td>
                </tr>
            @endif
            @if ((float)$order->gestion_fee > 0)
                <tr style="color:#666;">
                    <td>Gastos de gestión (5%)</td>
                    <td align="right">{{ number_format((float)$order->gestion_fee, 2, ',', '.') }} €</td>
                </tr>
            @endif
            <tr style="border-top:2px solid #1f1f1f; font-weight:bold; font-size:18px;">
                <td style="padding-top:10px;">TOTAL</td>
                <td align="right" style="padding-top:10px; color:#C8102E;">
                    {{ number_format((float)$order->total, 2, ',', '.') }} €
                </td>
            </tr>
        </table>

        @if ($order->tickets && $order->tickets->count())
            <h2 style="font-size:16px; margin-top:24px; border-bottom:2px solid #1f1f1f; padding-bottom:6px;">
                Tus QR
            </h2>
            <p style="font-size:13px; color:#666;">
                Tienes {{ $order->tickets->count() }} {{ Str::plural('código', $order->tickets->count()) }}
                de acceso disponibles en
                <a href="{{ url('/area-personal') }}" style="color:#C8102E;">Mi cuenta → Abonos / Entradas</a>
                con el QR para presentar en la puerta del estadio.
            </p>
        @endif

        <h2 style="font-size:16px; margin-top:32px; border-bottom:2px solid #1f1f1f; padding-bottom:6px;">
            Tu acceso a la app del Algeciras CF
        </h2>
        <p style="font-size:14px; color:#444; line-height:1.55; margin:12px 0;">
            Descarga la app oficial y lleva tu <strong>abono digital en el móvil</strong>:
            tu carnet con QR siempre a mano, sin papeles.
        </p>

        @if (!empty($appEmail) && !empty($appPassword))
            <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse; margin:8px 0 18px;">
                <tr>
                    <td style="background:#fbe9ec; border-left:4px solid #C8102E; padding:14px 16px; font-size:14px; color:#1f1f1f; line-height:1.7;">
                        <strong>Tus credenciales de acceso</strong><br>
                        Usuario (email): <strong>{{ $appEmail }}</strong><br>
                        Contraseña: <strong style="font-family:monospace; font-size:15px;">{{ $appPassword }}</strong><br>
                        <span style="font-size:12px; color:#777;">
                            Por seguridad, cámbiala desde la app la primera vez que entres.
                        </span>
                    </td>
                </tr>
            </table>
        @endif

        <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse; margin:4px 0 18px;">
            <tr>
                <td align="center" style="padding:4px;">
                    <a href="{{ $appStoreUrl ?? 'https://apps.apple.com/app/algeciras-cf' }}"
                       style="display:inline-block; background:#0A0A0A; color:#fff; text-decoration:none; padding:12px 22px; border-radius:8px; font-size:14px; font-weight:bold;">
                        📱 Descargar en App Store
                    </a>
                </td>
                <td align="center" style="padding:4px;">
                    <a href="{{ $playStoreUrl ?? 'https://play.google.com/store/apps/details?id=com.algecirascf.app' }}"
                       style="display:inline-block; background:#C8102E; color:#fff; text-decoration:none; padding:12px 22px; border-radius:8px; font-size:14px; font-weight:bold;">
                        ▶ Descargar en Google Play
                    </a>
                </td>
            </tr>
        </table>

        <p style="font-size:14px; color:#444; margin:6px 0 4px;"><strong>Ventajas de abonado en la app:</strong></p>
        <ul style="font-size:14px; color:#444; line-height:1.7; margin:4px 0 8px; padding-left:20px;">
            <li>Tu <strong>abono digital</strong> en el móvil, siempre contigo.</li>
            <li>Carnet con <strong>código QR</strong> para acceder al estadio.</li>
            <li><strong>Notificaciones</strong> de los próximos partidos.</li>
            <li><strong>FanZone</strong>: vota al MVP de cada encuentro.</li>
            <li><strong>Contenido exclusivo</strong> para abonados del club.</li>
        </ul>

        <p style="margin-top:32px; font-size:12px; color:#999; line-height:1.5;">
            Algeciras Club de Fútbol · Estadio Nuevo Mirador, Algeciras (Cádiz) ·
            info@algecirasclubdefutbol.com
        </p>
    </div>
</body>
</html>
