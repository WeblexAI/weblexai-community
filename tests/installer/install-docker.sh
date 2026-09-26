#!/usr/bin/env bash

set -Eeuo pipefail
IFS=$'\n\t'

test_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
test_directory="$(mktemp -d)"
fake_bin="$test_directory/bin"
fake_log="$test_directory/curl.log"
docker_log="$test_directory/docker.log"
fixture_root="$test_root"

cleanup() {
    rm -rf "$test_directory"
}
trap cleanup EXIT

mkdir -p "$fake_bin"

cat > "$fake_bin/curl" <<'EOF'
#!/usr/bin/env bash
set -Eeuo pipefail

output=''
url=''
while [[ $# -gt 0 ]]; do
    case "$1" in
        --output)
            output="$2"
            shift 2
            ;;
        *)
            url="$1"
            shift
            ;;
    esac
done

printf '%s\n' "$url" >> "$TEST_CURL_LOG"
case "$url" in
    */docker-compose.yml)
        cp "$TEST_FIXTURE_ROOT/docker-compose.yml" "$output"
        ;;
    */.env.example)
        cp "$TEST_FIXTURE_ROOT/.env.example" "$output"
        ;;
    *)
        printf 'Unexpected URL: %s\n' "$url" >&2
        exit 1
        ;;
esac
EOF

cat > "$fake_bin/docker" <<'EOF'
#!/usr/bin/env bash
set -Eeuo pipefail

printf '%s\n' "$*" >> "$TEST_DOCKER_LOG"
exit 0
EOF

chmod +x "$fake_bin/curl" "$fake_bin/docker"

export PATH="$fake_bin:$PATH"
export TEST_CURL_LOG="$fake_log"
export TEST_DOCKER_LOG="$docker_log"
export TEST_FIXTURE_ROOT="$fixture_root"

assert_contains() {
    local file="$1"
    local expected="$2"

    grep -Fq "$expected" "$file" || {
        printf 'Expected %s to contain: %s\n' "$file" "$expected" >&2
        exit 1
    }
}

assert_not_contains() {
    local file="$1"
    local unexpected="$2"

    if grep -Fq "$unexpected" "$file"; then
        printf 'Expected %s not to contain: %s\n' "$file" "$unexpected" >&2
        exit 1
    fi
}

run_installer() {
    bash "$test_root/scripts/install-docker.sh" "$@"
}

default_directory="$test_directory/default"
default_output="$test_directory/default-output.log"
run_installer --directory "$default_directory" > "$default_output" 2>&1

assert_contains "$default_directory/.env" 'APP_VERSION=latest'
assert_contains "$default_directory/.env" 'APP_URL=http://localhost:8787'
assert_contains "$default_directory/.env" 'DB_PASSWORD='
assert_contains "$fake_log" '/main/docker-compose.yml'
assert_contains "$fake_log" '/main/.env.example'

database_password="$(awk -F= '$1 == "DB_PASSWORD" { print $2 }' "$default_directory/.env")"
[[ "$database_password" =~ ^[a-f0-9]{64}$ ]] || {
    printf 'Generated database password has an unexpected format.\n' >&2
    exit 1
}
[[ "$(stat -c '%a' "$default_directory/.env")" == '600' ]] || {
    printf 'Generated .env does not have restrictive permissions.\n' >&2
    exit 1
}
assert_not_contains "$default_output" "$database_password"

if run_installer --directory "$default_directory" > "$test_directory/reinstall-output.log" 2>&1; then
    printf 'Reinstall unexpectedly succeeded over an existing .env.\n' >&2
    exit 1
fi

assert_contains "$test_directory/reinstall-output.log" 'An existing .env was found'

pinned_directory="$test_directory/pinned"
run_installer --version v1.0.0 --directory "$pinned_directory" > "$test_directory/pinned-output.log" 2>&1

assert_contains "$pinned_directory/.env" 'APP_VERSION=1.0.0'
assert_contains "$pinned_directory/.env" 'APP_URL=http://localhost:8787'
assert_contains "$fake_log" '/v1.0.0/docker-compose.yml'
assert_contains "$fake_log" '/v1.0.0/.env.example'

: > "$docker_log"
run_installer update --version 1.0.1 --directory "$default_directory" > "$test_directory/update-output.log" 2>&1

assert_contains "$default_directory/.env" 'APP_VERSION=1.0.1'
assert_contains "$default_directory/.env" 'APP_URL=http://localhost:8787'
assert_contains "$fake_log" '/v1.0.1/docker-compose.yml'
updated_password="$(awk -F= '$1 == "DB_PASSWORD" { print $2 }' "$default_directory/.env")"
[[ "$updated_password" == "$database_password" ]] || {
    printf 'Update changed the existing database password.\n' >&2
    exit 1
}

pull_line="$(grep -n ' pull$' "$docker_log" | tail -n 1 | cut -d: -f1)"
migrate_line="$(grep -n -- '--profile tools run --rm migrate$' "$docker_log" | tail -n 1 | cut -d: -f1)"
up_line="$(grep -n ' up -d$' "$docker_log" | tail -n 1 | cut -d: -f1)"

[[ -n "$pull_line" && -n "$migrate_line" && -n "$up_line" ]] || {
    printf 'Update did not run the expected Compose commands.\n' >&2
    exit 1
}

(( pull_line < migrate_line && migrate_line < up_line )) || {
    printf 'Update ran Compose commands in the wrong order.\n' >&2
    exit 1
}

printf 'Installer smoke tests passed.\n'
