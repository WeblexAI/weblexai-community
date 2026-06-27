# Traditional Ubuntu Installation

The reference platform is Ubuntu 24.04 LTS with PHP 8.4, FrankenPHP 1.x, PostgreSQL 14+, Redis 6+, Node.js 22, Composer 2, and systemd.

## Install

1. Create a dedicated `weblex` system user and `/srv/weblex`.
2. Install PHP extensions: `bcmath`, `curl`, `exif`, `gd`, `intl`, `mbstring`, `openssl`, `pcntl`, `pdo_pgsql`, `redis`, `sodium`, and `zip`.
3. Provision a PostgreSQL database and Redis instance that are not publicly reachable.
4. Clone the repository to `/srv/weblex/releases/1.0.0`.
5. Run:

```bash
composer install --no-dev --classmap-authoritative
npm ci
npm run build
mkdir -p /srv/weblex/shared
cp .env.example /srv/weblex/shared/.env
ln -s /srv/weblex/shared/.env .env
chown -R weblex:weblex storage bootstrap/cache /srv/weblex/shared
chmod 640 /srv/weblex/shared/.env
sudo -u weblex php artisan key:generate
```

6. Point `/srv/weblex/current` to the release directory.
7. Start FrankenPHP and open `/install`.

The web user needs write access only to `/srv/weblex/shared`, `storage`, and `bootstrap/cache`. Remove group write access from `.env` after installation when configuration is managed externally.

## FrankenPHP

Use the provided [Caddyfile](../deployment/Caddyfile). Terminate TLS in FrankenPHP or in a trusted reverse proxy. When proxying, preserve `Host`, `Origin`, `X-Forwarded-Proto`, and the client address.

## systemd

Copy the units in `deployment/systemd` to `/etc/systemd/system`, adjust the user and path if needed, then run:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now weblex-app weblex-horizon weblex-scheduler
```

Only one scheduler process should run. Horizon workers may be scaled horizontally. Run migrations once per release, never from every application replica.

## Upgrade

Create a backup, place the new release in a versioned directory, install dependencies, verify its release signature and checksum, and run:

```bash
php artisan weblex:update --driver=traditional
```

See [updates.md](updates.md) for permissions, rollback behavior, and supported release layout.
