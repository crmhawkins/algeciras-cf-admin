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
     * para que ésta decida si forzar actualización (min_version) o
     * sugerirla (latest_version).
     *
     * TODO: leer estos valores de la tabla `settings` cuando exista.
     */
    public function current(): JsonResponse
    {
        $payload = [
            'min_version'      => '1.0.7',
            'latest_version'   => '1.0.8',
            'force_update'     => false,
            'store_url_ios'    => 'https://apps.apple.com/es/app/id6773652477',
            'store_url_android'=> 'https://play.google.com/store/apps/details?id=es.algecirascf.abonos',
            'release_notes'    => 'Renovación de abonos, QR de entradas, plano del estadio mejorado.',
        ];

        return response()->json($payload)
            ->header('Cache-Control', 'public, max-age=60');
    }
}
