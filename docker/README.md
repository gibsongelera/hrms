# HRMS — Docker & Jenkins Setup

This guide shows how to run the HRMS app on **Docker Desktop** and how to build it with **Jenkins**.

---

## 1. Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows) — make sure WSL 2 backend is enabled.
- Free ports on the host: **8080** (app), **8081** (phpMyAdmin), **3307** (MySQL).
- (Optional) [Jenkins](https://www.jenkins.io/download/) with the **Docker Pipeline** and **Credentials Binding** plugins.

---

## 2. Run with Docker Desktop (one command)

From the project root (`c:\xampp\htdocs\hrms-main`):

```powershell
copy .env.example .env
docker compose up -d --build
```

That spins up three containers:

| Service     | Container   | URL / Port                      |
|-------------|-------------|---------------------------------|
| HRMS app    | `hrms_web`  | http://localhost:8080           |
| MySQL 8     | `hrms_db`   | localhost:3307 (inside: `db:3306`) |
| phpMyAdmin  | `hrms_pma`  | http://localhost:8081           |

The SQL in `sql/database_setup.sql` is auto-imported on the **first** start (because the MySQL data volume is empty).

### Default logins

- **Admin:** `admin@hrms.com` / `admin123`
- **phpMyAdmin:** user `hrms_user`, password `hrms_pass` (or root / `rootpass`)

### Useful commands

```powershell
docker compose ps                # status
docker compose logs -f web       # tail app logs
docker compose logs -f db        # tail MySQL logs
docker compose restart web       # restart app only
docker compose down              # stop (keep DB data)
docker compose down -v           # stop AND wipe DB volume (fresh install on next up)
docker compose exec web bash     # shell into the app container
docker compose exec db mysql -uroot -prootpass hrms_db   # MySQL shell
```

### File uploads

The `./uploads` folder on your host is bind-mounted into the container, so files uploaded through the app persist outside Docker.

---

## 3. Build only the image (no compose)

```powershell
docker build -t hrms-app:latest .
docker run -d --name hrms_web -p 8080:80 `
  -e DB_HOST=host.docker.internal -e DB_NAME=hrms_db `
  -e DB_USER=root -e DB_PASS= hrms-app:latest
```

(Use `host.docker.internal` to talk to a MySQL running on your Windows host / XAMPP.)

---

## 4. Jenkins Pipeline

The repo includes a `Jenkinsfile` with stages:

1. **Checkout** — pulls the repo.
2. **Lint PHP** — runs `php -l` on every `.php` file using a throwaway `php:8.2-cli` container.
3. **Build Image** — `docker build` tagged `hrms-app:<BUILD_NUMBER>` and `:latest`.
4. **Smoke Test** — `docker compose up -d`, hits `http://localhost:8080/`, then tears the stack down.
5. **Push Image** — runs only if `REGISTRY` is set; pushes both tags to your registry.

### Set it up in Jenkins

1. Install plugins: **Docker Pipeline**, **Credentials Binding**, **Pipeline**.
2. Make sure the Jenkins agent (or controller) has Docker installed and the Jenkins user is in the `docker` group (Linux) or that Docker Desktop is running (Windows).
3. **New Item → Pipeline →** name it `hrms`.
4. Under **Pipeline**, choose **Pipeline script from SCM**, Git, point it at your repo, script path `Jenkinsfile`.
5. (Optional, to push images) In Jenkins → **Manage Jenkins → Credentials**, add a *Username with password* credential whose ID is `dockerhub-credentials`, then edit the `REGISTRY` env in `Jenkinsfile` (e.g. `docker.io/yourname`).
6. **Build Now.**

---

## 5. Troubleshooting

| Symptom                                          | Fix                                                                                               |
|--------------------------------------------------|---------------------------------------------------------------------------------------------------|
| `port is already allocated` on 8080/8081/3307    | Edit `docker-compose.yml` and change the **host** side of the port mapping.                       |
| App loads but says "Connection failed"           | DB not ready yet — wait ~20s, or check `docker compose logs db`.                                  |
| Tables missing                                   | Run `docker compose down -v` then `docker compose up -d` to re-import `sql/database_setup.sql`.   |
| Can't log in as admin                            | Visit `http://localhost:8080/restore_admin.php` once to repair the admin user.                    |
| File uploads don't persist                       | Make sure the `uploads/` folder exists in the project root (the compose file bind-mounts it).     |
| Jenkins "Cannot connect to Docker daemon"        | On Windows, ensure Docker Desktop is running; on Linux, `usermod -aG docker jenkins` & restart.   |
