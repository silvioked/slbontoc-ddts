FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \       
    curl \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql zip gd

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Node.js & npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Set working directory
WORKDIR /var/www

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install JS dependencies
RUN npm install

# Build frontend assets (for production)
RUN npm run build

# Create required directories
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache public/qrcodes

# Set proper permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache public/qrcodes

# Create storage symlink
RUN php artisan storage:link || true

# Optimize Laravel
RUN php artisan optimize


# Clear Route Cache
RUN php artisan route:cache

# Expose port (Render will map to $PORT dynamically)
EXPOSE 10000

# Start Laravel (uses $PORT environment variable from Render)
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT