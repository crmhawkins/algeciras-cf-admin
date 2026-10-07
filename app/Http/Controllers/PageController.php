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

    /**
     * Hub público de la sección "Comprar": selector Abono vs Entrada.
     * Análogo a la pestaña Comprar de la app móvil. Sirve para que cuando
     * un visitante entre con intención de comprar, decida primero qué tipo
     * de producto quiere, en vez de mezclar 8 cards de abonos con N entradas.
     */
    public function comprar()
    {
        return view('pages.comprar');
    }

    /**
     * Listado público de partidos próximos con sus entradas a la venta.
     * Reutiliza el shape de /api/partidos/proximos pero render server-side
     * para SEO y para que funcione sin JS.
     */
    public function entradasPublico()
    {
        $partidos = \App\Models\FootballMatch::query()
            ->where('kickoff_at', '>=', now()->subHours(2))
            ->orderBy('kickoff_at')
            ->limit(20)
            ->get();

        // Fallback genérico: si el partido no tiene Products específicos
        // todavía, ofrecemos las entradas type=entrada sin match_id para
        // que el aficionado pueda comprar de todas formas.
        $genericos = Product::query()
            ->where('type', 'entrada')
            ->whereNull('match_id')
            ->where('active', true)
            ->orderBy('sort_order')
            ->get();

        $partidosConEntrada = $partidos->map(function (\App\Models\FootballMatch $m) use ($genericos) {
            $entradas = Product::query()
                ->where('type', 'entrada')
                ->where('match_id', $m->id)
                ->where('active', true)
                ->orderBy('sort_order')
                ->get();
            if ($entradas->isEmpty()) {
                $entradas = $genericos;
            }
            return ['match' => $m, 'entradas' => $entradas];
        });

        return view('pages.entradas', [
            'partidosConEntrada' => $partidosConEntrada,
        ]);
    }

    public function abonos(\Illuminate\Http\Request $request)
    {
        // Filtro por tipo: renovacion (socios_only=true) o nuevo (socios_only=false).
        // Sin filtro → la vista muestra el selector "¿Eres socio actual o nuevo?"
        $tipo = $request->query('tipo'); // 'renovacion' | 'nuevo' | null
        $q = Product::active()->abono()->with('season','zone');

        if ($tipo === 'renovacion') {
            // RENOVACIÓN: antes de mostrar las tarifas con descuento de
            // socio (que son sustancialmente más baratas), exigimos que el
            // visitante demuestre que ya es abonado validando su número
            // de abono + DNI contra BD. Si el lookup salió OK, los datos
            // del socio quedaron en session['abonado_verificado'].
            $abonado = $request->session()->get('abonado_verificado');
            if (! $abonado) {
                // Aún no se ha validado → vista del formulario lookup.
                return view('pages.abonos', [
                    'abonos'  => collect(),
                    'tipo'    => 'renovacion',
                    'lookup'  => true,
                    'abonado' => null,
                ]);
            }
            $q->where('socios_only', true);
        } elseif ($tipo === 'nuevo') {
            $q->where('socios_only', false);
        } else {
            // Sin tipo seleccionado: mandamos colección vacía para que la
            // vista enseñe el selector.
            return view('pages.abonos', ['abonos' => collect(), 'tipo' => null]);
        }

        return view('pages.abonos', [
            'abonos'  => $q->orderBy('sort_order')->get(),
            'tipo'    => $tipo,
            'lookup'  => false,
            'abonado' => $request->session()->get('abonado_verificado'),
        ]);
    }

    /**
     * Endpoint POST que verifica número de abono + DNI contra BD y, si
     * encuentra al socio, guarda sus datos en session y le manda a
     * /abonos?tipo=renovacion (esta vez verá las cards de renovación).
     */
    public function abonosRenovacionLookup(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'numero_abonado' => 'required|integer|min:1',
            'dni'            => 'required|string|max:24',
        ], [
            'numero_abonado.required' => 'El número de abonado es obligatorio.',
            'dni.required'            => 'El DNI es obligatorio.',
        ]);

        // Reutilizamos el endpoint API que ya existe — devuelve found+abonado.
        $apiResp = app(\App\Http\Controllers\Api\AbonadosController::class)
            ->lookup(new \Illuminate\Http\Request([
                'numero_abonado' => $data['numero_abonado'],
                'dni'            => $data['dni'],
            ]));
        $payload = $apiResp->getData(true);

        if (! ($payload['found'] ?? false)) {
            return back()
                ->withInput()
                ->withErrors(['numero_abonado' => $payload['message'] ?? 'No encontramos un abono con esos datos.']);
        }

        $request->session()->put('abonado_verificado', $payload['abonado']);
        return redirect()->route('abonos', ['tipo' => 'renovacion']);
    }

    /**
     * Limpia la sesión para que el visitante vuelva al paso del lookup
     * (útil cuando el usuario se equivocó o quiere renovar un abono
     * distinto). Se llama desde el link "Cambiar abonado" de la vista.
     */
    public function abonosRenovacionReset(\Illuminate\Http\Request $request)
    {
        $request->session()->forget('abonado_verificado');
        return redirect()->route('abonos', ['tipo' => 'renovacion']);
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
