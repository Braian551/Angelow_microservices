# Dockerfile del cart-service (PHP 8.4)
# Build optimizado para producción: extensión Redis, PostgreSQL, MySQL.
# Expone el puerto 8000 y ejecuta Artisan serve como servidor de desarrollo.

FROM php:8.4-cli

# Instala dependencias del sistema y extensiones PHP
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copia Composer desde la imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Instala dependencias de producción primero (capa cacheable)
COPY composer.json composer.lock ./
RUN composer install --optimize-autoloader --no-scripts

# Copia el código fuente
COPY . .

# Limpia .env de desarrollo y optimiza autoload
RUN rm -f .env && composer dump-autoload --optimize

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
