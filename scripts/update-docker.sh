#!/bin/sh
set -eu

version="${1:?Version is required}"
case "$version" in
    *[!0-9.]*|'') exit 2 ;;
esac

lock="/backups/update.lock"
if ! mkdir "$lock" 2>/dev/null; then
    echo "Another update is running."
    exit 1
fi
trap 'rmdir "$lock"' EXIT

previous="$(sed -n 's/^APP_VERSION=//p' .env | tr -d '"')"
previous="${previous:-1.0.0}"
/workspace/scripts/backup-docker.sh /backups

temporary=".env.update"
sed "s/^APP_VERSION=.*/APP_VERSION=$version/" .env > "$temporary"
if ! grep -q '^APP_VERSION=' "$temporary"; then
    printf '\nAPP_VERSION=%s\n' "$version" >> "$temporary"
fi
mv "$temporary" .env

if docker compose pull app worker scheduler \
    && docker compose --profile tools run --rm migrate \
    && docker compose up -d --no-build app worker scheduler \
    && docker compose ps --status running app | grep -q app; then
    echo "Updated to $version."
    exit 0
fi

sed "s/^APP_VERSION=.*/APP_VERSION=$previous/" .env > "$temporary"
mv "$temporary" .env
docker compose up -d --no-build app worker scheduler
echo "Update failed; containers were returned to $previous. Review database migration compatibility."
exit 1
