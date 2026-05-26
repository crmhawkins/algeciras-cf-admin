<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seat;
use App\Models\Sector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * StadiumApiController
 *
 * Endpoints en español consumidos por la app móvil (React Native) para
 * pintar el plano del estadio Nuevo Mirador, los sectores y la disponibilidad
 * de asientos.
 *
 * Estructura de respuesta pensada para que la app sólo tenga que mapear claves
 * (camelCase español) sin transformar nada extra.
 */
class StadiumApiController extends Controller
{
    /** Lista canónica de zonas del estadio (slug → label) */
    private const ZONAS = [
        'tribuna_alta',
        'tribuna_baja',
        'preferente',
        'fondo_norte',
        'fondo_sur',
        'palco',
    ];

    /**
     * GET /api/estadio
     * Devuelve el plano completo: sectores + listado de zonas.
     */
    public function estadio(): JsonResponse
    {
        $sectores = Sector::orderBy('zone')->orderBy('number')->get()
            ->map(fn (Sector $s) => $this->transformSector($s))
            ->values();

        return response()->json([
            'sectores' => $sectores,
            'zonas'    => self::ZONAS,
        ]);
    }

    /**
     * GET /api/gradas
     * Devuelve sólo las zonas/gradas (slug + label + color + agregados).
     */
    public function gradas(): JsonResponse
    {
        $agrupados = Sector::orderBy('zone')->get()->groupBy('zone');

        $gradas = collect(self::ZONAS)->map(function (string $zone) use ($agrupados) {
            $sectores = $agrupados->get($zone, collect());
            $primero  = $sectores->first();

            return [
                'slug'      => $zone,
                'nombre'    => $primero?->zone_label ?? $this->zoneLabel($zone),
                'color'     => $primero?->zone_color ?? '#9CA3AF',
                'sectores'  => $sectores->count(),
                'capacidad' => (int) $sectores->sum('capacity'),
            ];
        })->values();

        return response()->json(['gradas' => $gradas]);
    }

    /**
     * GET /api/sectores?zone={slug}
     * Listado plano de sectores. Filtrable por zona.
     */
    public function sectores(Request $request): JsonResponse
    {
        $q = Sector::query()->orderBy('zone')->orderBy('number');
        if ($zone = $request->query('zone')) {
            $q->where('zone', $zone);
        }
        $sectores = $q->get()->map(fn (Sector $s) => $this->transformSector($s))->values();

        return response()->json(['sectores' => $sectores]);
    }

    /**
     * GET /api/asientos?sector_id={id}
     * Lista de butacas de un sector (id, fila, número, estado).
     */
    public function asientos(Request $request): JsonResponse
    {
        $sectorId = (int) $request->query('sector_id');
        if ($sectorId <= 0) {
            return response()->json([
                'asientos' => [],
                'error'    => 'sector_id requerido',
            ], 422);
        }

        $asientos = Seat::where('sector_id', $sectorId)
            ->orderBy('row')->orderBy('number')
            ->get(['id','sector_id','row','number','status'])
            ->map(fn (Seat $s) => [
                'id'       => $s->id,
                'sectorId' => $s->sector_id,
                'fila'     => $s->row,
                'numero'   => $s->number,
                'estado'   => $s->status, // free / reserved / sold / blocked
            ])
            ->values();

        return response()->json(['asientos' => $asientos]);
    }

    /** Mapea un Sector a la estructura camelCase española esperada por la app */
    private function transformSector(Sector $s): array
    {
        return [
            'id'         => $s->id,
            'svgRegion'  => $s->svg_region,
            'name'       => $s->name,
            'zone'       => $s->zone,
            'zoneLabel'  => $s->zone_label,
            'parity'     => $s->parity,
            'number'     => $s->number,
            'priceAdult' => $s->price_adult !== null ? (float) $s->price_adult : null,
            'priceYouth' => $s->price_youth !== null ? (float) $s->price_youth : null,
            'capacity'   => (int) $s->capacity,
            'available'  => (bool) $s->available,
            'colorHex'   => $s->color_hex ?: $s->zone_color,
            'meta'       => $s->meta,
        ];
    }

    private function zoneLabel(string $zone): string
    {
        return match ($zone) {
            'tribuna_baja' => 'Tribuna Baja',
            'tribuna_alta' => 'Tribuna Alta',
            'preferente'   => 'Preferente',
            'fondo_norte'  => 'Fondo Norte',
            'fondo_sur'    => 'Fondo Sur',
            'palco'        => 'Palco de Honor',
            default        => 'Otros',
        };
    }
}
