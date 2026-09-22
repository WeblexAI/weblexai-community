# Backup and Restore

A recoverable backup contains PostgreSQL, the live `.env`, storage, and especially `APP_KEY`. Provider credentials and other encrypted settings cannot be decrypted without the original key.

## Docker

```bash
scripts/backup-docker.sh /var/backups/weblex
```

Restore onto a stopped stack:

```bash
scripts/restore-docker.sh /var/backups/weblex/weblex-YYYYmmddTHHMMSSZ.tar.gz
```

Review the script before use and test it against your Compose and retention policy.

The Docker stack keeps the live environment in the `weblex_config` volume. The host `.env` is bootstrap input for a new volume; the backup script exports the live copy.

## Verification

Start the restored application in an isolated environment. Verify administrator login, provider credential decryption, project membership, accepted origins, media, one translation request, Horizon, and the scheduler.

Create and verify a backup before every update. Keep at least one off-host encrypted copy.
