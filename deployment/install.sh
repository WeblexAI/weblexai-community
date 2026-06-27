#!/bin/sh
set -eu

install_dir="${WEBLEX_INSTALL_DIR:-/opt/weblexai}"
download_base="${WEBLEX_DOWNLOAD_BASE:-https://github.com/weblexai/weblexai-community/releases/latest/download}"
port="${WEBLEX_PORT:-8787}"

if [ "$(id -u)" -ne 0 ]; then
    echo "Run this installer as root." >&2
    exit 1
fi

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker is required. Install Docker Engine and run this command again." >&2
    exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
    echo "Docker Compose v2 is required." >&2
    exit 1
fi

if ! command -v curl >/dev/null 2>&1; then
    echo "curl is required." >&2
    exit 1
fi

random_hex() {
    bytes="$1"
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -hex "$bytes"
        return
    fi

    od -An -N "$bytes" -tx1 /dev/urandom | tr -d ' \n'
}

umask 077
mkdir -p "$install_dir/scripts"

curl -fsSL "$download_base/docker-compose.yml" -o "$install_dir/docker-compose.yml"
curl -fsSL "$download_base/scripts/backup-docker.sh" -o "$install_dir/scripts/backup-docker.sh"
curl -fsSL "$download_base/scripts/restore-docker.sh" -o "$install_dir/scripts/restore-docker.sh"
chmod 0700 "$install_dir/scripts/backup-docker.sh" "$install_dir/scripts/restore-docker.sh"

if [ -f "$install_dir/.env" ]; then
    configured_port="$(sed -n 's/^APP_PORT=//p' "$install_dir/.env" | tr -d '"')"
    port="${configured_port:-$port}"
    echo "Using the existing configuration at $install_dir."
else
    if command -v ss >/dev/null 2>&1; then
        while ss -ltn | awk '{print $4}' | grep -Eq ":${port}$"; do
            port=$((port + 1))
        done
    fi

    database_suffix="$(random_hex 6)"
    database_user_suffix="$(random_hex 4)"

    cat > "$install_dir/.env" <<EOF
APP_NAME="WeblexAI Community Edition"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost:${port}
APP_INSTALLED=false
APP_VERSION=stable
APP_PORT=${port}
APP_LOCALE=en
APP_TIMEZONE=UTC

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=weblex_${database_suffix}
DB_USERNAME=weblex_${database_user_suffix}
DB_PASSWORD=$(random_hex 32)

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0

CACHE_STORE=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
FILESYSTEM_DISK=public
MEDIA_DISK=public

ERROR_REPORTING_ENABLED=false
ERROR_REPORTING_WEBHOOK_URL=
ERROR_REPORTING_WEBHOOK_SECRET=
ERROR_REPORTING_TELEGRAM_BOT_TOKEN=
ERROR_REPORTING_TELEGRAM_CHAT_ID=

UPDATE_DRIVER=docker
UPDATE_AGENT_URL=http://update-agent:8080
UPDATE_AGENT_SECRET=$(random_hex 32)
EOF
fi

cd "$install_dir"
docker compose pull app worker scheduler postgres redis
docker compose up -d

attempt=0
until curl -fsS "http://127.0.0.1:${port}/up" >/dev/null 2>&1; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 60 ]; then
        echo "WeblexAI did not become healthy. Run: cd $install_dir && docker compose logs app" >&2
        exit 1
    fi
    sleep 2
done

server_address="$(hostname -I 2>/dev/null | awk '{print $1}')"
server_address="${server_address:-localhost}"

echo
echo "WeblexAI is running."
echo "Open http://${server_address}:${port}/install to finish setup."
