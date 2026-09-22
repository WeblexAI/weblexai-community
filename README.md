# WeblexAI Community Edition

WeblexAI is a self-hosted website translation platform for teams that want project data, provider keys, translation workflows, and API traffic on infrastructure they control.

Community Edition is distributed as a Docker image with a Docker Compose stack. You bring your own Google, OpenAI, OpenRouter, Gemini, or Qwen credentials.

## What Is Included

- Laravel 13 application with Inertia/Vue dashboard and Filament administration
- PostgreSQL for durable application data
- Redis for cache, sessions, and queues
- FrankenPHP application server
- Browser SDK served from `/wlai/weblexai.min.js`
- Google Cloud Translation, OpenAI, OpenRouter, Gemini, and Qwen providers

## Docker Quick Start

Requirements: Docker Engine or Docker Desktop with Docker Compose v2.

```bash
git clone https://github.com/WeblexAI/weblexai-community.git
cd weblexai-community
cp .env.example .env
```

Set a strong `DB_PASSWORD` in `.env`, then start the stack:

```bash
docker compose pull
docker compose up -d
```

Open `http://localhost:8787/install` and create the first administrator. For a server, set `APP_URL` to the final public URL before installation.

The public image is available at [Docker Hub](https://hub.docker.com/r/kofibusy/weblexai):

```bash
docker pull kofibusy/weblexai:latest
```

For a production deployment, set `APP_VERSION` to a published semver tag instead of `latest`.

## Custom Domains

WeblexAI exposes the application on `APP_PORT` (8787 by default). Point your external proxy or platform at that port, configure DNS and HTTPS there, and set `APP_URL` to the final HTTPS URL.

The proxy must preserve `Host`, `Authorization`, `Content-Type`, `Origin`, and `X-Page-Url`, and must not buffer streaming translation responses.

## First Project

1. Complete the Docker installer and create the first administrator.
2. Add a provider credential in `/admin`.
3. Create a project, assign the credential, and add the exact website origin.
4. Add at least one target language.
5. Copy the SDK snippet from **Project Setup** into the website layout.
6. Open the website from the accepted origin and confirm the integration is active.

See the [first project guide](docs/first-project.md) for the full walkthrough.

## Manual Upgrades

Pull the new image, run migrations once, and restart the services:

```bash
docker compose pull
docker compose --profile tools run --rm migrate
docker compose up -d
```

Use a semver `APP_VERSION` value when you need a pinned release.

## Browser SDK

Copy the installation snippet from the project setup page after the project has a provider credential, project API key, accepted origin, and target language.

```html
<link rel="stylesheet" href="https://translations.example.com/wlai/weblexai.css" />
<script defer src="https://translations.example.com/wlai/weblexai.min.js"></script>
<script>
    WeblexAI.init('your-project-api-key');
</script>
```

Accepted origins must be exact origins such as `https://www.example.com`. Wildcards, paths, and query strings are not supported.

## Documentation

- [First project guide](docs/first-project.md)
- [Known limits and SEO notes](docs/known-limits.md)
- [Provider credentials](docs/provider-credentials.md)
- [Why WeblexAI vs hosted website translation tools](docs/why-weblexai.md)
- [Docker hosting and upgrades](docs/docker-hosting.md)
- [Operations and troubleshooting](docs/operations.md)
- [Backup and restore](docs/backup-restore.md)
- [Caching strategy](docs/caching-strategy.md)
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
