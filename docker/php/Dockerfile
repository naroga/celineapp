FROM php:8.4-fpm-alpine

WORKDIR /srv/app

RUN apk add --no-cache \
        bash \
        curl \
        git \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-client \
        postgresql-dev \
        unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" intl pdo_pgsql opcache zip

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
