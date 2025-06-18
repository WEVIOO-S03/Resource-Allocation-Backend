FROM php:8.3-fpm

WORKDIR /var/www

# Install dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev \
    libpq-dev libzip-dev zip unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy application
COPY . .

# Install dependencies
RUN composer install --optimize-autoloader --no-interaction

# Generate JWT keys
RUN mkdir -p config/jwt && \
    openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:yourpassphrase && \
    openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:yourpassphrase

# Set permissions
RUN chown -R www-data:www-data /var/www

CMD ["php-fpm"]