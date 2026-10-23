# LOAN – Geräteausleihe & Raumbuchung

Fork von [LOAN](https://github.com/dusanvin/LOAN) (MIT), umfassend überarbeitet: neues Design mit Dark Mode,
mobil nutzbar, Inventar-IDs mit QR- und Barcode-Etiketten, Kalender, Rechteverwaltung und Audit-Log.

**Stack:** Laravel 12 · PHP 8.4 · MariaDB · Tailwind CSS 4 · Alpine.js (CSP-Build) · Docker Compose

## Funktionen

- **Dashboard**: fällige, überfällige und anstehende Rückgaben, Vormerkungen, Raumbelegung
- **Geräte** mit Kategorie, Bild, Bestand und **Zubehör-Checkliste** (z. B. Kaltgerätekabel)
- **Inventar-IDs** aus Kategorie-Kürzel und Nummer (`KAB-0042`), Exemplare mit Endung (`KAB-0042-3`)
  - **Etiketten** mit QR-Code, Code-128-Barcode oder nur Text (A4-Bögen oder Etikettenrollen)
  - **Scannen**: Handy-Kamera, Scanner-Seite im Browser, Hand-Scanner oder ID eintippen
  - Doppelt ausgegebene Exemplare werden erkannt
- **Ausleihe**:
  - Menge oder einzelne Exemplare
  - **Teilrückgabe**, **Verlängerung**
  - Fehlendes Zubehör wird vermerkt
- **Vormerkungen** mit Prüfung auf Verfügbarkeit, **mit einem Klick als Ausleihe ausgeben**
- **Raumbuchungen** mit Überschneidungsprüfung, auch über mehrere Tage
- **Kalender** für Räume, Vormerkungen und Ausleihen (Monat, Woche, Liste)
- **Globale Suche** (Strg+K) nach Gerät, ID, Person oder Raum
- **Einstellungen**:
  - Name, Logo, Farbe, Zeitzone
  - Ausleihregeln
  - **Rechte-Matrix je Rolle**
  - Passwortregeln, Etiketten, Impressum/Datenschutz, Backups
- **Sicherheit**:
  - Content-Security-Policy, Rechteprüfung serverseitig
  - Sitzungsübersicht mit „überall abmelden“
  - Login-Drosselung, vollständiges Protokoll (CSV-Export)
- **Dark Mode** (System, Hell, Dunkel), Deutsch und Englisch

## Installation (Docker)

```bash
git clone <repo> loan && cd loan
cp .env.example .env
# In .env zuerst DB_PASSWORD, DB_ROOT_PASSWORD, APP_URL und MAIL_* setzen.
docker compose build
# APP_KEY erzeugen und in .env eintragen:
docker compose run --rm --no-deps --entrypoint php loan artisan key:generate --show
docker compose up -d
docker compose exec loan php artisan loan:create-admin du@example.org --name="Dein Name"
```

nginx lauscht standardmäßig nur auf `127.0.0.1:8080` (`APP_BIND`, `APP_PORT`). Für HTTPS einen Reverse Proxy
(Caddy, Traefik, nginx) auf demselben Host davorschalten; `TRUSTED_PROXIES` ist für Docker-Netze vorbelegt.
Ohne Proxy direkt im Netz: `APP_BIND=0.0.0.0` und `TRUSTED_PROXIES=` (leer).

`APP_URL` muss die öffentliche Adresse sein: Links in E-Mails werden daraus gebaut, Anfragen an
andere Hostnamen werden abgewiesen.

Daten liegen auf dem Host:

| Ordner | Inhalt |
|---|---|
| `./data/db` | Datenbank |
| `./data/storage` | Bilder und Logos |
| `./data/backups` | tägliche Backups |

**Bestehende Installation aktualisieren:** siehe [UPGRADE.md](UPGRADE.md), danach immer `./deploy.sh`.

## Entwicklung

```bash
docker build --target dev -t loan:dev .
docker run --rm -v "$PWD:/var/www/html" loan:dev composer install
docker run --rm -v "$PWD:/var/www/html" loan:dev php artisan test
docker run --rm -v "$PWD:/var/www/html" loan:dev vendor/bin/phpstan analyse
npm ci && npm run dev
```

Die CI (`.github/workflows/ci.yml`) prüft Tests, Pint, Larastan, `composer audit`, `npm audit` und den Docker-Build.

## Lizenz

MIT – siehe [LICENSE](LICENSE). Ursprüngliches Projekt von Vincent Dusanek.
