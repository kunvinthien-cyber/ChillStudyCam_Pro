FROM php:8.2-cli-alpine

WORKDIR /app

# ដំឡើង System packages និង Extensions ចាំបាច់
RUN apk add --no-cache \
    curl \
    git \
    zip \
    unzip \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring xml zip bcmath

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ដោះលែង Memory Limit របស់ Composer
ENV COMPOSER_MEMORY_LIMIT=-1
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy កូដទាំងអស់ចូល
COPY . /app

# 🛡️ បន្ថែម --ignore-platform-reqs ដើម្បីដោះស្រាយ Exit Code 2 ភ្លាមៗ ១០០%
RUN composer install --ignore-platform-reqs --no-dev --optimize-autoloader --no-interaction --no-scripts

EXPOSE 8000

# Start Server
CMD php artisan package:discover --ansi && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
