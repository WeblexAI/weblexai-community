# Reverse Proxy Implementation Decision Plan

This is an internal decision note. It is not user documentation.

## Problem To Solve

Docker installs currently expose WeblexAI on a host port such as `8787`.

That is enough for local testing, but production users need a browser-facing HTTPS URL. The product needs a clear setup path for users who do not already know how to configure Nginx, Caddy, Traefik, Apache, Cloudflare Tunnel, or another proxy.

The implementation decision is whether WeblexAI should only guide users, or also provide an optional reverse proxy setup.

## Current Implementation

- App container listens on `8080`.
- Docker Compose maps `${APP_PORT:-8787}:8080`.
- Browser installer stores `app_url` as `APP_URL`.
- `APP_URL` is used when generating SDK asset URLs and project setup snippets.
- Docker installs mark `WEBLEX_DEPLOYMENT_MODE=docker`.
- There is no reverse-proxy automation today.

## Constraints

- WeblexAI cannot safely auto-detect the user's proxy stack from inside the app container.
- WeblexAI cannot reliably know whether DNS is correct.
- WeblexAI cannot know whether ports `80` and `443` are free.
- Existing servers may already run Coolify, Dokploy, CapRover, Nginx Proxy Manager, Traefik, Apache, Nginx, Caddy, or Cloudflare Tunnel.
- Automatic proxy setup must be explicit and opt-in.
- `http://localhost` must not be treated as a production default.

## What We Can Safely Implement

- Detect whether the browser opened `/install` through localhost, private IP, public host, HTTP, or HTTPS.
- Prefill public-looking request URLs only when reasonable.
- Warn when users select local/private URLs.
- Warn or block public `http://` URLs.
- Let users choose a deployment access mode.
- Generate proxy instructions and examples.
- Optionally add a Caddy service/profile for Docker installs.
- Optionally generate a Caddy config file.

## Decision Option A: Documentation-Only Proxy Support

### Behavior

The installer asks for the final public URL only.

Users configure their own reverse proxy manually using docs.

### Implementation

- Keep Docker Compose as-is.
- Installer validates and stores `APP_URL`.
- Add docs for Caddy, Nginx, Apache, Traefik, and Cloudflare Tunnel.
- Add clearer UI warnings for localhost/private/non-HTTPS URLs.

### Pros

- Smallest implementation.
- Lowest risk.
- No Compose rewriting.
- Works with any proxy.

### Cons

- Weakest experience for non-infrastructure users.
- More support burden.
- Users can still misconfigure proxy headers, HTTPS, or ports.

### Best If

We want to ship quickly and avoid managing proxy lifecycle.

## Decision Option B: Installer Generates Proxy Configs

### Behavior

The installer asks which proxy the user uses and generates copyable config.

Supported first:

- Caddy
- Nginx

Later:

- Apache
- Traefik
- Cloudflare Tunnel

### Implementation

- Add access mode: `Existing reverse proxy`.
- Add proxy selector.
- Generate config snippets using the selected `APP_URL` and detected Docker host port.
- Store only `APP_URL`; do not write proxy files.

### Pros

- Better than docs-only.
- Still safe.
- No port ownership conflict.
- Easy to support multiple proxies.

### Cons

- Still manual.
- Copy/paste config can be wrong for unusual setups.
- Does not solve beginner deployment fully.

### Best If

We want safe product guidance without owning the proxy.

## Decision Option C: Optional Built-In Caddy Proxy

### Behavior

Docker installs can enable a WeblexAI-managed Caddy service.

User provides:

- domain, for example `translate.example.com`
- TLS email

The TLS email is for Caddy's ACME account with the certificate authority. It is used for certificate lifecycle notices such as expiry, renewal, or account problems; it is not a WeblexAI login or notification email.

WeblexAI sets:

```text
APP_URL=https://translate.example.com
```

Caddy routes:

```text
https://translate.example.com -> app:8080
```

### Implementation

- Add `caddy` service to Compose behind a profile, e.g. `proxy`.
- Add a Caddyfile template.
- Add env keys:
  - `WEBLEX_PROXY_DRIVER=caddy`
  - `WEBLEX_PROXY_DOMAIN=translate.example.com`
  - `WEBLEX_PROXY_EMAIL=admin@example.com`
- Browser installer adds a `Managed HTTPS` mode for Docker installs.
- Installer validates domain/email and writes env values.
- User restarts with:

```bash
docker compose --profile proxy up -d
```

Alternative: the one-command installer can generate these values before first boot.

### Pros

- Best beginner experience.
- Automatic HTTPS.
- Clear production path.
- Stronger Coolify-style self-hosted story.

### Cons

- Can conflict with existing proxies on ports `80` and `443`.
- More moving parts.
- Needs clear recovery path if DNS or TLS fails.
- Browser installer cannot safely restart host Docker unless we add host-level tooling.

### Best If

We want WeblexAI to feel production-ready for non-Laravel/non-infra users.

## Decision Option D: One-Command Installer Handles Proxy Setup

### Behavior

The CLI install script asks proxy questions before Docker starts.

Example:

```text
Public URL mode:
1. Managed HTTPS with Caddy
2. Existing reverse proxy
3. Local/private only
```

If managed HTTPS is selected, the CLI writes Compose/env files and starts the proxy profile immediately.

### Implementation

- Update `deployment/install.sh`.
- Add interactive questions.
- Add non-interactive flags for automation.
- Generate `.env`, `Caddyfile`, and Compose profiles before first start.
- Browser installer only confirms `APP_URL`.

### Pros

- Cleanest first boot for managed HTTPS.
- No need for the web app to rewrite Compose.
- Good for production install docs.

### Cons

- Install script becomes more complex.
- Interactive shell UX has more edge cases.
- Needs flags for unattended installs.
- Harder to support across different server environments.

### Best If

We want a polished production-first one-command installer soon.

## Recommended Implementation Path

Implement in phases.

### Phase 1: Installer UI And Guardrails

Status: partly started.

- Rename application step to `Public access`.
- Do not prefill `http://localhost` as production URL.
- Add explicit local/private guidance.
- Add warnings for non-HTTPS public URLs.
- Add `Use current URL` as an intentional action.
- Keep proxy behavior unchanged.

### Phase 2: Existing Proxy Guidance In Installer

- Add access mode selector:
  - Existing reverse proxy
  - Local/private
  - Traditional/manual
- Generate copyable Caddy and Nginx examples.
- Store only `APP_URL`.
- Add tests for URL validation and mode-specific behavior.

### Phase 3: Optional Caddy Proxy

- Add Compose `proxy` profile with Caddy.
- Add generated Caddyfile.
- Add `Managed HTTPS` mode for Docker only.
- Add install script support for enabling proxy profile.
- Add docs after behavior is implemented and verified.

### Phase 4: CLI Automation

- Add interactive/non-interactive proxy flags to `deployment/install.sh`.
- Allow:
  - `--proxy=caddy`
  - `--domain=translate.example.com`
  - `--email=admin@example.com`
  - `--local-only`
- Generate all config before first boot.

## Acceptance Criteria

For any chosen implementation:

- Production setup never silently defaults to `http://localhost`.
- Users can clearly distinguish local/private setup from production setup.
- Docker installs can still run without a proxy.
- Existing reverse proxy users are not forced into WeblexAI-managed Caddy.
- Generated SDK snippets use the final configured `APP_URL`.
- Tests cover URL defaulting and validation behavior.
- Public docs only describe implemented behavior, not undecided options.

## Decisions Needed

1. Should WeblexAI ship a built-in Caddy proxy option?
2. Should Caddy be the recommended production path or only an advanced option?
3. Should the browser installer ever write proxy/Compose files, or only the CLI installer?
4. Should public `http://` URLs be blocked in production mode or only warned?
5. Which proxy examples should be implemented first?
6. Should Cloudflare Tunnel be treated as a first-class setup mode?
7. Should local/private mode be allowed after installation, or only during development?

## My Recommendation

Choose this path:

1. Finish Phase 1 now.
2. Implement Phase 2 next with Caddy and Nginx examples.
3. Add Phase 3 Caddy profile after the manual proxy UX is stable.
4. Move proxy automation into the CLI, not the browser installer.

Reasoning:

- It keeps the browser app from controlling host Docker.
- It gives users useful guidance immediately.
- It avoids breaking users who already have a proxy.
- It still leaves a path to a polished one-command production install.
