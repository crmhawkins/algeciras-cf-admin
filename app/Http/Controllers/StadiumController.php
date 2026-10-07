<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Illuminate\Support\Facades\File;

class StadiumController extends Controller
{
    /**
     * Vista general: plano del estadio con sectores clickables.
     *
     * Si llega `?product=slug` (y opcionalmente `?match=id` para entradas),
     * la vista sabrá que estamos en flujo de compra y pintará un banner
     * "Estás eligiendo butaca para [Abono X]". Cada link a un sector
     * mantendrá los params en query para llegar a la página de butacas.
     */
    public function index(\Illuminate\Http\Request $request)
    {
        $svgPath = public_path('img/club/plano-estadio-original.svg');
        $svg     = File::exists($svgPath) ? File::get($svgPath) : null;

        $sectors = Sector::orderBy('zone')->orderBy('parity')->orderBy('number')->get();

        $purchase = $this->resolvePurchaseContext($request);

        return view('pages.estadio', [
            'svg'      => $svg,
            'sectors'  => $sectors,
            'byZone'   => $sectors->groupBy('zone'),
            'purchase' => $purchase,
        ]);
    }

    /**
     * Vista detalle de un sector: grilla de butacas numeradas tipo cine.
     * Acepta también `?product=&match=` para el flujo de compra.
     */
    public function sector(int $svgRegion, \Illuminate\Http\Request $request)
    {
        $sector = Sector::where('svg_region', $svgRegion)
            ->where('available', true)
            ->firstOrFail();

        $purchase = $this->resolvePurchaseContext($request);

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
            'purchase'   => $purchase,
        ]);
    }

    /**
     * Resuelve el contexto de compra a partir de los query params.
     * Devuelve un array con { product, match, type } o null si no hay
     * compra activa (visita normal del plano).
     */
    protected function resolvePurchaseContext(\Illuminate\Http\Request $request): ?array
    {
        $slug = $request->query('product');
        if (! $slug) return null;

        $product = \App\Models\Product::where('slug', $slug)
            ->whereIn('type', ['abono', 'entrada'])
            ->where('active', true)
            ->first();
        if (! $product) return null;

        $match = null;
        if ($matchId = (int) $request->query('match')) {
            $match = \App\Models\FootballMatch::find($matchId);
        }

        return [
            'product' => $product,
            'match'   => $match,
            'type'    => $product->type,
        ];
    }
}
