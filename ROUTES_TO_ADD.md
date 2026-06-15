# Rutas pendientes de añadir a `routes/api.php`

Estas rutas las añade manualmente el integrador. No tocar `routes/api.php`
desde scripts automáticos para evitar conflictos.

## App version / force update

Endpoint para que la app móvil consulte la versión mínima soportada y
decida si forzar/sugerir actualización.

```php
use App\Http\Controllers\Api\AppVersionController;

Route::get('/app/version', [AppVersionController::class, 'current']);
```

- Método: `GET`
- URL pública: `https://algecirascf.hawkins.es/api/app/version`
- Autenticación: pública (sin token)
- Caché: 60s (`Cache-Control` ya viene en la respuesta)
- Controller: `app/Http/Controllers/Api/AppVersionController.php`

## Validador de QRs (abonos + entradas) + aforo live

Validación de tickets escaneados. El POST lo consume la app/PWA del personal
de puerta. El GET es la landing pública para cuando alguien escanea con la
cámara genérica del móvil (NO marca el ticket como usado).

```php
use App\Http\Controllers\Api\AbonoMatchQrController;
use App\Http\Controllers\Api\OperatorAuthController;
use App\Http\Controllers\Api\ValidatorController;

// Login del operador de puerta para la PWA (sin auth previo).
// Emite token Sanctum con ability `scope:operator`.
Route::post('/operator/login', [OperatorAuthController::class, 'login']);

// En routes/api.php — sustituye al post antiguo /validar-qr.
// El controlador comprueba internamente `tokenCan('scope:operator')`,
// por eso NO se envuelve en `auth:sanctum` aquí (devuelve 401/403 con
// el formato de respuesta de la PWA en lugar del default de Laravel).
Route::post('/validar-qr', [ValidatorController::class, 'validate']);
Route::get('/admin/matches/{match}/stats', [ValidatorController::class, 'matchStats']);

// QR rotativo por partido para abonados (PWA / app móvil del socio):
Route::middleware('auth:sanctum')
    ->get('/me/abonos/{ticket}/qr', [AbonoMatchQrController::class, 'forMatch']);

// En routes/web.php (es una vista, no JSON — token va en la URL pública)
Route::get('/v/{token}', [ValidatorController::class, 'showPublic'])
    ->where('token', '[A-Za-z0-9\-_]+');
```

- `POST /api/validar-qr`
    - Body: `{ "token": "...", "match_id": 123, "gate_id": "P5" }` (match_id **obligatorio**)
    - Auth: TODO añadir `auth:sanctum` con scope `operator` cuando exista.
    - Lógica:
        - **Abono**: si el QR es v2 (rotativo), exige que `match_id_from_token == match_id`.
          Registra `Attendance` (UNIQUE ticket+match) → segundo intento devuelve
          `reason: already_entered_match` con HTTP 409.
        - **Entrada**: exige `ticket.match_id == match_id`, marca ticket como `used`
          y registra `Attendance` también (para stats).
    - Respuesta OK: `{ valid: true, type, attendance_id, ticket: {...}, message }`
    - Respuestas KO con `reason` específico: `not_paid`, `cancelled`, `wrong_season`,
      `wrong_match`, `wrong_match_token`, `already_used`, `already_entered_match`,
      `ticket_not_found`, `bad_signature`, etc.

- `GET /api/admin/matches/{match}/stats`
    - Aforo en vivo del partido: total, por sector/zona, % ocupación.
    - TODO añadir middleware admin (`auth:sanctum` + `role:admin`).

- `GET /api/me/abonos/{ticket}/qr?match=X`
    - Auth: `auth:sanctum` (el dueño del abono, verificado por
      `user->customer_id == ticket->customer_id`).
    - Si no se pasa `match`, devuelve QR para el próximo partido en casa.
    - Devuelve `qr_data_uri` (PNG base64), token, datos del partido y del asiento.
    - Caducidad funcional: `kickoff_at + 6h`.

- `GET /v/{token}`
    - URL pública codificada dentro del QR.
    - Sólo muestra `pages.qr-resultado` con OK/KO. No muta estado.

## Renovación de abono (lookup + crear order)

Endpoints consumidos por la app móvil para que un abonado existente
renueve su abono usando su `numero_abonado` (= `tickets.id` del abono
emitido) + `dni`.

```php
use App\Http\Controllers\Api\AbonadosController;

Route::post('/abonados/lookup',  [AbonadosController::class, 'lookup']);
Route::post('/abonados/renovar', [AbonadosController::class, 'renovar']);
```

- Método: `POST`
- Autenticación: pública (sin token) — sólo expone datos si el DNI
  coincide con el del abono buscado.
- `lookup` body: `{ numero_abonado: int, dni: string }`
    - Resp: `{ found: true, abonado: {...} }` o `{ found: false, message }`
- `renovar` body: `{ numero_abonado, dni, items: [{product_id, qty}], stripe_payment_method_id? }`
    - Resp: `{ orderReference, checkoutUrl }` (igual que `/checkout/web-redirect`).
- Controller: `app/Http/Controllers/Api/AbonadosController.php`

## Apple Wallet + Google Wallet (añadir abono al pase nativo del móvil)

Endpoints que generan un `.pkpass` (Apple) y una URL `Save to Google Wallet`
para que el abonado guarde su abono en el Wallet nativo y vea el QR en
la lock screen (estilo Ticketmaster).

```php
use App\Http\Controllers\Api\WalletController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me/abonos/{ticket}/apple-wallet',  [WalletController::class, 'appleWallet']);
    Route::get('/me/abonos/{ticket}/google-wallet', [WalletController::class, 'googleWallet']);
});
```

- `GET /api/me/abonos/{ticket}/apple-wallet`
    - Auth: `auth:sanctum` (ownership por `customer_id`).
    - Sólo válido para tickets con `product.type == 'abono'`.
    - Resp 200: descarga binaria `application/vnd.apple.pkpass`
      (`algeciras-cf-abono.pkpass`).
    - Resp 500 si faltan certificados en servidor → la app muestra
      mensaje "próximamente disponible" al usuario.
    - Generado por `App\Services\AppleWalletService`. Requiere:
      `storage/app/wallet/passcert.p12`, `storage/app/wallet/AppleWWDRCA.pem`,
      `APPLE_WALLET_CERT_PASSWORD` en `.env`. Ver `docs/WALLET_SETUP.md`.

- `GET /api/me/abonos/{ticket}/google-wallet`
    - Auth: `auth:sanctum` (mismo check de ownership).
    - Resp 200: `{ "saveUrl": "https://pay.google.com/gp/v/save/<JWT>" }`.
    - La app abre esa URL con `Linking.openURL`; Google Wallet recibe el
      pass y lo guarda.
    - Generado por `App\Services\GoogleWalletService`. Requiere:
      `storage/app/wallet/google-service-account.json`,
      `GOOGLE_WALLET_ISSUER_ID` en `.env`. Ver `docs/WALLET_SETUP.md`.
