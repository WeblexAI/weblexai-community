# Known Limits

WeblexAI is designed for browser-delivered website translation with self-hosted control. It is not trying to replace every localization workflow.

## Best Fit

Use WeblexAI when:

- an existing website or web app needs a drop-in browser SDK
- provider keys and translation data should stay inside your infrastructure
- administrators need project access control and exact accepted origins
- glossary rules, review state, exclusions, and provider context matter
- teams want one place to inspect translation activity, logs, backups, and provider setup

## Not The Best First Choice

Use another tool first when:

- the only requirement is build-time string extraction from source code
- the product is a native mobile app with file-based localization
- translators must work mainly in XLIFF, PO, JSON, or other repository-managed files
- the site requires server-rendered multilingual SEO pages and no client-side translation layer

## SEO

The browser SDK translates content after the page loads. That is useful for apps, dashboards, portals, docs-like interfaces, and many existing websites, but it is not the same as serving separate crawlable pages for each locale.

If multilingual SEO is a hard requirement, validate the deployment strategy before launch. WeblexAI can still be useful for review, glossary, and provider-owned translation workflows, but the default SDK integration should not be treated as a complete SEO replacement.

## Operations

Self-hosting gives control and adds responsibility. The team operating WeblexAI must manage:

- PostgreSQL and Redis availability
- queue workers and scheduler health
- provider credential rotation
- backups and restore tests
- update checks and release verification
- reverse proxy and HTTPS configuration

Teams that want WeblexAI without operating infrastructure should use the managed service.
