# Changelog

## Unreleased

Changes since the initial Community Edition release candidate.

- One-command installer now supports automatic HTTPS with `WEBLEX_DOMAIN` (included Caddy proxy with automatic certificates).
- Added `scripts/local-quickstart.sh` to run the full stack locally with Docker Desktop or Docker Engine.
- Fixed the release pipeline source archive step that prevented release assets from being published.
- Release workflow now verifies the signing key and all release assets before publishing.
- Added a first-backup nudge to the admin dashboard when no backup exists yet.
- Removed builder-directed notes from user-facing documentation.

Initial WeblexAI Community Edition release candidate.

- Apache-2.0 licensed monolith with admin, dashboard, translation API, and browser SDK.
- Browser installer for PostgreSQL, Redis, storage, and first administrator setup.
- Admin-created users, admin-managed projects, direct project membership, and CLI password reset.
- Exact accepted-origin authentication for project config and translation API requests.
- Google Cloud Translation, OpenAI, OpenRouter, Gemini, and Qwen provider support.
- Redis-backed project configuration and translation cache stores.
- Docker, FrankenPHP, traditional hosting, backup/restore, and signed update documentation.
