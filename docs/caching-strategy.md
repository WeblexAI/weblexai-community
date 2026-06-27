# Caching Strategy

WeblexAI Community Edition uses Redis for project configuration, translation lookups, sessions, and queue state. PostgreSQL remains the durable source of truth.

## Goals

- Avoid provider calls for text that already has a translation.
- Keep project configuration reads cheap for browser SDK traffic.
- Make cache invalidation explicit and testable.
- Keep the implementation driver-oriented so other cache backends can be added later without rewriting translation flow code.

## Stores

`ConfigCacheStore` owns project configuration cache entries. It includes project status, owner status, original language, public target languages, switcher configuration, accepted origins, excluded blocks, and public API configuration.

`TranslationCacheStore` owns translated text lookups for a project, page, source language, target language, and text hash. It stores only translation data that can be rebuilt from PostgreSQL or the configured provider.

Cache key construction lives in small store classes, not controllers or middleware. New drivers should implement the same store methods and keep cache keys private to the driver.

## Invalidation

Configuration cache must be cleared when these change:

- project status, owner, original language, or switcher configuration
- accepted origins
- public target languages
- excluded blocks

Translation cache must be cleared when translations are edited, deleted, imported, retranslated, or when a language is detached from a project.

The project accepted-origin table is security-sensitive. Origin changes clear configuration cache after the database transaction commits so stale origins are not served.

## Accepted Origins

Accepted origins are part of authentication, not just CORS. The API requires a bearer project key, a browser `Origin`, and an `X-Page-Url` whose origin exactly matches `Origin`. Missing or unaccepted origins receive the same unauthenticated response as bad API keys.

Do not cache origin decisions outside `ConfigCacheStore`. This keeps cache invalidation centralized.

## Token and Provider Cost Control

The translation pipeline limits provider usage before sending text to external APIs:

- cache and database lookups run before provider calls
- text is batched by provider-specific character limits
- providers have configurable batch size and timeout settings
- glossary placeholders are applied before provider calls so models translate less ambiguous text
- the browser SDK sends only collected translatable text, not full page HTML

Administrators should start with conservative batch sizes, then raise them after observing provider latency and error rate.

## Operational Checks

After deployment or restore, verify:

- project config requests return current accepted origins behavior
- a known translated phrase is served without a provider call
- a new phrase reaches the selected provider once, then hits cache on repeat
- Redis eviction policy is appropriate for the available memory
- workers are restarted after code changes that alter cache key formats
