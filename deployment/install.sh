#!/bin/sh
set -eu

install_dir="${WEBLEX_INSTALL_DIR:-/opt/weblexai}"
download_base="${WEBLEX_DOWNLOAD_BASE:-https://github.com/weblexai/weblexai-community/releases/latest/download}"
github_url="${WEBLEX_GITHUB_URL:-https://github.com/weblexai/weblexai-community}"
docs_url="${WEBLEX_DOCS_URL:-https://github.com/weblexai/weblexai-community/tree/main/docs}"
release_feed_url="${WEBLEX_RELEASE_FEED_URL:-https://github.com/weblexai/weblexai-community/releases/latest/download/stable.json}"
release_public_key="${WEBLEX_RELEASE_PUBLIC_KEY:-zmQC1sHMkYYb01WwmEzFpbIYK/hCSra2hQBw+eVWr9M=}"
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

ensure_env_value() {
    key="$1"
    value="$2"

    if ! grep -q "^${key}=" "$install_dir/.env"; then
        printf '%s=%s\n' "$key" "$value" >> "$install_dir/.env"
    fi
}

set_env_value() {
    key="$1"
    value="$2"
    tmp_file="${install_dir}/.env.tmp"

    awk -v key="$key" -v value="$value" '
        BEGIN { replaced = 0 }
        $0 ~ "^" key "=" {
            print key "=" value
            replaced = 1
            next
        }
        { print }
        END {
            if (! replaced) {
                print key "=" value
            }
        }
    ' "$install_dir/.env" > "$tmp_file"
    mv "$tmp_file" "$install_dir/.env"
}

ensure_env_required() {
    key="$1"
    value="$2"

    if ! grep -Eq "^${key}=.+" "$install_dir/.env"; then
        set_env_value "$key" "$value"
    fi
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
WEBLEX_GITHUB_URL=${github_url}
WEBLEX_DOCS_URL=${docs_url}
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

RELEASE_FEED_URL=${release_feed_url}
RELEASE_PUBLIC_KEY=${release_public_key}
UPDATE_CHECK_HOURS=24
UPDATE_DRIVER=docker
UPDATE_AGENT_URL=http://update-agent:8080
UPDATE_AGENT_SECRET=$(random_hex 32)
EOF
fi

ensure_env_required WEBLEX_GITHUB_URL "$github_url"
ensure_env_required WEBLEX_DOCS_URL "$docs_url"
ensure_env_required RELEASE_FEED_URL "$release_feed_url"
ensure_env_required RELEASE_PUBLIC_KEY "$release_public_key"
ensure_env_value UPDATE_CHECK_HOURS "24"
ensure_env_value UPDATE_DRIVER "docker"
ensure_env_required UPDATE_AGENT_URL "http://update-agent:8080"
ensure_env_required UPDATE_AGENT_SECRET "$(random_hex 32)"
chmod 0600 "$install_dir/.env"

cd "$install_dir"
docker compose --profile updates pull app worker scheduler postgres redis update-agent
docker compose --profile updates up -d

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
