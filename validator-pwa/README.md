# Algeciras CF · Validador de Aforo (PWA)

PWA del personal de puerta para validar entradas y abonos en el Estadio Nuevo Mirador.

Stack: Vite + Vue 3 + TypeScript + Tailwind + Pinia + @zxing/library + vite-plugin-pwa.

## Características

- Escáner QR con la cámara trasera del móvil (@zxing/library).
- Validación contra el backend Laravel (`POST /api/validar-qr`).
- Banner visual VERDE/ROJO + beep sonoro tras cada escaneo.
- Modo manual para teclear el token si la pistola falla.
- Estadísticas de aforo en vivo por sector (refresco cada 10s).
- Instalable como PWA en Android/iOS — funciona offline para los assets,
  aunque la validación requiere internet.
- Selector de partido (sólo "scheduled" + "home", ordenados por fecha).

## Cómo arrancar

```bash
cd validator-pwa
cp .env.example .env   # ajustar VITE_API_BASE si hace falta
npm install
npm run dev            # http://localhost:5174/validator/
```

> El escáner necesita HTTPS (`getUserMedia`). En dev, usa Chrome y permite
> el `localhost` o expón con `vite --host` + un túnel `https`.

## Build

```bash
npm run build
```

Genera `dist/`. El `base` de Vite es `/validator/`, así que la app se
sirve correctamente desde `https://algecirascf.hawkins.es/validator/`.

## Deploy

Copiar el contenido de `dist/` a `public/validator/` del proyecto Laravel:

```bash
# desde la raíz del proyecto Laravel
mkdir -p public/validator
cp -R validator-pwa/dist/* public/validator/
```

Laravel sirve `public/` directamente, así que no hace falta tocar rutas.
Solo asegurarse de que el web server fallback a `index.html` dentro de
`/validator/` para que el `vue-router` funcione (Vite ya configura
`navigateFallback` en el service worker para offline).

## Variables de entorno

| Variable | Default | Descripción |
|---|---|---|
| `VITE_API_BASE` | `https://algecirascf.hawkins.es` | Base de la API Laravel |

## Limitaciones actuales

- **Auth provisional**: PIN `12345678` hardcoded para cualquier nombre.
  TODO: migrar a Sanctum (`POST /api/operators/login` con personal-access-tokens
  o session cookie + CSRF).
- No hay cola offline: si el escaneo se hace sin red, el banner avisa pero
  no se guarda el intento para reenvío.
- Las stats asumen el endpoint `GET /api/admin/matches/{id}/stats` con la
  forma `{ entered, capacity, sectors: [{sector_name, entered, capacity}] }`.

## Siguientes pasos

1. Auth real con Sanctum y endpoint `POST /api/operators/login`.
2. Cola offline IndexedDB → reenvío al recuperar señal.
3. Modo "puerta" — preseleccionar `gate_id` en MatchSelectorView.
4. Sonidos personalizados (mp3 cortos) en vez de WebAudio sintético.
5. Icono PWA en alta resolución vectorial + maskable proper.

## Estructura

```
src/
├── main.ts
├── App.vue
├── router.ts
├── style.css
├── env.d.ts
├── stores/
│   ├── auth.ts     # Pinia store con persist localStorage
│   └── match.ts
├── services/
│   ├── api.ts      # axios + tipos de respuesta + endpoints
│   └── scanner.ts  # wrapper @zxing/library + extractToken()
├── views/
│   ├── LoginView.vue
│   ├── MatchSelectorView.vue
│   ├── ScanView.vue    # cámara + decode + POST automático
│   ├── ManualView.vue  # fallback manual
│   └── StatsView.vue   # poll cada 10s
└── components/
    ├── HeaderBar.vue
    └── ResultBanner.vue
```
