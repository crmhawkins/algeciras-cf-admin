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

        $seats = $sector->load([])
            ->seats ?? collect();

        // Si las butacas no están cargadas en relación, las recuperamos directamente
        $seats = \App\Models\Seat::where('sector_id', $sector->id)
            ->orderBy('row')
            ->orderBy('number')
            ->get();

        $byRow = $seats->groupBy('row');

        return view('pages.sector', [
            'sector' => $sector,
            'byRow'  => $byRow,
            'totalSeats' => $seats->count(),
            'freeSeats'  => $seats->where('status', 'free')->count(),
        ]);
    }
}
