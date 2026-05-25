<?php

namespace App\Http\Controllers;

use App\Models\ClubStaff;
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
}
