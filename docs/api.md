# Browser API Contract

Contract version `1` is exposed through `X-Weblex-Contract: 1`.

`GET /api/project/config` and `POST /api/project/translations` require:

- `Authorization: Bearer <project key>`
- browser `Origin`
- `X-Page-Url` whose exact origin matches `Origin`

Authentication failures always return:

```json
{ "message": "Unauthenticated." }
```

The translations endpoint accepts `source`, `target`, and up to 100 `translatables` per request. Each translatable has an ID and up to 10,000 characters of text. The server streams translation results in `application/x-ndjson` batches until every item in the request is complete, followed by a `complete` event or a sanitized `error` event. The translation stream event schema is in [api-contract-v1.json](api-contract-v1.json).

The browser SDK is normally initialized with:

```html
<script>
    WeblexAI.init('your-project-api-key');
</script>
```

The project setup page generates the full snippet using the configured application URL.
