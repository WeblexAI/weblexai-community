# WeblexAI Community Edition

WeblexAI is a self-hosted alternative to Weglot and Localize for teams that want website translation without handing project data, provider keys, and translation workflows to a hosted-only vendor.

Community Edition packages the admin panel, project dashboard, translation API, browser SDK, workers, and provider integrations into one Apache-2.0 application. You bring your own Google, OpenAI, OpenRouter, Gemini, or Qwen credentials and run the stack on infrastructure you control.

Managed hosting is not available yet.

## Why Teams Use It

- Drop-in browser SDK for translating existing websites without rebuilding the frontend.
- Self-hosted data path with exact-origin API protection for every project.
- Bring-your-own provider keys instead of paying a translation markup.
- Administrator-created users and project access for controlled internal rollout.
- Glossary, exclusions, review states, provider context, usage tracking, and page-level controls in one dashboard.
- Docker-first deployment with traditional hosting support for teams that do not want containers.

## What Is Included

- Laravel 13 application with Inertia/Vue dashboard and Filament administration
- PostgreSQL 14+ for durable application data
- Redis 6+ for cache, sessions, and queues
- FrankenPHP application server
- Browser SDK served from `/wlai/weblexai.min.js`
- Google Cloud Translation, OpenAI, OpenRouter, Gemini, and Qwen providers

## Quick Start Path

1. Install WeblexAI with Docker.
2. Complete the browser installer and create the first administrator.
3. Add a provider credential in `/admin`.
4. Create a project, assign the credential, and add the exact accepted website origin.
5. Add at least one target language.
6. Copy the SDK snippet from **Project Setup** into the website layout.
7. Open the website from the accepted origin and confirm the integration status turns active.

For the full walkthrough, see [First project guide](docs/first-project.md).

## Fast Local Smoke Test

After WeblexAI is installed and a project has a provider credential, API key, accepted origin, and target language:

```bash
cd examples/plain-html
python -m http.server 4173
```

Add `http://localhost:4173` as an accepted origin on the project, open `http://localhost:4173`, paste the WeblexAI URL and project API key, then load the SDK.

More snippets are available in [examples](examples/).

## Try It Locally (No Server Required)

If you have Docker Desktop (Windows or macOS) or Docker Engine (Linux), run the full stack on your machine:

```bash
bash scripts/local-quickstart.sh
```

The script creates a local configuration, builds the application, and prints the installation URL. Data stays in local Docker volumes; `docker compose down` stops everything. Then follow the [First project guide](docs/first-project.md).

## Docker Quick Start

A Linux server with Docker and root access is the production path. The installer is a root shell script, so native Windows (PowerShell, cmd, Git Bash) cannot run it directly. On Windows, use the local quickstart above for an evaluation instance, or run the installer inside WSL2, where it works like any Linux server. Run:

```bash
curl -fsSL https://github.com/weblexai/weblexai-community/releases/latest/download/install.sh | sudo sh
```

The installer downloads the production Compose file, generates secure PostgreSQL credentials, pulls the current stable images, and starts WeblexAI. It uses the first available port beginning at `8787`.

Open the installation URL printed by the command. The browser setup writes the public application URL, runs migrations, seeds defaults, and creates the first administrator.

For HTTPS with your own domain, point an `A` record at the server and run the installer with `WEBLEX_DOMAIN=translations.example.com` set. Automatic certificates are issued for you; see [Docker hosting](docs/docker-hosting.md).

Stable releases publish public images at:

- `ghcr.io/weblexai/weblexai-community`
- `ghcr.io/weblexai/weblexai-community-update-agent`

After an upgrade, run migrations once:

```bash
cd /opt/weblexai
docker compose --profile tools run --rm migrate
```

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

## How It Compares

WeblexAI covers the core website-translation workflow offered by Weglot and Localize: add a browser SDK, manage languages and translations, and publish changes from a dashboard. It is built for teams that need that workflow on their own infrastructure, with provider credentials, project data, API traffic, and operations under their control.

It is not a replacement for every localization platform. If you need only source-code string extraction for mobile apps or backend services, a file-based localization tool may be a better fit. WeblexAI is designed for browser-delivered websites and teams that want operational control.

## Current Fit And Limits

WeblexAI translates browser-delivered pages through its SDK. It is a strong fit for SaaS dashboards, customer portals, documentation-style interfaces, internal tools, and existing websites where self-hosted control matters.

For SEO-heavy marketing sites, confirm your rendering strategy before relying on client-side translation alone. The browser SDK translates after the page loads; it is not a full server-rendered multilingual SEO system by itself.

## Documentation

- [First project guide](docs/first-project.md)
- [Known limits and SEO notes](docs/known-limits.md)
- [Provider credentials](docs/provider-credentials.md)
- [Why WeblexAI vs hosted website translation tools](docs/why-weblexai.md)
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
