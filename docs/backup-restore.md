# Backup and Restore

A full dashboard backup contains PostgreSQL, the live `.env`, storage, and especially `APP_KEY`. Provider credentials and other encrypted settings cannot be decrypted without the original key. Database-only and files-only archives are partial backups.

Dashboard backups are created from **System > Backups**. Set and save the archive password on that page before creating a backup. New archives created from the page use the saved password, and the backup table shows the password used for each archive. Changing the password does not change existing archives. Delete old dashboard backups from the same page when they are no longer needed.

## Docker

```bash
scripts/backup-docker.sh /var/backups/weblex
```

Restore a Docker backup from the directory containing your Compose files. The script stops the application services, replaces the PostgreSQL database, restores the application environment and storage, and starts the stack again:

```bash
scripts/restore-docker.sh /var/backups/weblex/weblex-YYYYmmddTHHMMSSZ.tar.gz
```

The archived `.env` is restored to the application's Docker config volume so the application can use its original settings and encryption key.

Review the script before use and test it against your Compose setup. These Docker scripts create a separate unencrypted tar archive containing the database dump, application storage, and live `.env`; they do not use the dashboard archive password. For additional protection, consider encrypting an archive before storing it outside the server.

## Verification

Start the restored application in an isolated environment. Verify administrator login, provider credential decryption, project membership, accepted origins, media, one translation request, the background workers, and the scheduler.

Create and verify a backup before every update. Keep at least one off-host encrypted copy.
