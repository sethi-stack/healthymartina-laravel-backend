# HealthyMartina API — Deployment & Migration Guide

> Complete guide for deploying the Laravel API to a DigitalOcean Droplet,
> migrating the existing MySQL database, and moving stored files from
> Google Cloud Storage (GCS) to DigitalOcean Spaces.

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Prerequisites](#2-prerequisites)
3. [Droplet Provisioning (one-time)](#3-droplet-provisioning-one-time)
4. [Laravel App Setup (one-time)](#4-laravel-app-setup-one-time)
5. [Nginx & SSL Configuration](#5-nginx--ssl-configuration)
6. [GitHub Actions CI/CD Setup](#6-github-actions-cicd-setup)
7. [DigitalOcean Spaces Setup](#7-digitalocean-spaces-setup)
8. [Cloudflare Pages Setup (React Frontend)](#8-cloudflare-pages-setup-react-frontend)
9. [Database Migration (Legacy → New Droplet)](#9-database-migration-legacy--new-droplet)
10. [File Migration: GCS → DO Spaces](#10-file-migration-gcs--do-spaces)
11. [DNS Cutover](#11-dns-cutover)
12. [Post-Migration Cleanup](#12-post-migration-cleanup)
13. [Verification Checklist](#13-verification-checklist)
14. [First-Deploy Server Fixes (Run Once)](#14-first-deploy-server-fixes-run-once)

---

## 1. Architecture Overview

```
Users
  │
  ├── React SPA ──────────────────► Cloudflare Pages (free, CDN)
  │                                    build: react-front-app/dist
  │
  └── API requests ────────────────► DigitalOcean Droplet $12/mo
                                        Nginx + PHP 8.3-FPM + MySQL
                                        /var/www/healthymartina/api
                                              │
                                              └── File uploads ──► DO Spaces (S3-compatible)
```

**Droplet spec:** Ubuntu 22.04, 1 vCPU / 2 GB RAM / 50 GB SSD — adequate for ~10 concurrent users + DomPDF PDF generation.

---

## 2. Prerequisites

- DigitalOcean account with billing enabled
- Domain name (e.g. `yourdomain.com`) with access to DNS records
- GitHub account with push access to this repo
- Local machine with `ssh`, `mysql`, `gcloud` CLI tools installed
- GCS service account JSON key (for migration — can be deleted after)

---

## 3. Droplet Provisioning (one-time)

### 3.1 Create the Droplet

In the DigitalOcean dashboard:

1. **Create → Droplet**
2. Choose **Ubuntu 22.04 LTS**
3. Size: **Basic — $12/mo** (1 vCPU / 2 GB / 50 GB)
4. Region: pick closest to your users (e.g. `AMS3` for Spain)
5. Authentication: **SSH Key** — paste your public key (`~/.ssh/id_ed25519.pub`)
   ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIOJ0IowAAdNiLblbSdqiNI0AcFRvRTYd8Xnh+K0HOmla dj@Dheerajs-MBP
6. Hostname: `healthymartina-api`
7. Click **Create Droplet** — note the IP address

### 3.2 SSH in

```bash
ssh root@YOUR_DROPLET_IP
```

### 3.3 Install system packages

```bash
# Update system
apt update && apt upgrade -y

# PHP 8.3 + extensions Laravel needs
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y \
  nginx \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-gd php8.3-zip php8.3-bcmath php8.3-intl \
  php8.3-gmp php8.3-tokenizer php8.3-fileinfo \
  mysql-server \
  git \
  unzip \
  curl

# Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Verify
php -v
composer --version
nginx -v
mysql --version
```

### 3.4 Create a deploy user (optional but recommended)

```bash
adduser deploy
usermod -aG sudo deploy
# Copy your SSH key to deploy user
rsync --archive --chown=deploy:deploy ~/.ssh /home/deploy
```

### 3.5 Set up MySQL

```bash
mysql_secure_installation   # follow prompts, set root password

mysql -u root -p. # ks<YdP67vXV4
```

```sql
CREATE DATABASE healthymartina CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hmartina'@'localhost' IDENTIFIED BY 'ks<YdP67vXV4';
GRANT ALL PRIVILEGES ON healthymartina.* TO 'hmartina'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 4. Laravel App Setup (one-time)

### 4.1 Add a Deploy Key to GitHub

On the Droplet, generate a key for the `deploy` (or `root`) user:

```bash
ssh-keygen -t ed25519 -C "healthymartina-droplet" -f ~/.ssh/github_deploy
cat ~/.ssh/github_deploy.pub

ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIBD2TGM53UluPnhJIIovjm/rJ2TDGrk84VVZ77kxKUum healthymartina-droplet
```

In GitHub → `api` repo → **Settings → Deploy keys → Add deploy key**

- Title: `DigitalOcean Droplet`
- Key: paste the output above
- Allow write access: **No** (read-only is enough)

Add to SSH config on the Droplet:

```bash
cat >> ~/.ssh/config <<EOF
Host github.com
  IdentityFile ~/.ssh/github_deploy
  StrictHostKeyChecking no
EOF
```

### 4.2 Clone the repo

```bash
mkdir -p /var/www/healthymartina
cd /var/www/healthymartina
git clone git@github.com:YOUR_ORG/api.git api
```

### 4.3 Install PHP dependencies

```bash
cd /var/www/healthymartina/api
composer install --no-dev --optimize-autoloader --no-interaction
```

> **Note on DO Spaces:** Laravel uses the built-in `s3` Flysystem driver for DO Spaces.
> The `google/cloud-storage` package in `composer.json` is a legacy dependency that will
> be removed in [post-migration cleanup](#12-post-migration-cleanup).

### 4.4 Configure environment

```bash
cp .env.production.example .env
nano .env   # fill in all real values (see .env.production.example for reference)
```

Key values to set:

```
APP_KEY=                        # generated in next step
APP_URL=https://api.yourdomain.com
DB_PASSWORD=STRONG_PASSWORD_HERE
SANCTUM_STATEFUL_DOMAINS=app.yourdomain.com,yourproject.pages.dev
CORS_ALLOWED_ORIGINS=https://app.yourdomain.com,https://yourproject.pages.dev
DO_SPACES_KEY=...
DO_SPACES_SECRET=...
DO_SPACES_BUCKET=healthymartina
SESSION_DOMAIN=.yourdomain.com
```

```bash
# Generate APP_KEY
php artisan key:generate
```

### 4.5 Run migrations

> **Wait** — if migrating an existing database, skip this step and follow
> [Section 9](#9-database-migration-legacy--new-droplet) first. Then come back
> and run only _new_ migrations:
>
> ```bash
> php artisan migrate
> ```
>
> For a **fresh installation** with no existing data:
>
> ```bash
> php artisan migrate
> ```

### 4.6 Set file permissions

```bash
chown -R www-data:www-data /var/www/healthymartina/api
chmod -R 755 /var/www/healthymartina/api
chmod -R 775 /var/www/healthymartina/api/storage
chmod -R 775 /var/www/healthymartina/api/bootstrap/cache
```

### 4.7 Create the storage symlink

```bash
php artisan storage:link
```

---

## 5. Nginx & SSL Configuration

### 5.1 Install the Nginx config

```bash
cp /var/www/healthymartina/api/scripts/nginx.conf \
   /etc/nginx/sites-available/healthymartina-api

# Edit: replace "api.yourdomain.com" with your real domain
nano /etc/nginx/sites-available/healthymartina-api

ln -s /etc/nginx/sites-available/healthymartina-api \
      /etc/nginx/sites-enabled/healthymartina-api

# Remove default site if present
rm -f /etc/nginx/sites-enabled/default

nginx -t && systemctl reload nginx
```

### 5.2 Point your DNS first (required for Certbot)

In your DNS provider, add an **A record**:

| Type | Name  | Value             |
| ---- | ----- | ----------------- |
| A    | `api` | `YOUR_DROPLET_IP` |

Wait for propagation (check with `dig api.yourdomain.com`), then:

### 5.3 Install SSL with Certbot

```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d api.yourdomain.com
# Follow prompts: enter email, agree to ToS, choose redirect HTTP→HTTPS
```

Certbot auto-renews via a cron job. Verify:

```bash
certbot renew --dry-run
```

### 5.4 Allow firewall ports

```bash
ufw allow 22
ufw allow 80
ufw allow 443
ufw enable
ufw status
```

---

## 6. GitHub Actions CI/CD Setup

The workflow file is at `.github/workflows/deploy-backend.yml` in this repo.
It SSHes into the Droplet and runs `deploy.sh` on every push to `main`.

### Add secrets to the GitHub repo

Go to GitHub → `api` repo → **Settings → Secrets and variables → Actions → New repository secret**:

| Secret name  | Value                                                                        |
| ------------ | ---------------------------------------------------------------------------- |
| `DO_HOST`    | Your Droplet IP address                                                      |
| `DO_USER`    | `root` or `deploy`                                                           |
| `DO_SSH_KEY` | Contents of `~/.ssh/id_ed25519` (your **private** key on your local machine) |

After adding secrets, push any change to `main` to verify the workflow triggers and succeeds.

---

## 7. DigitalOcean Spaces Setup

### 7.1 Create a Space

In DigitalOcean → **Spaces → Create a Space**:

- Region: same as your Droplet (e.g. `nyc3` or `ams3`)
- Name: `healthymartina`
- CDN: **Enable** (generates the CDN URL)
- File listing: **Restricted**

Note the endpoint: `https://nyc3.digitaloceanspaces.com` (replace region as appropriate)
CDN URL will be: `https://healthymartina.nyc3.cdn.digitaloceanspaces.com`

### 7.2 Create API credentials

In DigitalOcean → **API → Spaces Keys → Generate New Key**:

- Name: `healthymartina-laravel`
- Copy the **Access Key** and **Secret Key** — the secret is shown only once

### 7.3 Create folder structure in Spaces

You can use the DO dashboard or the AWS CLI (which works with Spaces):

```bash
# Install AWS CLI
pip install awscli

# Configure for DO Spaces
aws configure --profile spaces
# Access Key ID: YOUR_SPACES_KEY
# Secret Access Key: YOUR_SPACES_SECRET
# Default region name: nyc3
# Default output format: json

# Create folder placeholders (Spaces shows folders once files are uploaded)
# No explicit folder creation needed — they're created on first upload.
```

### 7.4 Make bucket public (for images)

In the Spaces dashboard → **Settings → Permissions → Make Public** or set via CORS policy for browser uploads.

---

## 8. Cloudflare Pages Setup (React Frontend)

One-time setup in the Cloudflare dashboard — no files to create.

1. Go to **Cloudflare → Workers & Pages → Create → Pages → Connect to Git**
2. Select the **monorepo** (or `react-front-app` if it's a separate repo)
3. Build settings:
    - **Framework preset:** Vite
    - **Build command:** `cd react-front-app && npm ci && npm run build`
    - **Build output directory:** `react-front-app/dist`
4. **Environment Variables → Production:**
    ```
    VITE_API_BASE_URL = https://api.yourdomain.com/api/v1
    ```
5. Click **Save and Deploy**

After deploy, add a custom domain under **Custom domains → Set up a custom domain**:

- `app.yourdomain.com` — add a `CNAME` record pointing to `yourproject.pages.dev`

---

## 9. Database Migration (Legacy → New Droplet)

### 9.1 Export from the existing database

**If the legacy DB is on Google Cloud SQL:**

```bash
# On your local machine (with gcloud CLI authenticated)
gcloud sql export sql YOUR_INSTANCE_NAME gs://YOUR_BUCKET/healthymartina-backup.sql \
  --database=healthymartina

# Download the dump
gsutil cp gs://YOUR_BUCKET/healthymartina-backup.sql ./healthymartina-backup.sql
```

**If the legacy DB is a regular MySQL server:**

```bash
mysqldump -h HOST -u USER -p healthymartina \
  --single-transaction \
  --routines \
  --triggers \
  > healthymartina-backup.sql
```

### 9.2 Review and adapt the dump (if needed)

The new schema may differ from the legacy schema (new migrations add tables:
`personal_access_tokens`, `sessions`, `cache`, `jobs`, `subscriptions`, `subscription_items`).

If the legacy dump includes these tables, they may conflict. Options:

- Import only the data tables you need (exclude Laravel framework tables)
- Or import everything and let `php artisan migrate` detect already-existing tables

### 9.3 Upload and import on the Droplet

```bash
# Copy dump to Droplet
scp healthymartina-backup.sql root@YOUR_DROPLET_IP:/tmp/

# SSH in and import
ssh root@YOUR_DROPLET_IP
mysql -u hmartina -p healthymartina < /tmp/healthymartina-backup.sql

# Clean up
rm /tmp/healthymartina-backup.sql
```

### 9.4 Run new migrations

After the import, run only the migrations that haven't been applied yet:

```bash
cd /var/www/healthymartina/api
php artisan migrate --force
```

Laravel's migration system tracks which migrations have run in the `migrations` table. If the legacy DB had no `migrations` table, run `migrate:fresh` only if you're comfortable with the import being complete, otherwise run `migrate` which will attempt to create any missing tables.

### 9.5 Verify data

```bash
php artisan tinker
>>> App\Models\User::count()
>>> App\Models\NewReceta::count()
```

---

## 10. File Migration: GCS → DO Spaces

All stored files (profile photos, business logos, recipe images, plan images) need to be copied from GCS to DO Spaces, preserving the same folder paths so that existing database records (which store relative paths like `users/profile_pictures/abc.jpg`) continue to work.

### Known GCS folder structure

| GCS path                   | DO Spaces path (same)     |
| -------------------------- | ------------------------- |
| `users/profile_pictures/`  | `users/profile_pictures/` |
| `users/business_logos/`    | `users/business_logos/`   |
| _(any recipe image paths)_ | _(same)_                  |
| _(any plan image paths)_   | _(same)_                  |

### Option A: rclone (recommended — handles large sets efficiently)

```bash
# Install rclone on your local machine
brew install rclone   # macOS
# or: curl https://rclone.org/install.sh | sudo bash

# Configure GCS remote
rclone config
# → New remote → name: gcs → Google Cloud Storage → use service account JSON
# → Your service account JSON path: /path/to/service-account.json
# → Project number: YOUR_GCP_PROJECT_NUMBER

# Configure DO Spaces remote
rclone config
# → New remote → name: spaces → S3-compatible → provider: DigitalOcean
# → access_key_id: YOUR_DO_SPACES_KEY
# → secret_access_key: YOUR_DO_SPACES_SECRET
# → endpoint: nyc3.digitaloceanspaces.com
# → region: nyc3

# Dry run first (lists what would be copied, no actual transfer)
rclone copy gcs:YOUR_GCS_BUCKET spaces:healthymartina --dry-run --progress

# Run the actual copy
rclone copy gcs:YOUR_GCS_BUCKET spaces:healthymartina --progress

# Verify counts match
rclone ls gcs:YOUR_GCS_BUCKET | wc -l
rclone ls spaces:healthymartina | wc -l
```

### Option B: gsutil + AWS CLI (if rclone is not available)

```bash
# Download everything from GCS to a local temp folder
mkdir /tmp/gcs-migration
gsutil -m rsync -r gs://YOUR_GCS_BUCKET /tmp/gcs-migration/

# Upload to DO Spaces using AWS CLI (configured for Spaces)
aws s3 sync /tmp/gcs-migration/ s3://healthymartina \
  --endpoint-url https://nyc3.digitaloceanspaces.com \
  --profile spaces \
  --acl public-read

# Verify
aws s3 ls s3://healthymartina --recursive --profile spaces | wc -l
```

### Verify file accessibility

After migration, test that images resolve correctly through the CDN:

```bash
# Get any image path from the DB
php artisan tinker
>>> App\Models\User::whereNotNull('image')->first()->image
# Should return: https://healthymartina.nyc3.cdn.digitaloceanspaces.com/users/profile_pictures/...

# Open that URL in a browser — it should display the image
```

### Handling existing GCS URLs already stored in the DB

If some columns store **full URLs** (starting with `https://storage.googleapis.com/...`) instead of relative paths, you need a one-time DB update:

```sql
-- Check if image column contains full URLs or relative paths
SELECT image FROM users WHERE image IS NOT NULL LIMIT 5;

-- If they are full GCS URLs, strip the GCS prefix:
UPDATE users
SET image = REPLACE(image, 'https://storage.googleapis.com/YOUR_GCS_BUCKET/', '')
WHERE image LIKE 'https://storage.googleapis.com/%';

UPDATE users
SET bimage = REPLACE(bimage, 'https://storage.googleapis.com/YOUR_GCS_BUCKET/', '')
WHERE bimage LIKE 'https://storage.googleapis.com/%';

-- Repeat for other models (recetas, plans, etc.) if they store full URLs
```

---

## 11. DNS Cutover

Make these DNS changes in your domain registrar / DNS provider:

| Type  | Name  | Value                   | TTL |
| ----- | ----- | ----------------------- | --- |
| A     | `api` | `YOUR_DROPLET_IP`       | 300 |
| CNAME | `app` | `yourproject.pages.dev` | 300 |

Lower TTLs to 300 before the cutover so changes propagate quickly.

**Traffic cutover sequence:**

1. Set DNS records
2. Wait for propagation: `dig api.yourdomain.com` should return the Droplet IP
3. Certbot SSL should already be installed at this point
4. Test: `curl https://api.yourdomain.com/up` → `{"status":"up"}`
5. Verify frontend login works end-to-end

---

## 12. Post-Migration Cleanup

Once the migration is confirmed working and you no longer need GCS:

### 12.1 Remove GCS packages from composer.json

```bash
cd /var/www/healthymartina/api
composer remove google/cloud-storage google/cloud-error-reporting google/cloud-logging
composer install --no-dev --optimize-autoloader
```

Commit and push — the GitHub Action will deploy the leaner build.

### 12.2 Remove GCS environment variables from .env

Remove or comment out on the Droplet:

```
# GOOGLE_CLOUD_PROJECT=
# GOOGLE_APPLICATION_CREDENTIALS=
# GCS_BUCKET=
```

### 12.3 Revoke GCS service account (in GCP console)

After confirming all files are accessible via DO Spaces CDN, revoke or delete the GCS service account key to eliminate the unused credential.

### 12.4 Delete the GCS bucket (when ready)

```bash
gsutil rm -r gs://YOUR_GCS_BUCKET
```

Only do this after verifying production is fully working from DO Spaces.

---

## 13. Verification Checklist

Run through these checks after the full deployment and migration:

- [ ] `curl https://api.yourdomain.com/up` returns `{"status":"up"}`
- [ ] `curl https://api.yourdomain.com/api/v1/recipes` returns JSON (or 401 — either means routing works)
- [ ] Login via React frontend returns a Sanctum token (no CORS errors)
- [ ] Profile photo upload works and URL resolves via DO Spaces CDN
- [ ] Business logo upload works and URL resolves via DO Spaces CDN
- [ ] Calendar PDF export downloads correctly (tests DomPDF + Nginx timeout)
- [ ] A push to `main` triggers the GitHub Action and deploys successfully
- [ ] SSL cert is valid: `curl -I https://api.yourdomain.com`
- [ ] Certbot auto-renewal works: `certbot renew --dry-run`
- [ ] Existing user profile photos (migrated from GCS) display correctly
- [ ] `php artisan migrate:status` shows all migrations as `Ran`

---

## Quick Reference

### Manual deploy (SSH to Droplet)

```bash
ssh root@YOUR_DROPLET_IP
bash /var/www/healthymartina/api/scripts/deploy.sh
```

### Clear all caches

```bash
cd /var/www/healthymartina/api
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Useful log locations

| Log          | Path                                           |
| ------------ | ---------------------------------------------- |
| Laravel      | `storage/logs/laravel.log`                     |
| Nginx access | `/var/log/nginx/healthymartina-api.access.log` |
| Nginx error  | `/var/log/nginx/healthymartina-api.error.log`  |
| PHP-FPM      | `/var/log/php8.3-fpm.log`                      |
| MySQL        | `/var/log/mysql/error.log`                     |

---

## 14. First-Deploy Server Fixes (Run Once)

These are one-time fixes discovered during the initial staging deployment when importing an existing production database dump. Run these **after** completing sections 4 and 9.

### 14.1 Handle migrations that conflict with the imported DB dump

When importing a production dump, the `migrations` table may be empty while all tables already exist. Running `php8.3 artisan migrate --force` will fail with "table already exists" errors.

Fix — fake the base migrations that already exist, then run only the new ones:

```bash
cd /var/www/healthymartina/api

# Mark migrations as run without executing them
php8.3 artisan tinker --execute="
DB::table('migrations')->insert([
    ['migration' => '0001_01_01_000000_create_users_table',                    'batch' => 1],
    ['migration' => '0001_01_01_000001_create_cache_table',                    'batch' => 1],
    ['migration' => '0001_01_01_000002_create_jobs_table',                     'batch' => 1],
    ['migration' => '2025_11_27_022347_create_personal_access_tokens_table',   'batch' => 1],
    ['migration' => '2025_11_27_051831_create_customer_columns',               'batch' => 1],
    ['migration' => '2025_11_27_051832_create_subscriptions_table',            'batch' => 1],
    ['migration' => '2025_11_27_051833_create_subscription_items_table',       'batch' => 1],
    ['migration' => '2025_11_27_051834_create_personal_access_tokens_table',   'batch' => 1],
]);"

# Run any remaining migrations normally
php8.3 artisan migrate --force
```

> If new migrations are added in future, only those will run — the faked ones are already recorded.

### 14.2 Fix storage disk config (FILESYSTEM_DISK)

The legacy app used `spaces` (DO Spaces / S3-compatible) as the default disk. Until DO Spaces is fully configured, set the disk to `public` to avoid missing S3 adapter errors:

```bash
sed -i 's/^FILESYSTEM_DISK=.*/FILESYSTEM_DISK=public/' /var/www/healthymartina/api/.env
```

Then clear cached config (required — Laravel caches the disk driver on boot):

```bash
php8.3 artisan config:clear && php8.3 artisan config:cache
```

> Once DO Spaces credentials are set in `.env`, revert to `FILESYSTEM_DISK=s3`.

### 14.3 Publish validation language files

Laravel 11+ no longer ships language files by default. Without publishing them, validation errors return raw keys like `validation.unique` instead of human-readable messages.

```bash
cd /var/www/healthymartina/api
php8.3 artisan lang:publish
```

### 14.4 Set app locale to English

The `.env` may have `APP_LOCALE=es` with no Spanish translation files, causing all validation messages to fall back to raw keys. Set to English:

```bash
sed -i 's/^APP_LOCALE=.*/APP_LOCALE=en/'           /var/www/healthymartina/api/.env
sed -i 's/^APP_FALLBACK_LOCALE=.*/APP_FALLBACK_LOCALE=en/' /var/www/healthymartina/api/.env
php8.3 artisan config:clear && php8.3 artisan config:cache
```

> To add Spanish translations later: `cp -r lang/en lang/es` and translate `lang/es/validation.php`.

### 14.5 Publish Backpack admin assets

The Backpack admin panel will load without CSS until assets are published:

```bash
cd /var/www/healthymartina/api
php8.3 artisan vendor:publish --provider="Backpack\CRUD\BackpackServiceProvider" --tag=public --force
php8.3 artisan basset:cache
php8.3 artisan storage:link
```

---

## 15. Droplet Setup History

This is a sanitized version of the command history used on the staging droplet.
Secrets, passwords, and one-off interactive input are intentionally redacted.

### 15.1 Base OS and packages

```bash
apt update && apt upgrade -y
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y \
  nginx \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-gd php8.3-zip php8.3-bcmath php8.3-intl \
  php8.3-gmp php8.3-tokenizer php8.3-fileinfo \
  mysql-server \
  git \
  unzip \
  curl
```

### 15.2 Composer, users, and SSH

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

adduser deploy
usermod -aG sudo deploy
rsync --archive --chown=deploy:deploy ~/.ssh /home/deploy

ssh-keygen -t ed25519 -C "healthymartina-droplet" -f ~/.ssh/github_deploy
chmod 600 ~/.ssh/github_deploy

cat >> ~/.ssh/config <<EOF
Host github.com
  IdentityFile ~/.ssh/github_deploy
  StrictHostKeyChecking no
EOF
```

### 15.3 App checkout and bootstrap

```bash
mkdir -p /var/www/healthymartina
cd /var/www/healthymartina
git clone git@github.com:sethi-stack/healthymartina-laravel-backend.git api

cd /var/www/healthymartina/api
composer install --no-dev --optimize-autoloader --no-interaction
cp .env.production.example .env
php8.3 artisan key:generate

chown -R www-data:www-data /var/www/healthymartina/api
chmod -R 755 /var/www/healthymartina/api
chmod -R 775 /var/www/healthymartina/api/storage
chmod -R 775 /var/www/healthymartina/api/bootstrap/cache

php8.3 artisan storage:link
```

### 15.4 Nginx and SSL

```bash
cp /var/www/healthymartina/api/scripts/nginx.conf \
   /etc/nginx/sites-available/healthymartina-api
ln -s /etc/nginx/sites-available/healthymartina-api \
      /etc/nginx/sites-enabled/healthymartina-api
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

apt install -y certbot python3-certbot-nginx
certbot --nginx -d api-test.healthymartina.com
certbot install --cert-name api-test.healthymartina.com
```

### 15.5 Database and migrations

```bash
mysql_secure_installation
mysql -u root -p
mysql -u root -p healthymartina < /tmp/prod-hm-app.sql

cd /var/www/healthymartina/api
php8.3 artisan migrate --force
php8.3 artisan migrate:status
php8.3 artisan tinker
```

### 15.6 One-time Laravel fixes

```bash
php8.3 artisan config:clear
php8.3 artisan config:cache
php8.3 artisan route:clear
php8.3 artisan view:clear

php8.3 artisan lang:publish

sed -i 's/^APP_LOCALE=.*/APP_LOCALE=en/' /var/www/healthymartina/api/.env
sed -i 's/^APP_FALLBACK_LOCALE=.*/APP_FALLBACK_LOCALE=en/' /var/www/healthymartina/api/.env

php8.3 artisan vendor:publish --provider="Backpack\CRUD\BackpackServiceProvider" --tag=public --force
php8.3 artisan basset:cache

sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

### 15.7 File and log checks

```bash
tail -50 /var/log/nginx/error.log
tail -50 /var/log/nginx/access.log
tail -n 40 /var/www/healthymartina/api/storage/logs/laravel.log
openssl s_client -connect api-test.healthymartina.com:443 -servername api-test.healthymartina.com
```

---

## 16. Files To Replicate On Prod

Use this as the "source of truth" checklist when rebuilding or cloning the prod droplet.

| Repo file / artifact | Destination on prod | Status | Notes |
| -------------------- | ------------------- | ------ | ----- |
| `laravel-backend-app/scripts/nginx.conf` | `/etc/nginx/sites-available/healthymartina-api` | Copy and edit | Replace `api.yourdomain.com` with the real host name, then symlink into `sites-enabled`. |
| `laravel-backend-app/.env.production.example` | `/var/www/healthymartina/api/.env` | Copy and edit | Fill in DB, Sanctum, CORS, Spaces, and session values. Do not commit secrets. |
| `laravel-backend-app/scripts/deploy.sh` | `/var/www/healthymartina/api/scripts/deploy.sh` | Keep in sync | Update `APP_DIR` and the PHP-FPM version to match the droplet. |
| `laravel-backend-app/lang/` | `/var/www/healthymartina/api/lang/` | Commit to git | Needed for published language files and Backpack translations. |
| `laravel-backend-app/resources/views/vendor/backpack/` | `/var/www/healthymartina/api/resources/views/vendor/backpack/` | Commit to git | Custom Backpack view overrides used by the admin UI. |
| `laravel-backend-app/routes/backpack/custom.php` | `/var/www/healthymartina/api/routes/backpack/custom.php` | Commit to git | Backpack route customizations. |

### Runtime-only items on the droplet

These are created on the server and should not be copied from the repo as source files:

- `/var/www/healthymartina/api/storage/`
- `/var/www/healthymartina/api/bootstrap/cache/`
- `/var/www/healthymartina/api/public/storage` symlink
- `/etc/ssl/` and Certbot-managed Nginx SSL config
- `/var/log/nginx/*` and `/var/www/healthymartina/api/storage/logs/*`

### Recommended deployment order

1. Copy the repo to `/var/www/healthymartina/api`
2. Copy `.env.production.example` to `.env` and fill secrets
3. Copy `scripts/nginx.conf` to `/etc/nginx/sites-available/healthymartina-api`
4. Install dependencies with `composer install --no-dev --optimize-autoloader --no-interaction`
5. Run `php8.3 artisan key:generate`, `storage:link`, and migrations
6. Publish Backpack assets and language files
7. Reload Nginx and PHP-FPM

---

## 17. PDF Export API (DigitalOcean Setup)

This section deploys `pdf-export-service` on the same droplet and connects Laravel to it.

### 17.1 Install Node.js (LTS)

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
node -v
npm -v
```

### 17.2 Clone service and install dependencies

```bash
cd /var/www/healthymartina
cd /var/www/healthymartina/api/pdf-export-service
npm ci --omit=dev
```

### 17.3 Configure service env

```bash
cd /var/www/healthymartina/api/pdf-export-service
cp .env.example .env
nano .env
```

Recommended `.env` values:

```env
PORT=4300
EXPORT_SHARED_SECRET=use-the-same-secret-as-laravel
EXPORT_MAX_CONCURRENCY=2
EXPORT_JOB_TIMEOUT_MS=180000
EXPORT_HTML_RENDER_TIMEOUT_MS=120000
EXPORT_IMAGE_WAIT_TIMEOUT_MS=45000
STORAGE_DIR=./storage
EXPORT_ALLOW_PDFKIT_FALLBACK=false
```

### 17.4 Run with systemd

Create `/etc/systemd/system/pdf-export.service`:

```ini
[Unit]
Description=HealthyMartina PDF Export Service
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/healthymartina/api/pdf-export-service
Environment=NODE_ENV=production
ExecStart=/usr/bin/node src/server.js
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
systemctl daemon-reload
systemctl enable pdf-export
systemctl restart pdf-export
systemctl status pdf-export --no-pager
```

### 17.5 Optional Nginx reverse proxy

If you want to expose it internally via Nginx (instead of direct `127.0.0.1:4300`), add:

```nginx
location /pdf-export/ {
    proxy_pass http://127.0.0.1:4300/;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

Then reload Nginx:

```bash
nginx -t && systemctl reload nginx
```

### 17.6 Laravel API wiring

In `/var/www/healthymartina/api/.env`:

```env
PDF_EXPORT_ASYNC_ENABLED=true
PDF_EXPORT_SERVICE_URL=http://127.0.0.1:4300
PDF_EXPORT_SHARED_SECRET=use-the-same-secret-as-node-service
PDF_EXPORT_HTTP_TIMEOUT_SECONDS=180
PDF_EXPORT_INTERNAL_TOKEN=
```

Apply config:

```bash
cd /var/www/healthymartina/api
php artisan config:clear
php artisan config:cache
```

### 17.7 Validation checklist

```bash
# Service health
curl -sS http://127.0.0.1:4300/health

# Service logs
journalctl -u pdf-export -f

# Laravel logs during export
tail -f /var/www/healthymartina/api/storage/logs/laravel.log
```

Expected:
- `/health` returns `ok`.
- Laravel can create export jobs without 500 timeout.
- Download/email endpoints complete for large calendars.
