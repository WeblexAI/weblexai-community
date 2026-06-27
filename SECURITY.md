# Security Policy

## Supported Versions

The latest stable minor release receives security fixes. Older releases may be asked to upgrade before a report is investigated.

| Version | Supported |
| --- | --- |
| Latest stable | Yes |
| Older releases | No |

## Hardening

Production operators should complete the [security hardening checklist](docs/security-hardening-checklist.md) and keep a tested backup that includes `.env`, storage, PostgreSQL, and `APP_KEY`.

## Reporting a Vulnerability

Report vulnerabilities privately through GitHub Security Advisories for this repository. If advisories are unavailable, email `security@weblexai.com` with the subject `WeblexAI Community Security Report`.

Include affected versions, deployment context, reproduction steps, impact, and any suggested remediation. Do not open a public issue before a fix is available.

We will acknowledge valid reports when capacity permits, investigate privately, prepare a coordinated patch and advisory, and credit reporters who request attribution. Community support has no contractual response-time guarantee.

Never include active credentials or personal data. Rotate any credential that may have been exposed during testing.
