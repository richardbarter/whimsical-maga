#!/bin/sh
set -e

# Discover packages based on what is actually installed in vendor.
# This runs in both dev and production to avoid stale cache issues.
php artisan package:discover --ansi

# Cache config, routes, and views at runtime so Fly.io secrets are available.
# config:cache must run first — route:cache and view:cache read view.compiled from the
# config cache, bypassing the realpath() call that fails without a prior config cache.
# Skip in local dev — cached files go stale when code/env changes with volume-mounted code.
if [ "$APP_ENV" != "local" ]; then
    echo "APP_ENV=${APP_ENV}, caching config, routes, and views..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Create the public/storage symlink
php artisan storage:link --force

# Run database migrations
php artisan migrate --force

# Start Supervisor (manages Nginx + PHP-FPM)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
