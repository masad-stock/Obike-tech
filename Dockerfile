# Use the official PHP image with necessary extensions
FROM php:8.2-cli

# Install system dependencies and PHP extensions
RUN apt-get update \
    && apt-get install -y libpq-dev git unzip curl libzip-dev libpng-dev libonig-dev libbrotli-dev \
    && docker-php-ext-install pdo pdo_pgsql zip mbstring exif pcntl bcmath gd

# Install Swoole for Octane
RUN pecl install swoole \
    && docker-php-ext-enable swoole

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Install Node.js (LTS)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Set working directory
WORKDIR /var/www/html

# Copy the entire application code (including artisan)
COPY . .

# Install dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Make build.sh executable and run it
RUN chmod +x build.sh && ./build.sh

# Expose port for Octane
EXPOSE 8080

# Start Laravel Octane
CMD ["php", "artisan", "octane:start", "--host=0.0.0.0", "--port=8080"]
