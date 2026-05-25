#!/usr/bin/env bash
set -e

cd /var/www/html

echo "==> Algeciras CF — Backend Laravel boot"

# Asegurar permisos (en runtime también, por si el volume montado los pierde)
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache public
chmod -R 775 storage bootstrap/cache

# Esperar a que MariaDB y Redis estén listos
echo "==> Esperando MariaDB en ${DB_HOST}:${DB_PORT}..."
for i in $(seq 1 30); do
    if php -r "exit(@fsockopen(getenv('DB_HOST'), (int)(getenv('DB_PORT') ?: 3306)) ? 0 : 1);"; then
        echo "    MariaDB OK"
        break
    fi
    sleep 2
done

# Cache de configuración (después de tener env vars)
echo "==> Ejecutando package:discover y caches"
php artisan package:discover --no-interaction --ansi || true
php artisan storage:link --no-interaction || true
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
php artisan event:cache --no-interaction || true

# Migraciones automáticas (idempotentes)
echo "==> Migraciones"
php artisan migrate --force --no-interaction

# Seeders solo si BD vacía (primer arranque)
if php artisan tinker --execute='exit(\App\Models\Player::count() === 0 ? 0 : 1);' 2>/dev/null; then
    echo "==> Primera vez: ejecutando seeders"
    php artisan db:seed --force --no-interaction || true
fi

echo "==> Boot completado, lanzando supervisord"
exec "$@"
