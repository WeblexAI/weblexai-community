# WeblexAI Community Edition

WeblexAI is a self-hosted website translation platform distributed as a Docker Compose stack.

The stack includes the WeblexAI app, background workers, PostgreSQL, Redis, and the browser SDK. You provide credentials for your translation providers.

## Install with Docker

Requirements: Docker Engine or Docker Desktop with Docker Compose v2. The Bash installer also needs `curl`, `bash`, and `openssl`.

Install the latest image and configuration:

```bash
curl -fsSL https://raw.githubusercontent.com/WeblexAI/weblexai-community/main/scripts/install-docker.sh \
  | bash
```

The installer creates `./weblexai`, generates the database configuration, pulls the required images, and starts the stack. Open `http://localhost:8787/install` to create the first administrator.

Use a published release when needed:

```bash
curl -fsSL https://raw.githubusercontent.com/WeblexAI/weblexai-community/main/scripts/install-docker.sh \
  | bash -s -- --version 1.0.0
```

On Windows PowerShell:

```powershell
& ([scriptblock]::Create((irm "https://raw.githubusercontent.com/WeblexAI/weblexai-community/main/scripts/install-docker.ps1"))) -Mode Install
```

The default installer follows `main` and uses the `latest` image. Its behavior can change. Use `--version X.Y.Z` for a release-pinned configuration and image. DNS and TLS are handled by your external proxy or hosting platform.

The image is published at [Docker Hub](https://hub.docker.com/r/kofibusy/weblexai).

## Update

Update an existing installation without replacing its `.env` or Docker volumes:

```bash
curl -fsSL https://raw.githubusercontent.com/WeblexAI/weblexai-community/main/scripts/install-docker.sh \
  | bash -s -- update --version 1.0.1 --directory ./weblexai
```

The update pulls the selected image, runs database migrations, and restarts the stack.

## First Project

1. Create the first administrator at `/install`.
2. Add a provider credential in `/admin`.
3. Create a project, assign the credential, and add the address of the website you want to translate.
4. Add a target language and copy the SDK snippet from **Project Setup** into the website.

See the [first project guide](docs/first-project.md) for details.

## Custom Domains

For public access, follow the [public URL setup](docs/docker-hosting.md#public-url) in the Docker hosting guide.

## Browser SDK

The browser SDK is available at `/wlai/weblexai.min.js`. Copy its installation snippet from **Project Setup** after configuring a project.

Accepted origins must be exact origins such as `https://www.example.com`; paths and wildcards are not supported.

## Documentation

- [First project](docs/first-project.md)
- [Docker hosting and updates](docs/docker-hosting.md)
- [Operations](docs/operations.md)
- [Backup and restore](docs/backup-restore.md)
- [Provider credentials](docs/provider-credentials.md)
- [Security hardening](docs/security-hardening-checklist.md)
- [Release checklist](docs/release-checklist.md)

## Project Policies

- [Apache License 2.0](LICENSE)
- [Trademark policy](TRADEMARKS.md)
- [Security policy](SECURITY.md)
- [Support](SUPPORT.md)
- [Contributing](CONTRIBUTING.md)
- [Third-party notices](THIRD_PARTY_NOTICES.md)
