#!/usr/bin/env bash
set -Eeuo pipefail

cd "$(dirname "$0")/.."

if [[ ! -f .env ]]; then
    echo "Missing .env. Copy deploy/hostinger.env.example to .env and configure it first."
    exit 1
fi

if [[ ! -f public/build/manifest.json ]]; then
    echo "Missing public/build/manifest.json. Run 'npm ci && npm run build' locally, then deploy the build directory."
    exit 1
fi

php artisan down --retry=60 || true
trap 'php artisan up || true' EXIT

composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php artisan migrate --force
php artisan storage:link || true
php artisan optimize:clear
php artisan optimize
php artisan schedule:interrupt || true

chmod -R ug+rwX storage bootstrap/cache

php artisan up
trap - EXIT

echo "Hostinger deployment completed successfully."
