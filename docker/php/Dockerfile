FROM php:8.2-fpm-alpine

# Install msmtp and system library dependencies as root
RUN apk add --no-cache \
    libpq-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    msmtp \
    ca-certificates

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql gd

# Copy and secure the mail transfer agent configuration before stripping root privileges
COPY ./docker/php/msmtprc /etc/msmtprc
RUN chown www-data:www-data /etc/msmtprc && chmod 600 /etc/msmtprc

# Route PHP's native mail() engine commands via msmtp
RUN echo 'sendmail_path = "/usr/bin/msmtp -t"' > /usr/local/etc/php/conf.d/mail.ini

WORKDIR /var/www/html

# Run as non-root system user to enforce the strict process isolation rules
USER www-data

EXPOSE 9000
CMD ["php-fpm"]