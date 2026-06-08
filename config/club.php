<?php

/**
 * Datos fiscales y de contacto del Algeciras Club de Fútbol.
 *
 * Centralizados aquí para que cualquier página legal (aviso legal,
 * condiciones de venta, política de entrega, aviso PSD2/GDPR) y los
 * impresos del TPV virtual los lean del mismo sitio.
 *
 * Cualquier cambio aquí afecta a TODAS las páginas legales automáticamente.
 * Sustituye los valores según el contrato firmado con Banco Sabadell / Redsys.
 */

return [
    // Razón social oficial (la que aparece en el contrato Redsys)
    'razon_social' => env('CLUB_RAZON_SOCIAL', 'Algeciras Club de Fútbol, S.A.D.'),

    // Nombre comercial (lo que ve el cliente)
    'nombre_comercial' => env('CLUB_NOMBRE_COMERCIAL', 'Algeciras C.F.'),

    // CIF/NIF — A11066521 es el CIF público del Algeciras C.F. SAD en
    // Registro Mercantil de Cádiz. Si difiere, sobreescribir en .env
    // como CLUB_CIF=...
    'cif' => env('CLUB_CIF', 'A11066521'),

    // Dirección postal completa
    'direccion' => [
        'calle'        => env('CLUB_CALLE', 'Polígono Industrial La Menacha, Estadio Nuevo Mirador'),
        'codigo_postal'=> env('CLUB_CP', '11205'),
        'localidad'    => env('CLUB_CIUDAD', 'Algeciras'),
        'provincia'    => env('CLUB_PROVINCIA', 'Cádiz'),
        'pais'         => env('CLUB_PAIS', 'España'),
    ],

    // Contacto público
    'email'      => env('CLUB_EMAIL', 'info@algecirasclubdefutbol.com'),
    'email_dpd'  => env('CLUB_EMAIL_DPD', 'digitalizacion@algecirascf.eu'), // Delegado Protección de Datos
    'telefono'   => env('CLUB_TELEFONO', '+34 956 632 877'),

    // URLs y webs
    'web' => env('CLUB_WEB', 'https://algecirasclubdefutbol.com'),

    // Dato técnico Redsys (para mostrarlo en aviso legal según exige Redsys)
    'banco' => env('CLUB_BANCO', 'Banco Sabadell'),

    // Información para política de venta/devolución
    'venta' => [
        // Días máximos para resolver una incidencia del usuario
        'plazo_resolucion_dias' => 14,
        // Email donde el cliente debe escribir para devoluciones/quejas
        'email_devoluciones'    => env('CLUB_EMAIL_DEVOLUCIONES', 'info@algecirasclubdefutbol.com'),
    ],
];
