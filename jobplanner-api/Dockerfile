# Build stage
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative

# Production stage
FROM php:8.4-cli

ENV APP_ENV=dev
ENV APP_DEBUG=1

RUN apt-get update && apt-get install -y --no-install-recommends \
    libkrb5-dev \
    libpq-dev \
    libzip-dev \
    poppler-utils \
    zip \
    openssl \
    curl \
    && rm -rf /var/lib/apt/lists/* \
    && curl -sL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o /usr/local/bin/install-php-extensions \
    && chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions imap pdo_pgsql pcntl zip

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=composer /app /srv/app
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

WORKDIR /srv/app

# .env minimal + clés JWT pour le build (exclu par .dockerignore)
RUN mkdir -p config/jwt && \
    openssl genrsa -out config/jwt/private.pem 4096 && \
    openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem && \
    echo 'APP_ENV=dev' > .env && \
    echo 'APP_SECRET=build' >> .env && \
    echo 'DATABASE_URL=postgresql://app:app@localhost:5432/app?serverVersion=16' >> .env && \
    echo 'MAILER_DSN=null://null' >> .env && \
    echo 'JWT_SECRET_KEY=/srv/app/config/jwt/private.pem' >> .env && \
    echo 'JWT_PUBLIC_KEY=/srv/app/config/jwt/public.pem' >> .env && \
    echo 'JWT_PASSPHRASE=' >> .env && \
    echo 'CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$' >> .env && \
    echo 'DEFAULT_URI=http://localhost' >> .env

RUN composer install --optimize-autoloader --no-interaction --no-scripts && \
    php bin/console cache:clear --env=dev

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

EXPOSE 8000
ENTRYPOINT ["sh", "/usr/local/bin/entrypoint.sh"]
CMD ["sh", "-c", "php -S 0.0.0.0:8000 -t public"]
