#!/bin/sh

echo "==> Running database migrations..."
php /var/www/run_migrations.php
if [ $? -ne 0 ]; then
    echo "WARNING: Migrations failed. Check logs. Continuing anyway..."
fi

echo "==> Seeding admin accounts..."
php /var/www/docker/php/seed_admin.php
if [ $? -ne 0 ]; then
    echo "WARNING: Seeding failed. Check logs. Continuing anyway..."
fi

echo "==> Starting PHP-FPM..."
exec php-fpm -F
