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

# A command was passed — e.g. Fly's release_command (`php artisan migrate --force`),
# which Fly runs through this ENTRYPOINT in a temporary machine. Run it and exit
# instead of starting the web server.
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

# Create the public/storage symlink
php artisan storage:link --force

# Production runs migrations once per deploy via release_command in fly.toml;
# local Docker keeps migrating on boot for convenience.
if [ "$APP_ENV" = "local" ]; then
    php artisan migrate --force
fi

# Start Supervisor (manages Nginx + PHP-FPM)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
