#!/bin/bash

# Install Laravel Octane
composer require laravel/octane

# Install Swoole PHP extension
if ! php -m | grep -q "swoole"; then
    echo "Installing Swoole PHP extension..."
    
    # Check if pecl is installed
    if ! command -v pecl &> /dev/null; then
        echo "PECL is not installed. Please install PECL first."
        exit 1
    fi
    
    # Install Swoole
    pecl install swoole
    
    # Add extension to php.ini
    echo "extension=swoole.so" | sudo tee -a $(php --ini | grep "Loaded Configuration File" | sed -e "s|.*:\s*||")
    
    echo "Swoole PHP extension installed."
else
    echo "Swoole PHP extension is already installed."
fi

# Install Octane
php artisan octane:install --server=swoole

# Create Octane configuration
php artisan vendor:publish --tag=octane-config

echo "Laravel Octane has been installed and configured!"
echo "You can start the Octane server with: php artisan octane:start"
echo "For production, consider using: php artisan octane:start --workers=4 --task-workers=2"