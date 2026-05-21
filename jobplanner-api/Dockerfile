# Build stage
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative

# Runtime stage
FROM dunglas/frankenphp:1-php8.4

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV SERVER_NAME=:8000

RUN apt-get update && apt-get install -y --no-install-recommends \
    libkrb5-dev \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    poppler-utils \
    zip \
    openssl \
    curl \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions imap pdo_pgsql pcntl zip intl opcache

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=composer /app /srv/app
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /srv/app

RUN mkdir -p config/jwt && \
    openssl genrsa -out config/jwt/private.pem 4096 && \
    openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem && \
    echo 'APP_ENV=prod' > .env && \
    echo 'APP_SECRET=build' >> .env && \
    echo 'DATABASE_URL=postgresql://app:app@localhost:5432/app?serverVersion=16' >> .env && \
    echo 'MAILER_DSN=null://null' >> .env && \
    echo 'JWT_SECRET_KEY=/srv/app/config/jwt/private.pem' >> .env && \
    echo 'JWT_PUBLIC_KEY=/srv/app/config/jwt/public.pem' >> .env && \
    echo 'JWT_PASSPHRASE=' >> .env && \
    echo 'CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$' >> .env && \
    echo 'DEFAULT_URI=http://localhost' >> .env

RUN composer install --optimize-autoloader --no-interaction --no-scripts && \
    APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear --env=prod --no-debug && \
    APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup --env=prod --no-debug && \
    chmod -R a+rwX var

EXPOSE 8000
ENTRYPOINT ["sh", "/usr/local/bin/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
