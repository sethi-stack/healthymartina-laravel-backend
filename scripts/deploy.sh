#!/bin/bash
# Server-side deploy script for HealthyMartina Laravel API
# Run this on the Droplet, or triggered by the GitHub Action.
# Usage: bash /var/www/healthymartina/api/scripts/deploy.sh

set -e

APP_DIR="/var/www/healthymartina/api"
PHP_BIN="php8.3"
PHP_FPM_SERVICE="php8.3-fpm"

echo "==> Pulling latest code..."
cd "$APP_DIR"
git pull origin main

echo "==> Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Running migrations..."
"$PHP_BIN" artisan migrate --force

echo "==> Caching config / routes / views..."
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

echo "==> Reloading PHP-FPM..."
sudo systemctl reload "$PHP_FPM_SERVICE"

echo "==> Done. $(date)"
