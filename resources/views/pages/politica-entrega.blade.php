@extends('layouts.app')

@section('title', 'Política de Entrega')
@section('description', 'Información sobre plazos y modalidades de entrega para los productos vendidos en la web del Algeciras CF.')

@section('content')
@php($c = config('club'))
<div class="container mx-auto px-4 lg:px-8 py-16 max-w-4xl prose prose-lg dark:prose-invert">
    <h1 class="font-display text-4xl uppercase tracking-wider text-algeciras-red">Política de Entrega</h1>
    <p class="text-sm text-gray-500">Última actualización: {{ now()->translatedFormat('d \\d\\e F \\d\\e Y') }}</p>

    <h2>1. Naturaleza de los productos</h2>
    <p>Esta política de entrega cubre los productos vendidos en
       <a href="{{ $c['web'] }}" class="text-algeciras-red">{{ $c['web'] }}</a> y a través de la
       app móvil oficial:</p>

    <table class="not-prose w-full text-sm border-collapse my-6">
        <thead>
            <tr class="border-b font-semibold">
                <td class="py-2 pr-4">Producto</td>
                <td class="py-2 pr-4">Tipo</td>
                <td class="py-2 pr-4">Gastos de envío</td>
                <td class="py-2">Plazo de entrega</td>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b">
                <td class="py-2 pr-4">Abono temporada</td>
                <td class="py-2 pr-4">Digital (QR)</td>
                <td class="py-2 pr-4 text-green-700 font-semibold">0,00 € (no aplica)</td>
                <td class="py-2 font-semibold">Inmediato</td>
            </tr>
            <tr class="border-b">
                <td class="py-2 pr-4">Entrada partido</td>
                <td class="py-2 pr-4">Digital (QR)</td>
                <td class="py-2 pr-4 text-green-700 font-semibold">0,00 € (no aplica)</td>
                <td class="py-2 font-semibold">Inmediato</td>
            </tr>
            <tr>
                <td class="py-2 pr-4">Merchandising</td>
                <td class="py-2 pr-4">Físico</td>
                <td class="py-2 pr-4">Gestionado por la tienda oficial externa</td>
                <td class="py-2">Ver
                    <a href="https://tienda.algecirasclubdefutbol.com" class="text-algeciras-red">
                        tienda.algecirasclubdefutbol.com</a></td>
            </tr>
        </tbody>
    </table>

    <h2>2. Productos digitales (abonos y entradas)</h2>
    <p>Los abonos y entradas son productos <strong>100% digitales</strong>. No hay envío físico.
       Tras la confirmación del pago por parte del banco, el sistema:</p>
    <ol>
        <li>Genera el carnet/entrada digital con código QR único y firmado.</li>
        <li>Envía un email de confirmación al correo facilitado durante la compra.</li>
        <li>Hace disponible el QR en el área personal de la web
            (<a href="{{ url('/area-personal') }}" class="text-algeciras-red">/area-personal</a>)
            y en la pestaña "Mi Cuenta" de la app móvil oficial.</li>
    </ol>
    <p>El plazo total desde el pago hasta la disponibilidad del QR es <strong>inferior a 1 minuto</strong>
       en condiciones normales.</p>

    <h2>3. Gastos de envío</h2>
    <p>Por la naturaleza digital de los productos vendidos en este sitio web (abonos y entradas),
       <strong>no se aplican gastos de envío</strong>. El precio mostrado al usuario es el precio
       total final, salvo los gastos de gestión del 5% que se desglosan antes de la confirmación
       de pago.</p>

    <h2>4. Acceso al estadio el día del partido</h2>
    <p>El día del partido, el usuario deberá presentar el código QR de su abono o entrada en
       cualquier acceso del Estadio Nuevo Mirador. El QR puede mostrarse desde:</p>
    <ul>
        <li>La app oficial del club (recomendado, funciona sin conexión).</li>
        <li>Una captura de pantalla del email recibido.</li>
        <li>Cualquier otra impresión o copia legible del QR.</li>
    </ul>
    <p>El sistema validador del club identifica el QR y autoriza la entrada en menos de un segundo.</p>

    <h2>5. Problemas con la entrega digital</h2>
    <p>Si transcurridos 5 minutos desde el pago no ha recibido el email con el QR:</p>
    <ol>
        <li>Revise la carpeta de spam/correo no deseado.</li>
        <li>Acceda a <a href="{{ url('/area-personal') }}" class="text-algeciras-red">/area-personal</a>
            iniciando sesión con el email usado en la compra. El QR estará disponible allí.</li>
        <li>Si tras los pasos anteriores no encuentra su entrada, contacte con
            <a href="mailto:{{ $c['email'] }}" class="text-algeciras-red">{{ $c['email'] }}</a>
            indicando la referencia del pedido.</li>
    </ol>

    <h2>6. Merchandising físico</h2>
    <p>La tienda oficial de merchandising (camisetas, accesorios, etc.) se gestiona en un sitio
       externo: <strong>tienda.algecirasclubdefutbol.com</strong>. Para conocer los gastos de envío,
       plazos de entrega y política de devolución aplicables al merchandising físico, consulte las
       condiciones publicadas en dicho sitio.</p>

    <h2>7. Contacto</h2>
    <p>Para cualquier consulta sobre la entrega de su pedido:</p>
    <ul>
        <li>Email: <a href="mailto:{{ $c['email'] }}" class="text-algeciras-red">{{ $c['email'] }}</a></li>
        <li>Teléfono: {{ $c['telefono'] }}</li>
    </ul>
</div>
@endsection
