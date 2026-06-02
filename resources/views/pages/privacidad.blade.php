@extends('layouts.app')

@section('title', 'Política de Privacidad')

@section('content')
<div class="container mx-auto px-4 lg:px-8 py-16 max-w-3xl">
    <h1 class="font-display text-5xl mb-4">Política de Privacidad</h1>
    <p class="text-sm text-algeciras-gray mb-10">Última actualización: 02 de junio de 2026</p>

    <div class="prose prose-lg max-w-none space-y-6 text-algeciras-black/85">

        <p>El <strong>Algeciras Club de Fútbol, S.A.D.</strong> ("el Club", "nosotros") es el responsable del tratamiento de los datos personales recogidos a través de la aplicación móvil <em>Algeciras CF Abonados</em> y del sitio web <a href="https://algecirascf.hawkins.es">algecirascf.hawkins.es</a>. Esta política describe qué información recopilamos, cómo la usamos y los derechos que te asisten conforme al Reglamento General de Protección de Datos (RGPD) y la Ley Orgánica 3/2018 de Protección de Datos.</p>

        <h2 class="font-display text-2xl">1. Responsable del tratamiento</h2>
        <ul>
            <li>Algeciras Club de Fútbol, S.A.D.</li>
            <li>Estadio Nuevo Mirador, Calle El Mirador, s/n — 11201 Algeciras (Cádiz)</li>
            <li>Email: <a href="mailto:dpo@algecirasclubdefutbol.com">dpo@algecirasclubdefutbol.com</a></li>
        </ul>

        <h2 class="font-display text-2xl">2. Datos que recogemos</h2>
        <ul>
            <li><strong>Datos de cuenta:</strong> nombre, apellidos, email, teléfono, DNI/NIE, dirección, fecha de nacimiento.</li>
            <li><strong>Datos de pago:</strong> los pagos son procesados por <a href="https://stripe.com/es/privacy" target="_blank" rel="noopener">Stripe</a>; nosotros NO almacenamos números de tarjeta. Sólo guardamos el importe, fecha y referencia de pago.</li>
            <li><strong>Datos de uso:</strong> abonos, entradas, asistencias a partidos, votaciones de FanZone, preferencias de notificaciones.</li>
            <li><strong>Datos técnicos:</strong> dirección IP, tipo de dispositivo, sistema operativo, versión de la app, identificador push de notificaciones (Expo/Apple/Google).</li>
        </ul>

        <h2 class="font-display text-2xl">3. Para qué los usamos</h2>
        <ul>
            <li>Gestionar tu condición de abonado o comprador de entradas.</li>
            <li>Emitir tickets QR de acceso al estadio.</li>
            <li>Enviar comunicaciones operativas (confirmaciones, recordatorios de partido) y, si lo autorizas, noticias del club.</li>
            <li>Cumplir obligaciones legales, fiscales y de seguridad en el acceso al estadio.</li>
        </ul>

        <h2 class="font-display text-2xl">4. Base legal</h2>
        <p>Tratamos tus datos en base a: (i) la ejecución del contrato de abonado o compra de entrada, (ii) el cumplimiento de obligaciones legales y (iii) tu consentimiento explícito cuando lo solicitamos (notificaciones promocionales, etc.).</p>

        <h2 class="font-display text-2xl">5. Conservación</h2>
        <p>Conservamos tus datos mientras dure la relación contractual y, después, durante los plazos legalmente exigibles (5 años para datos fiscales, 10 años para seguridad y antiterrorismo, según normativa vigente).</p>

        <h2 class="font-display text-2xl">6. Compartición con terceros</h2>
        <ul>
            <li><strong>Stripe</strong> (procesador de pagos, EE.UU., con cláusulas contractuales tipo).</li>
            <li><strong>Proveedores de email y SMS</strong> para comunicaciones operativas.</li>
            <li><strong>Hosting:</strong> Hawkins Software (servidores ubicados en la UE).</li>
            <li><strong>Cuerpos y fuerzas de seguridad</strong> cuando sea exigido por ley (ej.: identificación en accesos a recintos deportivos).</li>
        </ul>

        <h2 class="font-display text-2xl">7. Tus derechos</h2>
        <p>Puedes ejercer los derechos de acceso, rectificación, supresión, oposición, portabilidad y limitación enviando un email a <a href="mailto:dpo@algecirasclubdefutbol.com">dpo@algecirasclubdefutbol.com</a> con copia de tu DNI. También puedes reclamar ante la Agencia Española de Protección de Datos (<a href="https://www.aepd.es" target="_blank" rel="noopener">aepd.es</a>).</p>

        <h2 class="font-display text-2xl">8. Notificaciones push</h2>
        <p>La app puede pedirte permiso para enviarte notificaciones (avisos de partido, goles, novedades). Puedes desactivarlas en cualquier momento desde los ajustes del sistema operativo o desde "Mi Cuenta → Notificaciones" en la app.</p>

        <h2 class="font-display text-2xl">9. Datos de menores</h2>
        <p>Los abonos infantiles requieren autorización expresa de padre/madre/tutor legal. No recogemos datos de menores de 14 años sin consentimiento parental.</p>

        <h2 class="font-display text-2xl">10. Cambios en esta política</h2>
        <p>Publicaremos cualquier cambio sustancial en esta misma página con al menos 30 días de antelación. La fecha de la cabecera indica la última actualización.</p>

        <hr class="my-10">
        <p class="text-sm text-algeciras-gray">Si tienes dudas sobre esta política, escríbenos a <a href="mailto:dpo@algecirasclubdefutbol.com">dpo@algecirasclubdefutbol.com</a>.</p>
    </div>
</div>
@endsection
