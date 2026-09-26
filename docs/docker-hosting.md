# Docker Hosting

WeblexAI runs as one Docker Compose stack containing the app, worker, scheduler, PostgreSQL, and Redis.

## Install

Requirements: Docker Engine or Docker Desktop with Docker Compose v2.

Install the latest image and configuration:

```bash
curl -fsSL https://raw.githubusercontent.com/WeblexAI/weblexai-community/main/scripts/install-docker.sh \
  | bash
```

Install a specific release when needed:

```bash
curl -fsSL https://raw.githubusercontent.com/WeblexAI/weblexai-community/main/scripts/install-docker.sh \
  | bash -s -- --version 1.0.0
```

On Windows PowerShell:

```powershell
& ([scriptblock]::Create((irm "https://raw.githubusercontent.com/WeblexAI/weblexai-community/main/scripts/install-docker.ps1"))) -Mode Install
```

PowerShell uses `-Version` and `-Directory` for the same options.

The installer creates `.env`, generates `DB_PASSWORD`, pulls the images, and starts the stack. The container generates `APP_KEY` on first start.

- Local installation: open `http://localhost:8787/install`.
- Public installation: open `https://your-domain.example/install`.

Create the first administrator from the page that opens.

The default installer reads its files from `main` and uses the `latest` image. Its behavior can change. Use `--version X.Y.Z` to pin both to a release. A release-pinned script is available from a release tag that contains the installer.

## Public URL

For public access:

Docker Compose publishes the WeblexAI app on host port `8787` by default.

1. Point your domain's DNS record to the server running WeblexAI.
2. Configure your reverse proxy to forward HTTPS traffic to `http://127.0.0.1:8787`.
3. Enable HTTPS on the reverse proxy.
4. Open `https://your-domain.example/install` and create the first administrator.

## Update

```bash
curl -fsSL https://raw.githubusercontent.com/WeblexAI/weblexai-community/main/scripts/install-docker.sh \
  | bash -s -- update --version 1.0.1 --directory ./weblexai
```

Update mode preserves `.env` and Docker volumes, downloads the selected Compose file, pulls the image, runs migrations, and restarts the stack.

For Windows PowerShell:

```powershell
& ([scriptblock]::Create((irm "https://raw.githubusercontent.com/WeblexAI/weblexai-community/main/scripts/install-docker.ps1"))) -Mode Update -Version 1.0.1 -Directory (Join-Path $PWD "weblexai")
```

## Operations

```bash
docker compose ps
docker compose logs -f app worker scheduler
docker compose exec worker php artisan horizon:status
docker compose exec scheduler php artisan schedule:list
```

PostgreSQL and Redis are private Compose services. Application storage and backups use Docker volumes. See [backup and restore](backup-restore.md) and [operations](operations.md) for maintenance.
