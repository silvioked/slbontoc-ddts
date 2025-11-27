# Use PHP 8.2
FROM php:8.2-cli

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install NPM dependencies and build assets
RUN npm ci && npm run build

# Create storage and cache directories with proper permissions
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Create QR codes directory
RUN mkdir -p public/qrcodes \
    && chmod -R 775 public/qrcodes

# Copy .env.example to .env if .env doesn't exist (will be overridden by Render env vars)
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Expose port (Render sets PORT environment variable)
EXPOSE 10000

# Create startup script
RUN echo '#!/bin/bash\n\
# Cache Laravel configuration\n\
php artisan config:cache || true\n\
php artisan route:cache || true\n\
php artisan view:cache || true\n\
php artisan storage:link || true\n\
# Start Laravel server on Render PORT\n\
php artisan serve --host=0.0.0.0 --port=${PORT:-10000}' > /start.sh \
    && chmod +x /start.sh

# Start the application
CMD ["/start.sh"]
