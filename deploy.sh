#!/bin/bash
set -e

DEPLOY_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "==> Pulling latest code..."
cd "$DEPLOY_DIR"
git pull origin main

echo "==> Clearing cache directory..."
sudo rm -rf "$DEPLOY_DIR/app/var/cache"

echo "==> Rebuilding containers..."
docker compose build php

echo "==> Restarting app container..."
docker compose up -d --force-recreate php

echo "==> Running migrations..."
docker compose exec -T -u www-data php php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "==> Fixing cache ownership..."
docker compose exec -T php chown -R www-data:www-data var/cache var/log

echo "==> Warming cache as www-data..."
docker compose exec -T -u www-data php php bin/console cache:warmup --no-interaction

echo ""
echo "==> Deploy complete!"