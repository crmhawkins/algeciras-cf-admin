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

    /** GET /api/matches?upcoming=1|finished=1 */
    public function matches(Request $request)
    {
        $q = FootballMatch::query()->with('season');
        if ($request->boolean('upcoming')) $q->upcoming();
        elseif ($request->boolean('finished')) $q->finished();
        else $q->orderByDesc('kickoff_at');
        return FootballMatchResource::collection($q->limit(20)->get());
    }

    /** GET /api/match/{match} */
    public function match(FootballMatch $match)
    {
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

    /** GET /api/news?featured=1 */
    public function news(Request $request)
    {
        $q = News::published()->orderByDesc('published_at')->with('author');
        if ($request->boolean('featured')) $q->featured();
        return NewsResource::collection($q->paginate(12));
    }

    /**
     * GET /api/noticias
     * Alias en español con filtros adicionales que usa la app: q, limit, categoria.
     */
    public function noticias(Request $request)
    {
        $q = News::published()->orderByDesc('published_at')->with('author');

        if ($request->boolean('featured')) $q->featured();

        if ($cat = $request->query('categoria')) {
            $q->where('category', $cat);
        }

        if ($search = $request->query('q')) {
            // title/excerpt son traducibles (JSON) — buscamos por LIKE en el JSON.
            $like = '%' . $search . '%';
            $q->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                  ->orWhere('excerpt', 'like', $like);
            });
        }

        $limit = (int) $request->query('limit', 12);
        if ($limit < 1)  $limit = 12;
        if ($limit > 50) $limit = 50;

        return NewsResource::collection($q->paginate($limit));
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

        return NewsResource::collection($featured);
    }

    public function newsShow(News $news)
    {
        $news->increment('views');
        return new NewsResource($news->load('author'));
    }

    /**
     * GET /api/noticias/{slug}
     * Como newsShow pero añade un array `relacionadas` con 3 noticias de
     * la misma categoría (excluyendo la actual). La app las pinta debajo
     * del detalle.
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

        return (new NewsResource($news->load('author')))
            ->additional([
                'relacionadas' => NewsResource::collection($relacionadas),
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
     * Si el parámetro es numérico busca por id; en otro caso intenta por slug
     * para no romper si la app envía slug en algún punto.
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
}
