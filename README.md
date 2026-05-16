# HRMS — Human Resource Management System

A PHP + MySQL web application for managing employees, attendance, payroll, and leave requests.

---

## Table of Contents

1. [Features](#features)
2. [Option A — Run with Docker (Recommended)](#option-a--run-with-docker-recommended)
3. [Option B — Run with XAMPP (Traditional)](#option-b--run-with-xampp-traditional)
4. [Option C — Run Jenkins CI/CD Pipeline](#option-c--run-jenkins-cicd-pipeline)
5. [Moving to Another PC or Laptop](#moving-to-another-pc-or-laptop)
6. [Default Logins](#default-logins)
7. [Project Structure](#project-structure)
8. [Troubleshooting](#troubleshooting)

---

## Features

- **Admin Dashboard** — real-time stats and management overview
- **Employee Management** — add, edit, deactivate employees
- **Attendance Tracking** — clock in/out with late threshold and grace period rules
- **Leave Requests** — employees submit requests; admins approve or reject
- **Payroll & Payslips** — generate payroll and export PDF payslips
- **Department Management** — organize employees into departments
- **Dynamic Settings** — configure company name, currency (₱), shift hours

---

## Option A — Run with Docker (Recommended)

This is the easiest way to run HRMS on any machine. No XAMPP, no PHP installation needed.

### Prerequisites

| Software | Download | Notes |
|----------|----------|-------|
| Docker Desktop | https://www.docker.com/products/docker-desktop/ | Enable WSL 2 backend on Windows |
| Git | https://git-scm.com/downloads | To clone the repo |

### Step 1 — Install Docker Desktop

1. Download and install **Docker Desktop** from the link above.
2. Open Docker Desktop and wait for it to say **"Engine running"** in the bottom-left corner.
3. On Windows: make sure **WSL 2 backend** is enabled (Settings → General → Use WSL 2 based engine).

### Step 2 — Clone the repository

Open **PowerShell** or **Terminal** and run:

```bash
git clone https://github.com/gibsongelera/hrms.git
cd hrms
```

### Step 3 — Create the environment file

```bash
# Windows PowerShell
copy .env.example .env

# macOS / Linux
cp .env.example .env
```

> You can open `.env` and change the passwords if you want, but the defaults work fine for local use.

### Step 4 — Start the application

```bash
docker compose up -d --build
```

This command:
- Builds the PHP + Apache image
- Starts **3 containers**: web app, MySQL 8, phpMyAdmin
- Automatically imports the database from `sql/database_setup.sql`

Wait about **30 seconds** for the database to initialize, then open:

| Service | URL |
|---------|-----|
| HRMS App | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |

### Step 5 — Log in

Go to **http://localhost:8080** and log in:

- **Admin Portal**: http://localhost:8080/admin/login.php — `admin@hrms.com` / `admin123`
- **Employee Portal**: http://localhost:8080/login.php

### Useful Docker commands

```bash
docker compose ps                    # check container status
docker compose logs -f web           # view live app logs
docker compose restart web           # restart app only
docker compose down                  # stop (keeps database data)
docker compose down -v               # stop and wipe database (fresh install on next start)
docker compose exec web bash         # open a shell inside the app container
docker compose exec db mysql -uroot -prootpass hrms_db   # open MySQL shell
```

---

## Option B — Run with XAMPP (Traditional)

Use this if you prefer the classic XAMPP setup without Docker.

### Step 1 — Install XAMPP

1. Download **XAMPP** from https://www.apachefriends.org/
2. Install it (default path: `C:\xampp` on Windows, `/opt/lampp` on Linux).
3. Make sure you choose **PHP 8.x** during installation.

### Step 2 — Copy the project

Copy the entire `hrms` folder into the XAMPP `htdocs` directory:

```
C:\xampp\htdocs\hrms\
```

### Step 3 — Start XAMPP services

1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache** and **MySQL**.

### Step 4 — Set up the database

1. Open your browser and go to: **http://localhost/phpmyadmin**
2. Click **New** in the left sidebar.
3. Type `hrms_db` as the database name → click **Create**.
4. Click on `hrms_db` in the sidebar → go to the **Import** tab.
5. Click **Choose File** → navigate to `C:\xampp\htdocs\hrms\sql\database_setup.sql`.
6. Click **Go**.

### Step 5 — Access the system

- **First-time setup**: http://localhost/hrms/restore_admin.php *(run once to verify health)*
- **Admin Portal**: http://localhost/hrms/admin/login.php
- **Employee Portal**: http://localhost/hrms/login.php

---

## Option C — Run Jenkins CI/CD Pipeline

Jenkins lets you automatically build and test the Docker image every time you push code.

### Prerequisites

- Docker Desktop must be running (same as Option A)
- The HRMS Docker stack should be running (`docker compose up -d`)

### Step 1 — Start Jenkins

From inside the project folder:

```bash
docker compose -f docker-compose.jenkins.yml up -d --build
```

Jenkins will be available at: **http://localhost:9090**

> No login is required for local development. Jenkins opens directly to the Dashboard.

### Step 2 — Install required plugins (first time only)

Jenkins needs 4 plugins. Run this command — it installs them automatically:

```bash
docker exec -u jenkins hrms_jenkins jenkins-plugin-cli --plugins "pipeline-model-definition git credentials-binding docker-workflow workflow-aggregator"
```

Then restart Jenkins:

```bash
docker restart hrms_jenkins
```

Wait 30 seconds, then go to **http://localhost:9090**.

### Step 3 — Create the pipeline job

Run this in PowerShell from the project folder:

```powershell
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$crumbResp = Invoke-WebRequest -Uri "http://localhost:9090/crumbIssuer/api/json" -UseBasicParsing -SessionVariable session
$crumb = $crumbResp.Content | ConvertFrom-Json
$xml = Get-Content "docker\jenkins\job-config.xml" -Raw -Encoding UTF8
$bytes = [System.Text.Encoding]::UTF8.GetBytes($xml)
$headers = @{ $crumb.crumbRequestField = $crumb.crumb }
Invoke-WebRequest -Uri "http://localhost:9090/createItem?name=hrms-pipeline" `
    -Method POST -ContentType "application/xml; charset=utf-8" `
    -Headers $headers -Body $bytes -WebSession $session -UseBasicParsing
```

### Step 4 — Run a build

1. Open **http://localhost:9090/job/hrms-pipeline**
2. Click **Build Now**
3. Click the build number → **Console Output** to watch it run

The pipeline stages are:

```
1. Checkout      — clones the repo from GitHub
2. Lint PHP      — checks all .php files for syntax errors
3. Build Image   — builds hrms-app:latest Docker image
4. Smoke Test    — spins up HRMS on port 8888, tests it, tears it down
5. Push Image    — skipped unless you set REGISTRY in Jenkinsfile
```

### Stop Jenkins

```bash
docker compose -f docker-compose.jenkins.yml down
```

---

## Moving to Another PC or Laptop

Follow these steps to transfer HRMS to a new machine. **Option 1** (Git + Docker) is the fastest.

---

### Method 1 — Git + Docker (fastest, recommended)

**On the new PC:**

1. Install **Docker Desktop** (see Option A, Step 1).
2. Install **Git**.
3. Open PowerShell and run:

```bash
git clone https://github.com/gibsongelera/hrms.git
cd hrms
copy .env.example .env
docker compose up -d --build
```

Done. The app is running at **http://localhost:8080**.

> This works on Windows, macOS, and Linux without any other software.

---

### Method 2 — USB / Manual file transfer (no internet on new PC)

Use this if the new PC has no internet access.

#### On the OLD PC — export everything

**Step 1 — Export the database**

```bash
docker exec hrms_db mysqldump -uroot -prootpass hrms_db > hrms_db_backup.sql
```

**Step 2 — Save the Docker image**

```bash
docker save hrms-app:latest -o hrms-app-image.tar
```

**Step 3 — Copy to USB**

Copy these to your USB drive:
```
hrms/                    ← the entire project folder
hrms_db_backup.sql       ← database export
hrms-app-image.tar       ← Docker image
```

#### On the NEW PC — restore everything

**Step 1 — Install Docker Desktop** (download installer separately on another machine if needed).

**Step 2 — Copy project from USB**

Copy the `hrms` folder to anywhere on the new PC, e.g. `C:\hrms`.

**Step 3 — Load the Docker image**

```bash
docker load -i hrms-app-image.tar
```

**Step 4 — Start the stack**

```bash
cd C:\hrms
copy .env.example .env
docker compose up -d
```

> Skip `--build` since you already loaded the image from the tar file.

**Step 5 — Restore the database**

Wait for the DB container to be healthy (about 30 seconds), then:

```bash
docker exec -i hrms_db mysql -uroot -prootpass hrms_db < hrms_db_backup.sql
```

**Step 6 — Open the app**

Go to **http://localhost:8080** — all your data is restored.

---

### Method 3 — XAMPP to XAMPP transfer

Use this if both machines use XAMPP (no Docker).

**On the OLD PC:**

1. Open phpMyAdmin → select `hrms_db` → **Export** → Format: SQL → click **Go**.
   Save the file as `hrms_db_backup.sql`.
2. Copy `C:\xampp\htdocs\hrms` folder + `hrms_db_backup.sql` to USB.

**On the NEW PC:**

1. Install XAMPP.
2. Copy the `hrms` folder into `C:\xampp\htdocs\hrms`.
3. Start Apache and MySQL in XAMPP Control Panel.
4. Open http://localhost/phpmyadmin → create database `hrms_db`.
5. Select `hrms_db` → **Import** → choose `hrms_db_backup.sql` → **Go**.
6. Open http://localhost/hrms/restore_admin.php once.
7. Log in at http://localhost/hrms/admin/login.php.

---

## Default Logins

| Portal | URL | Username | Password |
|--------|-----|----------|----------|
| Admin (Docker) | http://localhost:8080/admin/login.php | admin@hrms.com | admin123 |
| Employee (Docker) | http://localhost:8080/login.php | *(employee email)* | *(set by admin)* |
| phpMyAdmin (Docker) | http://localhost:8081 | hrms_user | hrms_pass |
| Admin (XAMPP) | http://localhost/hrms/admin/login.php | admin@hrms.com | admin123 |
| Jenkins | http://localhost:9090 | *(no login required locally)* | — |

> If you can't log in, visit `http://localhost:8080/restore_admin.php` (Docker) or `http://localhost/hrms/restore_admin.php` (XAMPP) to repair the admin account.

---

## Project Structure

```
hrms/
├── admin/              # Admin portal pages
├── employee/           # Employee portal pages
├── assets/             # CSS, images
├── config/
│   └── database.php    # DB connection (reads env vars, falls back to XAMPP defaults)
├── includes/
│   ├── db_connection.php
│   ├── header.php
│   ├── footer.php
│   └── sidebar.php
├── sql/
│   └── database_setup.sql   # Full database schema + seed data
├── uploads/                 # User uploaded files (profile pictures etc.)
├── docker/
│   ├── 000-default.conf     # Apache virtual host config
│   ├── php.ini              # PHP settings override
│   └── jenkins/
│       ├── Dockerfile       # Jenkins image with Docker CLI
│       └── job-config.xml   # Pipeline job definition
├── Dockerfile               # HRMS PHP + Apache image
├── docker-compose.yml       # App + MySQL + phpMyAdmin stack
├── docker-compose.jenkins.yml  # Jenkins container
├── docker-compose.ci.yml    # Port override for CI smoke tests
├── Jenkinsfile              # CI/CD pipeline definition
├── .env.example             # Environment variable template
├── login.php                # Employee login
├── logout.php
├── restore_admin.php        # Admin account repair tool
└── README.md                # This file
```

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| `port is already allocated` on 8080 | Another app uses port 8080. Edit `docker-compose.yml` and change `"8080:80"` to e.g. `"8090:80"` |
| App loads but says "Connection failed" | DB not ready yet — wait 30s, or run `docker compose logs db` |
| Tables missing / blank dashboard | Run `docker compose down -v` then `docker compose up -d` to re-import SQL |
| Can't log in as admin | Visit http://localhost:8080/restore_admin.php once |
| File uploads not saved | Make sure `uploads/` folder exists in the project root |
| Docker Desktop not starting on Windows | Enable virtualization in BIOS; ensure WSL 2 is installed (`wsl --install`) |
| Jenkins "Cannot connect to Docker daemon" | Docker Desktop must be running before starting Jenkins |
| Jenkins port 9090 busy | Change `"9090:8080"` in `docker-compose.jenkins.yml` |
| Smoke test fails in Jenkins | Run `docker compose down` first to free ports 8888/8889/3308 |
| After moving to new PC, database is empty | Follow Method 2 Step 5 to restore from backup SQL file |
