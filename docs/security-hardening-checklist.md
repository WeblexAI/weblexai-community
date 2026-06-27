# Security Hardening Checklist

This checklist is for self-hosted operators preparing a production WeblexAI Community Edition install.

## Required

- Run behind HTTPS and set `APP_URL` to the public HTTPS origin.
- Set a strong `APP_KEY` and keep it in offline backups.
- Keep `APP_DEBUG=false` in production.
- Restrict PostgreSQL and Redis to private networks.
- Configure exact accepted origins for every project before using the browser SDK.
- Use unique project API keys and rotate them after exposure.
- Configure provider keys through `/admin`; do not place provider secrets in public frontend code.
- Run Horizon workers and scheduler as non-root users on traditional hosting.
- Back up PostgreSQL, `.env`, and storage before every update.
- Test restore in an isolated environment before relying on backups.

## Recommended

- Terminate TLS at FrankenPHP or a trusted reverse proxy that preserves `Host`, `Origin`, `Authorization`, and `X-Page-Url`.
- Disable proxy buffering for `application/x-ndjson` translation responses.
- Put Redis on dedicated databases for app data, cache, and queues.
- Keep only one scheduler process active.
- Limit outbound network access to configured providers and update endpoints.
- Monitor authentication failures, origin mismatches, provider errors, worker failures, and queue depth.
- Keep Docker update-agent on the internal Compose network with no published host port.
- Use least-privilege credentials for S3, Cloudinary, MaxMind, and provider APIs.

## Release Maintainer Gate

- Complete `docs/credential-rotation-checklist.md`.
- Run backend, frontend, container, and Docker E2E checks.
- Run dependency audits and secret scanning.
- Verify release manifest signature and artifact checksum.
- Review `TRADEMARKS.md`, `LICENSE`, and `NOTICE` before public publication.
