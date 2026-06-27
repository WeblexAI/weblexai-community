#!/bin/sh
set -eu

destination="${1:?Usage: backup-docker.sh DESTINATION}"
timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
work="$destination/weblex-$timestamp"
mkdir -p "$work"

compose() {
    if [ -n "${WEBLEX_ENV_FILE:-}" ]; then
        docker compose --env-file "$WEBLEX_ENV_FILE" "$@"
    else
        docker compose "$@"
    fi
}

compose exec -T postgres sh -c 'pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB"' > "$work/database.sql"
compose exec -T app tar -C /app -czf - storage > "$work/storage.tar.gz"
compose exec -T app cat /app/.env > "$work/.env"
tar -C "$destination" -czf "$work.tar.gz" "weblex-$timestamp"
rm -rf "$work"
printf '%s\n' "$work.tar.gz"
