<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/',             [PageController::class, 'home'])->name('home');
Route::get('/equipo',       [PageController::class, 'equipo'])->name('equipo');
Route::get('/calendario',   [PageController::class, 'calendario'])->name('calendario');
// La tienda vive en un subdominio externo (tienda.algecirasclubdefutbol.com).
// Mantenemos los nombres de ruta para no romper enlaces internos generados
// con route('tienda') / route('producto'), pero redirigimos 301 al subdominio.
Route::get('/tienda', fn () => redirect('https://tienda.algecirasclubdefutbol.com', 301))->name('tienda');
// No tenemos mapeo de slugs en el subdominio externo: redirigimos siempre al home.
Route::get('/tienda/{product:slug}', fn () => redirect('https://tienda.algecirasclubdefutbol.com', 301))->name('producto');
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

// Política de privacidad — requerida por App Store y Google Play.
Route::get('/privacidad', fn () => view('pages.privacidad'))->name('privacidad');

// Landing pública de QR — el QR codifica /v/{token}. NO marca como used.
// (la API /api/validar-qr SÍ marca used; la usa la PWA de puerta).
Route::get('/v/{token}', [\App\Http\Controllers\Api\ValidatorController::class, 'showPublic'])
    ->where('token', '[A-Za-z0-9\-_]+')
    ->name('qr.public');

Route::get('/zona-socio', [PageController::class, 'zonaSocio'])->name('zona-socio');
Route::get('/zona-socio/{content:slug}', [PageController::class, 'zonaSocioContent'])->name('zona-socio.content');

// Stripe webhook — sin CSRF (Stripe NO firma cookies).
// La exclusión CSRF se hace vía bootstrap/app.php (validateCsrfTokens->except).
Route::post('/webhooks/stripe', [\App\Http\Controllers\StripeWebhookController::class, 'handle'])
    ->name('webhooks.stripe');

/*
|--------------------------------------------------------------------------
| Compra directa abono/entrada (sin carrito)
|--------------------------------------------------------------------------
| El aficionado eligió un abono concreto en /abonos o /producto/{slug} —
| no tiene sentido pasarlo por el carro de la tienda como si fuera merch.
| Se crea una Order pending con UN único OrderItem y se le redirige a la
| pasarela (pago-app) directamente. Si después quiere cancelar lo hace
| desde su área personal, no desde un carrito que se ha de gestionar.
|
| Acepta opcionalmente ?qty=N (máx 6 para evitar abuso).
*/
Route::get('/comprar-directo/{product:slug}', function (\App\Models\Product $product, \Illuminate\Http\Request $request) {
    if (! in_array($product->type, ['abono','entrada'], true)) {
        return redirect()->route('producto', $product->slug);
    }

    $qty = max(1, min(6, (int) $request->query('qty', 1)));
    $unitPrice = (float) $product->price;
    $subtotalGross = round($unitPrice * $qty, 2);
    $vatRate = (int) ($product->vat_rate ?? 21);
    $subtotal = round($subtotalGross / (1 + $vatRate/100), 2);
    $vat      = round($subtotalGross - $subtotal, 2);
    $gestion  = \App\Models\Order::calcGestionFee($subtotalGross);
    $total    = round($subtotalGross + $gestion, 2);

    $order = \Illuminate\Support\Facades\DB::transaction(function () use ($product, $qty, $unitPrice, $subtotal, $vat, $gestion, $total, $subtotalGross, $vatRate) {
        $order = \App\Models\Order::create([
            'reference'        => \App\Models\Order::nextReference(),
            'guest_email'      => optional(auth()->user())->email ?? 'web@algecirascf.es',
            'status'           => 'pending',
            'channel'          => 'web',
            'subtotal'         => $subtotal,
            'vat'              => $vat,
            'shipping_cost'    => 0,
            'gestion_fee'      => $gestion,
            'total'            => $total,
            'currency'         => 'EUR',
            'payment_gateway'  => 'stripe',
            'admin_notes'      => sprintf('Compra directa web: product=%s qty=%d', $product->sku, $qty),
        ]);
        \App\Models\OrderItem::create([
            'order_id'      => $order->id,
            'product_id'    => $product->id,
            'product_type'  => $product->type,
            'name'          => $product->getTranslation('name','es'),
            'sku'           => $product->sku,
            'qty'           => $qty,
            'unit_price'    => $unitPrice,
            'vat_rate'      => $vatRate,
            'subtotal'      => $subtotal,
            'vat_amount'    => $vat,
            'total'         => $subtotalGross,
        ]);
        return $order;
    });

    return redirect()->route('pago-app', ['order' => $order->reference]);
})->name('comprar-directo');

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
