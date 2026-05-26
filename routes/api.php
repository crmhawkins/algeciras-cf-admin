<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FanzoneController;
use App\Http\Controllers\Api\MyAccountController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\StadiumApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST pública del Algeciras CF
|--------------------------------------------------------------------------
| Consumida por el frontend Next.js (crmhawkins/algeciras-cf-web).
| Sin auth para lectura pública. Sanctum solo para área socio (próximo).
*/

// Health + metadata del club
Route::get('/health', [PublicController::class, 'health']);

// Plantilla
Route::get('/players', [PublicController::class, 'players']);
Route::get('/players/{player:slug}', [PublicController::class, 'player']);

// Estructura del club
Route::get('/staff', [PublicController::class, 'staff']);

// Patrocinadores
Route::get('/sponsors', [PublicController::class, 'sponsors']);

// Partidos (calendario + resultados)
Route::get('/matches', [PublicController::class, 'matches']);
Route::get('/matches/{match}', [PublicController::class, 'match']);

// Noticias
Route::get('/news', [PublicController::class, 'news']);
Route::get('/news/{news:slug}', [PublicController::class, 'newsShow']);

// Tienda + abonos + entradas (productos polimórficos)
Route::get('/products', [PublicController::class, 'products']);
Route::get('/products/{product:slug}', [PublicController::class, 'product']);
Route::get('/abonos', [PublicController::class, 'abonos']);
Route::get('/entradas', [PublicController::class, 'entradas']);

/*
|--------------------------------------------------------------------------
| Aliases en español (consumidos por la app móvil React Native)
|--------------------------------------------------------------------------
| Reutilizan los mismos controladores que sus equivalentes en inglés.
| No duplican lógica: sólo cambian el nombre del path.
*/

// Jugadores
Route::get('/jugadores', [PublicController::class, 'players']);
Route::get('/jugadores/{id}', [PublicController::class, 'playerById'])
    ->whereNumber('id');

// Partidos
Route::get('/partidos', [PublicController::class, 'matches']);
// Eventos del partido (definido ANTES de /partidos/{id} para que matchee primero)
Route::get('/partidos/eventos/{id}', [PublicController::class, 'matchEvents'])
    ->whereNumber('id');
Route::get('/partidos/{match}', [PublicController::class, 'match']);

// Noticias
Route::get('/noticias', [PublicController::class, 'noticias']);
// Destacadas (debe ir ANTES de /noticias/{slug} para que matchee primero)
Route::get('/noticias/destacadas', [PublicController::class, 'newsFeatured']);
Route::get('/noticias/{news:slug}', [PublicController::class, 'noticiasShow']);

// Productos (tienda)
Route::get('/productos', [PublicController::class, 'products']);
Route::get('/productos/{idOrSlug}', [PublicController::class, 'productById']);

// Estadio / gradas / sectores / asientos
Route::get('/estadio',   [StadiumApiController::class, 'estadio']);
Route::get('/gradas',    [StadiumApiController::class, 'gradas']);
Route::get('/sectores',  [StadiumApiController::class, 'sectores']);
Route::get('/asientos',  [StadiumApiController::class, 'asientos']);

/*
|--------------------------------------------------------------------------
| Autenticación unificada (web + app móvil)
|--------------------------------------------------------------------------
| Endpoints en español, compatibles con la app React Native existente.
| Sanctum tokens — el mismo email+password sirve para web y app.
*/
Route::post('/authenticate',                       [AuthController::class, 'login']);
Route::post('/authenticate/recuperar-password',    [AuthController::class, 'recuperarPassword']);
Route::post('/user/create',                        [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get ('/me',                    [AuthController::class, 'me']);
    Route::put ('/user/profile',          [AuthController::class, 'updateProfile']);
    Route::put ('/user/change-password',  [AuthController::class, 'changePassword']);
    Route::put ('/user/profile-image',    [AuthController::class, 'updateProfileImage']);
    Route::put ('/user/push-token',       [AuthController::class, 'updatePushToken']);
    Route::post('/logout',                [AuthController::class, 'logout']);

    // Área socio: abonos, entradas, pedidos
    Route::get ('/abonos/usuario/{id}',   [MyAccountController::class, 'abonosUsuario'])->whereNumber('id');
    Route::get ('/entradas/usuario/{id}', [MyAccountController::class, 'entradasUsuario'])->whereNumber('id');
    Route::post('/abonos/liberar',        [MyAccountController::class, 'liberarAbono']);
    Route::get ('/me/orders',             [MyAccountController::class, 'misPedidos']);
});

/*
|--------------------------------------------------------------------------
| FanZone — votación MVP (Fan of the Match)
|--------------------------------------------------------------------------
| Consumido por la web Laravel y por la app móvil React Native.
*/
Route::get('/fanzone/historial-mvp', [FanzoneController::class, 'historial']);
Route::get('/fanzone/{matchId}/votos', [FanzoneController::class, 'votos'])
    ->whereNumber('matchId');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/fanzone/{matchId}/mi-voto', [FanzoneController::class, 'miVoto'])
        ->whereNumber('matchId');
    Route::post('/fanzone/{matchId}/votar', [FanzoneController::class, 'votar'])
        ->whereNumber('matchId');
});
