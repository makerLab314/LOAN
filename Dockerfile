# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Basis: PHP-FPM mit den benötigten Extensions
# ---------------------------------------------------------------------------
FROM php:8.4-fpm-bookworm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
        libicu-dev unzip mariadb-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip intl opcache bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-loan.ini
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Dev/Test: Composer-Abhängigkeiten inkl. dev, ohne opcache-Caching
# (docker build --target dev -t loan:dev .)
# ---------------------------------------------------------------------------
FROM base AS dev
RUN echo "opcache.validate_timestamps=1" > /usr/local/etc/php/conf.d/zz-dev.ini

# ---------------------------------------------------------------------------
# PHP-Abhängigkeiten (ohne dev)
# ---------------------------------------------------------------------------
FROM base AS vendor
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist

# ---------------------------------------------------------------------------
# Frontend-Build (Vite + Tailwind)
# ---------------------------------------------------------------------------
# Läuft immer auf der Build-Plattform – die Assets sind plattformunabhängig
FROM --platform=$BUILDPLATFORM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ---------------------------------------------------------------------------
# Produktion
# ---------------------------------------------------------------------------
FROM base AS production

# Wird vom Release-Workflow gesetzt (z. B. 2.1.0), lokal gebaut: "dev"
ARG LOAN_VERSION=dev
ENV LOAN_VERSION=$LOAN_VERSION

COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && php artisan package:discover --ansi \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions \
        storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    # Kopie von public/ für den nginx-Container (wird beim Start in das Shared Volume gespiegelt)
    && cp -a public /opt/public-dist

COPY docker/entrypoint.sh /usr/local/bin/loan-entrypoint
RUN chmod +x /usr/local/bin/loan-entrypoint

ENTRYPOINT ["loan-entrypoint"]
CMD ["php-fpm"]
