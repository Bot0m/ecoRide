FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash curl git unzip icu-dev libzip-dev oniguruma-dev \
    autoconf gcc g++ make mysql-client mongodb-tools

# PHP extensions
RUN docker-php-ext-install pdo pdo_mysql intl zip opcache

# MongoDB extension
RUN pecl install mongodb && docker-php-ext-enable mongodb

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .

USER www-data
RUN git config --global --add safe.directory /var/www/html && composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Remplacez votre CMD actuel par :
    CMD ["sh","-lc","php -S 0.0.0.0:${PORT} -t public public/index.php"]


