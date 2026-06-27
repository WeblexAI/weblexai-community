#!/bin/sh
set -eu

archive="${1:?Usage: restore-docker.sh BACKUP_ARCHIVE}"
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
tar -xzf "$archive" -C "$work"
backup="$(find "$work" -mindepth 1 -maxdepth 1 -type d | head -n 1)"

compose() {
    if [ -n "${WEBLEX_ENV_FILE:-}" ]; then
        docker compose --env-file "$WEBLEX_ENV_FILE" "$@"
    else
        docker compose "$@"
    fi
}

compose stop app worker scheduler
compose exec -T postgres sh -c 'dropdb -U "$POSTGRES_USER" --if-exists "$POSTGRES_DB"; createdb -U "$POSTGRES_USER" "$POSTGRES_DB"'
compose exec -T postgres sh -c 'psql -U "$POSTGRES_USER" "$POSTGRES_DB"' < "$backup/database.sql"
compose run --rm --no-deps --entrypoint sh app -c 'cat > /config/.env' < "$backup/.env"
compose run --rm --no-deps --entrypoint sh app -c 'tar -C /app -xzf -' < "$backup/storage.tar.gz"
compose up -d
