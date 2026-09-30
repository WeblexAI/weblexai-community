# Security Hardening Checklist

This checklist is for self-hosted operators preparing a production WeblexAI Community Edition install.

## Required

- Run behind HTTPS and set `APP_URL` to the public HTTPS origin.
- Keep the generated `APP_KEY` safe and include it in offline backups.
- Restrict PostgreSQL and Redis to private networks.
- Configure exact accepted origins for every project before using the browser SDK.
- Use unique project API keys and rotate them after exposure.
- Configure provider keys through `/admin`; do not place provider secrets in public frontend code.
- Back up PostgreSQL, `.env`, and storage before every update.
- Test restore in an isolated environment before relying on backups.

## Recommended

- Terminate TLS at the external reverse proxy or deployment platform; preserve `Host`, `Origin`, `Authorization`, and `X-Page-Url`.
- Disable proxy buffering for `application/x-ndjson` translation responses.
- Monitor authentication failures, origin mismatches, provider errors, worker failures, and queue depth.
