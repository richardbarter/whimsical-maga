# ============================================================
# Stage 1: Install Composer dependencies
# Run this first so vendor is available to both the node
# stage (for Ziggy TypeScript types) and the php stage.
# ============================================================
FROM composer:2 AS composer-deps

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction \
    --ignore-platform-reqs

# ============================================================
# Stage 2: Build frontend assets
# ============================================================
FROM node:22-alpine AS node

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .

# Copy vendor from composer stage — needed for Ziggy TypeScript types
# (app.ts imports directly from ../../vendor/tightenco/ziggy)
COPY --from=composer-deps /app/vendor ./vendor

RUN npm run build

# ============================================================
# Stage 3: PHP production image
# ============================================================
FROM php:8.4-fpm-alpine AS php

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
        nginx \
        supervisor \
        postgresql-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        zip \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        opcache \
        pcntl \
        gd \
        bcmath \
        exif \
    && rm -rf /var/cache/apk/*

# Use PHP's built-in production php.ini (hides errors from users, tightens security)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Configure OPcache for production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Allow uploads up to 10MB
RUN { \
    echo 'upload_max_filesize = 10M'; \
    echo 'post_max_size = 10M'; \
} > /usr/local/etc/php/conf.d/uploads.ini

# Install Composer binary (needed for dump-autoload below)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy vendor from composer stage (same install reused — no second composer install needed)
COPY --from=composer-deps /app/vendor ./vendor

# Copy application source
COPY . .

# Copy built frontend assets from node stage
COPY --from=node /app/public/build public/build

# Create required Laravel directories before dump-autoload runs,
# as post-autoload-dump triggers package:discover which needs bootstrap/cache
RUN mkdir -p storage/framework/sessions \
             storage/framework/views \
             storage/framework/cache \
             storage/logs \
             bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && mkdir -p /tmp/nginx_client_body \
    && mkdir -p /tmp/nginx_fastcgi \
    && chown www-data:www-data /tmp/nginx_client_body /tmp/nginx_fastcgi

# Finalise Composer autoloader — also runs package:discover via post-autoload-dump
RUN composer dump-autoload --no-dev --optimize --no-interaction

# Copy Docker config files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
