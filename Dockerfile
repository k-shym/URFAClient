FROM php:7.3-fpm-alpine3.8

RUN apk add --update --no-cache autoconf make g++ \
    && pecl install xdebug \
    && docker-php-ext-install bcmath \
    && docker-php-ext-enable xdebug \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && cp -a "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

ENV XDEBUG_MODE=coverage
