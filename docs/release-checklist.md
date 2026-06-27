# Release Checklist

Use this checklist for every WeblexAI Community Edition release.

## Build Inputs

- Version is updated in `package.json`, release manifest, Docker tags, and `CHANGELOG.md`.
- `composer.lock` and `package-lock.json` are committed and match manifests.
- Both GHCR packages are public and can be pulled without authentication.
- No private commercial-only module, credential, dump, log, or generated cache is included.
- Apache-2.0, NOTICE, trademark, support, security, and contributing docs are present.

## Verification

Run the local quality gates:

```bash
composer quality
```

Run the container and E2E gates:

```bash
docker compose --env-file tests/e2e/docker.env.example -f docker-compose.yml -f tests/e2e/docker-compose.e2e.yml config --quiet
sh tests/e2e/run.sh
```

On Windows with Docker Desktop:

```powershell
powershell -ExecutionPolicy Bypass -File tests\e2e\run.ps1
```

## Security

- Run `composer audit --locked`.
- Run `npm audit --audit-level=high`.
- Run secret scanning against the release branch.
- Complete `docs/credential-rotation-checklist.md`.
- Confirm project API authentication requires accepted origins.
- Confirm update manifests are signed and checksums are verified.

## Artifacts

- Build the source archive from a clean checkout.
- Build and tag the Docker image with the exact release version.
- Generate SHA-256 checksums for source and image artifacts.
- Sign the release manifest with the offline Ed25519 private key.
- Publish release notes with upgrade, rollback, and migration compatibility notes.

## Post-Release

- Install from the published artifact in a clean environment.
- Run the browser installer and create the first administrator.
- Configure a mock or low-risk provider key and complete one translation request.
- Verify backup and restore using the published artifact.
- Record any accepted audit exception in the release notes.
