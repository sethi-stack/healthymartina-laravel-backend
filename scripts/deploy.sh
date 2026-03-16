#!/bin/bash
# Server-side deploy script for HealthyMartina Laravel API
# Run this on the Droplet, or triggered by the GitHub Action.
# Usage: bash /var/www/healthymartina/laravel-backend-app/scripts/deploy.sh

set -e

APP_DIR="/var/www/healthymartina/laravel-backend-app"

echo "==> Pulling latest code..."
cd "$APP_DIR"
git pull origin main

echo "==> Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Caching config / routes / views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Reloading PHP-FPM..."
sudo systemctl reload php8.2-fpm

echo "==> Done. $(date)"
