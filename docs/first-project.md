# First Project Guide

Start with the Docker installation, then follow these steps to connect your website to WeblexAI and start translating it.

## 1. Install WeblexAI

Requirements: Docker Engine or Docker Desktop with Docker Compose v2.

Install the latest version:

```bash
curl -fsSL https://raw.githubusercontent.com/WeblexAI/weblexai-community/main/scripts/install-docker.sh \
  | bash
```

For PowerShell and version-pinned installations, see the [Docker hosting guide](docker-hosting.md).

When the stack starts:

- Local installation: open `http://localhost:8787/install` and create the first administrator.
- Public installation:
    1. Point your domain's DNS record to the server running WeblexAI.
    2. Configure your reverse proxy to forward HTTPS traffic to `http://127.0.0.1:8787`.
    3. Open `https://your-domain.example/install` and create the first administrator.

## What You Will Need

- **Administrator account**: create it at `/install` in step 1.
- **Translation provider credential**: obtain credentials from your chosen provider, then add them in `/admin` in step 2. See the [provider credentials guide](provider-credentials.md).
- **WeblexAI address**: use `http://localhost:8787` for a local installation, or the public HTTPS address configured in the previous step.
- **Accepted origin**: enter the address of the website whose content you want to translate, such as `https://www.example.com`. Do not include a page path such as `/about`.

## 2. Add A Provider Credential

Open `/admin`, then go to **Provider Credentials**.

Choose one provider:

| Provider                 | Type | Use when                                                                |
| ------------------------ | ---- | ----------------------------------------------------------------------- |
| Google Cloud Translation | NMT  | You want fast direct machine translation.                               |
| OpenAI                   | LLM  | You want tone, audience, and website context to influence translations. |
| OpenRouter               | LLM  | You want to route LLM requests through OpenRouter.                      |
| Gemini                   | LLM  | You want Google Gemini models.                                          |
| Qwen                     | NMT  | You want fast direct translation through Qwen Machine Translation.      |

LLM providers can use the project context configured on the dashboard. All providers use glossary rules for consistent terminology; NMT providers translate directly and do not use tone, audience, or website context.

## 3. Create The Project

In `/admin`, go to **Projects** and create a project.

Set:

- **Name**: the website or customer-facing project name
- **Original language**: the language currently used on the website
- **Translation provider**: the credential created in step 1
- **Status**: active

Open the project details page and confirm the project has an API key. If it does not, rotate the API key once.

## 4. Add Accepted Origins

Open the project in `/admin`, then add each website origin that is allowed to request translations.

Examples:

```text
https://www.example.com
https://app.example.com
http://localhost:3000
```

WeblexAI requires an exact origin match. Wildcards, paths, and query strings are rejected.

## 5. Add Target Languages

Open the user dashboard, select the project, then go to **Languages**.

Attach at least one target language. A project without target languages can load the SDK, but there is nothing to translate.

## 6. Review Translation Quality Settings

Open **Translation Provider** in the project dashboard.

For LLM credentials, add a short website context. Keep it specific:

```text
WeblexAI is a self-hosted website translation platform for technical teams.
Keep product names unchanged. Use direct professional language.
```

For any provider, create glossary rules for brand names, product terms, and phrases that must stay consistent. NMT providers use glossary rules without the LLM context settings.

## 7. Copy The Browser SDK Snippet

Open **Project Setup** in the project dashboard.

The launch checklist should show:

- provider credential configured
- project API key available
- accepted origin configured
- target language configured

Copy the browser SDK snippet and add it to the website layout so it loads on every page.

```html
<link rel="stylesheet" href="https://translations.example.com/wlai/weblexai.css" />
<script src="https://translations.example.com/wlai/weblexai.min.js"></script>
<script>
    WeblexAI.init('your-project-api-key');
</script>
```

The snippet uses the WeblexAI application URL configured during installation.

## 8. Verify The Integration

Open the website from an accepted origin and navigate through a page that should be translated.

Then return to **Project Setup**. The status changes to active after WeblexAI receives website content.

## Optional Local Smoke Test

If you want to test WeblexAI before touching a real website, use the plain HTML example:

```bash
cd examples/plain-html
python -m http.server 4173
```

Add this accepted origin to the project:

```text
http://localhost:4173
```

Open `http://localhost:4173`, enter your WeblexAI URL and project API key, then load the SDK.

## Common Blockers

| Symptom                                     | Fix                                                                                                                     |
| ------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| Integration remains inactive                | Confirm the website origin exactly matches an accepted origin.                                                          |
| SDK loads but requests are rejected         | Confirm the project API key in the snippet matches the project.                                                         |
| No translations appear                      | Add at least one target language and assign an active provider credential.                                              |
| LLM output ignores brand voice              | Add glossary rules and make the website context more specific.                                                          |
| Local testing works but production fails    | Set the application URL to the public HTTPS URL exposed by your proxy or tunnel.                                        |
| Search engines do not show translated pages | The browser SDK translates after page load. Review [known limits](known-limits.md) before using it for SEO-heavy sites. |

## Production Checklist

- WeblexAI is reachable over HTTPS.
- The public application URL matches the browser-facing URL.
- The project has only the origins that should use the SDK.
- Provider credentials belong to the team operating the installation.
- Backups are configured for PostgreSQL, uploaded files, and environment configuration.
