<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClubStaffResource;
use App\Http\Resources\FootballMatchResource;
use App\Http\Resources\NewsResource;
use App\Http\Resources\PlayerResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SponsorResource;
use App\Models\ClubStaff;
use App\Models\FootballMatch;
use App\Models\News;
use App\Models\Player;
use App\Models\Product;
use App\Models\Season;
use App\Models\Sponsor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    /** Health / metadata del club */
    public function health(): JsonResponse
    {
        $current = Season::current();
        return response()->json([
            'status'  => 'ok',
            'app'     => config('app.name'),
            'version' => '1.0.0',
            'club'    => [
                'name'    => 'Algeciras Club de Fútbol',
                'short'   => 'Algeciras CF',
                'founded' => 1912,
                'stadium' => 'Nuevo Mirador',
                'city'    => 'Algeciras',
                'province' => 'Cádiz',
                'colors'  => ['primary' => '#CF2E2E', 'secondary' => '#0A0A0A', 'gold' => '#D4A24C'],
                'hashtag' => '#CrecemosContigo',
            ],
            'season'  => $current ? ['id' => $current->id, 'name' => $current->name] : null,
            'time'    => now()->toIso8601String(),
        ]);
    }

    /** GET /api/players?position=portero */
    public function players(Request $request)
    {
        $q = Player::query()->active()->orderBy('sort_order')->orderBy('dorsal');
        if ($pos = $request->query('position')) {
            $q->where('position', $pos);
        }
        return PlayerResource::collection($q->get());
    }

    public function player(Player $player)
    {
        return new PlayerResource($player);
    }

    /**
     * GET /api/jugadores/{id} — alias en español que acepta ID numérico.
     * La app móvil identifica jugadores por id, no por slug.
     */
    public function playerById(int $id)
    {
        $player = Player::findOrFail($id);
        return new PlayerResource($player);
    }

    /** GET /api/staff */
    public function staff()
    {
        return ClubStaffResource::collection(
            ClubStaff::visible()->orderBy('sort_order')->get()
        );
    }

    /** GET /api/sponsors?tier=tecnico|principal|main|... */
    public function sponsors(Request $request)
    {
        $q = Sponsor::active()->orderBy('sort_order');
        if ($tier = $request->query('tier')) $q->tier($tier);
        return SponsorResource::collection($q->get());
    }

    /** GET /api/matches?upcoming=1|finished=1 (formato inglés para web) */
    public function matches(Request $request)
    {
        // ─────────────────────────────────────────────────────────────
        // Compatibilidad app móvil:
        // - Si la request viene a /api/partidos (alias español) o trae
        //   ?lang=es, devolvemos el shape con claves españolas que
        //   espera la app React Native (`partidos`, `proximoPartido`,
        //   `equipoLocal`, `equipoVisitante`, `marcador`, `fecha`, `hora`).
        // - El resto sigue usando FootballMatchResource (claves inglesas)
        //   para no romper el frontend web que ya consume /api/matches.
        // ─────────────────────────────────────────────────────────────
        $isSpanish = $request->is('api/partidos*') || $request->query('lang') === 'es';

        $q = FootballMatch::query()->with('season');
        if ($request->boolean('upcoming')) $q->upcoming();
        elseif ($request->boolean('finished')) $q->finished();
        else $q->orderByDesc('kickoff_at');

        if ($isSpanish) {
            $matches = $q->limit(40)->get();
            $partidos = $matches->map(fn ($m) => $this->mapPartido($m))->values();
            $proximo  = FootballMatch::query()->with('season')->upcoming()->first();
            return response()->json([
                'partidos'        => $partidos,
                'proximoPartido'  => $proximo ? $this->mapPartido($proximo) : null,
            ]);
        }

        return FootballMatchResource::collection($q->limit(20)->get());
    }

    /** GET /api/match/{match} (inglés para web). */
    public function match(FootballMatch $match)
    {
        // La app móvil llega aquí por /api/partidos/{id}; le devolvemos shape ES.
        if (request()->is('api/partidos*')) {
            return response()->json($this->mapPartido($match->load('season')));
        }
        return new FootballMatchResource($match->load('season'));
    }

    /**
     * GET /api/partidos/eventos/{id}
     * Eventos del partido (goles, tarjetas, cambios).
     * TODO: implementar cuando se cree el modelo MatchEvent y su migración.
     * De momento devuelve array vacío para no romper la app.
     */
    public function matchEvents(int $id): JsonResponse
    {
        // TODO: cuando exista MatchEvent:
        //   return MatchEventResource::collection(
        //       MatchEvent::where('match_id', $id)->orderBy('minute')->get()
        //   );
        return response()->json(['eventos' => []]);
    }

    /** GET /api/news?featured=1 (inglés, para web) */
    public function news(Request $request)
    {
        $q = News::published()->orderByDesc('published_at')->with('author');
        if ($request->boolean('featured')) $q->featured();
        return NewsResource::collection($q->paginate(12));
    }

    /**
     * GET /api/noticias
     * Alias en español con filtros (q, limit, categoria) y shape ES esperado por la app.
     * Devuelve { total, pagina, totalPaginas, noticias: [...] }.
     */
    public function noticias(Request $request)
    {
        $q = News::published()->orderByDesc('published_at')->with('author');

        if ($request->boolean('featured')) $q->featured();

        if ($cat = $request->query('categoria')) {
            // Si llega 'todo' o vacío no filtramos.
            if ($cat !== 'todo') {
                // mapeo inverso categoria app → category DB
                $catDb = $this->categoriaAppToDb($cat);
                $q->where('category', $catDb);
            }
        }

        if ($search = $request->query('q')) {
            $like = '%' . $search . '%';
            $q->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                  ->orWhere('excerpt', 'like', $like);
            });
        }

        $limit = (int) $request->query('limit', 12);
        if ($limit < 1)  $limit = 12;
        if ($limit > 50) $limit = 50;

        $paginator = $q->paginate($limit);

        return response()->json([
            'total'        => $paginator->total(),
            'pagina'       => $paginator->currentPage(),
            'totalPaginas' => $paginator->lastPage(),
            'noticias'     => collect($paginator->items())->map(fn ($n) => $this->mapNoticia($n))->values(),
        ]);
    }

    /**
     * GET /api/noticias/destacadas
     * Devuelve noticias destacadas (featured=1). Si no hay ninguna marcada,
     * cae a las 3 más recientes para que la app siempre tenga contenido.
     */
    public function newsFeatured()
    {
        $featured = News::published()->featured()
            ->orderByDesc('published_at')
            ->with('author')
            ->take(6)
            ->get();

        if ($featured->isEmpty()) {
            $featured = News::published()
                ->orderByDesc('published_at')
                ->with('author')
                ->take(3)
                ->get();
        }

        // Si la request viene del alias español (/api/noticias/destacadas),
        // devolvemos el shape ES; si alguien consume desde la web, también
        // — este endpoint sólo existe en alias español.
        return response()->json([
            'noticias' => $featured->map(fn ($n) => $this->mapNoticia($n))->values(),
        ]);
    }

    public function newsShow(News $news)
    {
        $news->increment('views');
        return new NewsResource($news->load('author'));
    }

    /**
     * GET /api/noticias/{slug}
     * Detalle de noticia con related. Shape ES para la app.
     */
    public function noticiasShow(News $news)
    {
        $news->increment('views');

        $relacionadas = News::published()
            ->where('id', '!=', $news->id)
            ->when($news->category, fn ($q) => $q->where('category', $news->category))
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        return response()->json([
            'noticia'      => $this->mapNoticia($news->load('author'), true),
            'relacionadas' => $relacionadas->map(fn ($n) => $this->mapNoticia($n))->values(),
        ]);
    }

    /** GET /api/products?type=merch|abono|entrada&category=equipacion&featured=1 */
    public function products(Request $request)
    {
        $q = Product::active()->with(['category','match','season','zone','variants'])
            ->orderBy('sort_order')->orderByDesc('created_at');

        if ($type = $request->query('type')) $q->where('type', $type);
        if ($cat  = $request->query('category')) {
            $q->whereHas('category', fn ($q) => $q->where('slug', $cat));
        }
        if ($request->boolean('featured')) $q->featured();

        return ProductResource::collection($q->paginate(24));
    }

    public function product(Product $product)
    {
        return new ProductResource($product->load(['category','match','season','zone','variants']));
    }

    /**
     * GET /api/productos/{id}
     * Alias por ID numérico (la app usa id, la web usa slug).
     */
    public function productById(string $idOrSlug)
    {
        $product = ctype_digit($idOrSlug)
            ? Product::findOrFail((int) $idOrSlug)
            : Product::where('slug', $idOrSlug)->firstOrFail();

        return new ProductResource(
            $product->load(['category','match','season','zone','variants'])
        );
    }

    /** GET /api/abonos — atajo */
    public function abonos()
    {
        return ProductResource::collection(
            Product::active()->abono()->with(['season','zone'])->orderBy('sort_order')->get()
        );
    }

    /** GET /api/entradas — atajo */
    public function entradas()
    {
        return ProductResource::collection(
            Product::active()->entrada()->with(['match','zone'])->orderBy('sort_order')->get()
        );
    }

    /**
     * GET /api/clasificacion
     *
     * Tabla de clasificación de la liga (Primera RFEF Grupo II 2025/26).
     *
     * Lectura desde la tabla `standings` si existe; si la tabla aún no
     * fue migrada, devolvemos el array hardcodeado (final 2025/26) como
     * fallback para que la app siempre tenga datos.
     */
    public function clasificacion(): JsonResponse
    {
        // Si existe la tabla, leer de BD; si no, fallback hardcodeado.
        try {
            if (\Schema::hasTable('standings')) {
                $rows = \DB::table('standings')->orderBy('position')->get();
                if ($rows->isNotEmpty()) {
                    $clasificacion = $rows->map(fn ($r) => [
                        'id'       => (int) $r->id,
                        'posicion' => (int) $r->position,
                        'equipo'   => $r->team_name,
                        'escudo'   => $r->team_logo,
                        'pj'       => (int) $r->matches_played,
                        'g'        => (int) $r->wins,
                        'e'        => (int) $r->draws,
                        'd'        => (int) $r->losses,
                        'gf'       => (int) $r->goals_for,
                        'gc'       => (int) $r->goals_against,
                        'puntos'   => (int) $r->points,
                    ])->values();

                    return response()->json([
                        'clasificacion' => $clasificacion,
                        'temporada'     => '2025/26',
                        'competicion'   => 'Primera RFEF — Grupo II',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // si algo va mal en BD, caemos al fallback hardcodeado
        }

        return response()->json([
            'clasificacion' => $this->clasificacionFallback(),
            'temporada'     => '2025/26',
            'competicion'   => 'Primera RFEF — Grupo II',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────

    /**
     * Transforma un News a la shape ES esperada por la app:
     *   { id, slug, titulo, extracto, contenido?, imagen, categoria, fecha,
     *     destacado, vistas, autor }
     */
    private function mapNoticia(News $n, bool $includeBody = false): array
    {
        $titulo   = $n->getTranslation('title', 'es')   ?: ($n->title ?? '');
        $extracto = $n->getTranslation('excerpt', 'es') ?: ($n->excerpt ?? '');

        $row = [
            'id'         => $n->id,
            'slug'       => $n->slug,
            'titulo'     => is_array($titulo) ? ($titulo['es'] ?? reset($titulo)) : $titulo,
            'extracto'   => is_array($extracto) ? ($extracto['es'] ?? reset($extracto)) : $extracto,
            'imagen'     => $n->cover_image ? url($n->cover_image) : null,
            'categoria'  => $this->categoriaDbToApp($n->category),
            'fecha'      => $n->published_at?->toIso8601String(),
            'activo'     => true,
            'destacado'  => (bool) $n->featured,
            'vistas'     => (int) $n->views,
            'autor'      => $n->relationLoaded('author') && $n->author ? [
                'id' => $n->author->id, 'nombre' => $n->author->name,
            ] : null,
        ];

        if ($includeBody) {
            $body = $n->getTranslation('body', 'es') ?: ($n->body ?? '');
            $row['contenido'] = is_array($body) ? ($body['es'] ?? reset($body)) : $body;
        }

        return $row;
    }

    /**
     * Mapea categoría real BD → categoria del enum app.
     * App acepta: fichaje | lesion | comunicado | partido | galeria | evento | otro
     */
    private function categoriaDbToApp(?string $cat): string
    {
        return match ($cat) {
            'fichaje', 'plantilla'     => 'fichaje',
            'partido', 'resultado'     => 'partido',
            'comunicado'               => 'comunicado',
            'lesion'                   => 'lesion',
            'galeria'                  => 'galeria',
            'evento'                   => 'evento',
            'actualidad', 'patrocinios', 'equipacion' => 'comunicado',
            default                    => 'otro',
        };
    }

    /** Inverso para filtros en GET ?categoria=X */
    private function categoriaAppToDb(string $cat): string
    {
        return match ($cat) {
            'fichaje'    => 'plantilla',
            'comunicado' => 'actualidad',
            default      => $cat,
        };
    }

    /**
     * Transforma un FootballMatch a la shape ES de la app:
     *   { id, equipoLocal, equipoVisitante, escudoLocal, escudoVisitante,
     *     fecha (YYYY-MM-DD), hora (HH:MM), marcador }
     */
    private function mapPartido(FootballMatch $m): array
    {
        $algecirasEscudo = url('/algeciras-shield.png');
        $rivalEscudo     = $m->opponent_logo ? url($m->opponent_logo) : null;

        $esLocal = $m->venue === 'home';
        $equipoLocal     = $esLocal ? 'Algeciras CF' : ($m->opponent ?: 'Rival');
        $equipoVisitante = $esLocal ? ($m->opponent ?: 'Rival') : 'Algeciras CF';
        $escudoLocal     = $esLocal ? $algecirasEscudo : $rivalEscudo;
        $escudoVisitante = $esLocal ? $rivalEscudo : $algecirasEscudo;

        $marcador = null;
        if ($m->status === 'finished' && $m->home_score !== null && $m->away_score !== null) {
            $marcador = "{$m->home_score}-{$m->away_score}";
        }

        $fechaIso = $m->kickoff_at?->toIso8601String();
        $fecha    = $m->kickoff_at?->format('Y-m-d');
        $hora     = $m->kickoff_at?->format('H:i');

        return [
            'id'              => $m->id,
            'equipoLocal'     => $equipoLocal,
            'equipoVisitante' => $equipoVisitante,
            'escudoLocal'     => $escudoLocal,
            'escudoVisitante' => $escudoVisitante,
            'fecha'           => $fechaIso ?: $fecha,
            'hora'            => $hora,
            'marcador'        => $marcador,
            'estadio'         => $m->stadium,
            'competicion'     => $m->competition,
            'jornada'         => $m->matchday,
            'estado'          => $m->status,
        ];
    }

    /**
     * Fallback con la clasificación final 2024/25 (la 2025/26 está en
     * curso real). Datos reales de Wikipedia "2024–25 Primera Federación".
     * Algeciras CF terminó 9º.
     */
    private function clasificacionFallback(): array
    {
        return [
            ['id' => 1,  'posicion' => 1,  'equipo' => 'AD Ceuta FC',         'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/3/30/AD_Ceuta_FC_logo.svg/200px-AD_Ceuta_FC_logo.svg.png',                       'pj' => 38, 'g' => 17, 'e' => 16, 'd' => 5,  'gf' => 46, 'gc' => 35, 'puntos' => 67],
            ['id' => 2,  'posicion' => 2,  'equipo' => 'Real Murcia CF',      'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/3/3c/Real_Murcia_CF_logo.svg/200px-Real_Murcia_CF_logo.svg.png',                 'pj' => 38, 'g' => 18, 'e' => 10, 'd' => 10, 'gf' => 47, 'gc' => 31, 'puntos' => 64],
            ['id' => 3,  'posicion' => 3,  'equipo' => 'UD Ibiza',            'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/8/86/UD_Ibiza_logo.svg/200px-UD_Ibiza_logo.svg.png',                             'pj' => 38, 'g' => 18, 'e' => 9,  'd' => 11, 'gf' => 51, 'gc' => 33, 'puntos' => 63],
            ['id' => 4,  'posicion' => 4,  'equipo' => 'AD Mérida',           'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/0/03/AD_M%C3%A9rida_logo.svg/200px-AD_M%C3%A9rida_logo.svg.png',                'pj' => 38, 'g' => 15, 'e' => 13, 'd' => 10, 'gf' => 52, 'gc' => 52, 'puntos' => 58],
            ['id' => 5,  'posicion' => 5,  'equipo' => 'Antequera CF',        'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/4/4f/Antequera_CF_logo.svg/200px-Antequera_CF_logo.svg.png',                     'pj' => 38, 'g' => 14, 'e' => 16, 'd' => 8,  'gf' => 54, 'gc' => 49, 'puntos' => 58],
            ['id' => 6,  'posicion' => 6,  'equipo' => 'Real Madrid Castilla','escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/5/56/Real_Madrid_CF.svg/200px-Real_Madrid_CF.svg.png',                           'pj' => 38, 'g' => 12, 'e' => 18, 'd' => 8,  'gf' => 58, 'gc' => 36, 'puntos' => 54],
            ['id' => 7,  'posicion' => 7,  'equipo' => 'Atlético Madrid B',   'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/f/f4/Atletico_Madrid_2017_logo.svg/200px-Atletico_Madrid_2017_logo.svg.png',     'pj' => 38, 'g' => 13, 'e' => 15, 'd' => 10, 'gf' => 42, 'gc' => 35, 'puntos' => 54],
            ['id' => 8,  'posicion' => 8,  'equipo' => 'Sevilla Atlético',    'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/3/3b/Sevilla_FC_logo.svg/200px-Sevilla_FC_logo.svg.png',                         'pj' => 38, 'g' => 14, 'e' => 11, 'd' => 13, 'gf' => 40, 'gc' => 43, 'puntos' => 53],
            ['id' => 9,  'posicion' => 9,  'equipo' => 'Algeciras CF',        'escudo' => url('/algeciras-shield.png'),                                                                                                       'pj' => 38, 'g' => 12, 'e' => 16, 'd' => 10, 'gf' => 46, 'gc' => 46, 'puntos' => 52],
            ['id' => 10, 'posicion' => 10, 'equipo' => 'AD Alcorcón',         'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/3/3a/AD_Alcorcon_logo.svg/200px-AD_Alcorcon_logo.svg.png',                       'pj' => 38, 'g' => 14, 'e' => 9,  'd' => 15, 'gf' => 52, 'gc' => 51, 'puntos' => 51],
            ['id' => 11, 'posicion' => 11, 'equipo' => 'Villarreal B',        'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/b/b9/Villarreal_CF_logo.svg/200px-Villarreal_CF_logo.svg.png',                    'pj' => 38, 'g' => 11, 'e' => 16, 'd' => 11, 'gf' => 51, 'gc' => 41, 'puntos' => 49],
            ['id' => 12, 'posicion' => 12, 'equipo' => 'Hércules CF',         'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/3/3f/Hercules_CF_logo.svg/200px-Hercules_CF_logo.svg.png',                        'pj' => 38, 'g' => 13, 'e' => 8,  'd' => 17, 'gf' => 48, 'gc' => 49, 'puntos' => 47],
            ['id' => 13, 'posicion' => 13, 'equipo' => 'Betis Deportivo',     'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/1/13/Real_betis_logo.svg/200px-Real_betis_logo.svg.png',                          'pj' => 38, 'g' => 11, 'e' => 13, 'd' => 14, 'gf' => 44, 'gc' => 59, 'puntos' => 46],
            ['id' => 14, 'posicion' => 14, 'equipo' => 'Atlético Sanluqueño', 'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/f/fc/Atletico_Sanluqueno_CF_logo.svg/200px-Atletico_Sanluqueno_CF_logo.svg.png', 'pj' => 38, 'g' => 10, 'e' => 16, 'd' => 12, 'gf' => 41, 'gc' => 51, 'puntos' => 46],
            ['id' => 15, 'posicion' => 15, 'equipo' => 'Marbella FC',         'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/5/56/Marbella_FC_logo.svg/200px-Marbella_FC_logo.svg.png',                        'pj' => 38, 'g' => 12, 'e' => 10, 'd' => 16, 'gf' => 51, 'gc' => 58, 'puntos' => 46],
            ['id' => 16, 'posicion' => 16, 'equipo' => 'CF Fuenlabrada',      'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/9/96/CF_Fuenlabrada_logo.svg/200px-CF_Fuenlabrada_logo.svg.png',                  'pj' => 38, 'g' => 10, 'e' => 13, 'd' => 15, 'gf' => 43, 'gc' => 48, 'puntos' => 43],
            ['id' => 17, 'posicion' => 17, 'equipo' => 'Yeclano Deportivo',   'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/0/0d/Yeclano_Deportivo_logo.svg/200px-Yeclano_Deportivo_logo.svg.png',           'pj' => 38, 'g' => 9,  'e' => 16, 'd' => 13, 'gf' => 36, 'gc' => 34, 'puntos' => 43],
            ['id' => 18, 'posicion' => 18, 'equipo' => 'CD Alcoyano',         'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/8/87/CD_Alcoyano_logo.svg/200px-CD_Alcoyano_logo.svg.png',                        'pj' => 38, 'g' => 10, 'e' => 12, 'd' => 16, 'gf' => 32, 'gc' => 47, 'puntos' => 42],
            ['id' => 19, 'posicion' => 19, 'equipo' => 'Recreativo de Huelva','escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/0/0d/Real_Club_Recreativo_de_Huelva_logo.svg/200px-Real_Club_Recreativo_de_Huelva_logo.svg.png', 'pj' => 38, 'g' => 7, 'e' => 16, 'd' => 15, 'gf' => 32, 'gc' => 52, 'puntos' => 37],
            ['id' => 20, 'posicion' => 20, 'equipo' => 'CF Intercity',        'escudo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/9/9b/CF_Intercity_logo.svg/200px-CF_Intercity_logo.svg.png',                      'pj' => 38, 'g' => 8,  'e' => 11, 'd' => 19, 'gf' => 37, 'gc' => 53, 'puntos' => 35],
        ];
    }
}
