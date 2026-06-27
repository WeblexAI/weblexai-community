#!/bin/sh
set -eu

destination="${1:?Usage: backup-traditional.sh DESTINATION APPLICATION_PATH}"
application="${2:?Usage: backup-traditional.sh DESTINATION APPLICATION_PATH}"
timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
archive="$destination/weblex-$timestamp.tar.gz"
mkdir -p "$destination"

set -a
. "$application/.env"
set +a
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
PGPASSWORD="$DB_PASSWORD" pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" "$DB_DATABASE" > "$work/database.sql"
cp "$application/.env" "$work/.env"
tar -C "$application" -czf "$work/storage.tar.gz" storage
tar -C "$work" -czf "$archive" .
printf '%s\n' "$archive"
