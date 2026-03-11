#!/bin/bash
set -e

DEPLOY_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$DEPLOY_DIR"

echo "==> Pulling latest code..."
git pull origin main

echo "==> Rebuilding containers..."
docker compose build php

echo "==> Restarting app container..."
docker compose up -d --force-recreate php

echo ""
echo "==> Deploy complete! (entrypoint handles migrations, cache & permissions)"
