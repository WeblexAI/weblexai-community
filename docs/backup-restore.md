# Backup and Restore

A full dashboard backup contains PostgreSQL, the live `.env`, storage, and especially `APP_KEY`. Provider credentials and other encrypted settings cannot be decrypted without the original key. Database-only and files-only archives are partial backups.

Dashboard backups are created from **System > Backups**. Set and save the archive password on that page before creating a backup. New archives created from the page use the saved password, and the backup table shows the password used for each archive. Changing the password does not change existing archives. Delete old dashboard backups from the same page when they are no longer needed.

## Docker

```bash
scripts/backup-docker.sh /var/backups/weblex
```

Restore onto a stopped stack:

```bash
scripts/restore-docker.sh /var/backups/weblex/weblex-YYYYmmddTHHMMSSZ.tar.gz
```

Review the script before use and test it against your Compose setup. These Docker scripts create a separate unencrypted tar archive containing the database dump, storage, and live `.env`; they do not use the dashboard archive password. Encrypt the resulting archive with your host or storage platform before keeping it outside the server.

The Docker stack keeps the live environment in the `weblex_config` volume. The host `.env` is bootstrap input for a new volume; the backup script exports the live copy.

## Verification

Start the restored application in an isolated environment. Verify administrator login, provider credential decryption, project membership, accepted origins, media, one translation request, Horizon, and the scheduler.

Create and verify a backup before every update. Keep at least one off-host encrypted copy.
