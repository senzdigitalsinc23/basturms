# syntax=docker/dockerfile:1

FROM php:8.2-fpm-alpine AS base

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    bash git curl icu-data-full icu tzdata \
    libpng libjpeg-turbo libwebp freetype \
    libzip \
    oniguruma \
    && apk add --no-cache --virtual .build-deps \
       $PHPIZE_DEPS icu-dev libzip-dev oniguruma-dev \
       freetype-dev libpng-dev libjpeg-turbo-dev libwebp-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl zip pdo pdo_mysql \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && apk del .build-deps

# Install Opcache for performance
RUN docker-php-ext-install opcache

# Install Composer
COPY --link --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy application files (excluding vendor, .env, .git, etc. via .dockerignore)
COPY --link . /var/www

# Install PHP dependencies (production)
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader || \
    (composer clear-cache && composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader)

# PHP configuration
COPY --link docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY --link docker/php/opcache.ini /usr/local/etc/php/conf.d/10-opcache.ini

# Ensure storage directories are writable
RUN mkdir -p storage/{logs,cache,uploads} && chown -R www-data:www-data storage

# --- Development variant with Xdebug ---
FROM base AS dev
RUN pecl install xdebug && docker-php-ext-enable xdebug
COPY --link docker/php/xdebug.ini /usr/local/etc/php/conf.d/20-xdebug.ini

# --- Final image ---
FROM base AS final

# Security: create non-root user and switch to it
RUN addgroup -S appgroup && adduser -S appuser -G appgroup
USER appuser

# Expose PHP-FPM port (default 9000)
EXPOSE 8000

# Default command: run PHP-FPM in foreground
CMD ["php-fpm", "-F"]
