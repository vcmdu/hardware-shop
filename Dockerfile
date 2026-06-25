FROM php:8.3-cli

WORKDIR /app

COPY . .

RUN apt-get update && apt-get install -y \
    unzip \
    git

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install

CMD php -S 0.0.0.0:$PORT -t public