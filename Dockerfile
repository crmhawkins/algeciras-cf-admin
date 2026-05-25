# =====================================================
# Algeciras CF — Backend Laravel 11 + Filament v5
# Imagen única php-fpm + nginx + supervisor
# =====================================================

# ---------- Stage 1: build frontend (Vite + Tailwind) ----------
FROM node:20-alpine AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---------- Stage 2: composer install ----------
FROM composer:2 AS composer-builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

# ---------- Stage 3: runtime ----------
FROM php:8.3-fpm-alpine AS runtime

# Dependencias de sistema + extensiones PHP
RUN apk add --no-cache \
    nginx supervisor bash \
    libpng libjpeg-turbo libwebp libzip icu-libs \
    libpng-dev libjpeg-turbo-dev libwebp-dev libzip-dev icu-dev oniguruma-dev libxml2-dev \
    $PHPIZE_DEPS \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) \
        pdo_mysql mysqli mbstring exif pcntl bcmath gd zip intl opcache \
 && pecl install redis && docker-php-ext-enable redis \
 && apk del libpng-dev libjpeg-turbo-dev libwebp-dev libzip-dev icu-dev oniguruma-dev libxml2-dev $PHPIZE_DEPS \
 && rm -rf /tmp/* /var/cache/apk/*

# Configuraciones nginx + php-fpm + supervisor
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Copiar la app construida
WORKDIR /var/www/html
COPY --from=composer-builder /app /var/www/html
COPY --from=node-builder /app/public/build /var/www/html/public/build

# Permisos para www-data (uid 82 en alpine php-fpm)
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public \
 && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
