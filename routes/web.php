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
Route::get('/contacto',     [PageController::class, 'contacto'])->name('contacto');
Route::get('/area-personal', fn () => view('pages.area-personal'))->name('area-personal');
Route::get('/carrito',      fn () => view('pages.carrito'))->name('carrito');
Route::get('/checkout',     fn () => view('pages.checkout'))->name('checkout');
Route::get('/pedido/{order:reference}', fn (\App\Models\Order $order) => view('pages.pedido', [
    'order' => $order->load('items.product', 'tickets.product', 'tickets.zone', 'customer'),
]))->name('pedido');
