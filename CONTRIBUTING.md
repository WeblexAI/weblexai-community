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
docker compose up -d --build
```

The repository includes `docker-compose.override.yml`, so Compose builds the application and update-agent images directly from the working tree. Production installations download only `docker-compose.yml` and use published images.

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

The CLA text is a project template and should be reviewed by WeblexAI's legal counsel before accepting public contributions.

## Security

Do not submit vulnerabilities through a public pull request. Follow [SECURITY.md](SECURITY.md).
