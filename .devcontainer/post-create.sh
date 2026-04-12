#!/bin/bash
set -e

echo "==> Installing PHP gd extension (required by phpspreadsheet)..."
sudo apt-get install -y libpng-dev libfreetype6-dev libjpeg62-turbo-dev > /dev/null 2>&1
docker-php-ext-install gd

echo "==> Installing PHP dependencies..."
composer install

echo "==> Installing Node dependencies..."
npm install

echo "==> Setting up .env file..."
cp .env.example .env

echo "==> Generating application key..."
php artisan key:generate

echo "==> Creating SQLite database file..."
touch database/database.sqlite

echo "==> Running database migrations..."
php artisan migrate --force

echo ""
echo "==> Setup complete!"
echo "    Run:  php artisan serve"
echo "    Then open http://localhost:8000"
