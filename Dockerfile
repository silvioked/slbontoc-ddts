# ----------------------------------------------------
# Stage 1: Composer Dependencies
# ----------------------------------------------------
FROM composer:2 AS build

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

# ----------------------------------------------------
# Stage 2: Production Image
# ----------------------------------------------------
FROM php:8.2-fpm

# Install system deps + Nginx + Supervisor + PostgreSQL driver
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    git \
    unzip \
    curl \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath

WORKDIR /var/www/html

# Copy Laravel application
COPY . .

# Copy vendor files from builder stage
COPY --from=build /app/vendor ./vendor

# Nginx config
RUN rm -f /etc/nginx/sites-enabled/default
COPY deploy/nginx.conf /etc/nginx/sites-enabled/default

# Create startup script to handle PORT dynamically
RUN echo '#!/bin/bash\n\
# Replace PORT in nginx config\n\
sed -i "s/listen 10000/listen $PORT/g" /etc/nginx/sites-enabled/default\n\
# Start supervisor\n\
/usr/bin/supervisord -n' > /start.sh \
    && chmod +x /start.sh

# Supervisor config to run both Nginx + PHP-FPM
COPY deploy/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

# Create required directories
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache public/qrcodes

# Laravel permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache public/qrcodes

EXPOSE 10000

CMD ["/start.sh"]
