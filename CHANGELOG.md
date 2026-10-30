# Änderungen

Der Abschnitt einer Version landet beim Release automatisch oben in den Release-Notizen
auf GitHub (siehe `.github/workflows/release.yml`). Neue Version: Abschnitt `## vX.Y.Z`
oben einfügen, dann taggen.

## v2.6.0

Sicherheits-Update für den Betrieb im Internet. **Vor dem Update lesen:** Abschnitt
„Beim Update“ unten.

### Sicherheit
- **Client-IP nicht mehr fälschbar:** `TRUSTED_PROXIES` vertraut nur noch der genauen Adresse des
  Reverse Proxys (`172.30.10.1`), nicht mehr ganzen privaten Netzen. Vorher ließ sich aus dem
  Schulnetz per `X-Forwarded-For` die Login-Sperre umgehen und die IP im Protokoll fälschen.
  Die Einstellung wird jetzt über `config/trustedproxy.php` gelesen und greift damit auch
  außerhalb von Docker.
- **Login-Sperre pro Konto:** nach 20 Fehlversuchen 15 Minuten, egal von welchen IPs. IPv6-Adressen
  zählen als ganzes /64-Netz.
- **Kiosk-Rückgabe:** Ein Scan deckt genau eine Rückgabe, auch bei mehreren Ausleihen auf einmal.
  Gleichzeitige Rückgaben derselben Sitzung laufen nacheinander.
- **Datenschutz am Kiosk:** Übersicht, Ausleihen, Geräteseite und Kalender zeigen dort nur noch
  Initialen der Ausleihenden.
- Admins können sich nicht mehr gleichzeitig gegenseitig herabstufen oder löschen, bis keiner übrig
  ist. Widerrufen einer Vormerkung und Verringern der Gerätemenge laufen unter Sperre.
- Nach einer Passwortänderung gilt ein altes „Angemeldet bleiben“-Cookie auch auf den Seiten zur
  Passwortbestätigung und zum Abmelden nicht mehr. `loan:create-admin` meldet ein bestehendes Konto
  überall ab.

### Geändert
- **Kiosk-Rückgabe ohne Scan:** Nur was mit Scan (oder ID-Eingabe) ausgeliehen wurde, braucht auch zur
  Rückgabe einen Scan. Alles andere – etwa ein großer Lautsprecher oder ein Gerät mit abgefallenem
  Etikett – gibt der Kiosk direkt auf der Geräteseite zurück. Bestehende Ausleihen zählen als
  „ohne Scan“.

### Betrieb
- nginx: Grenze von 20 Anfragen/s pro Client (Burst 100) und 40 gleichzeitigen Verbindungen,
  kürzere Timeouts für langsame Clients.
- PHP-FPM: 16 statt 5 Prozesse, hängende Anfragen werden nach 70 s beendet
  (`docker/php-fpm.conf`). `register_argc_argv` ist aus.
- `deploy.sh` prüft die Signatur des Release-Commits, sobald `gpg.ssh.allowedSignersFile`
  eingerichtet ist (siehe `UPGRADE.md`).
- GitHub Actions sind auf Commit-SHAs gepinnt; Dependabot hält sie aktuell.

### Beim Update
1. In der `.env`: `TRUSTED_PROXIES=172.30.10.1`, wenn der Reverse Proxy auf demselben Server läuft
   und auf `127.0.0.1:8080` zeigt. Kommt er anders an (andere Maschine, eigenes Docker-Netz), die
   echte Adresse eintragen – auch in `conf/default.conf` bei `set_real_ip_from`. Sonst teilen sich
   alle Nutzer eine IP und damit Login-Sperre und Anfragegrenze.
2. Im Reverse Proxy `proxy_set_header X-Forwarded-For $remote_addr;` verwenden.
3. Das Compose-Netz bekommt feste Adressen. Meldet `deploy.sh` am Ende, das Netz müsse neu angelegt
   werden: einmalig `docker compose down && docker compose up -d`.
4. Bekannt: Der Button „Backup jetzt“ bricht nach 70 s ab. Das nächtliche Backup und das Backup in
   `deploy.sh` sind nicht betroffen.

## v2.5.0 (2026-10-28)
Neues Design mit Glas-Optik und Inter (Kiosk ohne Effekte), mehr Etikettenformate mit eigenem Format
und Gestaltungsoptionen, Handyfotos werden richtig gedreht, Pagination im Theme, Formulare für
Passwortmanager, README auf Englisch mit Screenshots.

## v2.4.0 (2026-10-27)
Rolle Raumplanung (nur Räume, Buchungen, Kalender), Kalender mit Saalfarben und neuer
Termindarstellung, Fix Tagesleiste im Buchungsformular.

## v2.3.0 (2026-10-27)
Raumbuchung mit Ansprechperson, Live-Konfliktprüfung und Dauer-Presets; Gerätesuche bleibt nach
Zurück erhalten.

## v2.2.0 (2026-10-26)
Einfachere Oberfläche; nach der ersten Anmeldung mit einem vom Admin vergebenen Passwort muss ein
eigenes gesetzt werden.

## v2.1.0 (2026-10-24)

## v2.0.1 (2026-10-24)

## v2.0.0 (2026-10-23)
Renovierung.
