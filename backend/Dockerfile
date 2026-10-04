FROM php:8.2-cli-alpine

WORKDIR /app

# ដំឡើង System packages និង Extensions ចាំបាច់សម្រាប់ Laravel 11
RUN apk add --no-cache \
    curl \
    git \
    zip \
    unzip \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring xml zip bcmath

# Copy Composer ផ្លូវការ
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_MEMORY_LIMIT=-1
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy កូដទាំងអស់ចូល
COPY . /app

# ដំឡើង dependencies ដោយសុវត្ថិភាព
RUN composer install --ignore-platform-reqs --no-dev --optimize-autoloader --no-interaction --no-scripts

EXPOSE 8000

# ដំណើរការ Laravel 11 លើ Port របស់ Render
CMD php artisan package:discover --ansi && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
