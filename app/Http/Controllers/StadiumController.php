<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Illuminate\Support\Facades\File;

class StadiumController extends Controller
{
    /**
     * Página de selección de sector en el plano del estadio.
     * Carga el SVG inline + los datos de cada sector desde BD.
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
}
