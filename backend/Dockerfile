FROM composer:2 AS composer

FROM php:8.4-fpm AS base

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions amqp intl opcache pdo_pgsql zip

COPY docker/php/app.ini $PHP_INI_DIR/conf.d/zz-app.ini
COPY docker/php/fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf

WORKDIR /app

FROM base AS vendor

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative

FROM base AS dev

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN install-php-extensions pcov
ENV COMPOSER_HOME=/tmp/composer

FROM base AS prod

COPY docker/php/opcache.ini $PHP_INI_DIR/conf.d/zz-opcache.ini
COPY --from=vendor --chown=www-data:www-data /app /app
RUN install -d -o www-data -g www-data storage storage/images storage/imports var var/cache
