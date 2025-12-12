# LOAN (Fork by makerLab314)

> ⚡️ **This is a maintained, enhanced fork of the original LOAN [https://github.com/OriginalOwner/LOAN](https://github.com/OriginalOwner/LOAN) project. All core features remain, with improvements and ongoing support from makerLab314's team.**  
> Please report issues or suggest new features via GitHub Issues!

---

## Overview

**LOAN** is a flexible, modern system for device and equipment management, designed for scientific and media-education institutions.

- **Stack:** Ubuntu 24.04, Nginx, Postgres, PHP 8.3, Laravel, TailwindCSS
- **Purpose:** Effortless device booking, transparent loan processes, and smooth user management.
- **Open Source License:** [CC-BY-NC-SA-4.0](#license)

---

## Features

- User-friendly web interface in German and English
- LNPP architecture: modern, scalable, robust
- Local or Dockerized deployment
- Role-based access (users/admins), seed admin creation
- Onboarding scripts, assets, and migrations included

---

## Table of Contents

- [Demo Previews](#demo-previews)
- [Quick Start (Non-Docker)](#quick-start-non-docker)
- [Quick Start (Docker)](#quick-start-docker)
- [FAQ & Troubleshooting](#faq--troubleshooting)
- [License](#license)
- [Credits & Upstream](#credits--upstream)

---

## Demo Previews

**Verleihprozess / Loan Process:**  
![Loan process GIF](https://digillab.uni-augsburg.de/wp-content/uploads/2025/08/loan-2.gif)

**Login:**  
![Login](https://digillab.uni-augsburg.de/wp-content/uploads/2025/08/loan-1-login.png)

**Geräte / Devices:**  
![Devices](https://digillab.uni-augsburg.de/wp-content/uploads/2025/08/loan-2-devices.png)

**Gerät Details / Device Details:**  
![Device Detail](https://digillab.uni-augsburg.de/wp-content/uploads/2025/08/loan-3-device.png)

---

## Quick Start (Non-Docker)

These steps assume you're running on Linux/macOS. Use PowerShell on Windows where needed.

### 1. Prerequisites

- PHP 8.3+, Composer
- PostgreSQL or SQLite
- Node.js & npm (for frontend assets)
- [Optional] Redis (recommended for queues)

### 2. Clone and Setup

```bash
git clone https://github.com/makerLab314/LOAN.git
cd LOAN
composer install
cp .env.example .env
```

### 3. Generate App Key

```bash
php artisan key:generate
```

### 4. Configure Database

- **SQLite (simplest):**
  ```bash
  touch database/database.sqlite
  ```
  Then edit `.env`:
  ```
  DB_CONNECTION=sqlite
  DB_DATABASE=/absolute/path/to/your/project/database/database.sqlite
  ```
- **PostgreSQL:**  
  Update `.env` with your DB credentials:
  ```
  DB_CONNECTION=pgsql
  DB_HOST=127.0.0.1
  DB_PORT=5432
  DB_DATABASE=loan
  DB_USERNAME=youruser
  DB_PASSWORD=yourpassword
  ```

### 5. Migrate & Seed

```bash
php artisan migrate --seed
```
> This creates the first admin user:  
> **Email**: `admin@test.com`  
> **Password**: `geheimespasswort`  
> (Change after first login!)

### 6. Build Frontend Assets

```bash
npm install
npm run build
```

### 7. Setup Storage Symlink

```bash
php artisan storage:link
```

### 8. Run the Server

```bash
php artisan serve
```
Visit [http://127.0.0.1:8000](http://127.0.0.1:8000).

---

## Quick Start (Docker)

An all-in-one solution running via Docker Compose, including `php-fpm`, Redis, and MariaDB.

### 1. Clone the repo

```bash
git clone https://github.com/makerLab314/LOAN.git
cd LOAN
```

### 2. Create the `.env` file and set the `APP_KEY`

```bash
cp .env.example .env
echo "APP_KEY=base64:$(openssl rand -base64 32)" >> .env
```
Or manually edit `.env` and insert the key.

### 3. Start Services

```bash
docker compose up -d
```

### 4. Run Database Migrations and Seed Admin

```bash
docker compose run loan php artisan migrate --seed
```

### 5. Build Assets (if needed)

```bash
docker compose run loan npm install
docker compose run loan npm run build
```

### 6. Log In

Visit [http://localhost:8080](http://localhost:8080) and log in with the seeded admin account.

---

## FAQ & Troubleshooting

- **Change Admin Password**:  
  After setup, immediately log in as admin and change the password via the user interface.

- **.env values:**  
  Refer to Laravel documentation for advanced `.env` configuration.

- **File uploads not working?**  
  Ensure the `storage/` directory is writable and `php artisan storage:link` was run.

- **Asset build issues:**  
  If frontend styling/JS is missing, ensure `npm run build` finished without errors.

- **Database connection errors in Docker:**  
  Wait a few seconds after `docker compose up` before running migrations.

---

## License

**CC-BY-NC-SA-4.0** (Creative Commons Attribution–NonCommercial–ShareAlike 4.0 International)

- **Allowed:** Non-commercial use, modification, and redistribution with attribution, under the same license.
- **Not allowed:** Commercial use without prior permission.

SPDX-License-Identifier: `CC-BY-NC-SA-4.0`

> For commercial licensing, please [open a GitHub issue](https://github.com/makerLab314/LOAN/issues).

---

## Credits & Upstream

- **Origin:** Forked from [OriginalOwner/LOAN](https://github.com/OriginalOwner/LOAN)
- **This fork:** [makerLab314/LOAN](https://github.com/makerLab314/LOAN)
- **Frameworks & Tools:**  
    [Laravel](https://laravel.com) | [TailwindCSS](https://tailwindcss.com)

> We thank the original authors and contributors for their substantial initial work!
