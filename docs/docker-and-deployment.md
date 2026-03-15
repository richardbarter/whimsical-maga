# Docker & Fly.io Deployment Guide

## Overview

This project uses Docker for both local development and production deployment on Fly.io.

- **Local dev**: `docker compose up` starts Postgres, PHP/Nginx (app), and Vite (HMR) together
- **Production**: Fly.io runs a single Docker container (Nginx + PHP-FPM via Supervisor), backed by a Neon PostgreSQL database and Cloudflare R2 for file storage

---

## Local Development

### Prerequisites
- Docker Desktop installed and running

### First-time setup
```bash
# Copy env and set your APP_KEY
cp .env.example .env
php artisan key:generate   # or generate a base64 key manually

# Build and start all services
docker compose up --build
```

### Normal startup
```bash
docker compose up
```

| Service | URL |
|---------|-----|
| App (PHP via Nginx) | http://localhost:8000 |
| Vite dev server (HMR) | http://localhost:5173 (used internally by browser) |
| Postgres | localhost:5432 |

### How it works

Three Docker Compose services:

- **`postgres`** — PostgreSQL 16, data persisted in a named volume
- **`app`** — PHP 8.4-FPM + Nginx, source code volume-mounted (no rebuild needed for PHP changes)
- **`vite`** — Node 22 running `npm run dev`, source code volume-mounted

The `app` service overrides `DB_HOST=postgres` so it connects to the Docker network database.
Your `.env` can stay pointing to `127.0.0.1` for non-Docker use.

Vite writes a `public/hot` file when it starts; the app reads it and tells browsers where to load
assets from. HMR connects directly from your browser to `localhost:5173`.

### Stopping
```bash
docker compose down
# To also delete the database volume:
docker compose down -v
```

### Running Artisan commands
```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan tinker
```

---

## Production: Fly.io Deployment

### Architecture

- **App**: Single container running Nginx + PHP-FPM, managed by Supervisor
- **Database**: [Neon](https://neon.tech) — external hosted PostgreSQL (free tier)
- **File storage**: [Cloudflare R2](https://developers.cloudflare.com/r2/) — S3-compatible object storage for uploaded backgrounds
- **Assets**: Built into the image at build time (no Vite dev server in production)
- **Cost**: ~$4–6/month for the Fly.io app machine; database and R2 (under free tier limits) are free

### Database: Neon Setup

We use [Neon](https://neon.tech) for PostgreSQL hosting instead of Fly's built-in Postgres (which is significantly more expensive).

**One-time Neon setup:**

1. Sign up at [neon.tech](https://neon.tech)
2. Create a new project — choose **AWS** as the cloud provider and the region closest to your Fly.io region (we use **US East (Ohio)** to match `ord`/Chicago)
3. Skip Neon Auth and any AI/MCP tooling — not needed
4. In the Neon dashboard, go to your project → **Connection Details**
5. Copy the connection string — it looks like:
   ```
   postgres://user:password@ep-xxx.us-east-2.aws.neon.tech/dbname?sslmode=require
   ```
6. Set it as a Fly.io secret:
   ```bash
   fly secrets set DATABASE_URL="postgres://user:password@ep-xxx.us-east-2.aws.neon.tech/dbname?sslmode=require"
   ```

### File Storage: Cloudflare R2 Setup

We use [Cloudflare R2](https://developers.cloudflare.com/r2/) for storing uploaded files (background images). R2 is S3-compatible, so Laravel's `s3` filesystem driver handles it.

**One-time R2 setup:**

1. Sign up / log in at [dash.cloudflare.com](https://dash.cloudflare.com)
2. Go to **R2 Object Storage** → **Create bucket**
3. Under the bucket's **Settings** tab, enable **Public Access** so uploaded files can be served directly to browsers
4. Copy the **Public bucket URL** — it looks like `https://pub-<id>.r2.dev`
5. Go to **R2 → Manage R2 API tokens** → **Create API token** with **Object Read & Write** permissions scoped to your bucket
6. Copy the **Access Key ID**, **Secret Access Key**, and **Endpoint** (e.g. `https://<account-id>.r2.cloudflarestorage.com`)
7. Set them as Fly.io secrets:
   ```bash
   fly secrets set \
     CLOUDFLARE_R2_ACCESS_KEY_ID="..." \
     CLOUDFLARE_R2_SECRET_ACCESS_KEY="..." \
     CLOUDFLARE_R2_BUCKET="your-bucket-name" \
     CLOUDFLARE_R2_ENDPOINT="https://<account-id>.r2.cloudflarestorage.com" \
     CLOUDFLARE_R2_URL="https://pub-<id>.r2.dev"
   ```

> **Important**: `CLOUDFLARE_R2_URL` must be the **public bucket URL** (the `pub-*.r2.dev` domain), not the endpoint. This is what the app uses to generate public image URLs.

### One-time Fly.io Setup

Non-sensitive config lives in `fly.toml` under `[env]` (already committed). Only genuinely
sensitive values need to be set as secrets.

```bash
# 1. Install the Fly CLI
curl -L https://fly.io/install.sh | sh

# 2. Login
fly auth login

# 3. Launch the app (skips immediate deploy, uses our fly.toml)
fly launch --no-deploy

# 4. Set sensitive secrets
fly secrets set \
  APP_KEY="base64:..." \
  APP_URL="https://<your-app>.fly.dev" \
  DATABASE_URL="postgres://..." \
  CLOUDFLARE_R2_ACCESS_KEY_ID="..." \
  CLOUDFLARE_R2_SECRET_ACCESS_KEY="..." \
  CLOUDFLARE_R2_BUCKET="your-bucket-name" \
  CLOUDFLARE_R2_ENDPOINT="https://<account-id>.r2.cloudflarestorage.com" \
  CLOUDFLARE_R2_URL="https://pub-<id>.r2.dev"

# 5. Deploy
fly deploy
```

> Tip: Get your APP_KEY with `php artisan key:generate --show`

### Non-sensitive config (fly.toml)

The following are set in `fly.toml [env]` and do not need to be set as secrets:

| Variable | Value |
|----------|-------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `DB_CONNECTION` | `pgsql` |
| `FILESYSTEM_DISK` | `r2` |
| `SESSION_DRIVER` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `CACHE_STORE` | `database` |

### Seeding the Database

The admin user is created via seeders. `ADMIN_EMAIL` and `ADMIN_PASSWORD` must be set as secrets first:

```bash
fly secrets set ADMIN_EMAIL="your@email.com" ADMIN_PASSWORD="yourpassword"
```

Then run the seeders on the live machine (RoleSeeder must run first):

```bash
fly ssh console -C "php /var/www/html/artisan db:seed --class=RoleSeeder"
fly ssh console -C "php /var/www/html/artisan db:seed --class=AdminUserSeeder"
```

---

### Custom Domain

The app is permanently available at `whimsical-maga.fly.dev` (Fly.io's free subdomain — it doesn't expire). For a public-facing site you'll want your own domain (e.g. `whimsicalmaga.com`).

**Steps to add a custom domain:**

1. **Buy a domain** from a registrar such as Namecheap, Cloudflare, or Google Domains (~$10–15/year)
2. **Register the domain with Fly.io:**
   ```bash
   fly certs add yourdomain.com
   fly certs add www.yourdomain.com
   ```
   Fly.io will output DNS records to add and handles SSL certificates automatically.
3. **Point DNS to Fly.io** — at your registrar, add the `A` and `AAAA` records (or `CNAME` for www) that Fly.io provides
4. **Update `APP_URL`** secret to your new domain:
   ```bash
   fly secrets set APP_URL="https://yourdomain.com"
   ```
5. **Redeploy:**
   ```bash
   fly deploy
   ```

You can verify certificate status with:
```bash
fly certs show yourdomain.com
```

---

### Subsequent Deploys

```bash
fly deploy
```

Migrations run automatically at container startup via `docker/entrypoint.sh`.

### Viewing Logs

```bash
fly logs
```

### SSH into the running machine

```bash
fly ssh console
```

---

## Dockerfile Structure

Multi-stage build:

1. **`composer-deps` stage** — Installs PHP dependencies
2. **`node` stage** — Installs npm deps and runs `npm run build`, producing `public/build/`
3. **`php` stage** — PHP 8.4-FPM Alpine with Nginx and Supervisor; copies assets and vendor from previous stages

The final image serves the app on port **8080**.

### PHP configuration

Custom PHP INI overrides are written to `/usr/local/etc/php/conf.d/` at build time:

- **`opcache.ini`** — OPcache tuned for production
- **`uploads.ini`** — `upload_max_filesize = 10M` and `post_max_size = 10M` to support high-res background image uploads

### Nginx configuration

`docker/nginx.conf` configures the server with a few production-specific settings:

- `client_max_body_size 10M` — matches the PHP upload limit
- `client_body_temp_path /tmp/nginx_client_body` — nginx buffers uploaded files to disk before passing them to PHP-FPM; this path must be writable by the `www-data` worker process. The default Alpine path (`/var/lib/nginx/tmp/`) is owned by the `nginx` system user and not accessible to `www-data`, so we redirect it to `/tmp`.

Security headers (`X-Frame-Options`, `Content-Security-Policy`, etc.) are intentionally **not** set in nginx. They are handled entirely by the Laravel middleware stack (`bepsvpt/secure-headers` for general headers, `spatie/laravel-csp` for CSP), which allows environment-aware behaviour (e.g. HSTS only in production) and nonce-based CSP without duplicating header logic across two layers.

### Entrypoint behaviour

On every container start (`docker/entrypoint.sh`):

1. `package:discover` — ensures package manifest is up to date
2. `config:cache` / `route:cache` / `view:cache` — production only (skipped when `APP_ENV=local`)
3. `storage:link` — creates the `public/storage` symlink
4. `php artisan migrate --force` — runs any pending migrations
5. Supervisord starts Nginx + PHP-FPM

> **Note**: `config:cache` must run at container startup (not at build time) because Fly.io secrets
> are only injected at runtime. `route:cache` and `view:cache` follow after `config:cache` since
> they rely on the cached config to resolve the compiled views path correctly.

---

## Environment Variables Reference

| Variable | Local (Docker) | Production (Fly) |
|----------|---------------|-----------------|
| `APP_ENV` | `local` (docker-compose) | `production` (fly.toml) |
| `APP_DEBUG` | `true` (docker-compose) | `false` (fly.toml) |
| `APP_URL` | `http://localhost:8000` | `https://<app>.fly.dev` (secret) |
| `DB_CONNECTION` | set in `.env` | `pgsql` (fly.toml) |
| `DATABASE_URL` | — | Neon connection string (secret) |
| `FILESYSTEM_DISK` | `local` (`.env`) | `r2` (fly.toml) |
| `SESSION_DRIVER` | `database` | `database` (fly.toml) |
| `QUEUE_CONNECTION` | `database` | `database` (fly.toml) |
| `CACHE_STORE` | `database` | `database` (fly.toml) |
| `CLOUDFLARE_R2_ACCESS_KEY_ID` | — | R2 API token key (secret) |
| `CLOUDFLARE_R2_SECRET_ACCESS_KEY` | — | R2 API token secret (secret) |
| `CLOUDFLARE_R2_BUCKET` | — | R2 bucket name (secret) |
| `CLOUDFLARE_R2_ENDPOINT` | — | R2 S3-compatible endpoint (secret) |
| `CLOUDFLARE_R2_URL` | — | R2 public bucket URL (secret) |
