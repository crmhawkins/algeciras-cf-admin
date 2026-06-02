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

// Stripe webhook — sin CSRF (Stripe NO firma cookies).
// La exclusión CSRF se hace vía bootstrap/app.php (validateCsrfTokens->except).
Route::post('/webhooks/stripe', [\App\Http\Controllers\StripeWebhookController::class, 'handle'])
    ->name('webhooks.stripe');

// Pago iniciado desde la app móvil — abre en WebBrowser/SafariViewController.
Route::get('/pago-app/{order:reference}', function (\App\Models\Order $order) {
    // Crear PaymentIntent si Stripe operativo y la orden todavía está pending.
    $clientSecret = null;
    if ((string) config('services.stripe.secret') !== '' && $order->status === 'pending') {
        try {
            $intent = app(\App\Services\StripePaymentService::class)->createIntentForOrder($order);
            $clientSecret = $intent->client_secret;
        } catch (\Throwable $e) {
            // si falla la creación, la vista cae al mensaje "pasarela en preparación"
            \Log::warning('pago-app intent fail', ['err' => $e->getMessage()]);
        }
    }
    return view('pages.pago-app', [
        'order'        => $order->load('items'),
        'clientSecret' => $clientSecret,
    ]);
})->name('pago-app');

// Si Stripe no está configurado, el cliente confirma una "reserva sin cobro"
// para que el club los contacte. Marca la Order como paid simulada.
Route::post('/pago-app/{order:reference}/simulado', function (\App\Models\Order $order) {
    if ($order->status === 'pending') {
        app(\App\Services\CheckoutService::class)->markOrderPaid($order, 'sim_' . uniqid());
        $order->update(['payment_gateway' => 'simulated']);
    }
    return redirect()->route('pago-app.exito', $order->reference);
})->name('pago-app.simulado');

Route::get('/pago-app/{order:reference}/exito', function (\App\Models\Order $order) {
    return view('pages.pago-app-exito', ['order' => $order]);
})->name('pago-app.exito');
