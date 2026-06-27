# Updates

WeblexAI uses semantic versioning and the stable release channel. Release metadata is JSON signed with Ed25519. The signed payload includes the version, publication date, release notes URL, security classification, prerequisites, artifact URL, and SHA-256 checksum. The artifact is accepted only when both the metadata signature and artifact checksum pass.

Update checks send no installation identifier or telemetry.

## Configuration

```dotenv
RELEASE_FEED_URL=https://github.com/weblexai/weblexai-community/releases/latest/download/stable.json
RELEASE_PUBLIC_KEY=base64-ed25519-public-key
UPDATE_CHECK_HOURS=24
```

Administrators can check status in `/admin/updates` or run:

```bash
php artisan weblex:update --check
```

Release maintainers sign metadata with:

```bash
php scripts/generate-release-keypair.php
```

Store `RELEASE_PRIVATE_KEY` as a GitHub Actions secret in the repository or organization that publishes releases. Store `RELEASE_PUBLIC_KEY` in the application environment used by installations. The public key is safe to distribute; the private key must never be committed.

Each release signs metadata with:

```bash
RELEASE_PRIVATE_KEY=base64-ed25519-secret-key php scripts/sign-release-manifest.php \
  --version=1.0.1 \
  --artifact-url=https://github.com/weblexai/weblexai-community/releases/download/v1.0.1/weblexai-community-1.0.1.tar.gz \
  --sha256=64-lowercase-hex-characters \
  --notes-url=https://github.com/weblexai/weblexai-community/releases/tag/v1.0.1 \
  --output=stable.json
```

The release workflow uploads `stable.json` to each GitHub Release. The update feed uses GitHub's latest-release download URL, so publishing a new stable tag automatically points update checks at the newest signed manifest after the workflow completes.

## Traditional Driver

Traditional updates require the `/srv/weblex/releases/<version>` and `/srv/weblex/current` symlink layout. Configure:

```dotenv
UPDATE_DRIVER=traditional
UPDATE_BASE_PATH=/srv/weblex
UPDATE_BACKUP_PATH=/var/backups/weblex
```

The driver locks concurrent updates, downloads and verifies the release, creates a backup, extracts a versioned release, enters maintenance mode, links shared environment/storage, runs migrations once, atomically switches `current`, terminates Horizon, and restores service. A pre-switch failure returns the symlink to the previous release.

Database migrations are not automatically reversed. Release notes must state whether rollback is schema-compatible.

## Docker Driver

The web container never receives the Docker socket. A separate minimal update agent exposes no host port and accepts only a signed request containing a semantic version. It executes a fixed Compose update script for this project.

Generate a unique secret:

```bash
openssl rand -hex 32
```

Set the same value in `UPDATE_AGENT_SECRET`, configure `UPDATE_AGENT_URL=http://update-agent:8080`, set `UPDATE_DRIVER=docker`, and start:

```bash
docker compose --profile updates up -d update-agent
```

The agent has Docker socket access and must remain isolated on the internal network. It backs up state, pulls versioned images, runs the migration job once, replaces only the WeblexAI services, checks that the application is running, and restores the previous image version on failure where migrations remain compatible.

## Metadata Example

```json
{
  "version": "1.0.1",
  "channel": "stable",
  "published_at": "2026-06-07T00:00:00Z",
  "notes_url": "https://github.com/weblexai/weblexai-community/releases/tag/v1.0.1",
  "security": false,
  "requirements": {
    "application": "1.0.0",
    "php": "8.3.0",
    "postgres": "14.0",
    "redis": "6.0"
  },
  "artifact": {
    "url": "https://github.com/weblexai/weblexai-community/releases/download/v1.0.1/weblexai-community-1.0.1.tar.gz",
    "sha256": "64-lowercase-hex-characters"
  },
  "signature": "base64-ed25519-signature"
}
```
