FROM php:8.0-fpm-alpine AS cacert
FROM php:7.3-fpm-alpine3.8

RUN rm -rf /etc/ssl

COPY --from=cacert /etc/ssl /etc/ssl

RUN apk add --update --no-cache autoconf make g++ \
    && pear update-channels \
    && pear upgrade \
    && pecl -vvv install xdebug-3.1.6 \
    && docker-php-ext-enable xdebug \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && cp -a "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

ENV XDEBUG_MODE=coverage
