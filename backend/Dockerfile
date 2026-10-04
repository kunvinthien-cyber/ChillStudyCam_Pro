FROM php:8.2-cli-alpine

WORKDIR /app

# ដំឡើង System packages និង PHP extensions គ្រប់ទាំងអស់ដែល Laravel 11 ត្រូវការ
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

# Copy កូដ Backend ទាំងអស់
COPY . /app

# 🛡️ បន្ថែម --no-scripts ដើម្បីកុំឱ្យ Composer រត់ Script មុនពេលមាន .env (ដោះស្រាយ Exit Code 2)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

EXPOSE 8000

# ពេល Server ចាប់ផ្តើម ទើបឱ្យវា Discover packages និង Serve
CMD php artisan package:discover --ansi && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
