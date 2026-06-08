@extends('layouts.app')

@section('title', 'Condiciones de venta, cancelación y devolución')
@section('description', 'Condiciones aplicables a la compra de abonos, entradas y productos del Algeciras CF — política de cancelación, devolución y desistimiento.')

@section('content')
@php($c = config('club'))
<div class="container mx-auto px-4 lg:px-8 py-16 max-w-4xl prose prose-lg dark:prose-invert">
    <h1 class="font-display text-4xl uppercase tracking-wider text-algeciras-red">Condiciones de Venta</h1>
    <p class="text-sm text-gray-500">Última actualización: {{ now()->translatedFormat('d \\d\\e F \\d\\e Y') }}</p>

    <h2>1. Información general</h2>
    <p>Las presentes Condiciones Generales de Venta (en adelante, "Condiciones") regulan la adquisición
       de abonos de temporada, entradas para partidos y demás productos ofrecidos a través de
       <strong>{{ $c['web'] }}</strong> y la app móvil oficial del club.</p>
    <p><strong>Vendedor:</strong> {{ $c['razon_social'] }} (CIF {{ $c['cif'] }}), con domicilio en
       {{ $c['direccion']['calle'] }}, {{ $c['direccion']['codigo_postal'] }}
       {{ $c['direccion']['localidad'] }} ({{ $c['direccion']['provincia'] }}).</p>

    <h2>2. Productos ofrecidos</h2>
    <ul>
        <li><strong>Abono de temporada</strong>: derecho de acceso al estadio para todos los partidos
            de competición oficial disputados como local durante la temporada vigente.</li>
        <li><strong>Entrada para partido</strong>: derecho de acceso al estadio para un único partido.</li>
        <li><strong>Merchandising</strong>: productos físicos de la tienda oficial (camisetas,
            accesorios, etc.), gestionados desde la web externa
            <a href="https://tienda.algecirasclubdefutbol.com" class="text-algeciras-red">tienda.algecirasclubdefutbol.com</a>.</li>
    </ul>

    <h2>3. Proceso de compra y formalización</h2>
    <p>El usuario deberá completar el formulario con sus datos personales (obligatorios para emitir
       la factura electrónica y permitir el acceso al estadio), seleccionar el producto, aceptar
       expresamente estas Condiciones y proceder al pago. La compra queda perfeccionada una vez el
       banco confirma el cargo.</p>
    <p>Inmediatamente tras la confirmación del pago el usuario recibirá:</p>
    <ul>
        <li>Email de confirmación con la referencia del pedido.</li>
        <li>Carnet/Entrada digital con código QR único, descargable desde el área personal de la
            web o desde la app oficial.</li>
    </ul>

    <h2>4. Precios e impuestos</h2>
    <p>Todos los precios mostrados en la web incluyen el IVA aplicable según la legislación
       española vigente. Junto al precio del producto, podrán aplicarse:</p>
    <ul>
        <li><strong>Gastos de gestión</strong>: 5% sobre el subtotal, mostrado de forma desglosada
            antes de finalizar la compra.</li>
        <li><strong>Gastos de envío</strong>: no aplicables. Los abonos y entradas son productos
            <em>digitales</em> de acceso a evento; no se realiza envío físico.</li>
    </ul>

    <h2>5. Plazos de entrega</h2>
    <p>Por tratarse de productos digitales, la entrega es <strong>inmediata</strong> tras la
       confirmación del pago. El usuario puede acceder al QR de su abono/entrada:</p>
    <ul>
        <li>En su email de confirmación.</li>
        <li>En el área personal de la web <a href="{{ url('/area-personal') }}" class="text-algeciras-red">/area-personal</a>.</li>
        <li>En la pestaña "Mi Cuenta" de la app oficial del club.</li>
    </ul>
    <p>En caso de no recibir el email en los 5 minutos siguientes al pago, debe revisarse la carpeta
       de spam y, de no localizarlo, contactar a
       <a href="mailto:{{ $c['email'] }}" class="text-algeciras-red">{{ $c['email'] }}</a>.</p>

    <h2>6. Política de cancelación y devolución</h2>
    <p>De acuerdo con el artículo 103.l del Real Decreto Legislativo 1/2007, de 16 de noviembre,
       por el que se aprueba el texto refundido de la Ley General para la Defensa de los
       Consumidores y Usuarios (TRLGDCU), <strong>el derecho de desistimiento NO es de aplicación
       a los abonos ni a las entradas</strong>, al tratarse de prestaciones de servicios de ocio
       que han de prestarse en una fecha o periodo de ejecución específico.</p>

    <h3>6.1 Cuándo SÍ hay reembolso</h3>
    <ul>
        <li>Si el partido es <strong>cancelado o suspendido</strong> por el club y no se reprograma:
            reembolso íntegro del importe pagado, sin gastos para el usuario.</li>
        <li>Si se produce un <strong>error técnico</strong> en la compra (doble cargo, importe
            incorrecto): regularización inmediata previa verificación.</li>
        <li>Si el cliente <strong>no llega a recibir el QR</strong> por causa imputable al club
            y la compra no se puede materializar: reembolso completo.</li>
    </ul>

    <h3>6.2 Cuándo NO hay reembolso</h3>
    <ul>
        <li>Cambio de opinión del comprador.</li>
        <li>Imposibilidad de asistencia personal del comprador al partido.</li>
        <li>Suspensión del partido reprogramado a otra fecha (la entrada sigue siendo válida).</li>
        <li>No utilización del abono durante la temporada.</li>
    </ul>

    <h3>6.3 Cómo solicitar un reembolso</h3>
    <p>El usuario deberá enviar un email a
       <a href="mailto:{{ $c['venta']['email_devoluciones'] }}" class="text-algeciras-red">{{ $c['venta']['email_devoluciones'] }}</a>
       indicando referencia del pedido, motivo y, en su caso, justificación documental. El club se
       compromete a responder en un plazo máximo de <strong>{{ $c['venta']['plazo_resolucion_dias'] }} días</strong>
       desde la recepción de la solicitud. Los reembolsos se realizarán por el mismo medio de pago
       utilizado en la compra original.</p>

    <h2>7. Medios de pago</h2>
    <p>Aceptamos los siguientes medios:</p>
    <ul>
        <li>Tarjeta bancaria a través del TPV Virtual de {{ $c['banco'] }}/Redsys (Visa, Mastercard,
            Maestro), con 3D Secure obligatorio.</li>
        <li>Pago en taquilla del estadio (efectivo, Bizum, TPV físico) — sólo para compras
            presenciales gestionadas por el club.</li>
    </ul>
    <p>La operación de pago se realiza en una sesión segura cifrada (HTTPS/TLS) directamente
       conectada al servidor del banco. {{ $c['razon_social'] }} <em>no almacena los datos de la
       tarjeta</em>. Únicamente recibe la confirmación de pago o de error.</p>

    <h2>8. Atención al cliente</h2>
    <p>Para cualquier consulta, queja o reclamación relacionada con su compra:</p>
    <ul>
        <li>Email: <a href="mailto:{{ $c['email'] }}" class="text-algeciras-red">{{ $c['email'] }}</a></li>
        <li>Teléfono: {{ $c['telefono'] }}</li>
        <li>Postal: {{ $c['razon_social'] }}, {{ $c['direccion']['calle'] }},
            {{ $c['direccion']['codigo_postal'] }} {{ $c['direccion']['localidad'] }}.</li>
    </ul>

    <h2>9. Legislación aplicable y resolución de conflictos</h2>
    <p>Estas Condiciones se rigen por la legislación española. Cualquier litigio podrá someterse a
       la <a href="https://ec.europa.eu/consumers/odr/" class="text-algeciras-red" target="_blank" rel="noopener">plataforma europea de resolución de litigios en línea (ODR)</a>
       o a los Juzgados y Tribunales de {{ $c['direccion']['localidad'] }}.</p>
</div>
@endsection
