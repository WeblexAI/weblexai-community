# Credential Rotation Checklist

Use this checklist before publishing a public source archive and after any suspected secret exposure.

## Source Release

- Run secret scanning on the full release branch history.
- Confirm `.env`, `.env.*`, provider credentials, service-account JSON files, database dumps, and private keys are ignored.
- Rotate any credential that ever appeared in copied source, logs, screenshots, exported database rows, or test fixtures.
- Generate a fresh release signing key pair if private-key handling is uncertain.
- Store production secrets outside the repository in the host secret manager or deployment environment.

## Installed Application

- Preserve `APP_KEY` during normal backups and restores. Rotate it only when all encrypted settings can be re-entered.
- Rotate project API keys from the admin project page when a key may have been exposed.
- Rotate provider API keys directly at the provider, then update the provider credential in `/admin`.
- Rotate `UPDATE_AGENT_SECRET` if Docker update-agent traffic or host access may have been exposed.
- Rotate PostgreSQL, Redis, S3, Cloudinary, and MaxMind credentials at the infrastructure layer.

## Evidence

For each release, keep:

- secret scan report
- credential rotation log or explicit no-exposure attestation
- dependency audit output
- signed release manifest and checksum
- backup/restore verification output
