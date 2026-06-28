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

## Commands

```bash
cd /opt/weblexai
sudo docker compose ps
sudo docker compose logs -f app worker scheduler update-agent
sudo docker compose --profile updates pull
sudo docker compose --profile updates up -d
```

The application, worker, and scheduler use the same versioned WeblexAI image. PostgreSQL data, Redis data, application storage, and the final application environment are stored in named Docker volumes.
