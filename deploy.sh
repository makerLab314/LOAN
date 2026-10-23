#!/usr/bin/env bash
# LOAN aktualisieren: Backup → git pull → Image bauen → Container neu starten.
# Aufruf im Projektordner auf dem Server:  ./deploy.sh
set -euo pipefail

cd "$(dirname "$0")"

if [ ! -f .env ]; then
    echo "Keine .env gefunden – siehe UPGRADE.md" >&2
    exit 1
fi

echo "==> Backup vor dem Update"
if docker compose ps --status running loan >/dev/null 2>&1 && docker compose exec -T loan php artisan list loan 2>/dev/null | grep -q "loan:backup"; then
    if ! docker compose exec -T loan php artisan loan:backup --force; then
        echo "WARNUNG: Backup fehlgeschlagen – Abbruch mit Strg+C, sonst geht es in 10 s weiter."
        sleep 10
    fi
else
    # Erstes Update von der alten Version: Datenbank direkt aus dem DB-Container sichern
    mkdir -p data/backups
    docker compose exec -T db sh -c 'exec mariadb-dump -u"${MARIADB_USER:-$MYSQL_USER}" -p"${MARIADB_PASSWORD:-$MYSQL_PASSWORD}" "${MARIADB_DATABASE:-$MYSQL_DATABASE}"' \
        | gzip > "data/backups/loan-db-pre-update-$(date +%Y-%m-%d_%H%M%S).sql.gz" \
        || echo "WARNUNG: Datenbank-Backup fehlgeschlagen"
fi

# Bis zur Renovierung lagen hochgeladene Bilder nur im App-Container (kein Volume).
# Vor dem Neubau einmalig nach ./data/storage retten.
if [ ! -d data/storage/public ] && docker ps --format '{{.Names}}' | grep -qx loan_app; then
    echo "==> Uploads aus dem alten Container sichern"
    mkdir -p data/storage
    docker cp loan_app:/var/www/html/storage/app/. data/storage/ || echo "WARNUNG: Uploads konnten nicht kopiert werden"
fi

echo "==> Code aktualisieren"
git pull --ff-only

echo "==> Image bauen"
docker compose build --pull loan

echo "==> Container neu starten (Migrationen laufen automatisch)"
docker compose up -d --remove-orphans

echo "==> Status"
docker compose ps
echo "Fertig. Logs: docker compose logs -f loan"
