@extends('layouts.app')

@section('title', 'Aviso Legal')
@section('description', 'Aviso legal del Algeciras Club de Fútbol — datos del titular del sitio web, identificación fiscal y condiciones generales de uso.')

@section('content')
@php($c = config('club'))
<div class="container mx-auto px-4 lg:px-8 py-16 max-w-4xl prose prose-lg dark:prose-invert">
    <h1 class="font-display text-4xl uppercase tracking-wider text-algeciras-red">Aviso Legal</h1>
    <p class="text-sm text-gray-500">Última actualización: {{ now()->translatedFormat('d \\d\\e F \\d\\e Y') }}</p>

    <h2>1. Datos identificativos del titular</h2>
    <p>En cumplimiento del artículo 10 de la Ley 34/2002, de 11 de julio, de servicios de la
       sociedad de la información y de comercio electrónico (LSSI-CE), se informa al usuario:</p>

    <table class="not-prose w-full text-sm border-collapse my-6">
        <tr class="border-b"><td class="font-semibold py-2 pr-4 w-1/3">Titular</td>
            <td class="py-2">{{ $c['razon_social'] }}</td></tr>
        <tr class="border-b"><td class="font-semibold py-2 pr-4">CIF</td>
            <td class="py-2">{{ $c['cif'] }}</td></tr>
        <tr class="border-b"><td class="font-semibold py-2 pr-4">Domicilio social</td>
            <td class="py-2">{{ $c['direccion']['calle'] }},
                             {{ $c['direccion']['codigo_postal'] }}
                             {{ $c['direccion']['localidad'] }}
                             ({{ $c['direccion']['provincia'] }}),
                             {{ $c['direccion']['pais'] }}</td></tr>
        <tr class="border-b"><td class="font-semibold py-2 pr-4">Email</td>
            <td class="py-2"><a href="mailto:{{ $c['email'] }}" class="text-algeciras-red">{{ $c['email'] }}</a></td></tr>
        <tr class="border-b"><td class="font-semibold py-2 pr-4">Teléfono</td>
            <td class="py-2">{{ $c['telefono'] }}</td></tr>
        <tr><td class="font-semibold py-2 pr-4">Web</td>
            <td class="py-2"><a href="{{ $c['web'] }}" class="text-algeciras-red">{{ $c['web'] }}</a></td></tr>
    </table>

    <h3 class="font-display uppercase tracking-wide text-algeciras-red mt-8">Comercio que procesa los pagos (TPV Virtual)</h3>
    <p>La venta de abonos, entradas y servicios la realiza {{ $c['razon_social'] }}. El cobro con
       tarjeta se procesa de forma segura a través del TPV Virtual de Banco Sabadell (Redsys), cuyo
       comercio titular es:</p>

    <table class="not-prose w-full text-sm border-collapse my-6">
        <tr class="border-b"><td class="font-semibold py-2 pr-4 w-1/3">Comercio (pasarela de pago)</td>
            <td class="py-2">{{ $c['comercio']['razon_social'] }}</td></tr>
        <tr class="border-b"><td class="font-semibold py-2 pr-4">CIF</td>
            <td class="py-2">{{ $c['comercio']['cif'] }}</td></tr>
        <tr class="border-b"><td class="font-semibold py-2 pr-4">Domicilio</td>
            <td class="py-2">{{ $c['comercio']['direccion'] }}</td></tr>
        <tr @if(!empty($c['comercio']['telefono'])) class="border-b" @endif><td class="font-semibold py-2 pr-4">Email</td>
            <td class="py-2"><a href="mailto:{{ $c['comercio']['email'] }}" class="text-algeciras-red">{{ $c['comercio']['email'] }}</a></td></tr>
        @if(!empty($c['comercio']['telefono']))
        <tr><td class="font-semibold py-2 pr-4">Teléfono</td>
            <td class="py-2">{{ $c['comercio']['telefono'] }}</td></tr>
        @endif
    </table>

    <h2>2. Objeto</h2>
    <p>Este sitio web es propiedad de {{ $c['razon_social'] }} y tiene por objeto facilitar
       información sobre el club y la venta de abonos, entradas y otros servicios relacionados
       con su actividad deportiva.</p>

    <h2>3. Condiciones de uso</h2>
    <p>El acceso y/o uso de este portal atribuye la condición de usuario, que acepta, desde dicho
       acceso y/o uso, las presentes condiciones generales de uso. El usuario se compromete a hacer
       un uso adecuado de los contenidos y servicios que el club ofrece a través de su portal y a no
       emplearlos para incurrir en actividades ilícitas o lesivas de derechos e intereses de terceros.</p>

    <h2>4. Propiedad intelectual e industrial</h2>
    <p>Todos los contenidos del sitio web (textos, fotografías, gráficos, imágenes, escudo,
       tecnología, software, links y demás contenidos audiovisuales o sonoros, así como su diseño
       gráfico y códigos fuente), son propiedad intelectual de {{ $c['razon_social'] }} o de
       terceros, sin que puedan entenderse cedidos al usuario ninguno de los derechos de
       explotación reconocidos por la normativa vigente.</p>

    <h2>5. Aviso PSD2 / GDPR — Operativa de pago</h2>
    <p>En virtud del cumplimiento de la normativa EMVCo, MasterCard, VISA y la Directiva Europea
       PSDII, durante la operación de pago electrónico se comunicará información referida a la
       conexión empleada en la sesión al Banco emisor de la tarjeta para proceder a la validación
       de la operación de pago realizada por el usuario.</p>

    <h2>6. Política de privacidad y cookies</h2>
    <p>El tratamiento de los datos personales se realiza conforme a nuestra
       <a href="{{ url('/privacidad') }}" class="text-algeciras-red">Política de Privacidad</a>.
       Para cualquier consulta relativa a protección de datos puede contactar con el Delegado de
       Protección de Datos en <a href="mailto:{{ $c['email_dpd'] }}" class="text-algeciras-red">{{ $c['email_dpd'] }}</a>.</p>

    <h2>7. Legislación aplicable y jurisdicción</h2>
    <p>La relación entre {{ $c['razon_social'] }} y el usuario se regirá por la normativa española
       vigente. Cualquier controversia que pudiera surgir se someterá a los Juzgados y Tribunales
       de {{ $c['direccion']['localidad'] }} ({{ $c['direccion']['provincia'] }}).</p>

</div>
@endsection
