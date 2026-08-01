# PipraPay - Docker

Run the whole application (web server + MySQL) with a single command. All PHP
extensions, Apache rewrite rules and the bundled gateway dependencies are baked
into the image.

## Quick start

Run locally with the local override (adds the host port and creates the
Dokploy network locally):

```bash
docker compose -f docker-compose.yml -f docker-compose.local.yml up -d --build
```

On Dokploy, just deploy `docker-compose.yml` as-is (see below).

That is it. On first boot the container:

1. waits for MySQL to be ready,
2. creates the database and imports the bundled schema,
3. creates the admin account,
4. starts Apache.

Open your domain (e.g. `https://pay.jinnchat.net` on Dokploy, or
`http://localhost:8080` for the local command above) and log in:

- Username: `admin`
- Password: see "Finding your password" below.

## Deploying on Dokploy

The compose file is already Dokploy-ready:

- No host port mapping - the app only `expose`s port 80, so Traefik reaches it
  directly over `dokploy-network`.
- Data (MySQL + `pp-media/storage`) lives in named volumes, so it survives
  redeploys and can be backed up via Dokploy's Volume Backups.
- Add your domain in Dokploy's **Domains** tab (e.g. `pay.jinnchat.net`) -
  Dokploy generates the Traefik labels for you; nothing to configure here.

The admin password (auto-generated or from `ADMIN_PASSWORD`) is written to
`pp-media/storage/ADMIN_CREDENTIALS.txt` inside the `pp_media` volume.

## Finding your password (no terminal needed)

- If you set `ADMIN_PASSWORD` in `.env`, that is the password.
- If you left it empty, the installer generated a random one. It is written to
  **`pp-media/storage/ADMIN_CREDENTIALS.txt`** next to your project - open that
  file with any text editor. It is also printed in the container logs
  (`docker compose logs app | grep -i password`), or viewable in your Docker
  GUI (Docker Desktop / Portainer) under the `piprapay` container's Logs tab.

The `pp-media/storage` folder is blocked from direct web access, so the
credentials file and any backups are never served over HTTP.

## Configuration

Copy `.env.example` to `.env` and adjust before first boot:

```bash
cp .env.example .env
```

| Variable            | Default           | Purpose                                   |
| ------------------- | ----------------- | ----------------------------------------- |
| `DB_NAME`           | `piprapay`        | MySQL database name                       |
| `DB_USER`           | `piprapay`        | MySQL application user                    |
| `DB_PASSWORD`       | `piprapay`        | MySQL application password                |
| `MYSQL_ROOT_PASSWORD` | `root`           | MySQL root password (db container only)   |
| `DB_PREFIX`         | `pp_`             | Table prefix (must match backups)         |
| `ADMIN_USERNAME`    | `admin`           | Admin login                               |
| `ADMIN_EMAIL`       | `admin@example.com` | Admin email                             |
| `ADMIN_PASSWORD`    | *(generated)*     | Admin password; empty = auto-generate     |

## Data persistence

- The database lives in the named volume `db_data`; `docker compose down` keeps
  it, `docker compose down -v` deletes it.
- Backups, imports and uploads live in `./pp-media/storage` on your host, next
  to the project - grab a backup file directly from your filesystem.

## Common tasks

```bash
docker compose logs -f app     # follow web logs
docker compose logs app        # show install log / generated password
docker compose restart app     # restart the web server
docker compose down            # stop (data kept)
docker compose down -v         # stop and wipe database volume
```

## Notes

- The bundled `pp-install` directory is still shipped with the image. To match
  the project's own post-install security guidance, remove it by adding to your
  `docker-compose.yml` (app service):

  ```yaml
  command: sh -c "rm -rf /var/www/html/pp-content/pp-install && apache2-foreground"
  ```

  (The automatic installer does not run it again once `pp-config.php` exists.)

- In-app updates/imports write inside the container filesystem, which is
  ephemeral. Back up the code (`docker build`) or bind-mount the whole project
  directory if you rely on the in-app update feature.
