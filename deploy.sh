#!/bin/bash
set -e

DEPLOY_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "==> Pulling latest code..."
cd "$DEPLOY_DIR"
git pull origin main

echo "==> Rebuilding containers..."
docker compose build php

echo "==> Restarting app container..."
docker compose up -d --force-recreate php

echo "==> Running migrations & cache inside container..."
docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
docker compose exec -T php php bin/console cache:clear --no-interaction
docker compose exec -T php php bin/console cache:warmup --no-interaction

echo "==> Clearing cache directory..."
sudo rm -rf "$DEPLOY_DIR/app/var/cache"

echo ""
echo "==> Deploy complete!"