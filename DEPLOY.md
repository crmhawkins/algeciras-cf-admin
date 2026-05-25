# Deploy a Coolify (217.160.39.79)

## Staging (mientras la WP actual sigue viva)

**Dominio**: `nuevo.algecirasclubdefutbol.com`
**Servidor**: 217.160.39.79
**Stack**: Laravel 11 + Filament v5 + MariaDB + Redis

## Pasos

### 1. DNS (acción del propietario del dominio)

Crear registro A en el panel del dominio `algecirasclubdefutbol.com`:

```
Tipo: A
Host: nuevo
Valor: 217.160.39.79
TTL: 3600
```

### 2. Coolify — crear proyecto + recursos

En el panel Coolify del 217.160.39.79:

1. **Project**: crear nuevo proyecto `algeciras-cf`
2. **Environment**: `production`
3. **Resources** → "Add a New Resource":
   - **MariaDB** → nombre `mariadb-algeciras` → DB `algeciras_cf` → user `algeciras_cf`
   - **Redis** → nombre `redis-algeciras`
   - **Application** → "Public Repository" → URL del repo GitHub
     - Branch: `main`
     - Build pack: **Nixpacks** (auto-detect Laravel)
     - Domain: `nuevo.algecirasclubdefutbol.com` (Coolify hace SSL Let's Encrypt automático)
     - Port: `80` (interno) → Traefik mapea a 443 público

### 3. Variables de entorno (en la app de Coolify)

Copiar todo `.env.example` y rellenar:
- `APP_KEY=` → generar con `php artisan key:generate --show`
- `DB_PASSWORD=` → la que pongas al crear MariaDB en Coolify
- `MAIL_PASSWORD=` → password SMTP del email info@
- `STRIPE_*` → cuando se contrate Stripe del club

### 4. Deploy

Coolify hace deploy automático en cada push a `main`. Después del primer deploy:

```bash
# Crear usuario admin desde dentro del contenedor
docker exec -u www-data laravel-algeciras-XXXX \
    php artisan make:filament-user
```

## Rollback rápido

Coolify guarda historial de deploys. Botón "Redeploy" sobre cualquier commit anterior.

## Verificación post-deploy

- [ ] https://nuevo.algecirasclubdefutbol.com responde 200
- [ ] SSL Let's Encrypt válido (A+ en ssllabs)
- [ ] /admin login Filament funciona
- [ ] Migraciones ejecutadas (tabla `users` existe)
- [ ] Assets Vite cargan (CSS/JS desde /build/)
