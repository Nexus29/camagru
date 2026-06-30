FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    libpq-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql gd

RUN mkdir -p /var/www/uploads/overlays \
    && chown -R www-data:www-data /var/www/uploads \
    && chmod -R 775 /var/www/uploads

WORKDIR /var/www/html

RUN mkdir -p /var/www/html/backend \
    && ln -s /var/www/uploads /var/www/html/backend/uploads