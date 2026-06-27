# Operations and Troubleshooting

## Health and Logs

- `/up` is the process liveness endpoint.
- Application logs are in `storage/logs`.
- Administrators can open **System > Application logs** in a new tab from `/admin`.
- Docker logs: `docker compose logs -f app worker scheduler`.
- Queue status: `php artisan horizon:status`.
- Scheduler: `php artisan schedule:list`.

The application log viewer is read-only; log deletion is disabled.

## Optional error reporting

Remote error reporting is disabled by default. Enable one or both destinations in `.env`:

```dotenv
ERROR_REPORTING_ENABLED=true
ERROR_REPORTING_WEBHOOK_URL=https://errors.example.com/weblexai
ERROR_REPORTING_WEBHOOK_SECRET=replace-with-a-random-secret
ERROR_REPORTING_TELEGRAM_BOT_TOKEN=
ERROR_REPORTING_TELEGRAM_CHAT_ID=
```

Webhook requests include an `X-WeblexAI-Signature` HMAC when a secret is configured. Telegram requires a bot token from BotFather and the destination chat ID.

Reports contain application versions, exception type and message, application stack frames, and the request method and path. Request bodies, query strings, headers, cookies, user data, environment variables, and credentials are not included. Duplicate exception locations are throttled for 15 minutes.

Run one application replica until external shared storage is configured. PostgreSQL, Redis, and uploaded media must be shared before horizontal scaling.

## Providers

Configure provider credentials in `/admin`. Credentials are encrypted with `APP_KEY`; losing that key makes them unrecoverable. A project must select a configured provider before automatic translation works.

For provider failures, verify the endpoint, model name, API permissions, account balance, outbound HTTPS, and worker logs. OpenAI and OpenRouter are separate providers and require separate credentials.

## Accepted Origins and CORS

Add exact origins such as `https://www.example.com` to each project. Do not add paths or wildcards. The browser SDK sends the project key as a bearer credential and the application verifies the browser `Origin` and page URL origin. Reverse proxies must not remove `Origin`.

Preflight requests are allowed without credentials. Actual requests with a missing key, missing origin, mismatched page URL, inactive owner, or inactive project receive the same `401` response.

## Password Recovery

Administrators can reset another user's password in `/admin`. For emergency recovery on the server:

```bash
php artisan weblex:user:reset-password user@example.com
```

## Application Reset

Administrators can open **System > Reset application** to return the instance to the installation wizard. The action requires the current administrator password and the exact confirmation phrase shown on screen.

The reset runs fresh database migrations and deletes local public uploads, caches, compiled views, and sessions. It preserves infrastructure credentials, application logs, backups, and objects stored in external storage. Create and verify a backup before resetting an instance.

## Cache and Queues

Redis databases default to `0` for general data, `1` for cache, and `2` for queues. Do not run `FLUSHALL` on shared Redis infrastructure. Restart workers after deploying code:

```bash
php artisan horizon:terminate
```

## Proxy Issues

Disable response buffering for streaming translation responses. Permit `Authorization`, `Content-Type`, `Origin`, and `X-Page-Url` headers. Configure HTTPS at the public edge and set `APP_URL` to that public URL.

## Installer Recovery

The installer records progress in `storage/app/installation.json`. If a process dies and leaves a stale lock, verify no installation is running and execute:

```bash
php artisan weblex:install:unlock
```
