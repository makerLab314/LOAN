# 1. Base image with PHP and Composer
FROM php:8.2-fpm

# 2. Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        gd \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 3. Install Composer (if not included)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 4. Set workdir
WORKDIR /var/www/html

# 5. Copy project files
COPY . .

# 6. Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 7. Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 8. Expose port (built-in server) or leave for external webserver
EXPOSE 9000

# 9. Run Laravel via PHP-FPM
CMD ["php-fpm"]