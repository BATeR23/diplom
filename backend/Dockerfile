FROM dunglas/frankenphp:php8.4-bookworm

# Устанавливаем unzip и zip расширение
RUN apt-get update && apt-get install -y unzip && \
    install-php-extensions zip pdo_mysql

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Устанавливаем Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && \
    apt-get install -y nodejs

WORKDIR /app

COPY . .

RUN composer install --no-dev --optimize-autoloader

# Устанавливаем недостающие npm пакеты
RUN npm install && npm install laravel-echo pusher-js --save && npm run build

CMD php artisan serve --host=0.0.0.0 --port=$PORT
