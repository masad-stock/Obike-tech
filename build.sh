#!/bin/bash
set -e

composer install --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run production

php artisan config:cache
php artisan route:cache
php artisan view:cache
