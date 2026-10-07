<?php

/**
 * Configuración del TPV Virtual Redsys (Caja Rural del Sur — comercio
 * "ALGECIRAS CF", FUC 370436875, terminal 100; credenciales en .env).
 *
 * En entorno PRUEBAS la URL es https://sis-t.redsys.es:25443/sis/realizarPago
 * En entorno PRODUCCIÓN la URL es https://sis.redsys.es/sis/realizarPago
 *
 * Las claves SHA256 de pruebas y producción son DISTINTAS — Redsys las
 * envía aparte cuando aprueba el alta en real.
 */

return [
    'env' => env('REDSYS_ENV', 'test'),  // 'test' | 'prod'

    // Comercio y terminal reales del club (no son secretos: salen en la propia
    // pantalla de pago y son los mismos en pruebas y en real). Por defecto aquí
    // porque el .env del contenedor no forma parte de la imagen y un redeploy
    // lo perdería; antes el defecto era un comercio ajeno (337699599 / 2).
    'merchant_code' => env('REDSYS_MERCHANT_CODE', '370436875'),
    'terminal'      => env('REDSYS_TERMINAL', '100'),
    'currency'      => env('REDSYS_CURRENCY', '978'),   // 978 = EUR
    'sha256_key'    => env('REDSYS_SHA256_KEY', 'sq7HjrUOBfKmC576ILgskD5srU870gJ7'),

    // URLs Redsys
    'url' => [
        'test' => 'https://sis-t.redsys.es:25443/sis/realizarPago',
        'prod' => 'https://sis.redsys.es/sis/realizarPago',
    ],

    // URLs de retorno (configurables en .env si el dominio cambia)
    'urls' => [
        // El usuario es devuelto aquí tras pago OK
        'ok'  => env('REDSYS_URL_OK',  '/pago/ok'),
        // El usuario es devuelto aquí tras pago KO / cancelación
        'ko'  => env('REDSYS_URL_KO',  '/pago/ko'),
        // Redsys hace POST server-to-server aquí (la verdad sobre el pago)
        'notify' => env('REDSYS_URL_NOTIFY', '/pago/redsys/notify'),
    ],

    'language' => env('REDSYS_LANGUAGE', '001'),  // 001=ES, 002=EN, etc.

    // Si true, el flujo nunca se envía a Redsys real — solo loggea y simula
    // un pago OK. Útil en local. NO USAR EN PRODUCCIÓN.
    'mock' => env('REDSYS_MOCK', false),
];
