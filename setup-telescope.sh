#!/bin/bash

# Install Laravel Telescope
composer require laravel/telescope --dev

# Publish Telescope assets and migrations
php artisan telescope:install

# Run migrations to create Telescope tables
php artisan migrate

# Update composer.json to prevent auto-discovery in production
# This is done by adding laravel/telescope to the dont-discover section
if ! grep -q "dont-discover" composer.json; then
    echo "Adding dont-discover section to composer.json"
    sed -i 's/"extra": {/"extra": {\n        "laravel": {\n            "dont-discover": [\n                "laravel\/telescope"\n            ]\n        },/g' composer.json
else
    echo "dont-discover section already exists, adding laravel/telescope"
    sed -i '/dont-discover/a \                "laravel\/telescope"' composer.json
fi

# Add Telescope environment variables to .env
if ! grep -q "TELESCOPE_ENABLED" .env; then
    echo "Adding Telescope configuration to .env"
    echo "
# Telescope Configuration
TELESCOPE_ENABLED=true
TELESCOPE_PATH=telescope" >> .env
fi

echo "Laravel Telescope has been installed and configured!"
echo "You can access Telescope at: /telescope"