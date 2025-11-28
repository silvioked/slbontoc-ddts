# Use PHP 8.2
FROM php:8.2-cli

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    xz-utils \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node.js 18.x
RUN curl -fsSL https://nodejs.org/dist/v18.20.0/node-v18.20.0-linux-x64.tar.xz -o /tmp/node.tar.xz \
    && tar -xJf /tmp/node.tar.xz -C /usr/local --strip-components=1 \
    && rm /tmp/node.tar.xz

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy package files for npm
COPY package.json package-lock.json ./

# Install NPM dependencies
RUN npm ci

# Copy the rest of the application
COPY . .

# Build frontend assets
RUN npm run build

# Create required directories
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache public/qrcodes \
    && chmod -R 775 storage bootstrap/cache public/qrcodes

# Copy .env.example if .env doesn't exist
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Expose port
EXPOSE 10000

# Create startup script
RUN echo '#!/bin/bash\n\
set -e\n\
php artisan config:cache || true\n\
php artisan route:cache || true\n\
php artisan view:cache || true\n\
php artisan storage:link || true\n\
php artisan serve --host=0.0.0.0 --port=${PORT:-10000}' > /start.sh \
    && chmod +x /start.sh

# Start the application
CMD ["/start.sh"]
