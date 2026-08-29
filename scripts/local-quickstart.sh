#!/usr/bin/env bash
set -eu

# WeblexAI local evaluation quickstart.
#
# Runs the full stack on your machine with Docker Desktop (Windows, macOS)
# or Docker Engine (Linux) so you can evaluate WeblexAI before renting a
# server. Data stays in local Docker volumes.
#
# Requirements:
#   - Docker with Docker Compose v2 (Docker Desktop on Windows or macOS)
#   - curl
#
# Windows: run this from Git Bash or WSL2.
#
# Usage:
#   bash scripts/local-quickstart.sh

port="${WEBLEX_PORT:-8787}"

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker is required. Install Docker Desktop and run this again." >&2
    exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
    echo "Docker Compose v2 is required." >&2
    exit 1
fi

if ! command -v curl >/dev/null 2>&1; then
    echo "curl is required." >&2
    exit 1
fi

cd "$(dirname "$0")/.."

if ! command -v openssl >/dev/null 2>&1; then
    echo "openssl is required to generate local secrets." >&2
    exit 1
fi

set_env_value() {
    key="$1"
    value="$2"
    temporary=".env.${key}.tmp"

    if grep -q "^${key}=" .env; then
        sed "s|^${key}=.*|${key}=${value}|" .env > "$temporary"
    else
        cp .env "$temporary"
        printf '\n%s=%s\n' "$key" "$value" >> "$temporary"
    fi

    mv "$temporary" .env
}

if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env from .env.example."
fi

set_env_value APP_KEY "base64:$(openssl rand -base64 32 | tr -d '\n')"
set_env_value DB_PASSWORD "$(openssl rand -hex 32)"
set_env_value UPDATE_AGENT_SECRET "$(openssl rand -hex 32)"
set_env_value APP_URL "http://localhost:${port}"
set_env_value APP_INSTALLED "false"

echo "Building the application image. The first build takes several minutes; later starts are fast."
docker compose up -d --build

attempt=0
until curl -fsS "http://127.0.0.1:${port}/up" >/dev/null 2>&1; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 120 ]; then
        echo "WeblexAI did not become healthy. Run: docker compose logs app" >&2
        exit 1
    fi
    sleep 2
done

echo
echo "WeblexAI is running locally."
echo "Open http://localhost:${port}/install to finish setup."
echo
echo "Next steps:"
echo "  1. Complete the browser installer and create the first administrator."
echo "  2. Add a provider credential in /admin (you need a Google, OpenAI, OpenRouter, Gemini, or Qwen API key)."
echo "  3. Create a project, add an accepted origin, and add at least one target language."
echo "  4. Serve a test website: cd examples/plain-html && python -m http.server 4173"
echo "  5. Add http://localhost:4173 as an accepted origin and load the SDK from the example page."
echo
echo "Stop everything with: docker compose down"
