<?php

use App\Http\Controllers\Api\PublicController;
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

// Área socio autenticada (próximo bloque)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', fn (Request $r) => $r->user()->load('customer'));
    // /me/orders, /me/tickets, /me/abonos vendrán aquí
});
