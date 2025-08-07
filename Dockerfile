FROM php:8.3-fpm-alpine

# Installer les extensions nécessaires
RUN apk add --no-cache \
    nginx \
    bash \
    curl \
    git \
    icu-dev \
    zlib-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    zip \
    unzip \
    && docker-php-ext-install intl pdo pdo_mysql opcache zip

# Installer Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Préparer les dossiers
WORKDIR /var/www/html
COPY . /var/www/html

# Copier la conf Nginx
COPY ./nginx.conf /etc/nginx/conf.d/default.conf

# Donne les bons droits
RUN chown -R www-data:www-data /var/www/html

# Exposer le port NGINX
EXPOSE 80

# Lancer PHP-FPM + NGINX
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
