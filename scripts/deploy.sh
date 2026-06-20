#!/bin/bash
# Server-side deploy script for HealthyMartina Laravel API
# Run this on the Droplet, or triggered by the GitHub Action.
# Usage: bash /var/www/healthymartina/api/scripts/deploy.sh

set -e

APP_DIR="/var/www/healthymartina/api"
PDF_SERVICE_DIR="$APP_DIR/pdf-export-service"
PHP_BIN="php8.3"
PHP_FPM_SERVICE="php8.3-fpm"

echo "==> Pulling latest code..."
cd "$APP_DIR"
git pull origin main
CURRENT_SHA="$(git rev-parse HEAD)"
echo "==> Deployed commit: $CURRENT_SHA"

echo "==> Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

if [ -d "$PDF_SERVICE_DIR" ]; then
  echo "==> Installing PDF export service dependencies..."
  cd "$PDF_SERVICE_DIR"

  # Keep existing environment values on server; only bootstrap once if missing.
  if [ ! -f ".env" ] && [ -f ".env.example" ]; then
    cp .env.example .env
    echo "==> Created pdf-export-service/.env from .env.example (please verify secrets)"
  fi

  npm ci --omit=dev

  echo "==> Verifying PDF compression code is present..."
  if grep -q "compressCalendarImages" "$PDF_SERVICE_DIR/src/server.js"; then
    echo "    - server.js includes calendar image compression"
  else
    echo "    - WARNING: server.js does not include calendar image compression"
  fi

  if grep -q 'data-pdf-calendar-image="1"' "$PDF_SERVICE_DIR/src/templates/legacy/components/weeklyPlan.js"; then
    echo "    - weeklyPlan.js includes calendar image marker"
  else
    echo "    - WARNING: weeklyPlan.js does not include calendar image marker"
  fi
else
  echo "==> PDF export service directory not found, skipping Node deploy step."
fi

cd "$APP_DIR"

echo "==> Running migrations..."
"$PHP_BIN" artisan migrate --force

echo "==> Caching config / routes / views..."
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan basset:cache

echo "==> Reloading PHP-FPM..."
sudo systemctl reload "$PHP_FPM_SERVICE"

if systemctl list-unit-files | grep -q '^pdf-export\.service'; then
  echo "==> Restarting PDF export service..."
  sudo systemctl restart pdf-export
  sleep 2
  echo "==> PDF export service status:"
  sudo systemctl --no-pager --full status pdf-export | sed -n '1,18p'
  echo "==> Recent PDF export service logs:"
  sudo journalctl -u pdf-export -n 20 --no-pager
fi

echo "==> Done. $(date)"
