# Docker Hosting

The Docker installer creates the deployment in `/opt/weblexai` and starts these services:

| Service | Network port | Host access |
| --- | ---: | --- |
| WeblexAI | `8080` | Published on the first available port beginning at `8787` |
| PostgreSQL | `5432` | Private Compose network |
| Redis | `6379` | Private Compose network |
| Update agent | `8080` | Private Compose network |

PostgreSQL receives a random database name, username, and 64-character password. The update agent receives a separate random secret. These values are stored in `/opt/weblexai/.env`, which is created with owner-only permissions.

Do not publish PostgreSQL or Redis ports to the host. View the generated configuration only when required:

```bash
sudo cat /opt/weblexai/.env
```

## HTTPS With Your Own Domain

The installer serves plain HTTP by default. To enable automatic HTTPS certificates with your own domain:

1. Point an `A` record at the server IP (for example `translations.example.com -> 203.0.113.10`).
2. Re-run the installer with the domain set:

```bash
sudo WEBLEX_DOMAIN=translations.example.com sh install.sh
```

3. Allow TCP `80` and `443` in the server firewall. Certificates are issued and renewed automatically by the included Caddy proxy.

The public URL becomes `https://translations.example.com`. Set `WEBLEX_EMAIL` (also as an installer environment variable) to receive Let's Encrypt expiry notices.

Without a domain, the stack keeps serving plain HTTP on the configured port. HTTPS is required for browser SDK traffic from other sites, so production installs should use a domain.

## Commands

```bash
cd /opt/weblexai
sudo docker compose ps
sudo docker compose logs -f app worker scheduler update-agent
sudo docker compose --profile updates pull
sudo docker compose --profile updates up -d
```

The application, worker, and scheduler use the same versioned WeblexAI image. PostgreSQL data, Redis data, application storage, and the final application environment are stored in named Docker volumes.
