# =====================================================
# Algeciras CF — Backend Laravel 11 + Filament v5
# Single-stage runtime con composer install in-place
# (evita problema de composer:2 image trayendo PHP 8.5 sin ext-intl)
# =====================================================

# ---------- Stage 1: build frontend (Vite + Tailwind) ----------
FROM node:20-alpine AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---------- Stage 2: runtime (PHP-FPM + nginx + supervisor) ----------
FROM php:8.2-fpm-alpine AS runtime

# Dependencias de sistema + extensiones PHP
RUN apk add --no-cache \
        nginx supervisor bash git unzip curl \
        libpng libjpeg-turbo libwebp libzip icu-libs oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libpng-dev libjpeg-turbo-dev libwebp-dev libzip-dev icu-dev oniguruma-dev libxml2-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql mysqli mbstring exif pcntl bcmath gd zip intl opcache \
    && pecl install redis && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/*

# Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_INTERACTION=1

# Configs nginx + php-fpm + supervisor
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /var/www/html

# Preparar directorios cache ANTES de composer (package:discover los necesita)
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Copiar composer.* y instalar deps (cache layer Docker)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copiar el resto del código + assets compilados
COPY . .
COPY --from=node-builder /app/public/build /var/www/html/public/build

# Generar autoloader optimizado (SIN scripts; el package:discover lo ejecuta el entrypoint)
RUN composer dump-autoload --no-dev --optimize --no-scripts

# Permisos
RUN chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
