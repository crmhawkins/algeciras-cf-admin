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
 */
class StadiumApiController extends Controller
{
    /** Lista canónica de zonas del estadio (slug → id estable) */
    private const ZONAS = [
        'tribuna_alta'  => 1,
        'tribuna_baja'  => 2,
        'preferente'    => 3,
        'fondo_norte'   => 4,
        'fondo_sur'     => 5,
        'palco'         => 6,
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
            'zonas'    => array_keys(self::ZONAS),
        ]);
    }

    /**
     * GET /api/gradas
     * Devuelve las gradas con id numérico estable (para que la app navegue
     * a /api/sectores filtrando por gradaId).
     */
    public function gradas(): JsonResponse
    {
        $agrupados = Sector::orderBy('zone')->get()->groupBy('zone');

        $gradas = collect(self::ZONAS)->map(function (int $id, string $zone) use ($agrupados) {
            $sectores = $agrupados->get($zone, collect());
            $primero  = $sectores->first();
            $minPrecio = $sectores->filter(fn ($s) => $s->price_adult !== null)->min('price_adult');

            return [
                'id'          => $id,
                'slug'        => $zone,
                'nombre'      => $primero?->zone_label ?? $this->zoneLabel($zone),
                'descripcion' => $this->descripcionGrada($zone, $minPrecio),
                'color'       => $primero?->zone_color ?? '#9CA3AF',
                'sectores'    => $sectores->count(),
                'capacidad'   => (int) $sectores->sum('capacity'),
                'precioDesde' => $minPrecio !== null ? (float) $minPrecio : null,
            ];
        })->values();

        return response()->json(['gradas' => $gradas]);
    }

    /**
     * GET /api/sectores?zone={slug}&gradaId={id}
     * Listado plano de sectores con shape ES esperada por la app:
     *   { id, nombre, capacidad, precio, gradaId, activo, asientosDisponibles }
     */
    public function sectores(Request $request): JsonResponse
    {
        $q = Sector::query()->orderBy('zone')->orderBy('number');

        if ($zone = $request->query('zone')) {
            $q->where('zone', $zone);
        }
        if ($gradaId = $request->query('gradaId')) {
            $slug = array_search((int) $gradaId, self::ZONAS, true);
            if ($slug !== false) $q->where('zone', $slug);
        }

        $sectores = $q->get()->map(fn (Sector $s) => $this->transformSector($s))->values();

        return response()->json(['sectores' => $sectores]);
    }

    /**
     * GET /api/asientos?sector_id={id}
     * Lista de butacas de un sector.
     * Mapea status DB → estado app: 'free' → 'disponible', el resto → 'ocupado'.
     */
    public function asientos(Request $request): JsonResponse
    {
        $sectorId = (int) ($request->query('sector_id') ?? $request->query('sectorId') ?? 0);
        if ($sectorId <= 0) {
            return response()->json([
                'asientos' => [],
                'error'    => 'sector_id requerido',
            ], 422);
        }

        $asientos = Seat::where('sector_id', $sectorId)
            ->orderBy('row')->orderBy('number')
            ->get(['id','sector_id','row','number','status'])
            ->map(function (Seat $s) {
                $estado = $s->status === 'free' ? 'disponible'
                        : ($s->status === 'reserved' ? 'liberado' : 'ocupado');
                return [
                    'id'       => $s->id,
                    'sectorId' => $s->sector_id,
                    'fila'     => $s->row,
                    'numero'   => $s->number,
                    'estado'   => $estado,
                ];
            })
            ->values();

        return response()->json(['asientos' => $asientos]);
    }

    /**
     * Mapea un Sector a la shape ES esperada por la app móvil.
     * Mantiene también las claves inglesas (svgRegion, name, priceAdult, etc.)
     * para no romper el plano del estadio en la web.
     */
    private function transformSector(Sector $s): array
    {
        $gradaId = self::ZONAS[$s->zone] ?? 0;
        $precio  = $s->price_adult !== null ? (float) $s->price_adult : 0.0;

        // Conteo de asientos libres (cache simple: una query por sector).
        $disponibles = Seat::where('sector_id', $s->id)
            ->where('status', 'free')
            ->count();

        return [
            // Shape ES esperada por la app
            'id'                  => $s->id,
            'nombre'              => $s->name,
            'capacidad'           => (int) $s->capacity,
            'precio'              => $precio,
            'gradaId'             => $gradaId,
            'gradaSlug'           => $s->zone,
            'activo'              => (bool) $s->available,
            'asientosDisponibles' => $disponibles,
            'imagen'              => null,

            // Shape EN (compatibilidad web)
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

    private function descripcionGrada(string $zone, $minPrecio): string
    {
        $precio = $minPrecio !== null ? " · Desde " . (int) $minPrecio . "€" : '';
        return match ($zone) {
            'tribuna_alta' => "Vista panorámica desde la zona principal del estadio$precio",
            'tribuna_baja' => "Junto al césped, ambiente cercano al partido$precio",
            'preferente'   => "La zona más exclusiva, asientos numerados$precio",
            'fondo_norte'  => "Fondo de la afición rojiblanca más caliente$precio",
            'fondo_sur'    => "Fondo sur del Nuevo Mirador$precio",
            'palco'        => "Palco de honor con servicio premium$precio",
            default        => 'Zona del estadio',
        };
    }
}
