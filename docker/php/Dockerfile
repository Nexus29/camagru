FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    libpq-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql gd

WORKDIR /var/www/html

COPY ./backend /var/www/html/backend

RUN mkdir -p /var/www/html/backend/uploads/overlays \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Run as a non-root system user for strict process isolation
USER www-data

EXPOSE 9000
CMD ["php-fpm"]