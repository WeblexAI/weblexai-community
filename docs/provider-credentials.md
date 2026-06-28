# Provider Credentials

Provider credentials are created by administrators and assigned to projects. The browser SDK never receives provider keys. It only receives the project API key.

## Provider Types

WeblexAI uses two provider types:

| Type | Providers | Behavior |
| --- | --- | --- |
| NMT | Google Cloud Translation | Direct machine translation. Fast and predictable. Does not use tone, audience, or website context. |
| LLM | OpenAI, OpenRouter, Gemini, Qwen | Context-aware translation. Uses glossary rules, website context, tone, and audience settings. |

The assigned credential determines the project translation type. Project users do not choose NMT or LLM separately.

## Creating A Credential

Open `/admin`, then go to **Provider Credentials**.

Set:

- **Name**: a clear internal label, such as `Production OpenAI` or `Client Google Translation`
- **Provider**: the provider that owns the key
- **API key** or **Service account JSON**: the secret supplied by the provider
- **Model**: optional for LLM providers
- **Base URL**: optional for OpenAI-compatible custom endpoints
- **Active**: enabled when projects are allowed to use it

Leave secret fields blank when editing a credential if you want to keep the existing secret.

## Assigning A Credential

Open `/admin`, then edit a project and select **Translation provider**.

Translations remain disabled until a project has:

- an active provider credential
- a project API key
- at least one accepted origin
- at least one target language

## Rotating Provider Keys

To rotate a provider key:

1. Open the credential in `/admin`.
2. Paste the new key or service account JSON.
3. Save the credential.
4. Test one low-risk page from an accepted origin.
5. Review application logs if translation requests fail.

Existing projects continue using the same credential record after rotation.

## Choosing A Provider

Use Google Cloud Translation when speed and predictable direct translation matter most.

Use an LLM provider when tone, audience, and website context are important enough to justify slower requests and higher provider cost.

Use glossary rules with every provider. Glossaries protect brand terms before the provider sees the text and remain the most reliable way to enforce terminology.

## Troubleshooting

| Problem | Check |
| --- | --- |
| Project says no provider is assigned | Confirm the project has an active credential selected in `/admin`. |
| LLM context fields are hidden | The assigned credential is an NMT provider. |
| Translation requests fail after saving | Confirm the provider key is valid and the provider account has access to the selected model. |
| Google credential fails | Confirm the service account JSON is complete and the Google Cloud Translation API is enabled for the project. |
| OpenAI-compatible endpoint fails | Confirm the base URL points to the provider API root expected by that provider. |
