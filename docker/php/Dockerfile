ARG PHP_VERSION=8.4

FROM php:${PHP_VERSION}-cli-alpine

RUN apk add --no-cache \
        bash \
        bubblewrap \
        ca-certificates \
        curl \
        fd \
        git \
        icu-dev \
        jq \
        linux-headers \
        make \
        nodejs \
        npm \
        oniguruma-dev \
        openssh-client \
        procps \
        python3 \
        ripgrep \
        unzip \
        zip \
    && docker-php-ext-install \
        intl \
        mbstring \
        pcntl \
        pdo_mysql

RUN npm install -g @openai/codex

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/serve-skeleton /usr/local/bin/serve-skeleton

WORKDIR /workspace

EXPOSE 8000

CMD ["sh", "-lc", "if [ ! -f vendor/autoload.php ]; then composer install --no-interaction --prefer-dist; fi; exec tail -f /dev/null"]
