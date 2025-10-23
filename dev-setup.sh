#!/bin/bash
# dev-setup.sh

echo " STU Key Management - Development Setup"

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate:fresh

# Seed database
php artisan db:seed

# Storage link
php artisan storage:link

# Cache clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Generate IDE helper
php artisan ide-helper:generate
php artisan ide-helper:models --nowrite
php artisan ide-helper:meta

echo " Setup complete! Run: php artisan serve"
