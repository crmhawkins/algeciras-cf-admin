<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Producción
        'https://algecirasclubdefutbol.com',
        'https://www.algecirasclubdefutbol.com',
        'https://nuevo.algecirasclubdefutbol.com',

        // Dev local (Next.js)
        'http://localhost:3000',
        'http://127.0.0.1:3000',
    ],

    'allowed_origins_patterns' => [
        // Subdominios de algecirasclubdefutbol.com (previews Vercel, etc.)
        '/^https:\/\/[a-z0-9-]+\.algecirasclubdefutbol\.com$/',
        // Previews de Vercel del repo algeciras-cf-web
        '/^https:\/\/algeciras-cf-web-.*-crmhawkins\.vercel\.app$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,
];
