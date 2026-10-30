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

Wer ursprünglich vom Original [dusanvin/LOAN](https://github.com/dusanvin/LOAN) installiert hat, stellt
zuerst die Quelle um:

```bash
git remote set-url origin https://github.com/makerLab314/LOAN.git
```

Dann:

```bash
cd /pfad/zu/LOAN
git stash                 # lokale Anpassungen (z. B. Port in compose.yml) beiseitelegen
git fetch origin
git checkout main         # vor dem Merge: renovation
git pull --ff-only
```

Ohne `git stash` bricht `git pull` ab, sobald eine mitgelieferte Datei lokal geändert wurde. Beim Original
war das fast immer `compose.yml` (fehlerhaftes `depends_on: app`). Port und Adresse stehen künftig in der
`.env` (`APP_PORT`, `APP_BIND`), Änderungen an `compose.yml` sind nicht mehr nötig.

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
| `TRUSTED_PROXIES` | genau die Adresse des Reverse Proxys (Proxy auf demselben Host: `172.30.10.1`), leer ohne Proxy – keine ganzen Netze, nicht `*` |
| `MAIL_*` | SMTP-Zugang für „Passwort vergessen“ |
| `APP_PORT` | Host-Port für nginx (Standard 8080) |
| `APP_BIND` | `127.0.0.1` (Standard, nur für einen Proxy auf demselben Host) oder `0.0.0.0` (direkt im Netz erreichbar) |

> Die DB-Passwörter in der `.env` müssen zu der **bestehenden** Datenbank passen. MariaDB übernimmt neue
> Werte aus der Umgebung nur bei einer leeren Datenbank.

> **Ohne Reverse Proxy** (bisher üblich: direkt `http://server:8080`) ist LOAN nach dem Update sonst nicht
> mehr erreichbar, denn nginx lauscht jetzt standardmäßig nur auf `127.0.0.1`. Dann setzen:
> `APP_BIND=0.0.0.0`, `TRUSTED_PROXIES=` (leer), `SESSION_SECURE_COOKIE=false` und
> `APP_URL=http://server:8080` (genau die Adresse, die im Browser steht; andere Hostnamen werden abgewiesen).

## 4. Update ausführen

```bash
chmod +x deploy.sh
./deploy.sh
```

Das Skript erledigt Folgendes:

1. Es sichert die Datenbank nach `./data/backups/` und bricht ab, falls das nicht klappt.
2. Es kopiert hochgeladene Bilder **aus dem alten Container** nach `./data/storage/`. Bisher lagen sie nur im Container und wären beim Neubau verloren gegangen.
3. Es wechselt auf das neueste Release, lädt dessen fertiges Image von `ghcr.io` und startet alles neu.
   Migrationen laufen automatisch. Die Version steht danach als `LOAN_VERSION` in der `.env`.

Wer das Image lieber selbst baut (oder falls noch kein Release existiert): `./deploy.sh --build`.

**Empfohlen: Signatur prüfen.** Releases sind signierte Commits. Hinterlegst du den öffentlichen
Schlüssel, bricht `deploy.sh` ab, wenn ein Release-Stand nicht von dir signiert ist:

```bash
echo "deine@mail.de sk-ssh-ed25519@openssh.com AAAA… (öffentlicher Schlüssel)" > ~/.config/loan-allowed-signers
git config gpg.ssh.allowedSignersFile ~/.config/loan-allowed-signers
```

**Nach dem Update auf diese Version:** In der `.env` `TRUSTED_PROXIES=172.30.10.1` setzen (statt ganzer
privater Netze) und im Reverse Proxy `proxy_set_header X-Forwarded-For $remote_addr;` verwenden. Das
Compose-Netz bekommt dafür feste Adressen. Meldet `deploy.sh` am Ende, das Netz müsse neu angelegt
werden: einmalig `docker compose down && docker compose up -d`.

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
./deploy.sh              # neuestes Release
./deploy.sh v2.1.0       # bestimmte Version, auch zurück auf eine ältere
./deploy.sh --build      # Image selbst bauen
```

Das Skript erstellt vorher ein Backup (`php artisan loan:backup`). Tägliche Backups laufen
zusätzlich um 02:30 Uhr (Container `loan_scheduler`), einstellbar unter Einstellungen → Backups.

Beim Zurückgehen auf eine ältere Version bleibt die Datenbank auf dem neuen Stand. Hat die neuere
Version Migrationen mitgebracht, vorher das Backup aus `./data/backups/` zurückspielen.

## Zurück zur alten Version

```bash
docker compose down
git checkout <alter-commit>
git stash pop             # nur falls in Schritt 2 etwas beiseitegelegt wurde
# Datenbank aus ./data/backups/loan-db-pre-update-*.sql.gz zurückspielen:
docker compose up -d db
gunzip -c data/backups/loan-db-pre-update-XXXX.sql.gz | docker compose exec -T db mariadb -uloan -p<passwort> loan
docker compose up -d --build   # --build, sonst startet das neue Image mit der alten Konfiguration
```

Die alte Version speichert Bilder nur im Container. Die gesicherten Dateien aus `./data/storage/public/`
müssen dafür per `docker cp` zurück nach `loan_app:/var/www/html/storage/app/public/`.

## Hinweise

- **Kamera-Scanner** im Browser funktioniert nur über **HTTPS**. Die normale Handy-Kamera erkennt die
  QR-Etiketten aber immer und öffnet direkt das Gerät.
- **Hand-Scanner** mit US-Tastaturlayout tippen „ß“ statt „-“. LOAN korrigiert das automatisch.
- Sitzungen liegen jetzt in der Datenbank. Nach dem Update müssen sich alle einmal neu anmelden.
