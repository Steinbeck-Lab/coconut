# syntax=docker/dockerfile:1.4
# =============================================================================
# Laravel + FrankenPHP Production Dockerfile
# =============================================================================
# Multi-stage build for optimal image size and security
# Following industry best practices for PHP/Laravel applications
# =============================================================================

ARG PHP_VERSION=8.3
ARG FRANKENPHP_VERSION=latest
ARG COMPOSER_VERSION=latest
ARG NODE_VERSION=18

# =============================================================================
# Composer Dependencies Stage
# =============================================================================
FROM composer:${COMPOSER_VERSION} AS composer

# =============================================================================
# Main Application Stage
# =============================================================================
FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION}

# Build arguments
ARG WWWUSER=1000
ARG WWWGROUP=1000
ARG TZ=UTC
ARG COMPOSER_AUTH

# Environment variables
ENV DEBIAN_FRONTEND=noninteractive \
    TZ=$TZ \
    OCTANE_SERVER=frankenphp \
    COMPOSER_FUND=0 \
    COMPOSER_MAX_PARALLEL_HTTP=24

# Set timezone
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    netcat-openbsd \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    libmagickwand-dev \
    libicu-dev \
    supervisor \
    unzip \
    && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    && pecl install \
    redis \
    imagick \
    && docker-php-ext-enable \
    redis \
    && echo "extension=imagick.so" > /usr/local/etc/php/conf.d/imagick.ini \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP - combine echo commands
RUN { \
        echo 'max_execution_time = 3600'; \
        echo 'memory_limit = 4G'; \
        echo 'upload_max_filesize = 100M'; \
        echo 'post_max_size = 250M'; \
        echo 'max_input_time = 3600'; \
    } > /usr/local/etc/php/conf.d/docker-php-custom.ini

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Create application user
RUN groupadd --gid $WWWGROUP laravel \
&& useradd --uid $WWWUSER --gid laravel --shell /bin/bash --create-home laravel

# Set working directory
WORKDIR /app

# Copy Composer from official image
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Copy application files
COPY --chown=laravel:laravel . .

RUN --mount=type=secret,id=composer_auth \
    composer install \
    --no-dev \
    --no-interaction \
    --no-ansi \
    --optimize-autoloader \
    --no-scripts \
    --audit

# Install npm dependencies and build frontend assets
RUN npm ci --no-audit && npm run build

# Remove Vite development server hot file (forces Laravel to use built assets)
RUN rm -f public/hot

# Create required directories and set permissions
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R laravel:laravel storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache


# Copy and setup startup script
COPY deployment/start-container /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

# Copy supervisor configurations
COPY deployment/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY deployment/supervisord-worker.conf /etc/supervisor/conf.d/supervisord-worker.conf

# Copy Caddyfile for FrankenPHP
COPY deployment/Caddyfile /etc/caddy/Caddyfile

# Copy and setup health check script
COPY deployment/healthcheck /usr/local/bin/healthcheck
RUN chmod +x /usr/local/bin/healthcheck

# Expose port
EXPOSE 8000

# Set entrypoint
ENTRYPOINT ["start-container"]

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD healthcheck 