# Credential Rotation Checklist

Use this checklist when a credential may have been exposed on a running installation.

## Installed Application

- Preserve `APP_KEY` during normal backups and restores. Rotate it only when all encrypted settings can be re-entered.
- Rotate project API keys from the admin project page when a key may have been exposed.
- Rotate provider API keys directly at the provider, then update the provider credential in `/admin`.
- Rotate `UPDATE_AGENT_SECRET` if Docker update-agent traffic or host access may have been exposed.
- Rotate PostgreSQL, Redis, S3, Cloudinary, and MaxMind credentials at the infrastructure layer.

For release publication secret hygiene, see the [release checklist](release-checklist.md).
