#!/bin/sh
set -eu

role="${CONTAINER_ROLE:-app}"

if [ "$(id -u)" = "0" ]; then
    if [ ! -f /config/.env ]; then
        cp /bootstrap/.env /config/.env
        chmod 600 /config/.env
    fi

    chown www-data:www-data /config/.env
    mkdir -p /backups /app/storage/app/backups
    chown www-data:www-data /backups /config /app/storage /app/storage/app/backups /app/bootstrap/cache /tmp/caddy /tmp/caddy/config /tmp/caddy/data
    exec gosu www-data "$0" "$@"
fi

mkdir -p storage/app/public
if [ -e public/storage ] && [ ! -L public/storage ]; then
    rm -rf public/storage
fi
ln -sfn /app/storage/app/public public/storage

if [ ! -f /config/.env ]; then
    cp /bootstrap/.env /config/.env
    chmod 600 /config/.env
fi

set_env_value() {
    key="$1"
    value="$2"
    temporary="/config/.env.${key}.tmp"

    if grep -q "^${key}=" /config/.env; then
        sed "s|^${key}=.*|${key}=${value}|" /config/.env > "$temporary"
    else
        cp /config/.env "$temporary"
        printf '\n%s=%s\n' "$key" "$value" >> "$temporary"
    fi

    chmod 600 "$temporary"
    mv "$temporary" /config/.env
}

if ! grep -q '^LOG_CHANNEL=' /config/.env; then
    set_env_value LOG_CHANNEL stack
fi

if ! grep -q '^LOG_STACK=' /config/.env || grep -q '^LOG_STACK=single$' /config/.env; then
    set_env_value LOG_STACK daily
fi

if ! grep -q '^LOG_DAILY_DAYS=' /config/.env; then
    set_env_value LOG_DAILY_DAYS 30
fi

if ! grep -q '^BACKUP_DISK=' /config/.env || grep -q '^BACKUP_DISK=local$' /config/.env; then
    set_env_value BACKUP_DISK backups
fi

if ! grep -Eq '^BACKUP_PATH=.+$' /config/.env; then
    set_env_value BACKUP_PATH /backups
fi

if ! grep -Eq '^RELEASE_FEED_URL=.+$' /config/.env; then
    set_env_value RELEASE_FEED_URL https://github.com/weblexai/weblexai-community/releases/latest/download/stable.json
fi

if ! grep -Eq '^RELEASE_PUBLIC_KEY=.+$' /config/.env; then
    set_env_value RELEASE_PUBLIC_KEY zmQC1sHMkYYb01WwmEzFpbIYK/hCSra2hQBw+eVWr9M=
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
