<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AppVersionController extends Controller
{
    /**
     * GET /api/app/version
     *
     * Devuelve la versión mínima y la última versión de la app móvil
     * para que el cliente decida si forzar actualización (min_version)
     * o sugerirla (latest_version).
     *
     * Configurable vía .env (sin redeploy):
     *   APP_MIN_VERSION       — versión mínima soportada. Si la instalada
     *                           es menor, modal bloqueante.
     *   APP_LATEST_VERSION    — última versión publicada. Si la instalada
     *                           es menor pero >= min, modal recomendado.
     *   APP_FORCE_UPDATE      — true|false; cuando true se fuerza siempre.
     *   APP_STORE_URL_IOS     — destino del botón "Actualizar" en iOS.
     *                           Mientras estemos en TestFlight, debe ser
     *                           el enlace público (testflight.apple.com/join/...).
     *                           Tras publicar en App Store: apps.apple.com/...
     *   APP_STORE_URL_ANDROID — Play Store (o APK directo durante closed beta).
     *   APP_RELEASE_NOTES     — texto opcional que se muestra bajo el mensaje.
     *
     * Defaults — mantenidos al día con la versión publicada en producción.
     */
    public function current(): JsonResponse
    {
        $payload = [
            'min_version'       => (string) env('APP_MIN_VERSION',    '1.2.12'),
            'latest_version'    => (string) env('APP_LATEST_VERSION', '1.2.12'),
            'force_update'      => (bool)   env('APP_FORCE_UPDATE',   false),
            'store_url_ios'     => (string) env('APP_STORE_URL_IOS',
                'https://testflight.apple.com/join/cHtYtbCG'),
            'store_url_android' => (string) env('APP_STORE_URL_ANDROID',
                'https://play.google.com/store/apps/details?id=es.algecirascf.abonos'),
            'release_notes'     => (string) env('APP_RELEASE_NOTES',
                'Actualiza para acceder a los últimos arreglos: estabilidad en Mi Cuenta y FanZone, nuevas secciones El Club / Contacto / Privacidad y mejoras de rendimiento.'),
        ];

        return response()->json($payload)
            ->header('Cache-Control', 'public, max-age=60');
    }
}
