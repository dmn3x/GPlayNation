#!/bin/sh
set -eu

# The gpn-serve container sets RUN_BOOTSTRAP=true. One-off commands run from the
# same image (artisan, tests) skip the one-time boot work below.
if [ "${RUN_BOOTSTRAP:-false}" = "true" ]; then
    cd /var/www/html

    if [ -z "${APP_KEY:-}" ]; then
        echo "ERROR: APP_KEY is empty. Generate one and put it in .env.docker:" >&2
        echo "  echo \"base64:\$(openssl rand -base64 32)\"" >&2
        exit 1
    fi

    mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache

    if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
        db_path="${DB_DATABASE:-database/database.sqlite}"
        mkdir -p "$(dirname "$db_path")"
        [ -e "$db_path" ] || touch "$db_path"
        chown www-data:www-data "$db_path"
    fi

    chown -R www-data:www-data storage bootstrap/cache
    ln -sfn /var/www/html/storage/app/public public/storage

    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        attempts=0
        until su-exec www-data php artisan migrate --force; do
            attempts=$((attempts + 1))
            if [ "$attempts" -ge 10 ]; then
                echo "ERROR: database migrations failed after $attempts attempts." >&2
                exit 1
            fi
            echo "Database is not ready (attempt $attempts/10), retrying in 3s..." >&2
            sleep 3
        done
    fi

    if [ "${RUN_OPTIMIZE:-true}" = "true" ]; then
        su-exec www-data php artisan optimize
    fi
fi

exec "$@"
