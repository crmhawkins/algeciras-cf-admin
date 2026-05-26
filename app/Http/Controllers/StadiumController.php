<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Illuminate\Support\Facades\File;

class StadiumController extends Controller
{
    /**
     * Vista general: plano del estadio con sectores clickables.
     */
    public function index()
    {
        $svgPath = public_path('img/club/plano-estadio-original.svg');
        $svg     = File::exists($svgPath) ? File::get($svgPath) : null;

        $sectors = Sector::orderBy('zone')->orderBy('parity')->orderBy('number')->get();

        return view('pages.estadio', [
            'svg'     => $svg,
            'sectors' => $sectors,
            'byZone'  => $sectors->groupBy('zone'),
        ]);
    }

    /**
     * Vista detalle de un sector: grilla de butacas numeradas tipo cine.
     */
    public function sector(int $svgRegion)
    {
        $sector = Sector::where('svg_region', $svgRegion)
            ->where('available', true)
            ->firstOrFail();

        $seats = \App\Models\Seat::where('sector_id', $sector->id)
            ->orderBy('row')
            ->orderBy('number')
            ->get();

        // Leer direction del layout JSON real (1 = L→R, 2 = R→L espejado)
        $direction = 1;
        $jsonPath = database_path('data/sectors_layout.json');
        if (\Illuminate\Support\Facades\File::exists($jsonPath)) {
            $layout = collect(json_decode(\Illuminate\Support\Facades\File::get($jsonPath), true))
                ->firstWhere('id', $sector->svg_region);
            if ($layout) $direction = (int) ($layout['direction'] ?? 1);
        }

        // Agrupar por fila, ordenar butacas dentro de cada fila según direction
        $byRow = $seats->groupBy('row')->map(function ($rowSeats) use ($direction) {
            return $direction === 2
                ? $rowSeats->sortByDesc('number')->values()
                : $rowSeats->sortBy('number')->values();
        });

        return view('pages.sector', [
            'sector'     => $sector,
            'byRow'      => $byRow,
            'totalSeats' => $seats->count(),
            'freeSeats'  => $seats->where('status', 'free')->count(),
            'direction'  => $direction,
        ]);
    }
}
