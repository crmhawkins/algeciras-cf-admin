# Wallet setup — Apple Wallet (.pkpass) + Google Wallet

Pasos para que los abonados puedan guardar su abono en el Wallet nativo
del móvil (lock screen QR estilo Ticketmaster).

Backend: `app/Services/AppleWalletService.php`, `app/Services/GoogleWalletService.php`,
`app/Http/Controllers/Api/WalletController.php`.

App: `appalgeciras/src/services/wallet.ts` + botones en `MisAbonosScreen`.

---

## 0. Dependencias (servidor + app)

### Servidor PHP

Las dos clases backend **NO usan ninguna lib de Composer**. Lo único
necesario es `ext-openssl` (incluido en cualquier PHP 8 estándar) y
`ext-zip` para empaquetar el .pkpass. Verificación rápida:

```bash
docker exec laravel-... php -m | grep -iE 'openssl|zip|gd'
```

`gd` se usa sólo si no subes iconos custom (se generan placeholders);
si no está, se cae a un PNG 1x1 mínimo válido.

Si más adelante el cliente prefiere una librería mantenida, las opciones
recomendadas son:

```bash
composer require thenetworg/oauth2-apple   # NO sirve para wallet
composer require nicmart/passes            # generación pkpass
# o
composer require chrissnyder/laravel-passbook
```

Hoy día la implementación de este repo no las necesita.

### App React Native

Para abrir el .pkpass desde la app la solución más limpia es
`expo-file-system` + `expo-sharing` (ya parte del SDK Expo 54):

```bash
cd appalgeciras
npx expo install expo-file-system expo-sharing
```

Si no se añaden, hay fallback automático que abre la URL del backend en
Safari, que también dispara Apple Wallet.

Para Google Wallet basta con `Linking` de React Native nativo (ya disponible).

---

## 1. Apple Wallet

### 1.1 Crear Pass Type ID y certificado

1. Entra en [developer.apple.com](https://developer.apple.com/account/resources/identifiers/list/passTypeId)
   con la cuenta del cliente (Apple Developer Program, 99 USD/año).
2. **Identifiers → Pass Type IDs → +**:
   - Description: `Abonos Algeciras CF`
   - Identifier: `pass.es.algecirascf.abonos`  ← debe empezar por `pass.`
3. Genera el certificado para ese Pass Type ID:
   - **Configure → Create Certificate**
   - Pide un CSR. Desde el llavero de macOS:
     **Acceso a Llaveros → Asistente para certificados → Solicitar certificado**.
     Guarda el `.certSigningRequest`, súbelo.
   - Descarga el `.cer` resultante (`pass.cer`).
4. Importa el `.cer` en Acceso a Llaveros (doble click en mac).
   Aparece como certificado + clave privada juntos.
5. Click derecho sobre el certificado **Exportar a .p12**, ponle una
   contraseña fuerte. Ese es tu `passcert.p12`.

### 1.2 Descargar el certificado intermedio WWDR

Apple firma con un cert intermedio (Apple Worldwide Developer Relations).

- https://www.apple.com/certificateauthority/
- Busca **Apple Worldwide Developer Relations Certification Authority (G4)**
  (el actual, expira 2030). Descarga el `.cer`.
- Conviértelo a PEM:

```bash
openssl x509 -inform DER -in AppleWWDRCAG4.cer -out AppleWWDRCA.pem
```

### 1.3 Subir al servidor

```bash
ssh -i ~/.ssh/hawcert_server claude@<servidor>

# Crear directorio
docker exec -u www-data laravel-... mkdir -p /var/www/html/storage/app/wallet

# Copiar p12 + pem
docker cp passcert.p12   laravel-...:/var/www/html/storage/app/wallet/passcert.p12
docker cp AppleWWDRCA.pem laravel-...:/var/www/html/storage/app/wallet/AppleWWDRCA.pem

# Permisos
docker exec laravel-... chown -R www-data:www-data /var/www/html/storage/app/wallet
docker exec laravel-... chmod 600 /var/www/html/storage/app/wallet/passcert.p12
```

### 1.4 Variables de entorno

En `.env` del contenedor del CRM Algeciras CF:

```
APPLE_WALLET_PASS_TYPE_IDENTIFIER=pass.es.algecirascf.abonos
APPLE_WALLET_TEAM_IDENTIFIER=ABCDE12345
APPLE_WALLET_ORG_NAME="Algeciras CF"
APPLE_WALLET_CERT_PASSWORD=<la-password-del-p12>
# Las paths usan los defaults; sobreescribir sólo si se cambian de sitio:
# APPLE_WALLET_CERT_PATH=/var/www/html/storage/app/wallet/passcert.p12
# APPLE_WALLET_WWDR_PATH=/var/www/html/storage/app/wallet/AppleWWDRCA.pem
```

El `team_identifier` (10 caracteres alfanuméricos) lo encuentras en
[developer.apple.com/account → Membership](https://developer.apple.com/account/#MembershipDetailsCard).

### 1.5 Iconos (opcional pero recomendado)

Coloca iconos en `storage/app/wallet/assets/`:

- `icon.png`     29×29   (lock screen / Wallet list)
- `icon@2x.png`  58×58
- `logo.png`     ≤160×50 (cabecera del pass)
- `logo@2x.png`  ≤320×100

Si no los subes, el servicio genera placeholders rojos del color del club.

### 1.6 Prueba

Con un abono real:

```
GET https://algecirascf.hawkins.es/api/me/abonos/{ticket_id}/apple-wallet
Authorization: Bearer <sanctum-token>
```

Debe devolver `Content-Type: application/vnd.apple.pkpass` y el binario
del .pkpass. Si abres ese fichero en un iPhone, Apple Wallet lo importa.

---

## 2. Google Wallet

### 2.1 Crear proyecto + habilitar API

1. https://console.cloud.google.com/ → Crear proyecto (o reutilizar uno).
2. **APIs & Services → Library → buscar "Google Wallet API" → Enable**.

### 2.2 Service Account

1. **IAM & Admin → Service Accounts → +**.
   - Nombre: `algecirascf-wallet`
   - Rol: ninguno aquí (lo asignamos en Wallet Console).
2. Click en el SA recién creado **→ Keys → Add Key → JSON**.
   Se descarga `algecirascf-wallet-XXXXX.json`. **Es secreto.**

### 2.3 Registrar Issuer en Google Wallet Business Console

1. https://pay.google.com/business/console/
2. Crear cuenta de Issuer (datos del club). Aprobación inmediata para
   sandbox; producción requiere review manual.
3. Anota tu **Issuer ID** (~16 dígitos, p.ej. `3388000000022xxx`).
4. **Users → Add user** → email del service account
   (`algecirascf-wallet@<project>.iam.gserviceaccount.com`) → rol
   "Wallet Object Issuer Admin".

### 2.4 Crear EventTicketClass

Sólo una vez por temporada. Se hace con un script o llamada directa REST:

```bash
# Pseudocódigo — pide al SA un token y POSTea la clase:
POST https://walletobjects.googleapis.com/walletobjects/v1/eventTicketClass
Authorization: Bearer <oauth2 service account>
{
  "id": "3388000000022xxx.abonos_2526",
  "issuerName": "Algeciras CF",
  "eventName": {
    "defaultValue": {"language": "es", "value": "Abono Algeciras CF 25/26"}
  },
  "venue": {
    "name": {"defaultValue": {"language": "es", "value": "Estadio Nuevo Mirador"}},
    "address": {"defaultValue": {"language": "es", "value": "Algeciras, Cádiz"}}
  },
  "reviewStatus": "UNDER_REVIEW",
  "hexBackgroundColor": "#cf2e2e"
}
```

(Se puede automatizar más adelante. Por ahora el script lo hace
manualmente el integrador.)

### 2.5 Subir al servidor + .env

```bash
docker cp google-service-account.json \
    laravel-...:/var/www/html/storage/app/wallet/google-service-account.json
docker exec laravel-... chown www-data:www-data \
    /var/www/html/storage/app/wallet/google-service-account.json
docker exec laravel-... chmod 600 \
    /var/www/html/storage/app/wallet/google-service-account.json
```

`.env`:

```
GOOGLE_WALLET_ISSUER_ID=3388000000022xxx
GOOGLE_WALLET_CLASS_SUFFIX=abonos_2526
# GOOGLE_WALLET_SA_JSON_PATH=/var/www/html/storage/app/wallet/google-service-account.json
# GOOGLE_WALLET_ORIGIN=https://algecirascf.hawkins.es
```

### 2.6 Prueba

```
GET https://algecirascf.hawkins.es/api/me/abonos/{ticket_id}/google-wallet
Authorization: Bearer <sanctum-token>
```

Debe devolver `{ "saveUrl": "https://pay.google.com/gp/v/save/<JWT>" }`.
Abrir esa URL desde un Android logueado en Google → el abono se importa
en Google Wallet.

---

## 3. Rutas (a añadir manualmente)

Ver `ROUTES_TO_ADD.md`, sección "Apple Wallet + Google Wallet".

```php
use App\Http\Controllers\Api\WalletController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me/abonos/{ticket}/apple-wallet',  [WalletController::class, 'appleWallet']);
    Route::get('/me/abonos/{ticket}/google-wallet', [WalletController::class, 'googleWallet']);
});
```

---

## 4. Errores comunes

| Síntoma | Causa | Fix |
|---|---|---|
| iPhone abre el .pkpass pero dice "Pass no válido" | Firma incorrecta (.p12 + WWDR no encajan) o iconos faltan | Asegura que `passcert.p12` viene del Pass Type ID `pass.es.algecirascf.abonos` y la WWDR es la G4 actual. |
| 500 "Apple Wallet no configurado" | Falta `passcert.p12` en `storage/app/wallet/` | Sigue §1.3 |
| Google Wallet "No se pudo añadir" | Class ID no existe o SA sin permiso | Sigue §2.3 y §2.4 |
| App muestra "próximamente disponible" | Backend devolvió 500 (cert/config faltante) | Es el comportamiento intencional — el usuario no ve un crash. |
