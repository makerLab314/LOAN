# Update auf die renovierte Version

Diese Anleitung gilt für bestehende Installationen mit Docker Compose. Rechne mit etwa 15 Minuten.
Beim ersten Start migriert LOAN die Datenbank automatisch:

- Ausleihen, die noch direkt am Gerät gespeichert waren, werden übernommen.
- Jedes Gerät bekommt eine Inventar-ID nach dem Muster `KAB-0001`.
- Raumbuchungen werden auf Datum und Uhrzeit umgestellt.
- Die bisherige Geräte-Historie wandert ins neue Protokoll.

## 1. Vorher

- Plane ein kurzes Wartungsfenster ein.
- Prüfe, welche Werte die laufende Installation nutzt. Sie stehen in `.env` bzw. in der alten `compose.yml`:
  - **APP_KEY**: unbedingt beibehalten.
  - **DB-Benutzer und -Passwort**: bisher fest `loan` / `loan`.

## 2. Code holen

```bash
cd /pfad/zu/LOAN
git fetch
git checkout renovation   # bzw. main, sobald gemergt
```

## 3. `.env` anlegen bzw. ergänzen

Lege eine `.env` aus `.env.example` an und übernimm die bestehenden Werte:

```bash
cp .env .env.alt 2>/dev/null || true
cp .env.example .env
```

| Variable | Wert |
|---|---|
| `APP_KEY` | **der bisherige Schlüssel** (aus `.env.alt` bzw. der Umgebung) |
| `APP_URL` | öffentliche Adresse, z. B. `https://loan.schule.de` |
| `DB_USERNAME` / `DB_PASSWORD` | bisher `loan` / `loan` (siehe 6.) |
| `DB_ROOT_PASSWORD` | bisher `rootpassword` |
| `SESSION_SECURE_COOKIE` | `true` bei HTTPS, sonst `false` |
| `TRUSTED_PROXIES` | `*`, wenn ein Reverse Proxy (z. B. Traefik, Caddy, nginx) davor sitzt |
| `MAIL_*` | SMTP-Zugang für „Passwort vergessen“ |
| `APP_PORT` | Host-Port für nginx (Standard 8080) |

> Die DB-Passwörter in der `.env` müssen zu der **bestehenden** Datenbank passen. MariaDB übernimmt neue
> Werte aus der Umgebung nur bei einer leeren Datenbank.

## 4. Update ausführen

```bash
chmod +x deploy.sh
./deploy.sh
```

Das Skript erledigt Folgendes:

1. Es sichert die Datenbank nach `./data/backups/`.
2. Es kopiert hochgeladene Bilder **aus dem alten Container** nach `./data/storage/`. Bisher lagen sie nur im Container und wären beim Neubau verloren gegangen.
3. Es baut das neue Image und startet alles neu. Migrationen laufen automatisch.

Danach erreichst du LOAN wie gewohnt. Zum Prüfen:

```bash
docker compose ps
docker compose logs -f loan
```

## 5. Nach dem Update

1. **Standard-Admin prüfen!** Der alte Seeder legte `admin@test.com` mit einem öffentlich bekannten
   Passwort an. Falls es dieses Konto noch gibt: Passwort ändern oder löschen. Einen neuen Admin
   (mit Zufallspasswort) legst du so an:

   ```bash
   docker compose exec loan php artisan loan:create-admin du@schule.de --name="Dein Name"
   ```

2. Unter **Einstellungen**:
   - Name, Logo und Farbe festlegen
   - **Rechte** für Moderation prüfen. Standard: ausleihen, zurücknehmen, vormerken, Räume buchen, Etiketten drucken. Stammdaten (Geräte, Räume, Kategorien) dürfen nur Admins pflegen, bis du es freigibst.
   - **Impressum/Datenschutz** hinterlegen. Die alten Seiten enthielten die Angaben des ursprünglichen Entwicklers.
3. Unter **Kategorien** die automatisch vergebenen Kürzel prüfen, bevor du Etiketten druckst. Spätere Änderungen gelten nur für neue Geräte.
4. **Etiketten** drucken und aufkleben.

## 6. Empfohlen: DB-Passwörter ändern

```bash
docker compose exec db mariadb -uroot -p
```

```sql
ALTER USER 'loan'@'%' IDENTIFIED BY 'neues-starkes-passwort';
ALTER USER 'root'@'%' IDENTIFIED BY 'neues-root-passwort';
ALTER USER 'root'@'localhost' IDENTIFIED BY 'neues-root-passwort';
```

Anschließend `DB_PASSWORD` und `DB_ROOT_PASSWORD` in `.env` anpassen und `docker compose up -d` ausführen.

## Künftige Updates

```bash
./deploy.sh
```

Das Skript erstellt vorher ein Backup (`php artisan loan:backup`). Tägliche Backups laufen
zusätzlich um 02:30 Uhr (Container `loan_scheduler`), einstellbar unter Einstellungen → Backups.

## Zurück zur alten Version

```bash
docker compose down
git checkout <alter-commit>
# Datenbank aus ./data/backups/loan-db-pre-update-*.sql.gz zurückspielen:
docker compose up -d db
gunzip -c data/backups/loan-db-pre-update-XXXX.sql.gz | docker compose exec -T db mariadb -uloan -p<passwort> loan
docker compose up -d
```

## Hinweise

- **Kamera-Scanner** im Browser funktioniert nur über **HTTPS**. Die normale Handy-Kamera erkennt die
  QR-Etiketten aber immer und öffnet direkt das Gerät.
- **Hand-Scanner** mit US-Tastaturlayout tippen „ß“ statt „-“. LOAN korrigiert das automatisch.
- Sitzungen liegen jetzt in der Datenbank. Nach dem Update müssen sich alle einmal neu anmelden.
