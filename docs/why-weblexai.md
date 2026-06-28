# Why WeblexAI

WeblexAI is built for teams that like the Weglot and Localize website-translation workflow but want to own the infrastructure, provider keys, and translation data path.

## Best Fit

WeblexAI is a strong fit when:

- the product is a browser-delivered website or web application
- the team wants a drop-in SDK instead of rebuilding the frontend around translation files
- provider keys should stay inside the team's infrastructure
- translation cost should come directly from the provider account
- administrators need project access control, accepted origins, glossary rules, and review workflows

It is not the right first choice when the only requirement is translating static app strings at build time.

## Comparison

| Area | WeblexAI | Hosted website translation tools |
| --- | --- | --- |
| Hosting | Runs on your infrastructure. | Runs on the vendor platform. |
| Provider keys | You bring and control provider credentials. | Provider choice and cost model are controlled by the vendor. |
| Data path | Browser SDK talks to your WeblexAI instance. | Browser SDK talks to the vendor service. |
| Access control | Admin-created users, project membership, exact accepted origins. | Vendor-defined access model. |
| Translation quality controls | Glossary, exclusions, review state, LLM context, tone, and audience. | Depends on vendor plan and workflow. |
| Operational responsibility | Your team manages updates, backups, logs, and infrastructure. | Vendor manages operations. |
| Managed option | Available separately for teams that want WeblexAI without operating it. | Included by default. |

## Positioning

Choose WeblexAI when the requirement is:

```text
Weglot-style website translation, but self-hosted and provider-key-owned.
```

The tradeoff is operational responsibility. Self-hosting gives control, but it also means the team must manage backups, upgrades, monitoring, and provider credentials. Teams that do not want that responsibility should use the managed WeblexAI service.

## Product Principles

- The browser integration should be simple enough for a frontend developer to add in minutes.
- Project setup should make missing configuration obvious before the first request.
- Translation quality should improve through glossary, exclusions, context, and review rather than hidden vendor behavior.
- Operators should be able to inspect logs, rotate credentials, back up the system, and update safely.
