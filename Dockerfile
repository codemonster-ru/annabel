FROM php:8.3-cli-alpine

WORKDIR /app

RUN apk add --no-cache git unzip \
    && docker-php-ext-install pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

EXPOSE 8000

CMD ["sh", "-lc", "composer install && php -S 0.0.0.0:8000 -t public"]
