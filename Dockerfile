# syntax=docker/dockerfile:1
# ── Stage 1: dependencies ────────────────────────────────────────────────────
FROM php:8.2-fpm-alpine AS deps

RUN apk add --no-cache \
        bash git curl icu-data-full icu tzdata \
        libpng libjpeg-turbo libwebp freetype libzip oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS icu-dev libzip-dev oniguruma-dev \
        freetype-dev libpng-dev libjpeg-turbo-dev libwebp-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl zip pdo pdo_mysql opcache \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

# ── Stage 2: production image ────────────────────────────────────────────────
FROM php:8.2-fpm-alpine AS production

RUN apk add --no-cache \
        icu-data-full icu tzdata \
        libpng libjpeg-turbo libwebp freetype libzip oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS icu-dev libzip-dev oniguruma-dev \
        freetype-dev libpng-dev libjpeg-turbo-dev libwebp-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl zip pdo pdo_mysql opcache \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && apk del .build-deps

WORKDIR /var/www

# Copy vendor from deps stage
COPY --from=deps /var/www/vendor ./vendor

# Copy application source (excluding what's in .dockerignore)
COPY . .

# PHP production config
COPY docker/php/php.ini     /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/10-opcache.ini

# Use production env file as .env (app reads /var/www/.env at runtime)
COPY .env.production .env

# Ensure storage dirs exist and are writable
RUN mkdir -p storage/logs storage/cache storage/uploads storage/files storage/jobs \
    && chown -R www-data:www-data storage \
    && chmod -R 775 storage

# Entrypoint: runs migrations then starts php-fpm
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Non-root user
RUN addgroup -S appgroup && adduser -S appuser -G appgroup -G www-data
USER appuser

EXPOSE 9000
CMD ["/entrypoint.sh"]
