<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/',             [PageController::class, 'home'])->name('home');
Route::get('/equipo',       [PageController::class, 'equipo'])->name('equipo');
Route::get('/calendario',   [PageController::class, 'calendario'])->name('calendario');
Route::get('/tienda',       [PageController::class, 'tienda'])->name('tienda');
Route::get('/tienda/{product:slug}', [PageController::class, 'producto'])->name('producto');
Route::get('/abonos',       [PageController::class, 'abonos'])->name('abonos');
Route::get('/estadio',      [\App\Http\Controllers\StadiumController::class, 'index'])->name('estadio');
Route::get('/estadio/sector/{svgRegion}', [\App\Http\Controllers\StadiumController::class, 'sector'])->name('estadio.sector')->whereNumber('svgRegion');
Route::get('/actualidad',   [PageController::class, 'actualidad'])->name('actualidad');
Route::get('/actualidad/{news:slug}', [PageController::class, 'noticia'])->name('noticia');
Route::get('/club',         [PageController::class, 'club'])->name('club');
Route::get('/fanzone',      [PageController::class, 'fanzone'])->name('fanzone');
Route::get('/contacto',     [PageController::class, 'contacto'])->name('contacto');
// Área personal — públicas (login/registro)
Route::get ('/area-personal',          [\App\Http\Controllers\AreaPersonalController::class, 'index'])->name('area-personal');
Route::post('/area-personal/login',    [\App\Http\Controllers\AreaPersonalController::class, 'login'])->name('area-personal.login');
Route::post('/area-personal/register', [\App\Http\Controllers\AreaPersonalController::class, 'register'])->name('area-personal.register');
Route::post('/area-personal/logout',   [\App\Http\Controllers\AreaPersonalController::class, 'logout'])->name('area-personal.logout');

// Área personal — protegidas (sub-páginas tipo "Mi Cuenta" de club grande)
Route::middleware('auth')
    ->prefix('area-personal')
    ->name('area-personal.')
    ->group(function () {
        $c = \App\Http\Controllers\AreaPersonalController::class;

        Route::get ('/resumen',          [$c, 'resumen'])->name('resumen');
        Route::get ('/carnet',           [$c, 'carnet'])->name('carnet');
        Route::get ('/abonos',           [$c, 'abonos'])->name('abonos');
        Route::get ('/entradas',         [$c, 'entradas'])->name('entradas');
        Route::get ('/compras',          [$c, 'compras'])->name('compras');
        Route::get ('/compras/{reference}', [$c, 'compraDetalle'])->name('compras.detalle');
        Route::get ('/beneficios',       [$c, 'beneficios'])->name('beneficios');
        Route::get ('/actividad',        [$c, 'actividad'])->name('actividad');

        Route::get ('/datos',            [$c, 'datos'])->name('datos');
        Route::post('/datos',            [$c, 'actualizarDatos'])->name('datos.update');
        Route::post('/cambiar-password', [$c, 'cambiarPassword'])->name('password.update');

        Route::get ('/notificaciones',   [$c, 'notificaciones'])->name('notificaciones');
        Route::post('/notificaciones',   [$c, 'actualizarNotificaciones'])->name('notificaciones.update');
    });
Route::get('/carrito',      fn () => view('pages.carrito'))->name('carrito');
Route::get('/checkout',     fn () => view('pages.checkout'))->name('checkout');
Route::get('/pedido/{order:reference}', fn (\App\Models\Order $order) => view('pages.pedido', [
    'order' => $order->load('items.product', 'tickets.product', 'tickets.zone', 'customer'),
]))->name('pedido');

Route::get('/zona-socio', [PageController::class, 'zonaSocio'])->name('zona-socio');
Route::get('/zona-socio/{content:slug}', [PageController::class, 'zonaSocioContent'])->name('zona-socio.content');
