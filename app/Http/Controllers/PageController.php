<?php

namespace App\Http\Controllers;

use App\Models\ClubStaff;
use App\Models\ExclusiveContent;
use App\Models\FootballMatch;
use App\Models\News;
use App\Models\Player;
use App\Models\Product;
use App\Models\Season;
use App\Models\Sponsor;

class PageController extends Controller
{
    public function home()
    {
        return view('pages.home', [
            'season'      => Season::current(),
            'nextMatch'   => FootballMatch::upcoming()->first(),
            'featured'    => Product::active()->featured()->take(8)->get(),
            'sponsorsMain' => Sponsor::active()->whereIn('tier', ['tecnico','principal','main'])->orderBy('sort_order')->get(),
            'news'        => News::published()->orderByDesc('published_at')->take(3)->get(),
        ]);
    }

    public function equipo()
    {
        $byPos = [
            'portero'        => Player::active()->byPosition('portero')->orderBy('dorsal')->get(),
            'defensa'        => Player::active()->byPosition('defensa')->orderBy('dorsal')->get(),
            'centrocampista' => Player::active()->byPosition('centrocampista')->orderBy('dorsal')->get(),
            'delantero'      => Player::active()->byPosition('delantero')->orderBy('dorsal')->get(),
            'tecnico'        => Player::active()->byPosition('tecnico')->orderBy('sort_order')->get(),
        ];
        return view('pages.equipo', compact('byPos'));
    }

    public function calendario()
    {
        return view('pages.calendario', [
            'upcoming' => FootballMatch::upcoming()->with('season')->get(),
            'finished' => FootballMatch::finished()->with('season')->take(10)->get(),
        ]);
    }

    public function tienda(\Illuminate\Http\Request $request)
    {
        $type = $request->query('type'); // merch|abono|entrada|null
        $q = Product::active()->with('category','zone','season','match')->orderBy('sort_order');
        if ($type) $q->where('type', $type);
        return view('pages.tienda', [
            'products' => $q->get(),
            'type'     => $type,
        ]);
    }

    public function producto(Product $product)
    {
        return view('pages.producto', [
            'product' => $product->load('category','zone','season','match','variants'),
        ]);
    }

    public function abonos()
    {
        return view('pages.abonos', [
            'abonos' => Product::active()->abono()->with('season','zone')->orderBy('sort_order')->get(),
        ]);
    }

    public function actualidad()
    {
        return view('pages.actualidad', [
            'news' => News::published()->orderByDesc('published_at')->paginate(12),
        ]);
    }

    public function noticia(News $news)
    {
        $news->increment('views');
        return view('pages.noticia', compact('news'));
    }

    public function club()
    {
        return view('pages.club', [
            'staff' => ClubStaff::visible()->orderBy('sort_order')->get(),
        ]);
    }

    public function contacto()
    {
        return view('pages.contacto');
    }

    /**
     * FanZone — pantalla web equivalente a la app móvil.
     * Voto MVP del partido activo (último jugado o próximo) + historial.
     */
    public function fanzone()
    {
        // Partido activo: priorizamos el último FINALIZADO en los últimos 7 días.
        // Si no hay, cogemos el próximo programado.
        $matchActivo = FootballMatch::query()
            ->where('status', 'finished')
            ->where('kickoff_at', '>=', now()->subDays(7))
            ->orderByDesc('kickoff_at')
            ->first()
            ?? FootballMatch::upcoming()->first();

        // Plantilla disponible para votar (excluye cuerpo técnico)
        $jugadores = Player::active()
            ->whereIn('position', ['portero','defensa','centrocampista','delantero'])
            ->orderBy('dorsal')
            ->get();

        // Partidos finalizados con historial (los pillará la API en JS)
        $partidosFinalizados = FootballMatch::finished()->take(6)->get();

        return view('pages.fanzone', [
            'matchActivo'         => $matchActivo,
            'jugadores'           => $jugadores,
            'partidosFinalizados' => $partidosFinalizados,
        ]);
    }

    public function zonaSocio()
    {
        // PLACEHOLDER: el control de acceso real llegará con Fase 1 (middleware auth:socio).
        // Por ahora hardcodeamos $isSocio = true para que se vea el grid de contenidos.
        $isSocio = true;

        $contents = $isSocio
            ? ExclusiveContent::published()->orderByDesc('publish_at')->get()
            : collect();

        return view('pages.zona-socio', compact('isSocio', 'contents'));
    }

    public function zonaSocioContent(ExclusiveContent $content)
    {
        // PLACEHOLDER: cuando Fase 1 esté lista, aquí va el middleware auth:socio.
        $isSocio = true;

        abort_unless($content->is_published, 404);

        return view('pages.zona-socio-content', compact('isSocio', 'content'));
    }
}
