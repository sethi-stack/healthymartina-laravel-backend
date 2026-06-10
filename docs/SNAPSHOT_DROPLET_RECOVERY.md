# Snapshot Droplet Recovery Runbook

This guide is for a new or snapshot-restored DigitalOcean droplet that already has the
Laravel app and MySQL dump imported, but is not fully serving traffic yet.

It documents the recovery path that came out of the staging shell history:

- Nginx site was present but not enabled
- Nginx was pointing at a Let’s Encrypt certificate path that did not exist
- The app was deployed with a domain-based config, but the droplet IP changed
- The imported database dump had the tables, but the `migrations` ledger did not match

## 1. What a snapshot does and does not fix

A droplet snapshot usually restores the filesystem, but it does not guarantee that the
runtime state is still valid for a new public IP or a changed hostname.

You still need to verify:

- DNS points your domain to the new droplet IP
- Nginx is enabled in `/etc/nginx/sites-enabled`
- The SSL certificate exists for the exact hostname in the Nginx config
- Laravel `.env` still matches the live domain
- The `migrations` table matches the imported database

## 2. Why the public IP showed 404

Seeing `404 Not Found` on the raw droplet IP is usually normal when the app is configured
for a hostname such as `bo.healthymartina.com` or `api-test.healthymartina.com`.

That means:

- The site is likely meant to be accessed through the domain, not the IP
- Nginx may be serving the default server block for direct IP requests
- HTTPS may still be working for the domain even if the IP itself is not intended to respond

The real test is:

```bash
curl -I https://YOUR_API_DOMAIN
curl https://YOUR_API_DOMAIN/up
```

## 3. Restore order

Use this order after importing the DB dump onto the snapshot-restored droplet.

### 3.1 Enable the Nginx site

Check that the site exists in `sites-available` and is linked into `sites-enabled`.

```bash
sudo ln -sf /etc/nginx/sites-available/healthymartina-api /etc/nginx/sites-enabled/healthymartina-api
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
```

If `nginx -t` fails because of SSL, temporarily remove the `listen 443 ssl` block and the
`ssl_certificate` lines until the certificate is recreated.

### 3.2 Fix the hostname in Nginx

The Nginx config should use the real API hostname, not the droplet IP.

Example:

```nginx
server_name bo.healthymartina.com;
```

If the live API host changes later, update `server_name` before reloading Nginx.

### 3.3 Reissue the certificate

If the cert file is missing, recreate it with Certbot for the exact hostname.

If `certbot --nginx` fails with `cannot load certificate ... fullchain.pem`, the Nginx
config still contains a broken SSL block. Temporarily remove or comment out the
`listen 443 ssl` and `ssl_certificate*` lines, then reload Nginx with HTTP-only first:

```bash
sudo nano /etc/nginx/sites-available/healthymartina-api
sudo nginx -t
sudo systemctl reload nginx
```

Then rerun Certbot:

```bash
sudo apt update
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d bo.healthymartina.com
```

Certbot will rewrite the Nginx config with the correct `ssl_certificate` and
`ssl_certificate_key` paths.

### 3.4 Confirm Laravel env values

The domain-based values should still match the public host name:

```env
APP_URL=https://bo.healthymartina.com
SESSION_DOMAIN=.healthymartina.com
SANCTUM_STATEFUL_DOMAINS=app.healthymartina.com,yourproject.pages.dev
CORS_ALLOWED_ORIGINS=https://app.healthymartina.com,https://yourproject.pages.dev
```

After editing `.env`, clear and rebuild config:

```bash
cd /var/www/healthymartina/api
php8.3 artisan config:clear
php8.3 artisan config:cache
```

### 3.5 Handle the imported database

If you imported a production dump and then ran `deploy.sh`, Laravel may try to create
tables that already exist because the `migrations` table is empty or incomplete.

Run the migration bootstrap from `DEPLOYMENT.md` first:

```bash
cd /var/www/healthymartina/api
php8.3 artisan tinker --execute="
DB::table('migrations')->insert([
    ['migration' => '0001_01_01_000000_create_users_table', 'batch' => 1],
    ['migration' => '0001_01_01_000001_create_cache_table', 'batch' => 1],
    ['migration' => '0001_01_01_000002_create_jobs_table', 'batch' => 1],
    ['migration' => '2025_11_27_022347_create_personal_access_tokens_table', 'batch' => 1],
    ['migration' => '2025_11_27_051831_create_customer_columns', 'batch' => 1],
    ['migration' => '2025_11_27_051832_create_subscriptions_table', 'batch' => 1],
    ['migration' => '2025_11_27_051833_create_subscription_items_table', 'batch' => 1],
    ['migration' => '2025_11_27_051834_create_personal_access_tokens_table', 'batch' => 1],
]);"
php8.3 artisan migrate --force
```

### 3.6 Recover from a missing cache table

If the site starts returning:

```text
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'healthymartina.cache' doesn't exist
```

that means Laravel is using the database cache store, but the `cache` table has not
been created yet.

Fastest recovery:

1. Temporarily switch `.env` to `CACHE_STORE=file`
2. Run `php8.3 artisan config:clear && php8.3 artisan config:cache`
3. Run `php8.3 artisan migrate --force`
4. Verify the `cache` table exists
5. Switch `CACHE_STORE` back to `database`
6. Clear and rebuild config again

If you prefer to keep database cache permanently, make sure the migration
`0001_01_01_000001_create_cache_table.php` has run on the restored database.

### 3.7 Recover from a missing Sanctum token table

If Laravel starts throwing:

```text
Table 'healthymartina.personal_access_tokens' doesn't exist
```

then Sanctum’s token table is missing on the restored database.

Run the pending migrations after the cache issue is fixed:

```bash
cd /var/www/healthymartina/api
php8.3 artisan migrate:status | grep personal_access_tokens
php8.3 artisan migrate --force
```

If the table still does not appear, check whether the imported dump included an old
`personal_access_tokens` table but the `migrations` ledger was not restored correctly.
In that case, record the relevant migration in the `migrations` table, then rerun
`php8.3 artisan migrate --force`.

Note: this codebase currently contains two migrations that both create
`personal_access_tokens`:

- `2025_11_27_022347_create_personal_access_tokens_table.php`
- `2025_11_27_051834_create_personal_access_tokens_table.php`

If one is marked as ran and the other is still pending, that is expected. The second
one still needs to be applied or recorded, depending on the restored DB state.

## 4. Safe verification sequence

Use these checks before you consider the droplet live:

```bash
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl status nginx --no-pager

curl -I http://127.0.0.1
curl -I https://bo.healthymartina.com
curl https://bo.healthymartina.com/up

cd /var/www/healthymartina/api
php8.3 artisan migrate:status
php8.3 artisan config:cache
```

If the domain works but the IP still shows 404, that is fine as long as the public DNS
points to the new droplet and the domain returns the app correctly.

## 5. Notes from the shell history

The captured history shows the droplet was configured with:

- `nginx`
- `php8.3-fpm`
- `mysql-server`
- `certbot`
- a GitHub deploy key
- a custom Nginx vhost at `/etc/nginx/sites-available/healthymartina-api`

The history also shows repeated manual fixes for:

- `php artisan migrate --force`
- `php artisan config:clear`
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`
- `php artisan lang:publish`
- `php artisan vendor:publish --provider="Backpack\\CRUD\\BackpackServiceProvider" --tag=public --force`

That is the normal recovery surface for a snapshot-restored Laravel droplet.
