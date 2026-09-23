# Release Checklist

Use this checklist for each semver Docker image release.

## Before tagging

- `composer.lock` and `package-lock.json` match their manifests.
- `CHANGELOG.md` contains the release notes.
- The Docker Hub repository `kofibusy/weblexai` is public.
- GitHub has the `DOCKERHUB_USERNAME` and `DOCKERHUB_TOKEN` repository secrets configured.
- No private credentials, dumps, logs, or generated caches are included.
- Apache-2.0, security, support, contributing, and third-party notices are present.

## Verification

Run the quality gates:

```bash
composer quality
```

Validate the Compose files and run Docker E2E:

```bash
docker compose --env-file tests/e2e/docker.env.example -f docker-compose.yml -f tests/e2e/docker-compose.e2e.yml config --quiet
sh tests/e2e/run.sh
```

On Windows with Docker Desktop:

```powershell
powershell -ExecutionPolicy Bypass -File tests\e2e\run.ps1
```

Run the plain HTML smoke test, confirm accepted-origin authentication, complete one translation request, and verify backup/restore.

## Security

- Run `composer audit --locked`.
- Run `npm audit --audit-level=high`.
- Run secret scanning.
- Keep the Docker Hub credentials in GitHub Actions secrets.
- Confirm project API authentication still requires exact accepted origins.

## Publish

Push a tag in the form `vX.Y.Z`. CI publishes:

- `kofibusy/weblexai:X.Y.Z`
- `kofibusy/weblexai:latest`

After publishing, pull both tags anonymously and start a clean Compose stack with the published image.
