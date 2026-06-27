#!/bin/sh
set -eu

role="${CONTAINER_ROLE:-app}"

if [ "$(id -u)" = "0" ]; then
    if [ ! -f /config/.env ]; then
        cp /bootstrap/.env /config/.env
        chmod 600 /config/.env
    fi

    chown www-data:www-data /config/.env
    chown www-data:www-data /config /app/storage /app/bootstrap/cache /tmp/caddy /tmp/caddy/config /tmp/caddy/data
    exec gosu www-data "$0" "$@"
fi

if [ ! -f /config/.env ]; then
    cp /bootstrap/.env /config/.env
    chmod 600 /config/.env
fi

if [ "$role" = "app" ] && ! grep -Eq '^APP_KEY=.+$' .env; then
    app_key="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    temporary="/config/.env.key"

    if grep -q '^APP_KEY=' .env; then
        sed "s|^APP_KEY=.*|APP_KEY=$app_key|" .env > "$temporary"
    else
        cp .env "$temporary"
        printf '\nAPP_KEY=%s\n' "$app_key" >> "$temporary"
    fi

    chmod 600 "$temporary"
    mv "$temporary" /config/.env
fi

if [ "$role" = "worker" ] || [ "$role" = "scheduler" ]; then
    until [ -f storage/app/installed ]; do
        sleep 5
    done
fi

if [ "$role" = "worker" ]; then
    exec php artisan horizon
fi

if [ "$role" = "scheduler" ]; then
    exec php artisan schedule:work
fi

if [ "$role" = "migrate" ]; then
    exec php artisan migrate --force
fi

exec "$@"
