# First Project Guide

This guide takes a fresh WeblexAI installation to a working translated website.

## Before You Start

You need:

- an administrator account
- one translation provider credential
- the public URL where WeblexAI is reachable from browsers
- the website origin that will load the WeblexAI browser SDK

The website origin is the scheme, host, and optional port only. Use `https://www.example.com`, not `https://www.example.com/about`.

## 1. Add A Provider Credential

Open `/admin`, then go to **Provider Credentials**.

Choose one provider:

| Provider | Type | Use when |
| --- | --- | --- |
| Google Cloud Translation | NMT | You want fast direct machine translation. |
| OpenAI | LLM | You want tone, audience, and website context to influence translations. |
| OpenRouter | LLM | You want to route LLM requests through OpenRouter. |
| Gemini | LLM | You want Google Gemini models. |
| Qwen | LLM | You want Qwen-compatible LLM translation. |

LLM providers can use the project context configured on the dashboard. NMT providers translate directly and use glossary rules for consistency.

## 2. Create The Project

In `/admin`, go to **Projects** and create a project.

Set:

- **Name**: the website or customer-facing project name
- **Original language**: the language currently used on the website
- **Translation provider**: the credential created in step 1
- **Status**: active

Open the project details page and confirm the project has an API key. If it does not, rotate the API key once.

## 3. Add Accepted Origins

Open the project in `/admin`, then add each website origin that is allowed to request translations.

Examples:

```text
https://www.example.com
https://app.example.com
http://localhost:3000
```

WeblexAI requires an exact origin match. Wildcards, paths, and query strings are rejected.

## 4. Add Target Languages

Open the user dashboard, select the project, then go to **Languages**.

Attach at least one target language. A project without target languages can load the SDK, but there is nothing to translate.

## 5. Review Translation Quality Settings

Open **Translation Provider** in the project dashboard.

For LLM credentials, add a short website context. Keep it specific:

```text
WeblexAI is a self-hosted website translation platform for technical teams.
Keep product names unchanged. Use direct professional language.
```

For NMT credentials, create glossary rules for brand names, product terms, and phrases that must stay consistent.

## 6. Copy The Browser SDK Snippet

Open **Project Setup** in the project dashboard.

The launch checklist should show:

- provider credential configured
- project API key available
- accepted origin configured
- target language configured

Copy the browser SDK snippet and add it to the website layout so it loads on every page.

```html
<link rel="stylesheet" href="https://translations.example.com/wlai/weblexai.css">
<script src="https://translations.example.com/wlai/weblexai.min.js"></script>
<script>
  WeblexAI.init('your-project-api-key');
</script>
```

Use the URL of your own WeblexAI installation.

## 7. Verify The Integration

Open the website from an accepted origin and navigate through a page that should be translated.

Then return to **Project Setup**. The status changes to active after WeblexAI receives website content.

## Common Blockers

| Symptom | Fix |
| --- | --- |
| Integration remains inactive | Confirm the website origin exactly matches an accepted origin. |
| SDK loads but requests are rejected | Confirm the project API key in the snippet matches the project. |
| No translations appear | Add at least one target language and assign an active provider credential. |
| LLM output ignores brand voice | Add glossary rules and make the website context more specific. |
| Local testing works but production fails | Set the application URL to the public HTTPS URL exposed by your proxy or tunnel. |

## Production Checklist

- WeblexAI is reachable over HTTPS.
- The public application URL matches the browser-facing URL.
- The project has only the origins that should use the SDK.
- Provider credentials belong to the team operating the installation.
- Backups are configured for PostgreSQL, uploaded files, and environment configuration.
- Update checks are enabled with the signed release feed.
