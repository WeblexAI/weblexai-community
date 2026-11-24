FROM dunglas/frankenphp:1.13.6-php8.4.28-bookworm AS php-base

RUN php -r 'if (version_compare(PHP_VERSION, "8.4.28", "<") || version_compare(PHP_VERSION, "8.5.0", ">=")) { fwrite(STDERR, "Build requires patched PHP 8.4.28 or newer within 8.4.x; found ".PHP_VERSION.PHP_EOL); exit(1); } echo "Verified PHP ".PHP_VERSION.PHP_EOL;'

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

FROM php-base AS php-dependencies

COPY --from=composer:2.8.9 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
ARG WEBLEX_E2E_QWEN_ENDPOINT=
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/cache \
    composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --no-autoloader
COPY . .
RUN if [ -n "$WEBLEX_E2E_QWEN_ENDPOINT" ]; then \
    sed -i "/'qwen' => \[/,/^[[:space:]]*],/ s#'url' => '[^']*'#'url' => '$WEBLEX_E2E_QWEN_ENDPOINT'#" config/ai.php; \
fi
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative --no-scripts
RUN rm -f bootstrap/cache/*.php \
    && APP_ENV=production \
    APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
    CACHE_STORE=array \
    SESSION_DRIVER=array \
    DB_CONNECTION=sqlite \
    php artisan package:discover --ansi

FROM node:22.23.5-alpine3.23 AS assets

RUN node -e 'const [major, minor, patch] = process.versions.node.split(".").map(Number); if (major !== 22 || minor < 23 || (minor === 23 && patch < 5)) { console.error("Build requires patched Node 22.23.5 or newer within 22.x; found " + process.versions.node); process.exit(1); } console.log("Verified Node " + process.versions.node);'

WORKDIR /app
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm npm ci
COPY . .
COPY --from=php-dependencies /app/vendor /app/vendor
RUN npm run build

FROM php-base

ENV XDG_CONFIG_HOME=/tmp/caddy/config \
    XDG_DATA_HOME=/tmp/caddy/data

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl gnupg gosu \
    && install -d -m 0755 /usr/share/postgresql-common/pgdg \
    && curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.gpg \
    && echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.gpg] https://apt.postgresql.org/pub/repos/apt bookworm-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends postgresql-client-17 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY --from=php-dependencies --chown=www-data:www-data /app /app
COPY --from=assets --chown=www-data:www-data /app/public/build /app/public/build
COPY --from=assets --chown=www-data:www-data /app/public/wlai /app/public/wlai

RUN chmod +x /app/entrypoint.sh \
    && mkdir -p /backups /bootstrap /config /tmp/caddy/config /tmp/caddy/data storage/app/backups storage/app/private storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && ln -s /config/.env /app/.env \
    && rm -rf /app/public/storage \
    && ln -s /app/storage/app/public /app/public/storage \
    && chown -R www-data:www-data /backups /config /tmp/caddy storage bootstrap/cache

EXPOSE 8080
ENTRYPOINT ["/app/entrypoint.sh"]
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8080"]
