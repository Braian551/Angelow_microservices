# --------------------------------------------------------------------
# Dockerfile del servicio de autenticación (auth-service)
#
# Construye una imagen PHP 8.4 CLI con las extensiones necesarias
# para conectar a PostgreSQL, MySQL, Redis y enviar correos.
#
# Extensiones instaladas:
#   - pdo_mysql / pdo_pgsql : conexión a bases de datos
#   - mbstring / exif       : manejo de cadenas UTF-8 y metadatos
#   - pcntl                 : soporte para procesos hijo (colas)
#   - bcmath                : operaciones matemáticas precisas
#   - gd                    : manipulación de imágenes (avatares)
#   - redis                 : caché y colas con Redis
# --------------------------------------------------------------------
FROM php:8.4-cli

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

# Directorio de trabajo dentro del contenedor
WORKDIR /var/www/html

# Instala dependencias de Composer (primero solo metadatos para cachear capa)
COPY composer.json composer.lock ./
RUN composer install --optimize-autoloader --no-scripts

# Copia el resto del código fuente
COPY . .

# Elimina .env (se inyecta por entorno) y optimiza autoload
RUN rm -f .env && composer dump-autoload --optimize

# Puerto donde corre el servidor embebido de Artisan
EXPOSE 8000

# Comando por defecto: servidor de desarrollo de Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
