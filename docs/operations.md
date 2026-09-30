# Operations and Troubleshooting

## Health and Logs

- `/up` is the process liveness endpoint.
- Application logs are in `storage/logs`.
- Administrators can open **System > Application logs** in a new tab from `/admin`.
- Administrators can open **System > Health** to review database, cache, Redis, disk, environment, debug mode, and backup checks.
- Docker logs: `docker compose logs -f app worker scheduler`.
- Scheduler: `docker compose exec scheduler php artisan schedule:list`.

The application log viewer is read-only; log deletion is disabled.

The backup health check reports a failure until the first backup exists.

## Dashboard Backups

Administrators can open **System > Backups** in `/admin` to create, download, and delete backup archives. A full backup includes PostgreSQL, local storage, and the live `.env` file. The page also supports database-only and files-only backups.

Set and save the archive password on the Backups page before creating an archive. Every backup created from that page uses the saved password, and the backup table shows the password used for each archive. Changing the password does not change existing archives. Backups are created and deleted manually; the application does not create or remove them on a schedule. The Docker image includes the PostgreSQL client required by `pg_dump`.

## Error reporting

Administrators can enable diagnostic error reports in `/admin` under **Settings > Diagnostics**. When enabled, sanitized reports are sent to the WeblexAI collector.

Reports contain application and exception details, application stack frames, and the request method and path. Request bodies, query strings, headers, cookies, user data, environment variables, and credentials are not included. Duplicate exception locations are throttled for 15 minutes. Disable the setting to keep reports local.

## Providers

Configure provider credentials in `/admin`. Credentials are encrypted with `APP_KEY`; losing that key makes them unrecoverable. A project must select a configured provider before automatic translation works.

For provider failures, verify the provider credentials, model name, API permissions, account balance, outbound HTTPS, and worker logs.

## Accepted Origins and CORS

Add the exact origin of each website that will use the browser SDK, such as `https://www.example.com`. Do not add paths or wildcards. The browser SDK sends the project key as a bearer credential, and the application verifies both the browser `Origin` and the origin in `X-Page-Url`. Reverse proxies must preserve these headers.

Preflight requests are allowed without credentials. Actual requests with a missing key, missing origin, mismatched page URL, inactive owner, or inactive project receive the same `401` response.

## Password Recovery

Administrators can reset another user's password in `/admin`. For emergency recovery on the server:

```bash
docker compose exec app php artisan weblex:user:reset-password user@example.com
```

## Application Reset

Administrators can open **System > Reset application** to return the instance to the installation wizard. The action requires the current administrator password and the exact confirmation phrase shown on screen.

The reset runs fresh database migrations, removing database records for the installation. It also deletes local public uploads, caches, compiled views, sessions, and backup files in the Docker backup volume. The `.env` file and application logs remain. Create and verify a backup before resetting an instance.

## Proxy Issues

Disable response buffering for streaming translation responses. Permit `Authorization`, `Content-Type`, `Origin`, and `X-Page-Url` headers. Configure HTTPS at the public edge and set `APP_URL` to that public URL.

## Installer Recovery

If a process dies and leaves a stale installer lock, verify no installation is running and execute:

```bash
docker compose exec app php artisan weblex:install:unlock
```
