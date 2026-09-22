# Contributing

## Workflow

1. Open an issue for substantial behavior changes.
2. Keep pull requests focused and avoid unrelated formatting or generated-file churn.
3. Add tests for changed behavior.
4. Run `composer quality`.
5. Explain operational, migration, security, and compatibility effects in the pull request.

Use clear commit messages. Review prioritizes correctness, authorization, secret handling, backward compatibility, and maintainability.

## Docker Development

Copy the example environment and set a development database password:

```bash
cp .env.example .env
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
```

The development override builds the application image from the working tree. Production uses the published Docker Hub image from `docker-compose.yml`.

Use `--build` when the image needs to be rebuilt:

- after changing `Dockerfile` or `entrypoint.sh`
- after changing Composer or npm dependencies
- after changing frontend or SDK assets that must be compiled into the image
- after pulling changes that affect the built application image

For normal restarts while containers already exist, use:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

The development build runs Composer install, npm install, frontend build, SDK build, PHP extension setup, and PostgreSQL client setup. It is expected to take longer than a normal restart.

## Quality Checks

Use one command before opening a pull request:

```bash
composer quality
```

This runs Composer validation, Composer manifest normalization checks, Pint formatting checks, Larastan/PHPStan analysis, Rector dry-run refactoring checks, Pest architecture checks, Pest type-coverage reporting, the PHP test suite, frontend linting, frontend formatting checks, TypeScript checks, SDK tests, and the production build.

Use targeted commands while developing:

```bash
composer test
composer analyse
composer rector:check
composer arch
composer type-coverage
npm run lint:check
npm run typecheck
```

## Contributor License Agreement

Contributors retain copyright. Before a pull request can be merged, contributors must accept the applicable CLA in `docs/cla`. The CLA grants WeblexAI rights needed to distribute contributions in Community Edition and commercial offerings while preserving the contributor's ownership.

## Security

Do not submit vulnerabilities through a public pull request. Follow [SECURITY.md](SECURITY.md).
