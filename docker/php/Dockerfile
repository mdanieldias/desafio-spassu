FROM php:8.5-fpm-alpine

# No PHP 8.5 o opcache já vem compilado. docker-php-ext-install falha se tentar instalá-lo.
RUN apk add --no-cache git unzip icu-dev libpq-dev libzip-dev $PHPIZE_DEPS \
    && docker-php-ext-install intl pdo_pgsql zip \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
