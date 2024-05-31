FROM php:8.3-fpm-alpine3.19 AS base

RUN apk add --update linux-headers zlib-dev libpng-dev libzip-dev icu-dev $PHPIZE_DEPS
RUN apk add git

RUN docker-php-ext-install exif
RUN docker-php-ext-install gd
RUN docker-php-ext-install zip
RUN docker-php-ext-install sockets
RUN pecl install apcu
RUN docker-php-ext-enable apcu
RUN docker-php-ext-install pcntl
RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl

RUN set -ex \
  && apk --no-cache add \
    postgresql-dev
RUN docker-php-ext-install pdo pdo_pgsql

# OVERRIDE DEFAULT VALUES FROM THE PHP INI FILE ON THE DOCKER CONTAINER
RUN echo 'max_execution_time = 3600' >> /usr/local/etc/php/conf.d/docker-php-maxexectime.ini;
RUN echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini;
RUN echo 'upload_max_filesize = 100M' >> /usr/local/etc/php/conf.d/docker-php-uploadmaxfilesize.ini;
RUN echo 'post_max_size = 100M' >> /usr/local/etc/php/conf.d/docker-php-postmaxsize.ini;

FROM base AS dev

COPY /composer.json composer.json
COPY /composer.lock composer.lock
COPY /app app
COPY /bootstrap bootstrap
COPY /config config
COPY /artisan artisan

FROM base AS build-fpm
WORKDIR /var/www/html
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# TODO: REMOVE REDUNDANT STEPS
COPY /artisan artisan
COPY /composer.json composer.json
COPY /bootstrap bootstrap
COPY /app app
COPY /config config
COPY /routes routes
COPY . /var/www/html

ARG COMPOSER_AUTH
ENV COMPOSER_AUTH=$COMPOSER_AUTH

RUN COMPOSER_AUTH="$COMPOSER_AUTH" composer install --no-dev --no-interaction --no-progress --no-ansi --no-scripts
RUN composer dump-autoload -o

FROM node:18-alpine AS assets-build
WORKDIR /var/www/html
COPY . /var/www/html/

COPY --from=build-fpm /var/www/html/vendor/filament /vendor/filament
COPY --from=build-fpm /var/www/html/vendor/archilex /vendor/archilex

RUN npm ci
RUN npm run build

FROM nginx:1.19-alpine AS nginx
COPY /resources/ops/docker/nginx/vhost.conf /etc/nginx/conf.d/default.conf
COPY --from=assets-build /var/www/html/public /var/www/html