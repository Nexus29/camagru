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

# Set the working directory
WORKDIR /var/www/html

# Copy ONLY the backend directory from the root context into the container
COPY backend/ ./backend/

# Create a symbolic link from Render's secure runtime secret path to the expected /etc/msmtprc
RUN ln -sf /etc/secrets/msmtprc /etc/msmtprc

# Safely apply permissions to the symlink destination if it is resolved, preventing build failures
RUN if [ -e /etc/msmtprc ]; then chown www-data:www-data /etc/msmtprc && chmod 600 /etc/msmtprc; fi

# Route PHP's native mail() engine commands via msmtp
RUN echo 'sendmail_path = "/usr/bin/msmtp -t"' > /usr/local/etc/php/conf.d/mail.ini

# Secure directory ownership before switching users
RUN chown -R www-data:www-data /var/www/html

# Run as non-root system user to enforce the strict process isolation rules
USER www-data

# Render expects an HTTP port (8080) instead of the raw PHP-FPM fastcgi port (9000)
EXPOSE 8080

# Serve the application directly. Point the web root (-t) to the backend directory
CMD ["php", "-S", "0.0.0.0:8080", "-t", "backend", "backend/index.php"]
