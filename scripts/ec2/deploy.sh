#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/notifix}"

cd "$APP_DIR"
git pull origin main
composer install --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl reload nginx

echo "Deploy completed."
