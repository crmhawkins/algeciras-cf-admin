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
// Reset password form (target del email que envía Laravel cuando llamas a
// Password::sendResetLink). ANTES esta ruta NO existía con el nombre
// `password.reset` → la notificación Laravel hacía route('password.reset')
// y daba 500 en /api/authenticate/recuperar-password.
Route::get('/password/reset/{token}', function (string $token, \Illuminate\Http\Request $request) {
    return view('pages.password-reset', [
        'token' => $token,
        'email' => $request->query('email', ''),
    ]);
})->name('password.reset');

Route::post('/password/reset', function (\Illuminate\Http\Request $request) {
    $data = $request->validate([
        'token'                 => 'required',
        'email'                 => 'required|email',
        'password'              => 'required|min:6|confirmed',
    ]);
    $status = \Illuminate\Support\Facades\Password::reset(
        $data,
        function ($user, $password) {
            $user->forceFill(['password' => \Illuminate\Support\Facades\Hash::make($password)])->save();
        }
    );
    return $status === \Illuminate\Support\Facades\Password::PASSWORD_RESET
        ? redirect()->route('area-personal')->with('status', 'Contraseña actualizada. Inicia sesión.')
        : back()->withErrors(['email' => __($status)]);
})->name('password.update');

// Hub público "Comprar": selector Abono vs Entrada (espejo de la app móvil).
Route::get('/comprar',      [PageController::class, 'comprar'])->name('comprar');
Route::get('/abonos',       [PageController::class, 'abonos'])->name('abonos');
// Lookup nº abonado + DNI para acceder a tarifas de renovación.
Route::post('/abonos/renovacion/lookup', [PageController::class, 'abonosRenovacionLookup'])
    ->name('abonos.renovacion.lookup');
Route::get('/abonos/renovacion/cambiar', [PageController::class, 'abonosRenovacionReset'])
    ->name('abonos.renovacion.reset');
Route::get('/entradas',     [PageController::class, 'entradasPublico'])->name('entradas');
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
        // Foto de perfil del socio (se ve en el carnet digital + cabecera)
        Route::post('/foto',             [$c, 'subirFoto'])->name('foto.upload');
        Route::post('/foto/eliminar',    [$c, 'eliminarFoto'])->name('foto.delete');

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

// Páginas legales requeridas por Redsys / Ley 34/2002 (LSSI-CE).
Route::get('/aviso-legal',        fn () => view('pages.aviso-legal'))->name('aviso-legal');
Route::get('/condiciones-venta',  fn () => view('pages.condiciones-venta'))->name('condiciones-venta');
Route::get('/politica-entrega',   fn () => view('pages.politica-entrega'))->name('politica-entrega');

// === REDSYS TPV Virtual (Banco Sabadell) ===
// Pago: el form auto-postea al servidor Redsys con HMAC_SHA256_V1.
Route::get('/pago/redsys/{order:reference}',
    [\App\Http\Controllers\RedsysController::class, 'startPayment'])
    ->name('redsys.start');

// Notificación server-to-server desde Redsys (exenta de CSRF abajo)
Route::post('/pago/redsys/notify',
    [\App\Http\Controllers\RedsysController::class, 'notify'])
    ->name('redsys.notify')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Redirects post-pago
Route::get('/pago/ok', [\App\Http\Controllers\RedsysController::class, 'returnOk'])->name('redsys.ok');
Route::get('/pago/ko', [\App\Http\Controllers\RedsysController::class, 'returnKo'])->name('redsys.ko');

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

    // Guest checkout: NO bloqueamos la compra si el visitante no está
    // logueado. En /pago-app/{ref} le pediremos los datos legales
    // obligatorios (nombre, DNI, dirección...) y al confirmar el pago
    // le crearemos la cuenta automáticamente. UX mucho más fluida que
    // forzar el login antes de mostrar el precio.

    $qty = max(1, min(6, (int) $request->query('qty', 1)));
    $unitPrice = (float) $product->price;
    $subtotalGross = round($unitPrice * $qty, 2);
    $vatRate = (int) ($product->vat_rate ?? 21);
    $subtotal = round($subtotalGross / (1 + $vatRate/100), 2);
    $vat      = round($subtotalGross - $subtotal, 2);
    $gestion  = \App\Models\Order::calcGestionFee($subtotalGross);
    $total    = round($subtotalGross + $gestion, 2);

    // Capturamos el asiento elegido en el plano (`?seat=ID&row=X&number=Y&sector=Z`).
    // Lo persistimos en el item para que el carnet, el QR y la confirmación
    // sepan en qué butaca se ha sentado el socio.
    $seatId   = (int) $request->query('seat', 0);
    $seatMeta = [
        'sector_nombre' => $request->query('sector'),
        'fila'          => $request->query('row'),
        'asiento'       => $request->query('number'),
        'match_id'      => (int) $request->query('match', 0) ?: null,
    ];

    $order = \Illuminate\Support\Facades\DB::transaction(function () use ($product, $qty, $unitPrice, $subtotal, $vat, $gestion, $total, $subtotalGross, $vatRate, $seatId, $seatMeta) {
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
            'admin_notes'      => sprintf(
                'Compra directa web: product=%s qty=%d seat=%s sector=%s fila=%s asiento=%s',
                $product->sku, $qty,
                $seatId ?: '-',
                $seatMeta['sector_nombre'] ?? '-',
                $seatMeta['fila']    ?? '-',
                $seatMeta['asiento'] ?? '-'
            ),
        ]);
        \App\Models\OrderItem::create([
            'order_id'      => $order->id,
            'product_id'    => $product->id,
            'product_type'  => $product->type,
            'name'          => $product->getTranslation('name','es')
                . ($seatMeta['sector_nombre'] ? ' · '.$seatMeta['sector_nombre'] : '')
                . ($seatMeta['fila']    !== null ? ' · Fila '.$seatMeta['fila'] : '')
                . ($seatMeta['asiento'] !== null ? ' · Asiento '.$seatMeta['asiento'] : ''),
            'sku'           => $product->sku,
            'qty'           => $qty,
            'unit_price'    => $unitPrice,
            'vat_rate'      => $vatRate,
            'subtotal'      => $subtotal,
            'vat_amount'    => $vat,
            'total'         => $subtotalGross,
            'meta'          => array_filter($seatMeta + ['seat_id' => $seatId ?: null]),
        ]);
        // Marcar la butaca como reservada para impedir doble venta.
        if ($seatId) {
            \App\Models\Seat::where('id', $seatId)->update(['status' => 'reserved']);
        }
        return $order;
    });

    return redirect()->route('pago-app', ['order' => $order->reference]);
})->name('comprar-directo');

// Pago iniciado desde la app móvil — abre en WebBrowser/SafariViewController.
/*
|--------------------------------------------------------------------------
| Guest checkout: registrar al comprador antes de pagar
|--------------------------------------------------------------------------
| El visitante llega a /pago-app/{ref} sin estar logueado. La página le
| muestra el formulario de datos legales obligatorios. Al enviarlo:
|   - Si el email existe en BD: hacemos Auth::attempt con la password que
|     pidamos, O simplemente vinculamos la orden a ese Customer si el
|     email coincide (sin permitir login sin password). Para simplificar
|     pedimos password (mismo flow del registro) y creamos cuenta.
|   - Si el email NO existe: creamos User + Customer con todos los datos
|     y vinculamos la orden.
| En ambos casos hacemos Auth::login() y redirigimos a /pago-app/{ref}
| que ya verá la pasarela Stripe.
*/
Route::post('/pago-app/{order:reference}/guest-checkout', function (\App\Models\Order $order, \Illuminate\Http\Request $request) {
    if ($order->status !== 'pending') {
        return redirect()->route('pago-app', $order->reference);
    }

    $data = $request->validate([
        'first_name'  => 'required|string|max:80',
        'last_name'   => 'required|string|max:80',
        'email'       => 'required|email',
        'phone'       => 'required|string|min:9|max:32',
        'dni'         => 'required|string|max:24',
        'birth_date'  => ['required', 'date', 'before:'.now()->subYears(14)->format('Y-m-d')],
        'address'     => 'required|string|max:255',
        'city'        => 'required|string|max:120',
        'postal_code' => 'required|string|regex:/^[0-9]{5}$/',
        'province'    => 'nullable|string|max:120',
        'password'    => 'required|string|min:6|confirmed',
    ], [
        'first_name.required' => 'El nombre es obligatorio.',
        'last_name.required'  => 'Los apellidos son obligatorios.',
        'email.required'      => 'El email es obligatorio.',
        'phone.required'      => 'El teléfono es obligatorio.',
        'dni.required'        => 'El DNI es obligatorio.',
        'birth_date.required' => 'La fecha de nacimiento es obligatoria.',
        'birth_date.before'   => 'Debes ser mayor de 14 años.',
        'address.required'    => 'La dirección es obligatoria.',
        'city.required'       => 'La ciudad es obligatoria.',
        'postal_code.required'=> 'El código postal es obligatorio.',
        'postal_code.regex'   => 'El código postal debe tener 5 dígitos.',
        'password.min'        => 'Mínimo 6 caracteres.',
        'password.confirmed'  => 'Las contraseñas no coinciden.',
    ]);

    // ¿Email/DNI ya en BD?
    $existingUser = \App\Models\User::where('email', $data['email'])->first();
    if ($existingUser) {
        return back()
            ->withInput()
            ->withErrors(['email' => 'Ya tienes una cuenta con este email. Inicia sesión arriba para continuar la compra.']);
    }
    if (\App\Models\Customer::where('dni', strtoupper($data['dni']))->exists()) {
        return back()
            ->withInput()
            ->withErrors(['dni' => 'Ya existe una cuenta con este DNI.']);
    }

    $user = \App\Models\User::create([
        'name'     => trim($data['first_name'].' '.$data['last_name']),
        'email'    => $data['email'],
        'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
    ]);

    $customer = \App\Models\Customer::create([
        'user_id'     => $user->id,
        'email'       => $data['email'],
        'first_name'  => $data['first_name'],
        'last_name'   => $data['last_name'],
        'phone'       => $data['phone'],
        'dni'         => strtoupper($data['dni']),
        'birth_date'  => $data['birth_date'],
        'address'     => $data['address'],
        'city'        => $data['city'],
        'postal_code' => $data['postal_code'],
        'province'    => $data['province'] ?? 'Cádiz',
        'country'     => 'ES',
    ]);

    $order->update([
        'customer_id' => $customer->id,
        'guest_email' => $data['email'],
    ]);

    \Illuminate\Support\Facades\Auth::login($user);
    $request->session()->regenerate();

    return redirect()->route('pago-app', $order->reference);
})->name('pago-app.guest-checkout');

Route::get('/pago-app/{order:reference}', function (\App\Models\Order $order) {
    // === Switch de pasarela ===
    // Por defecto enviamos a Redsys (config 'services.payment.gateway').
    // Mantenemos el código Stripe intacto debajo por si hace falta volver:
    //   PAYMENT_GATEWAY=stripe en .env reactiva el flujo Stripe.
    $gateway = config('services.payment.gateway', 'redsys');
    $freeOrder = ((float) $order->total) < 0.50;

    // Pedido gratis (cupón 100%) — confirmación sin pasarela
    if ($freeOrder && $order->status === 'pending') {
        return view('pages.pago-app', [
            'order'        => $order->load('items'),
            'clientSecret' => null,
            'freeOrder'    => true,
            'gateway'      => $gateway,
        ]);
    }

    // STRIPE — flujo legacy (sólo si PAYMENT_GATEWAY=stripe explícito)
    $clientSecret = null;
    if ($gateway === 'stripe'
        && (string) config('services.stripe.secret') !== ''
        && $order->status === 'pending'
    ) {
        try {
            $intent = app(\App\Services\StripePaymentService::class)->createIntentForOrder($order);
            $clientSecret = $intent->client_secret;
        } catch (\Throwable $e) {
            \Log::warning('pago-app intent fail', ['err' => $e->getMessage()]);
        }
    }
    return view('pages.pago-app', [
        'order'        => $order->load('items'),
        'clientSecret' => $clientSecret,
        'freeOrder'    => $freeOrder,
        'gateway'      => $gateway,
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
