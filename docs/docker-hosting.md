# Docker Hosting

WeblexAI runs as one Docker Compose stack:

| Service | Host access |
| --- | --- |
| WeblexAI app | `APP_PORT` (8787 by default) |
| PostgreSQL | Private Compose network |
| Redis | Private Compose network |
| Worker and scheduler | Private Compose network |

## Start the stack

The published image is available at `kofibusy/weblexai`. You do not need to clone the repository to use the full Compose stack. Download the deployment files into a new directory:

```bash
mkdir weblexai && cd weblexai
curl -fsSL https://raw.githubusercontent.com/WeblexAI/weblexai-community/main/docker-compose.yml -o docker-compose.yml
curl -fsSL https://raw.githubusercontent.com/WeblexAI/weblexai-community/main/.env.example -o .env.example
cp .env.example .env
```

From a directory containing `docker-compose.yml` and `.env`:

```bash
cp .env.example .env
```

Set a strong `DB_PASSWORD` and review `APP_URL`, `APP_PORT`, `DB_DATABASE`, and `DB_USERNAME`. Then run:

```bash
docker compose pull
docker compose up -d
```

Open `http://localhost:8787/install`, or use the configured `APP_URL` from a remote browser, to create the first administrator.

PostgreSQL, Redis, application storage, backups, and the persistent application environment use named Docker volumes. Do not publish PostgreSQL or Redis ports to the host.

You can also run the application image directly with `docker run` without mounting an `.env`; it falls back to the bundled `.env.example` template. This requires PostgreSQL and Redis to be reachable from the container, with their connection settings passed as container environment variables. Compose provisions the complete stack automatically, so it is the recommended no-clone setup.

## Custom domains

Use an external reverse proxy or deployment platform for public access. Point DNS at that platform, terminate HTTPS there, and proxy requests to the WeblexAI app port.

Set the public URL before completing the installer:

```dotenv
APP_URL=https://translations.example.com
```

The proxy must preserve `Host`, `Authorization`, `Content-Type`, `Origin`, and `X-Page-Url`. Disable response buffering for streaming translation responses.

## Upgrades

For a convenient rolling tag:

```bash
docker compose pull
docker compose --profile tools run --rm migrate
docker compose up -d
```

For a pinned release, set `APP_VERSION` in `.env` to a published semver tag before pulling. Apply pending database migrations and create and verify a backup before upgrading.

## Logs and service status

```bash
docker compose ps
docker compose logs -f app worker scheduler
docker compose exec worker php artisan horizon:status
docker compose exec scheduler php artisan schedule:list
```

See [backup and restore](backup-restore.md) and [operations](operations.md) for ongoing maintenance.
