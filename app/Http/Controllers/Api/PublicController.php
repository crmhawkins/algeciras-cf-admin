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

    /** GET /api/news?featured=1 */
    public function news(Request $request)
    {
        $q = News::published()->orderByDesc('published_at')->with('author');
        if ($request->boolean('featured')) $q->featured();
        return NewsResource::collection($q->paginate(12));
    }

    public function newsShow(News $news)
    {
        $news->increment('views');
        return new NewsResource($news->load('author'));
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
