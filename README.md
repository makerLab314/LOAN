## LOAN

Willkommen be <em>LOAN, dem System zur Geräteausleihe für wissenschaftliche und medienpädagogische Einrichtungen</em>. Das System läuft auf einer LNPP-Struktur: 
<ul>
    <li>Ubuntu 24.04</li>
    <li>Nginx</li>
    <li>Postgres</li>
    <li>PHP 8.3</li>
</ul>
und wurde mit <a href="https://laravel.com" target="_blank">Laravel</a> sowie <a href="https://github.com/tailwindlabs/tailwindcss" target="_blank">TailwindCSS</a> programmiert und ist unter einer <a href="https://opensource.org/licenses/MIT"target="_blank">MIT-Lizenz</a> verfügbar. Um Zugang zu einem Testaccount unter <a href="http://loan.vdus.de" target="_blank">http://loan.vdus.de</a> zu erhalten, kontaktieren Sie mich bitte unter vincent.dusanek[at]gmail.com.
<br><br>
<em>LOAN | A free loan management system</em> is made with <a href="https://laravel.com" target="_blank">Laravel</a> and <a href="https://github.com/tailwindlabs/tailwindcss" target="_blank">TailwindCSS</a>. Content Language: lang="de".

## Lizenz/ License

Dieses Projekt unterliegt der Creative Commons Attribution–NonCommercial–ShareAlike 4.0 International (CC BY-NC-SA 4.0) Lizenz.

- **Erlaubt:** Nutzung, Vervielfältigung, Änderung und Weitergabe für nichtkommerzielle Zwecke, sofern Sie die Quelle angemessen angeben und abgeleitete Werke unter derselben Lizenz verbreiten.
- **Nicht erlaubt:** kommerzielle Nutzung dieses Projekts oder abgeleiteter Werke ohne vorherige Genehmigung.
SPDX-License-Identifier: **CC-BY-NC-SA-4.0**

Für Anfragen zu kommerziellen Lizenzen eröffnen Sie bitte ein Ticket auf GitHub.

This project is licensed under the **Creative Commons Attribution–NonCommercial–ShareAlike 4.0 International** (CC BY-NC-SA 4.0) license.

- **Allowed:** use, copy, modify, and share **for non-commercial purposes**, provided you give appropriate credit and distribute derivative works under the **same license**.
- **Not allowed:** **commercial use** of this project or any derivative works without prior permission.

SPDX-License-Identifier: **CC-BY-NC-SA-4.0**

For commercial licensing inquiries, please open an issue on GitHub.

## Preview

### Verleihprozess/ Loan process

![](https://digillab.uni-augsburg.de/wp-content/uploads/2025/08/loan-2.gif)

### Anmeldung/ Login

![](https://digillab.uni-augsburg.de/wp-content/uploads/2025/08/loan-1-login.png)

### Geräte/ Devices

![](https://digillab.uni-augsburg.de/wp-content/uploads/2025/08/loan-2-devices.png)

### Detailansicht: Gerät/ Details: Device

![](https://digillab.uni-augsburg.de/wp-content/uploads/2025/08/loan-3-device.png)


## Lokale Installation ohne Docker/ Run locally without Docker

```
# Installation der Abhängigkeiten
composer install
# Anlegen der .env
copy .env.example .env
# Setzung des App-Keys
php artisan key:generate
# Erstellung der DB
New-Item -ItemType File .\database\database.sqlite -Force | Out-Null
# (Aus-)kommentieren der (ir-)relevanten DB-Parameter in der .env
DB_CONNECTION=sqlite
DB_DATABASE="ABSOLUTER_PFAD_ZUR_DB"
# Ausführen der Migrationen
php artisan migrate
# Ausführen des Seeds für den ersten Admin: admin@test.com mit geheimespasswort
php artisan db:seed
# Starten des Servers unter 127.0.0.1:8000
php artisan serve
# Setzung des Storage-Links
php artisan storage:link
```

## Lokale Installation mit Docker/ Run locally with Docker

Using docker, one can run this app locally using php-fpm, redis and mariadb.

### APP_KEY creation

The laravel app needs an `APP_KEY` which can be created like that:

`echo "APP_KEY=base64:$(openssl rand -base64 32)" >> .env`
and should be added to the compose.yml

### Setup service

The service can be set up using

`docker compose up -d`

Run the migrations and seed the database (to create the first admin user):

`docker compose run loan php artisan migrate --seed`

### Login

Go to http://localhost:8080/ and login with the seeded user.

You can then start using the app.
