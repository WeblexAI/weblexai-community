FROM composer:2.8.9 AS php-dependencies

WORKDIR /app
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/cache \
    composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-req=ext-exif \
    --ignore-platform-req=ext-gd \
    --ignore-platform-req=ext-intl \
    --ignore-platform-req=ext-pcntl
COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative --no-scripts
RUN rm -f bootstrap/cache/*.php \
    && APP_ENV=production \
    APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
    CACHE_STORE=array \
    SESSION_DRIVER=array \
    DB_CONNECTION=sqlite \
    php artisan package:discover --ansi

FROM node:22.16.0-alpine3.22 AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm npm ci
COPY . .
COPY --from=php-dependencies /app/vendor /app/vendor
RUN npm run build

FROM dunglas/frankenphp:1.12.4-php8.4-bookworm

ENV XDG_CONFIG_HOME=/tmp/caddy/config \
    XDG_DATA_HOME=/tmp/caddy/data

RUN install-php-extensions \
    bcmath \
    exif \
    gd \
    intl \
    opcache \
    pcntl \
    pdo_pgsql \
    redis \
    zip

RUN apt-get update \
    && apt-get install -y --no-install-recommends gosu \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY --from=php-dependencies --chown=www-data:www-data /app /app
COPY --from=assets --chown=www-data:www-data /app/public/build /app/public/build
COPY --from=assets --chown=www-data:www-data /app/public/wlai /app/public/wlai

RUN chmod +x /app/entrypoint.sh \
    && mkdir -p /bootstrap /config /tmp/caddy/config /tmp/caddy/data storage/app/private storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && ln -s /config/.env /app/.env \
    && chown -R www-data:www-data /config /tmp/caddy storage bootstrap/cache

EXPOSE 8080
ENTRYPOINT ["/app/entrypoint.sh"]
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8080"]
