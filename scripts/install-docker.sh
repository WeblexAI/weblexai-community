#!/usr/bin/env bash

set -Eeuo pipefail
IFS=$'\n\t'
umask 077

readonly repository_url="https://raw.githubusercontent.com/WeblexAI/weblexai-community"

mode="install"
version="latest"
directory="$PWD/weblexai"

usage() {
    cat <<'EOF'
Usage:
  install-docker.sh [install] [options]
  install-docker.sh update [options]

Options:
  --version VERSION   Use latest or a semver release such as 1.0.0
  --directory PATH    Deployment directory (default: ./weblexai)
  -h, --help          Show this help
EOF
}

fail() {
    printf 'Error: %s\n' "$1" >&2
    exit 1
}

require_value() {
    if [[ $# -lt 2 || -z "$2" ]]; then
        fail "${1} requires a value."
    fi
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        install)
            mode="install"
            shift
            ;;
        update)
            mode="update"
            shift
            ;;
        --version)
            require_value "$@"
            version="$2"
            shift 2
            ;;
        --version=*)
            version="${1#*=}"
            shift
            ;;
        --directory)
            require_value "$@"
            directory="$2"
            shift 2
            ;;
        --directory=*)
            directory="${1#*=}"
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            fail "Unknown argument: $1"
            ;;
    esac
done

if [[ "$version" == "latest" ]]; then
    source_ref="main"
    docker_version="latest"
elif [[ "$version" =~ ^v?([0-9]+\.[0-9]+\.[0-9]+)$ ]]; then
    docker_version="${BASH_REMATCH[1]}"
    source_ref="v${docker_version}"
else
    fail 'Version must be latest or a semver value such as 1.0.0.'
fi

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "Required command not found: $1"
}

require_command curl
require_command docker

if ! docker compose version >/dev/null 2>&1; then
    fail 'Docker Compose v2 is required.'
fi

if ! docker info >/dev/null 2>&1; then
    fail 'The Docker daemon is not running or is not accessible.'
fi

directory="$(mkdir -p "$directory" && cd "$directory" && pwd)"
compose_file="$directory/docker-compose.yml"
env_file="$directory/.env"

download() {
    local url="$1"
    local destination="$2"

    curl --fail --location --silent --show-error --retry 3 --output "$destination" "$url"
}

set_env_value() {
    local file="$1"
    local key="$2"
    local value="$3"
    local temporary="${file}.tmp.$$"

    awk -v key="$key" -v value="$value" '
        BEGIN { updated = 0 }
        index($0, key "=") == 1 {
            print key "=" value
            updated = 1
            next
        }
        { print }
        END {
            if (! updated) {
                print key "=" value
            }
        }
    ' "$file" > "$temporary"

    mv "$temporary" "$file"
}

compose() {
    docker compose \
        --project-directory "$directory" \
        --env-file "$env_file" \
        --file "$compose_file" \
        "$@"
}

validate_compose() {
    compose config --quiet || fail 'The generated Docker Compose configuration is invalid.'
}

source_url="${repository_url}/${source_ref}"
temporary_directory="$directory/.weblexai-install.$$"
cleanup() {
    rm -rf "$temporary_directory"
}
trap cleanup EXIT

if [[ "$mode" == "install" ]]; then
    if [[ -e "$env_file" ]]; then
        fail "An existing .env was found in $directory. Choose another directory or use update mode."
    fi

    if [[ -e "$compose_file" ]]; then
        fail "An existing docker-compose.yml was found in $directory. Choose another directory or use update mode."
    fi

    require_command openssl
    mkdir -p "$temporary_directory"

    printf 'Downloading WeblexAI %s deployment files...\n' "$docker_version"
    download "${source_url}/docker-compose.yml" "$temporary_directory/docker-compose.yml"
    download "${source_url}/.env.example" "$temporary_directory/.env.example"
    cp "$temporary_directory/.env.example" "$temporary_directory/.env"

    database_password="$(openssl rand -hex 32)"
    [[ "$database_password" =~ ^[a-f0-9]{64}$ ]] || fail 'Unable to generate a database password.'

    set_env_value "$temporary_directory/.env" APP_VERSION "$docker_version"
    set_env_value "$temporary_directory/.env" DB_PASSWORD "$database_password"
    chmod 600 "$temporary_directory/.env"

    compose_file="$temporary_directory/docker-compose.yml"
    env_file="$temporary_directory/.env"
    validate_compose

    mv "$temporary_directory/docker-compose.yml" "$directory/docker-compose.yml"
    mv "$temporary_directory/.env" "$directory/.env"
    compose_file="$directory/docker-compose.yml"
    env_file="$directory/.env"

    printf 'Pulling Docker images...\n'
    compose pull
    printf 'Starting WeblexAI...\n'
    compose up -d
    printf 'WeblexAI is starting at http://localhost:8787/install\n'
    exit 0
fi

if [[ ! -f "$env_file" || ! -f "$compose_file" ]]; then
    fail "No WeblexAI deployment was found in $directory. Run install mode first."
fi

mkdir -p "$temporary_directory"
printf 'Downloading WeblexAI %s deployment files...\n' "$docker_version"
download "${source_url}/docker-compose.yml" "$temporary_directory/docker-compose.yml"
cp "$env_file" "$temporary_directory/.env"
set_env_value "$temporary_directory/.env" APP_VERSION "$docker_version"
chmod 600 "$temporary_directory/.env"

compose_file="$temporary_directory/docker-compose.yml"
env_file="$temporary_directory/.env"
validate_compose

mv "$temporary_directory/docker-compose.yml" "$directory/docker-compose.yml"
mv "$temporary_directory/.env" "$directory/.env"
compose_file="$directory/docker-compose.yml"
env_file="$directory/.env"

printf 'Pulling Docker images...\n'
compose pull
printf 'Running database migrations...\n'
compose --profile tools run --rm migrate
printf 'Starting WeblexAI...\n'
compose up -d
printf 'WeblexAI was updated in %s\n' "$directory"
