#!/bin/sh
set -e

php artisan storage:link --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan db:seed --class=PermisoRolSeeder --force

exec /usr/bin/supervisord -c /etc/supervisord.conf
