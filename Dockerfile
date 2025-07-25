FROM php:8.4-fpm
RUN apt-get update -y && apt-get install -y \
    build-essential \
    openssl \
    zip \
    unzip \
    git \
    libpq-dev \
    nano \
    supervisor \
    && docker-php-ext-install pdo pdo_pgsql exif pcntl bcmath opcache \
    && pecl install redis \
    && docker-php-ext-enable redis


RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
WORKDIR /hireverse
COPY . /hireverse

# Create log directory
RUN mkdir -p /hireverse/storage/logs
RUN composer update --with-all-dependencies --no-scripts \
    && composer install --no-scripts \
    && composer dump-autoload -o
# Change CMD to handle migrations, seeding, and supervisor
CMD php artisan migrate:fresh --seed && supervisord -c /etc/supervisor/supervisord.conf && php artisan storage:link && php artisan serve --host=0.0.0.0 --port=8000
EXPOSE 8000