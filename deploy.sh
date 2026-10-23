#!/usr/bin/env bash
# LOAN aktualisieren: Backup → Code und Image holen → Container neu starten.
# Aufruf im Projektordner auf dem Server:
#
#   ./deploy.sh            neuestes Release (fertiges Image von ghcr.io)
#   ./deploy.sh v2.1.0     bestimmte Version, z. B. um zurückzugehen
#   ./deploy.sh --build    aktuellen Branch holen und Image lokal bauen
set -euo pipefail

# Alles steht in main(), damit Bash das Skript vollständig einliest, bevor
# "git checkout" bzw. "git pull" es während des Laufs ersetzt.
main() {
    cd "$(dirname "$0")"

    local mode=release target="" version
    case "${1:-}" in
        "") ;;
        --build) mode=build ;;
        v*) target=$1 ;;
        *)
            echo "Aufruf: ./deploy.sh [vX.Y.Z | --build]" >&2
            exit 1
            ;;
    esac

    if [ ! -f .env ]; then
        echo "Keine .env gefunden – siehe UPGRADE.md" >&2
        exit 1
    fi

    for var in APP_KEY DB_PASSWORD DB_ROOT_PASSWORD; do
        if ! grep -Eq "^${var}=.+" .env; then
            echo "$var ist in .env nicht gesetzt – siehe UPGRADE.md" >&2
            exit 1
        fi
    done

    # Version festlegen, bevor irgendetwas verändert wird
    if [ "$mode" = release ]; then
        git fetch --tags --force --quiet origin
        if [ -z "$target" ]; then
            # Neuestes Release ohne Vorabversionen (v2.1.0-rc.1)
            target=$(git tag -l 'v*' --sort=-v:refname | grep -v -- - | head -n1 || true)
        fi
        if [ -z "$target" ]; then
            echo "Kein Release gefunden. Zeigt origin auf github.com/makerLab314/LOAN? Lokal bauen: ./deploy.sh --build" >&2
            exit 1
        fi
        if ! git rev-parse -q --verify "refs/tags/$target" >/dev/null; then
            echo "Version $target gibt es nicht. Vorhanden: $(git tag -l 'v*' --sort=-v:refname | head -n5 | tr '\n' ' ')" >&2
            exit 1
        fi
        echo "==> Update auf $target"
    fi

    echo "==> Backup vor dem Update"
    if docker compose ps --status running loan >/dev/null 2>&1 && docker compose exec -T loan php artisan list loan 2>/dev/null | grep -q "loan:backup"; then
        if ! docker compose exec -T -u www-data loan php artisan loan:backup --force; then
            echo "WARNUNG: Backup fehlgeschlagen – Abbruch mit Strg+C, sonst geht es in 10 s weiter."
            sleep 10
        fi
    elif [ ! -d data/db/mysql ]; then
        echo "Neuinstallation – nichts zu sichern"
    else
        # Erstes Update von der alten Version: Datenbank direkt aus dem DB-Container sichern.
        # Die Migrationen danach entfernen Altspalten – ohne Backup daher abbrechen.
        mkdir -p data/backups
        local dump
        dump="data/backups/loan-db-pre-update-$(date +%Y-%m-%d_%H%M%S).sql.gz"
        if ! docker compose exec -T db sh -c 'exec mariadb-dump -u"${MARIADB_USER:-$MYSQL_USER}" -p"${MARIADB_PASSWORD:-$MYSQL_PASSWORD}" "${MARIADB_DATABASE:-$MYSQL_DATABASE}"' \
            | gzip > "$dump"; then
            rm -f "$dump"
            echo "Datenbank-Backup fehlgeschlagen – Abbruch. Läuft der Container \"db\"? Siehe UPGRADE.md" >&2
            exit 1
        fi
    fi

    # Bis zur Renovierung lagen hochgeladene Bilder nur im App-Container (kein Volume).
    # Vor dem Neubau einmalig nach ./data/storage retten – sonst gehen sie beim Neustart verloren.
    if [ ! -d data/storage/public ] && docker ps -a --format '{{.Names}}' | grep -qx loan_app; then
        echo "==> Uploads aus dem alten Container sichern"
        mkdir -p data/storage
        if ! docker cp loan_app:/var/www/html/storage/app/. data/storage/; then
            echo "Uploads konnten nicht kopiert werden – Abbruch." >&2
            exit 1
        fi
    fi

    echo "==> Code aktualisieren"
    if [ "$mode" = release ]; then
        git -c advice.detachedHead=false checkout --quiet "$target"
        version=${target#v}
    else
        if git symbolic-ref -q HEAD >/dev/null; then
            git pull --ff-only
        else
            echo "Kein Branch ausgecheckt (Release-Stand) – baue den vorhandenen Code"
        fi
        version=local
    fi

    # In .env festhalten, damit auch spätere "docker compose"-Befehle dieses Image nutzen
    set_env LOAN_VERSION "$version"

    if [ "$mode" = release ]; then
        echo "==> Images laden"
        docker compose pull
    else
        echo "==> Image bauen"
        docker compose pull db nginx
        docker compose build --pull loan
    fi

    echo "==> Container neu starten (Migrationen laufen automatisch)"
    docker compose up -d --remove-orphans

    echo "==> Status"
    docker compose ps
    echo "Fertig ($version). Logs: docker compose logs -f loan"
}

set_env() {
    if grep -q "^$1=" .env; then
        sed -i "s|^$1=.*|$1=$2|" .env
    else
        printf '\n%s=%s\n' "$1" "$2" >> .env
    fi
}

main "$@"
exit
