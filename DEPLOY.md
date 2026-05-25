# Deploy a Coolify (217.160.39.79)

## Arquitectura híbrida

Este repo (`crmhawkins/algeciras-cf-admin`) es **el backend**: Filament admin + API REST + tienda con Stripe + sistema de entradas/abonos con QR.

El **frontend público** vive en otro repo: `crmhawkins/algeciras-cf-web` (Next.js 16). Consume la API de este Laravel.

| Pieza | Repo | Dominio staging |
|---|---|---|
| **Backend Laravel** (admin + API + tienda) | `crmhawkins/algeciras-cf-admin` | `admin.algecirasclubdefutbol.com` |
| **Frontend Next.js** (web pública) | `crmhawkins/algeciras-cf-web` | `nuevo.algecirasclubdefutbol.com` |

Ambos en el mismo servidor Coolify `217.160.39.79`, mismos recursos compartidos opcionalmente (BD/Redis si conviene).

## Staging (mientras la WP actual sigue viva)

**Dominio**: `admin.algecirasclubdefutbol.com`
**Servidor**: 217.160.39.79
**Stack**: Laravel 11 + Filament v5 + MariaDB + Redis

## Pasos

### 1. DNS (acción del propietario del dominio)

Crear registro A en el panel del dominio `algecirasclubdefutbol.com`:

```
Tipo: A
Host: admin
Valor: 217.160.39.79
TTL: 3600
```

(Y otro para `nuevo` apuntando al mismo IP cuando despleguemos el Next.js)

### 2. Coolify — crear proyecto + recursos

En el panel Coolify del 217.160.39.79:

1. **Project**: `algeciras-cf` (uno solo, alberga admin + futuro front)
2. **Environment**: `production`
3. **Resources** → "Add a New Resource":
   - **MariaDB** → nombre `mariadb-algeciras` → DB `algeciras_cf` → user `algeciras_cf`
   - **Redis** → nombre `redis-algeciras`
   - **Application** → "Public Repository" o "Private Repository":
     - URL: `https://github.com/crmhawkins/algeciras-cf-admin`
     - Branch: `main`
     - Build pack: **Nixpacks** (auto-detect Laravel desde `nixpacks.toml`)
     - Domain: `admin.algecirasclubdefutbol.com` (Coolify hace SSL Let's Encrypt automático)
     - Port interno: `80` → Traefik mapea a 443 público

### 3. Variables de entorno (en la app de Coolify)

Copiar todo `.env.example` y rellenar:
- `APP_KEY=` → generar con `php artisan key:generate --show` (localmente o dentro del contenedor)
- `DB_HOST=mariadb-algeciras` (el nombre del resource Coolify)
- `DB_PASSWORD=` → la que pongas al crear MariaDB en Coolify
- `REDIS_HOST=redis-algeciras`
- `MAIL_PASSWORD=` → password SMTP de info@algecirasclubdefutbol.com (IONOS)
- `STRIPE_*` → cuando se contrate Stripe del club
- `APP_URL=https://admin.algecirasclubdefutbol.com`

### 4. CORS (para que el Next.js front consuma la API)

Cuando el Next.js esté en `nuevo.algecirasclubdefutbol.com`, añadir en `config/cors.php`:

```php
'allowed_origins' => [
    'https://nuevo.algecirasclubdefutbol.com',
    'https://algecirasclubdefutbol.com',
    'http://localhost:3000', // dev local Next.js
],
```

### 5. Deploy

Coolify hace deploy automático en cada push a `main`. Después del primer deploy:

```bash
# Crear usuario admin desde dentro del contenedor
docker exec -u www-data laravel-algeciras-XXXX php artisan make:filament-user
```

## Rollback rápido

Coolify guarda historial de deploys. Botón "Redeploy" sobre cualquier commit anterior.

## Verificación post-deploy

- [ ] https://admin.algecirasclubdefutbol.com responde 200
- [ ] SSL Let's Encrypt válido (A+ en ssllabs)
- [ ] `/admin` → login Filament funciona
- [ ] `/api/health` (cuando exista) → 200 OK
- [ ] Migraciones ejecutadas (tabla `users` existe en MariaDB)
- [ ] Assets Vite cargan (CSS/JS desde `/build/`)
