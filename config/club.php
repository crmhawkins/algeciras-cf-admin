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

    // Enlaces de descarga de la app oficial (abono digital + ventajas).
    // Placeholders hasta publicar la app; sobreescribir en .env cuando
    // estén las fichas reales en las tiendas.
    'app_store_url'  => env('CLUB_APP_STORE_URL',  'https://apps.apple.com/app/algeciras-cf'),
    'play_store_url' => env('CLUB_PLAY_STORE_URL', 'https://play.google.com/store/apps/details?id=com.algecirascf.app'),

    // Dato técnico Redsys (para mostrarlo en aviso legal según exige Redsys)
    'banco' => env('CLUB_BANCO', 'Banco Sabadell'),

    // COMERCIO / pasarela de pago — titular del TPV Virtual Redsys (Banco Sabadell).
    // OPCIÓN B: el CLUB es el VENDEDOR de cara al cliente; thwork3000, S.L. es el
    // COMERCIO que procesa el cobro con tarjeta. Estos datos DEBEN coincidir con el
    // contrato de comercio firmado con el banco (incidencia nº1 de Sabadell).
    // Datos recuperados del CRM thwork (CIF vía identificador SEPA ES52000B72284631).
    'comercio' => [
        'razon_social'     => env('COMERCIO_RAZON_SOCIAL', 'thwork3000, S.L.'),
        'nombre_comercial' => env('COMERCIO_NOMBRE_COMERCIAL', 'thwork3000'),
        'cif'              => env('COMERCIO_CIF', 'B72284631'),
        'direccion'        => env('COMERCIO_DIRECCION', 'C/ General Primo de Rivera, s/n, 11201 Algeciras (Cádiz)'),
        'email'            => env('COMERCIO_EMAIL', 'administracion@hawkins.es'),
        'telefono'         => env('COMERCIO_TEL', '+34 956 662 942'),
    ],

    // Información para política de venta/devolución
    'venta' => [
        // Días máximos para resolver una incidencia del usuario
        'plazo_resolucion_dias' => 14,
        // Email donde el cliente debe escribir para devoluciones/quejas
        'email_devoluciones'    => env('CLUB_EMAIL_DEVOLUCIONES', 'info@algecirasclubdefutbol.com'),
    ],

    // Mapa SECTOR -> PUERTA física del estadio para el carnet PVC ("PTA: ...").
    // VERIFICADO contra el proveedor anterior que la puerta NO es derivable
    // (Tribuna Alta Par 4 -> "4, 51"; Preferente Par 6 -> "28"). Rellenar con el
    // plano de accesos del Nuevo Mirador (clave = sectors.name EXACTO).
    // Mientras esté vacío, el carnet OMITE la línea PTA en vez de inventarla.
    // Tiene prioridad la puerta por abonado (notes['puerta']) si el export del
    // proveedor llega a traerla; este mapa es el fallback por grada.
    // PUERTA física por GRANDSTAND. Verificado leyendo abonos reales del proveedor
    // que par e impar comparten puerta dentro de cada grada, así que se casa por
    // PREFIJO del nombre del sector (ver CarnetAbonoController). Confirmaciones:
    //   TRIBUNA  -> Alta Par 4 + Impar 6/7/8/9/10/B, Baja Par 2 + Impar 8   => 4, 51
    //   PREFERENTE -> Par 6 + Impar 9                                       => 28
    //   FONDO NORTE -> Par 1 + Impar 5                                      => 40
    //   FONDO SUR  -> Par 4 + Impar 6 + Impar 7                             => 16
    //   PALCO     -> ¡par e impar DISTINTOS! Par (362,262)=> "4, 0"; Impar (271)=> "0"
    // (Palco es la ÚNICA grada donde par != impar; las otras 4 comparten puerta.)
    // Orden: prefijos más específicos primero (FONDO NORTE/SUR y PALCO PAR/IMPAR).
    'puertas' => [
        'FONDO NORTE'          => '40',
        'FONDO SUR'            => '16',
        'TRIBUNA'              => '4, 51',
        'PREFERENTE'           => '28',
        'PALCO DE HONOR PAR'   => '4, 0',
        'PALCO DE HONOR IMPAR' => '0',
    ],
];
