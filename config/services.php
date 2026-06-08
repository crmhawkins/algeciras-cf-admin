<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    /*
    | Pasarela de pago activa: 'redsys' (Banco Sabadell) o 'stripe'.
    | Por defecto Redsys, que es la que el club tiene contratada en producción.
    */
    'payment' => [
        'gateway' => env('PAYMENT_GATEWAY', 'redsys'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | Stripe — Algeciras CF (cuenta agencia, temporal hasta Redsys).
    | El plan es: STRIPE_KEY/STRIPE_SECRET ahora; cuando el club obtenga
    | sus propias credenciales Redsys, este bloque se desactiva y entra
    | un Driver Redsys equivalente.
    */
    'stripe' => [
        'key'     => env('STRIPE_KEY'),
        'secret'  => env('STRIPE_SECRET'),
        'webhook' => [
            'secret'    => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    /*
    | Apple Wallet — generación de .pkpass para abonos.
    | Requiere certificado Pass Type ID + WWDR. Ver docs/WALLET_SETUP.md.
    */
    'apple_wallet' => [
        'pass_type_identifier' => env('APPLE_WALLET_PASS_TYPE_IDENTIFIER', 'pass.es.algecirascf.abonos'),
        'team_identifier'      => env('APPLE_WALLET_TEAM_IDENTIFIER'),
        'organization_name'    => env('APPLE_WALLET_ORG_NAME', 'Algeciras CF'),
        'certificate_path'     => env('APPLE_WALLET_CERT_PATH', storage_path('app/wallet/passcert.p12')),
        'certificate_password' => env('APPLE_WALLET_CERT_PASSWORD'),
        'wwdr_path'            => env('APPLE_WALLET_WWDR_PATH', storage_path('app/wallet/AppleWWDRCA.pem')),
    ],

    /*
    | Google Wallet — JWT firmado con cuenta de servicio.
    | Requiere project + Wallet Object Issuer en Google Cloud.
    */
    'google_wallet' => [
        'service_account_json_path' => env(
            'GOOGLE_WALLET_SA_JSON_PATH',
            storage_path('app/wallet/google-service-account.json')
        ),
        'issuer_id'    => env('GOOGLE_WALLET_ISSUER_ID'),
        'class_suffix' => env('GOOGLE_WALLET_CLASS_SUFFIX', 'abonos_2526'),
        'origin'       => env('GOOGLE_WALLET_ORIGIN', env('APP_URL', 'https://algecirascf.hawkins.es')),
    ],

];
