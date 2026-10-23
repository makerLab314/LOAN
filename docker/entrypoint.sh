#!/bin/sh
set -e

cd /var/www/html

# Nur der App-Container (php-fpm) führt Migrationen & Caches aus;
# scheduler/backup starten mit anderem Kommando und überspringen das.
if [ "$1" = "php-fpm" ]; then
    # Öffentliche Dateien (Build-Assets, Bilder) in das mit nginx geteilte Volume spiegeln
    if [ -d /var/www/public-shared ]; then
        rm -rf /var/www/public-shared/build
        cp -a /opt/public-dist/. /var/www/public-shared/
    fi

    mkdir -p storage/app/public storage/framework/cache storage/framework/sessions \
        storage/framework/views storage/logs
    chown -R www-data:www-data storage

    # Auf die Datenbank warten
    tries=0
    until php artisan db:show --counts >/dev/null 2>&1; do
        tries=$((tries + 1))
        if [ "$tries" -ge 30 ]; then
            echo "Datenbank nicht erreichbar – Abbruch." >&2
            exit 1
        fi
        echo "Warte auf Datenbank ($tries)..."
        sleep 2
    done

    php artisan migrate --force
    php artisan storage:link --force >/dev/null 2>&1 || true
    php artisan optimize
fi

exec "$@"
