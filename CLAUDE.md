# algeciras-cf-web — Memoria del proyecto

## REGLA #1 — Deploy directo a Coolify, NO esperar a GitHub

**Política**: cuando hay un cambio que debe reflejarse en producción ya
mismo, subir los archivos DIRECTAMENTE al contenedor Coolify por `docker
cp` + `view:clear`/`route:clear` según toque. NO esperar push a GitHub
ni redeploy de Coolify (rebuild de imagen) salvo que:

- El cambio requiera rebuild de assets (Vite/npm build),
- El cambio toque `composer.json` / `package.json` / Dockerfile / entrypoint,
- Sea el último paso del día y queramos consolidar todo en `main`.

### Flujo correcto

> ⚠️ (2026-10-07) El acceso `-i ~/.ssh/hawcert_server claude@...` de abajo YA NO
> VALE. Al 79 se entra con `~/.ssh/ivan_servers`, usuario `ivan`, saltando por el
> 81 con ProxyCommand, y `sudo docker` (detalle en `~/.claude/CLAUDE.md`).
> El contenedor actual es `mos48s4400kwo44w0g0w0ssk-075909058440`.

```bash
# 1. Editar local en D:\proyectos\programasivan\algeciras-cf-web
# 2. SCP al servidor (host)
scp -i ~/.ssh/hawcert_server archivo.php \
    claude@217.160.39.79:/tmp/archivo.php

# 3. Identificar el contenedor actual de la app (puede cambiar tras cada deploy)
ssh -i ~/.ssh/hawcert_server claude@217.160.39.79 \
    "docker ps --filter 'label=coolify.applicationId=6' --format '{{.Names}}'"

# 4. Backup + copy + ownership + lint
CONTAINER=mos48s4400kwo44w0g0w0sskXXXXXXXX   # del paso 3
docker exec -u www-data $CONTAINER \
    cp /var/www/html/ruta/archivo.php /var/www/html/ruta/archivo.php.bak.YYYYMMDD-desc
docker cp /tmp/archivo.php $CONTAINER:/var/www/html/ruta/archivo.php
docker exec $CONTAINER chown www-data:www-data /var/www/html/ruta/archivo.php
docker exec -u www-data $CONTAINER php -l /var/www/html/ruta/archivo.php

# 5. Limpiar caches según tipo
# - Blade:    php artisan view:clear
# - Routes:   php artisan route:clear
# - Config:   php artisan config:clear
docker exec -u www-data $CONTAINER php /var/www/html/artisan view:clear

# 6. (Opcional, sólo al final del día) Push a GitHub para preservar histórico
cd /d/proyectos/programasivan/algeciras-cf-web
git add ... && git commit -m "..." && git push origin main
```

### Lo que NO se debe hacer

1. **NO esperar a Coolify para verificar cambios** — Coolify tarda
   2-5 minutos en rebuild + OPcache nuevo. Los `docker cp` son instantáneos.
2. **NO disparar redeploy de Coolify automáticamente** tras cada commit.
   Coolify auto-deploya solo en pushes a `main` ADEMÁS si tiene webhook
   configurado (que parece no estar). Si quieres forzar redeploy, hacerlo
   explícitamente y SOLO si hace falta (rebuild de assets, etc.).
3. **NO esperar a que el push a GitHub aterrice** para ejecutar el
   seeder / artisan / lo que sea. El push puede tardar (renegotiation
   SSL, red, lo que sea) y el deploy a Coolify es independiente.

## Reglas heredadas (válidas también aquí)

Las reglas de `~/.claude/CLAUDE.md` (NO ROMPER PRODUCCIÓN, ownership
www-data, backups forenses, verificación en Chrome) siguen aplicando.

## Infraestructura

| | Valor |
|---|---|
| Servidor | `217.160.39.79` (Coolify) |
| App | `algeciras-cf-admin` (ID interno Coolify: 6) |
| UUID Coolify | `mos48s4400kwo44w0g0w0ssk` |
| Contenedor (cambia tras cada deploy) | `mos48s4400kwo44w0g0w0ssk-XXXXXXXX` |
| FQDN actual | `http://mos48s4400kwo44w0g0w0ssk.217.160.39.79.sslip.io` |
| Repo GitHub | `crmhawkins/algeciras-cf-admin` (branch `main`) |
| Stack | Laravel 11 + Filament v5 + Livewire 4 + Tailwind v4 + MariaDB + Redis |
| OPcache producción | `validate_timestamps=0` → cambios en `.php` NO se ven hasta opcache reset o rebuild |

### OPcache: cómo activar los `.php` copiados (NO redeploy)

Con `validate_timestamps=0` los `.php` (y las vistas compiladas) copiados por
`docker cp` no se activan solos. Se resetea OPcache con un script temporal de
nombre aleatorio en `public/` creado como `www-data`, que se llama por HTTPS y se
borra en el acto (`<?php echo opcache_reset() ? "OK" : "FAIL";`). Antes, `view:clear`
como `www-data`. Los cambios del `.env` NO lo necesitan (`env()` se lee en caliente).

⚠️ **NO usar redeploy de Coolify para esto**: `main` va muy por detrás del
contenedor (hotfixes por `docker cp` sin commitear) y el `.env` del contenedor no
está en la imagen (`.dockerignore`). Un redeploy hoy borraría los hotfixes y la
config de Redsys. Consolidar primero en git.

### Cuándo SÍ hace falta disparar redeploy de Coolify

- Solo con cambios de assets (Vite), `composer.json`, Dockerfile o entrypoint,
  y SOLO después de consolidar el contenedor en `main`. Pedir permiso antes.

### Disparar redeploy de Coolify (cuando haga falta)

```bash
scp trigger_deploy.php claude@217.160.39.79:/tmp/
ssh claude@217.160.39.79 "docker cp /tmp/trigger_deploy.php coolify:/tmp/ && \
  docker exec coolify sh -c 'echo \"require \\\"/tmp/trigger_deploy.php\\\";\" | \
  php artisan tinker --no-interaction'"
```

`trigger_deploy.php` está en `~/proyectos/programasivan/NuevoHeraAppartment/tmp/`.

## Contexto del proyecto

Web pública del Algeciras CF (Primera RFEF). Plano interactivo del
estadio Nuevo Mirador con selección de butacas tipo cine (1:1 con
compralaentrada.com).

### Datos relevantes

- 66 sectores en SVG, 54 vendibles + 6 no-vendibles (palco/agotados) + 6 reservados
- 60 sectores con disposición real de butacas en `database/data/sectors_layout.json`
- Geometría extraída del API oculto de compralaentrada
  (`apiteatros.compralaentrada.com/api1/f/zonas/{id}?conf=true`)
- 7610 butacas creadas (3839 libres, 3771 vendidas)

### Modelos

- `Sector` (svg_region, name, zone, parity, number, price_adult, price_youth, capacity, available)
- `Seat` (sector_id, row, number, status[free|sold|reserved|blocked])
- `Product` polimórfico (merch / abono / entrada)
- `Order`, `OrderItem`, `Ticket` (con QR)

### Rutas clave

| Ruta | Controlador |
|---|---|
| `/estadio` | `StadiumController@index` (plano SVG interactivo) |
| `/estadio/sector/{svgRegion}` | `StadiumController@sector` (grilla butacas) |
| `/tienda`, `/tienda/{product:slug}` | `PageController` |
| `/abonos`, `/calendario`, etc. | `PageController` |

## Pagos (Redsys)

- TPV Virtual de **Caja Rural del Sur**, comercio "ALGECIRAS CF", FUC `370436875`,
  terminal `100` (por defecto en `config/redsys.php`). Pasarela activa:
  `PAYMENT_GATEWAY=redsys|stripe|simulated` (`config/services.php`).
- TEST: `REDSYS_ENV=test` + clave pública de pruebas de Redsys. REAL: `REDSYS_ENV=prod`
  + `REDSYS_SHA256_KEY` de producción (la manda Redsys al validar las pruebas).
- Flujo: `/comprar-directo/{slug}` o carrito → `/pago-app/{ref}` → `/pago/redsys/{ref}`
  (form firmado HMAC_SHA256_V1) → banco → `notify` server-to-server + vuelta a
  `/pago/ok|ko` (ambos verifican firma, importe y marcan el pedido; idempotente).
- `/pago-app/{ref}/simulado` solo confirma sin cobro si total < 0,50€ o gateway
  `simulated` (antes regalaba abonos con QR válido a cualquiera).
- Pruebas con tarjeta: en LOCAL (`php artisan serve --host=127.0.0.1`), tarjeta de
  test Redsys 4548 8120 4940 0004, 12/34, CVV 123. Tests:
  `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --filter=RedsysPagoTest`
  (¡nunca sin esas variables: phpunit.xml apunta a la SQLite local!).

## Cambios recientes

- **2026-10-07**: Redsys (Caja Rural del Sur) en TEST en el subdominio; cerrado el
  agujero de `/simulado`; carrito y app también pagan por Redsys; textos legales
  con el banco correcto; migraciones compatibles con SQLite para desarrollo local.

- **2026-05-26**: Disposición real de butacas 1:1 desde compralaentrada
  (commit `2b4621b`). 60 sectores con rows/seats_row/initial_row/initial_seat
  reales extraídos del API.
- **2026-05-26**: Fix click en plano SVG → navega a `/estadio/sector/{id}`
  (commit `123285c`). Event delegation desde el `<svg>` raíz para que
  funcione aunque el click caiga en un `<polygon>`/`<rect>` hijo.
