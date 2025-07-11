# Create new Laravel project
composer create-project laravel/laravel obike-tech-system

cd obike-tech-system

# Install key packages
composer require spatie/laravel-permission # For role management
composer require laravel/ui # For authentication scaffolding
composer require barryvdh/laravel-dompdf # For PDF generation
composer require maatwebsite/excel # For Excel imports/exports
composer require laravel/cashier # For Stripe payment processing

# Generate authentication scaffolding
php artisan ui bootstrap --auth

# Create database migrations
php artisan migrate
