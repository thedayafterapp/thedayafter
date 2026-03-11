#!/bin/bash
set -e

cd /var/www/html

echo "==> Installing PHP dependencies..."
if [ ! -f vendor/autoload.php ]; then
    COMPOSER_ALLOW_SUPERUSER=1 composer update \
        --no-interaction \
        --no-scripts \
        --no-security-blocking \
        --prefer-dist \
        --optimize-autoloader
    echo "    Done."
else
    echo "    vendor/ already present, skipping."
fi

echo "==> Clearing old cache..."
rm -rf var/cache/*
mkdir -p var/cache/prod var/log

echo "==> Waiting for MySQL..."
until php -r "
  try {
    \$pdo = new PDO('mysql:host=mysql;dbname=alco', 'alco', 'alcopass', [PDO::ATTR_TIMEOUT => 3]);
    echo 'ok';
  } catch (Exception \$e) { exit(1); }
" 2>/dev/null | grep -q ok; do
  echo "    MySQL not ready, retrying..."
  sleep 2
done
echo "==> MySQL ready!"

echo "==> Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>/dev/null \
  || php bin/console doctrine:schema:update --force --no-interaction 2>/dev/null \
  || echo "    Migration warning (continuing)"

echo "==> Seeding achievements..."
php bin/console app:seed-achievements --no-interaction 2>/dev/null || true

echo "==> Warming cache..."
php bin/console cache:warmup --no-interaction 2>/dev/null || true

echo "==> Fixing permissions..."
chown -R www-data:www-data var/

echo ""
echo "==> App ready at http://localhost:8080"
echo ""

exec "$@"
