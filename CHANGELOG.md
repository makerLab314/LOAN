# Änderungen

Der Abschnitt einer Version landet beim Release automatisch oben in den Release-Notizen
auf GitHub (siehe `.github/workflows/release.yml`). Neue Version: Abschnitt `## vX.Y.Z`
oben einfügen, dann taggen.

## v2.10.0 (2026-11-04)

### Neu
- **`tools/ptouch-labels.sh` prüft das PDF**, bevor Band verbraucht wird: A4-Seiten (Browser hat das
  Papierformat überschrieben), Seitenhöhe ohne passendes TZe-Band oder ein PDF für breiteres Band als
  eingelegt brechen mit einer Erklärung ab. Bei schmalerem Band oder einem Druckerfehler (z. B.
  Abdeckung offen) erscheint eine Warnung.
- **Übersicht vor dem Druck**: Anzahl, Bandbreite und Länge, Bandverbrauch, eingelegtes Band (Breite,
  Art, Farbe) und die ersten Etiketten mit ID. **Vorschau** öffnet den Streifen als Bild.
- **Fortschritt** beim Aufbereiten und Drucken mit Animation, aktuellem Etikett und Restzeit, danach
  eine Erfolgsmeldung mit Bandverbrauch. Im Terminal mit farbigem Fortschrittsbalken.

### Geändert
- Die TZe-Formate heißen „TZe … · auch Flexi TZe-FX für Kabel“: Flexi-Band hat dieselben Maße.
- Der Hinweis bei „Länge je Etikett“ erklärt, dass die Länge im PDF steckt (eine Seite je Etikett).

## v2.9.0 (2026-11-04)

### Neu
- **Kabelfahne** für TZe-Band: Etikett, Stück zum Umwickeln (Länge einstellbar), Etikett – einmal um
  das Kabel legen und die Enden zusammenkleben. Die Fahne ist von beiden Seiten lesbar; auf Wunsch
  bleibt die Rückseite frei.
- **`tools/ptouch-labels.sh`** druckt das Etiketten-PDF unter Linux auf Brother P-touch-Druckern
  (z. B. PT-P700, für den es keinen Linux-Treiber gibt) über ptouch-print direkt per USB:
  - `--install` legt den Starter **„Etiketten drucken“** an (App-Übersicht, Schreibtisch, „Öffnen mit“).
    Ein Doppelklick druckt die neueste `Etiketten*.pdf` aus „Downloads“ mit Rückfrage und Fortschritt
    und verschiebt sie danach nach „Downloads/Etiketten gedruckt“.
  - Der Drucker schneidet nach jedem Etikett ab; dank Kettendruck fällt der Vorlauf von ~25 mm nur
    einmal an. `--streifen` druckt einen Streifen mit Schnittmarken, `--png` nur eine Vorschau.
  - Schrift wird vierfach fein gerendert und auf die 180 dpi des Druckers heruntergerechnet, dünne
    Striche brechen nicht mehr weg.
  - Einrichtung unter Fedora: README, Abschnitt „Labels and scanning“.

### Geändert
- Die Druckansicht heißt „Etiketten“, beim Speichern als PDF entsteht also `Etiketten.pdf`.
- Auf Schriftband ist der Gerätename halbfett und auf 24- und 18-mm-Band etwas größer.
- TZe 24 mm ist standardmäßig 50 statt 60 mm lang.

## v2.8.0 (2026-11-03)

### Neu
- **P-touch-Endlosband (TZe)** in 24, 18, 12 und 9 mm, z. B. für den Brother PT-P700. Die Länge je
  Etikett ist frei wählbar (15–200 mm). QR-Code und Text bleiben im bedruckbaren Streifen in der
  Bandmitte, die Druckansicht zeigt die nicht bedruckbaren Ränder grau.

### Geändert
- Die Formate „Schriftband 12 mm“ und „Schriftband 24 mm“ heißen jetzt „TZe 12 mm“ und „TZe 24 mm“
  und halten den Druckbereich des Bandes ein. Gespeicherte Voreinstellungen gelten weiter.

## v2.7.0 (2026-11-02)

Optimiert für das Kiosk-Tablet (Samsung Galaxy Tab A 9.7, SM-T550, 1024×768 quer).

### Neu
- **Schalter „Hand-Scanner“** am ID-Feld (Gerät finden, Ausleihen, Zurückgeben), gemerkt je Gerät.
  An: Das ID-Feld hat immer den Fokus, Scanner-Eingaben landen auch dann dort, wenn gerade etwas
  anderes ausgewählt ist. Aus: kein Autofokus, die Bildschirmtastatur erscheint erst beim Antippen.
  Ohne Wahl an bei Maus/Trackpad, aus bei Touch.
- **Kamera wechseln** (Vorder-/Rückkamera) im Scanner, gemerkt je Gerät. Am Kiosk startet die
  Vorderkamera, gespiegelt angezeigt.

### Geändert
- Bei 1024–1279 px Breite ist die Seitenleiste eine schmale Symbolleiste mit Kurzbeschriftung.
- Kiosk: ab Tablet-Breite keine Kopfleiste mehr, Farbschema und „Kiosk beenden“ stehen in der
  Seitenleiste. Größere Knöpfe, Eingabefelder und Listeneinträge.
- Ausleihen und Zurückgeben im Querformat: Erfassen und Liste je zur Hälfte, flacheres Kamerabild,
  größeres ID-Feld.

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
