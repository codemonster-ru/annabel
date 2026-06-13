FROM php:8.3-fpm-bookworm

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        bash \
        autoconf \
        ca-certificates \
        curl \
        fd-find \
        g++ \
        git \
        gnupg \
        jq \
        make \
        openssh-client \
        perl \
        pkg-config \
        procps \
        python-is-python3 \
        python3 \
        python3-pip \
        ripgrep \
        unzip \
    && install -d -m 0755 /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key -o /etc/apt/keyrings/nodesource.asc \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.asc] https://deb.nodesource.com/node_22.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends nodejs \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && ln -sf /usr/bin/fdfind /usr/local/bin/fd \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

EXPOSE 9000

CMD ["sh", "-lc", "mkdir -p /app/storage/sessions && if chown www-data:www-data /app/storage/sessions; then chmod 2770 /app/storage/sessions; else chmod 2777 /app/storage/sessions; fi && if [ ! -f vendor/autoload.php ]; then composer install; fi && if [ ! -x node_modules/.bin/vite ]; then npm ci; fi && npm run build && exec php-fpm"]
