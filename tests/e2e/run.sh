#!/bin/sh
set -eu

root="$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)"
cd "$root"

project="${WEBLEX_E2E_PROJECT:-weblex-e2e}"
port="${WEBLEX_E2E_PORT:-18080}"
env_file="tests/e2e/docker.env.runtime"
cookie_file="tests/e2e/cookies.runtime.txt"
install_html="tests/e2e/install.runtime.html"
install_response="tests/e2e/install-response.runtime.txt"
config_response="tests/e2e/config-response.runtime.json"
translation_response="tests/e2e/translation-response.runtime.ndjson"
translation_payload="tests/e2e/translation-payload.runtime.json"
denied_response="tests/e2e/denied-response.runtime.json"
backup_dir="tests/e2e/backups.runtime"

cp tests/e2e/docker.env.example "$env_file"
chmod 600 "$env_file"
sed -i "s|APP_PORT=.*|APP_PORT=$port|" "$env_file"
sed -i "s|APP_URL=.*|APP_URL=http://localhost:$port|" "$env_file"

compose() {
    docker compose --env-file "$env_file" -p "$project" -f docker-compose.yml -f tests/e2e/docker-compose.e2e.yml "$@"
}

cleanup() {
    if [ "${WEBLEX_E2E_KEEP_ON_FAILURE:-}" = "1" ] && [ "${e2e_succeeded:-0}" != "1" ]; then
        echo "Keeping E2E stack '$project' for inspection."
        return
    fi

    compose down -v --remove-orphans
}
trap cleanup EXIT

wait_for_url() {
    url="$1"
    attempts="${2:-120}"
    i=0

    while [ "$i" -lt "$attempts" ]; do
        if curl -fsS "$url" >/dev/null 2>&1; then
            return 0
        fi

        i=$((i + 1))
        sleep 2
    done

    return 1
}

assert_contains() {
    file="$1"
    pattern="$2"

    if ! grep -F "$pattern" "$file" >/dev/null; then
        echo "Expected $file to contain: $pattern" >&2
        cat "$file" >&2
        exit 1
    fi
}

compose down -v --remove-orphans
compose up -d --build

wait_for_url "http://localhost:$port/install" 180
curl -fsS -c "$cookie_file" "http://localhost:$port/install" > "$install_html"
csrf="$(sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' "$install_html" | head -n 1)"

if [ -z "$csrf" ]; then
    echo "Could not extract installer CSRF token." >&2
    exit 1
fi

curl -sS -i -b "$cookie_file" -c "$cookie_file" \
    -X POST "http://localhost:$port/install" \
    -F "_token=$csrf" \
    -F "app_name=WeblexAI Community Edition" \
    -F "app_url=http://localhost:$port" \
    -F "app_locale=en" \
    -F "app_timezone=UTC" \
    -F "filesystem_disk=public" \
    -F "admin_name=E2E Admin" \
    -F "admin_email=admin@example.test" \
    -F "admin_password=E2e-Password-123!" \
    -F "admin_password_confirmation=E2e-Password-123!" \
    > "$install_response"

assert_contains "$install_response" "HTTP/1.1 302"

wait_for_url "http://localhost:$port/login" 60
compose exec -T app php artisan tinker --execute="require base_path('tests/e2e/prepare.php');"

api_key="$(compose exec -T app cat /tmp/weblex-e2e-api-key | tr -d '\r\n')"

curl -fsS \
    -H "Authorization: Bearer $api_key" \
    -H "Accept: application/json" \
    -H "Origin: http://fixture.test" \
    -H "X-Page-Url: http://fixture.test/products" \
    -H "X-Page-Title: Products" \
    "http://localhost:$port/api/project/config" > "$config_response"
assert_contains "$config_response" '"is_active":true'
assert_contains "$config_response" '"iso_2":"fr"'

printf '%s' '{"source":"en","target":"fr","translatables":[{"id":"headline","text":"Hello world"}]}' > "$translation_payload"
translation_status="$(curl -sS -o "$translation_response" -w "%{http_code}" \
    -H "Authorization: Bearer $api_key" \
    -H "Accept: application/json" \
    -H "Origin: http://fixture.test" \
    -H "X-Page-Url: http://fixture.test/products" \
    -H "X-Page-Title: Products" \
    -H "Content-Type: application/json" \
    --data-binary "@$translation_payload" \
    "http://localhost:$port/api/project/translations")"

if [ "$translation_status" != "200" ]; then
    echo "Expected translation endpoint to return 200, got $translation_status." >&2
    cat "$translation_response" >&2
    exit 1
fi

assert_contains "$translation_response" '"type":"batch"'
assert_contains "$translation_response" 'Mock FR: Hello world'
assert_contains "$translation_response" '"type":"complete"'

denied_status="$(curl -sS -o "$denied_response" -w "%{http_code}" \
    -H "Authorization: Bearer $api_key" \
    -H "Accept: application/json" \
    -H "Origin: http://evil.test" \
    -H "X-Page-Url: http://evil.test/products" \
    "http://localhost:$port/api/project/config")"

if [ "$denied_status" != "401" ]; then
    echo "Expected rejected origin to return 401, got $denied_status." >&2
    cat "$denied_response" >&2
    exit 1
fi

provider_requests="$(compose exec -T mock-provider wget -qO- http://127.0.0.1:8081/stats)"
echo "$provider_requests" | grep -E '"requests": [1-9][0-9]*' >/dev/null

mkdir -p "$backup_dir"
backup_archive="$(COMPOSE_PROJECT_NAME="$project" COMPOSE_FILE="docker-compose.yml:tests/e2e/docker-compose.e2e.yml" WEBLEX_ENV_FILE="$env_file" scripts/backup-docker.sh "$backup_dir")"
compose exec -T app rm -f /app/storage/app/private/e2e-sentinel.txt
COMPOSE_PROJECT_NAME="$project" COMPOSE_FILE="docker-compose.yml:tests/e2e/docker-compose.e2e.yml" WEBLEX_ENV_FILE="$env_file" scripts/restore-docker.sh "$backup_archive"
wait_for_url "http://localhost:$port/login" 120
compose exec -T app test -f /app/storage/app/private/e2e-sentinel.txt
compose exec -T worker php artisan horizon:status
compose exec -T scheduler php artisan schedule:list

e2e_succeeded=1
echo "Docker install, origin auth, translation, backup, restore, worker, and scheduler E2E passed."
