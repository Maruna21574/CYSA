#!/usr/bin/env bash
# Deployment on the server (Websupport, over SSH). Run from the project root:
#   bash deploy.sh
# Assets (public/build) are built locally with `npm run build` and uploaded separately,
# see docs/DEPLOYMENT.md.
set -euo pipefail

PHP=${PHP:-php}
# On some hostings composer is available only as a phar: COMPOSER="php composer.phar" bash deploy.sh
COMPOSER=${COMPOSER:-composer}

$PHP artisan down --retry=30 || true

git pull --ff-only
$COMPOSER install --no-dev --optimize-autoloader --no-interaction

$PHP artisan migrate --force
$PHP artisan optimize:clear
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan event:cache

$PHP artisan queue:restart || true
$PHP artisan up

echo "Deployment finished."
