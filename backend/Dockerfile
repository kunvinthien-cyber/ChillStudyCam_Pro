FROM php:8.2-cli-alpine

WORKDIR /app

# ដំឡើង Extensions ចាំបាច់សម្រាប់ Laravel & MySQL
RUN apk add --no-cache curl git libzip-dev zip \
    && docker-php-ext-install pdo pdo_mysql

# ទាញយក Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy កូដ Backend ទាំងអស់
COPY . /app

# ដំឡើង Dependencies
RUN composer install --no-dev --optimize-autoloader

# បើក Port
EXPOSE 8000

# បញ្ជាឱ្យ Laravel Serve រត់លើ Port របស់ Render
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
