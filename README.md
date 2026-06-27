# WeblexAI Community Edition

WeblexAI is a self-hosted website translation platform. It provides an administrator panel, a project dashboard, a translation API, and an embeddable browser SDK in one application.

Community Edition is Apache-2.0 licensed and intended for teams that operate their own infrastructure. WeblexAI also offers a separately operated managed service for teams that do not want to maintain PostgreSQL, Redis, workers, backups, and upgrades.

## Components

- Laravel 13 application with Inertia/Vue dashboard and Filament administration
- PostgreSQL 14+ for durable application data
- Redis 6+ for cache, sessions, and queues
- FrankenPHP application server
- Browser SDK served from `/wlai/weblexai.min.js`
- Google Cloud Translation, OpenAI, OpenRouter, Gemini, and Qwen providers

## Docker Quick Start

```bash
curl -fsSL https://raw.githubusercontent.com/weblexai/weblexai-community/main/deployment/install.sh | sudo sh
```

The installer downloads the production Compose file, generates secure PostgreSQL credentials, pulls the current stable images, and starts WeblexAI. It uses the first available port beginning at `8787`.

Open the installation URL printed by the command. The browser setup verifies the host, configures storage, runs migrations, and creates the first administrator.

Release images are published publicly at:

- `ghcr.io/weblexai/weblexai-community`
- `ghcr.io/weblexai/weblexai-community-update-agent`

After an upgrade, run migrations once:

```bash
cd /opt/weblexai
docker compose --profile tools run --rm migrate
```

## Browser SDK

Add a provider credential in `/admin`, create a project, assign the credential, add each exact accepted origin, and copy the installation snippet from the project setup page.

```html
<script>
window.WeblexAIConfig = {
  apiKey: "your-project-api-key"
};
</script>
<script defer src="https://translations.example.com/wlai/weblexai.min.js"></script>
```

Accepted origins must be exact origins such as `https://www.example.com`. Wildcards, paths, and query strings are not supported.

## Documentation

- [Docker hosting and generated credentials](docs/docker-hosting.md)
- [Traditional Ubuntu and FrankenPHP installation](docs/traditional-hosting.md)
- [Operations and troubleshooting](docs/operations.md)
- [Backup and restore](docs/backup-restore.md)
- [Caching strategy](docs/caching-strategy.md)
- [Update architecture and operations](docs/updates.md)
- [Security hardening checklist](docs/security-hardening-checklist.md)
- [Release checklist](docs/release-checklist.md)

## Project Policies

- [Apache License 2.0](LICENSE)
- [Trademark policy](TRADEMARKS.md)
- [Security policy](SECURITY.md)
- [Community support](SUPPORT.md)
- [Contributing](CONTRIBUTING.md)
- [Third-party notices](THIRD_PARTY_NOTICES.md)
- [Dependency policy](docs/dependency-policy.md)

WeblexAI names and logos are not granted under the Apache License. See [TRADEMARKS.md](TRADEMARKS.md).
