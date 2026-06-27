# Browser API Contract

Contract version `1` is exposed through `X-Weblex-Contract: 1`.

`GET /api/project/config` and `POST /api/project/translations` require:

- `Authorization: Bearer <project key>`
- browser `Origin`
- `X-Page-Url` whose exact origin matches `Origin`

Authentication failures always return:

```json
{"message":"Unauthenticated."}
```

The translations endpoint accepts `source`, `target`, and up to 100 `translatables`. Each translatable has an ID and up to 10,000 characters of text. It returns `application/x-ndjson` containing `batch`, `complete`, or sanitized `error` events. The event schema is in [api-contract-v1.json](api-contract-v1.json).

The browser SDK auto-initializes when `window.WeblexAIConfig.apiKey` is set before loading `/wlai/weblexai.min.js`.

Breaking changes require a new contract version and parallel compatibility period.
