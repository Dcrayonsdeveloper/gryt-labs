# Deploying GRYT Labs to Hostinger (Cloud Startup) via GitHub

This app was adapted to run on Hostinger's **MySQL** (no PostgreSQL/Redis/Meilisearch needed).
Drivers used in production: **database** cache, **database** sessions, **database** queue (cron worker),
**database** search (Scout). Single-tenant: the store domain is registered as one tenant.

Replace every `<PLACEHOLDER>` below with your real values.

---

## 1. Point the domain to Hostinger
- hPanel → **Domains** → add `<YOUR_DOMAIN>` (or update its nameservers/DNS to Hostinger).
- hPanel → **Websites** → **Add website** → choose `<YOUR_DOMAIN>` on the Cloud Startup plan.
- Set **PHP version to 8.2 or 8.3** (hPanel → Advanced → PHP Configuration).
- Enable **free SSL** for the domain (hPanel → Security → SSL).

## 2. Create the MySQL database
- hPanel → **Databases → MySQL Databases**.
- Create a database + user (Hostinger prefixes them, e.g. `u123456_grytdb` / `u123456_gryt`).
- Grant the user all privileges on the database.
- Note the **DB name, user, password** — they go into `.env`.

## 3. Connect GitHub (auto-deploy)
- hPanel → **Advanced → GIT**.
- Repository: `https://github.com/Dcrayonsdeveloper/gryt-labs.git`  ·  Branch: `main`
- Install path: a folder under your domain, e.g. `domains/<YOUR_DOMAIN>/app`
  (clone OUTSIDE `public_html` so the app root isn't web-exposed).
- After it clones, copy the **Webhook URL** and add it in GitHub:
  repo → Settings → Webhooks → Add webhook → paste URL, content type `application/json`, event: *push*.
  Now every push to `main` auto-pulls to the server. *(Composer/migrations still run via SSH — step 5.)*

## 4. Set the document root to `/public`
- hPanel → **Websites → `<YOUR_DOMAIN>` → Website settings → Document root**.
- Set it to: `domains/<YOUR_DOMAIN>/app/public`
  (Laravel must serve from its `public/` folder — never the project root.)

## 5. First-time setup over SSH
hPanel → **Advanced → SSH Access** (enable it, note host/port/user), then connect and run:

```bash
cd ~/domains/<YOUR_DOMAIN>/app

# PHP dependencies (production)
composer install --no-dev --optimize-autoloader

# Create the env file from the prepared template, then edit the placeholders
cp .env.hostinger .env
nano .env          # fill DB_*, APP_URL, CENTRAL_DOMAIN, TENANT_SUBDOMAIN_BASE, MAIL_*

# Link storage for uploaded/generated images
php artisan storage:link

# --- Database schema (single-tenant: tenant migrations + 3 infra tables) ---
php artisan migrate --path=database/migrations/tenant --force
php artisan migrate --path=database/migrations/2019_09_15_000010_create_tenants_table.php --force
php artisan migrate --path=database/migrations/2019_09_15_000020_create_domains_table.php --force
php artisan migrate --path=database/migrations/2019_09_15_000030_create_super_admins_table.php --force

# --- Seed base data (settings, brands, legal pages, blog) + product catalog ---
php artisan db:seed --force
php artisan db:seed --class=ProductSeeder --force

# Cache config/routes/views for speed
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Do NOT run plain `php artisan migrate` — the central `database/migrations` folder has
> legacy MySQL-ENUM migrations that aren't part of the single-tenant store schema.

## 6. Register the store as a tenant
The site resolves the tenant by domain. Run this once in **hPanel → Databases → phpMyAdmin**
(select your DB → SQL tab). `tenancy_db_name` MUST equal your real DB name:

```sql
INSERT INTO tenants (id, name, is_active, plan, data, created_at, updated_at)
VALUES ('gryt', 'GrytLabs', 1, 'standard',
        '{"tenancy_db_name":"<HOSTINGER_DB_NAME>"}', NOW(), NOW());

INSERT INTO domains (domain, tenant_id, created_at, updated_at)
VALUES ('<YOUR_DOMAIN>', 'gryt', NOW(), NOW());
```

## 7. Cron jobs (queue + scheduler)
hPanel → **Advanced → Cron Jobs**. Add two (adjust the path):

```
# Laravel scheduler — every minute
* * * * * cd ~/domains/<YOUR_DOMAIN>/app && php artisan schedule:run >> /dev/null 2>&1

# Queue worker — drains queued jobs (emails, Shiprocket pushes) each minute
* * * * * cd ~/domains/<YOUR_DOMAIN>/app && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

## 8. Folder permissions
```bash
chmod -R 775 storage bootstrap/cache
```

## 9. Verify
- Visit `https://<YOUR_DOMAIN>/` → storefront loads.
- `https://<YOUR_DOMAIN>/health` → all checks `ok`.
- `https://<YOUR_DOMAIN>/admin` → admin login (seeded: `admin@example.com` / `password` — **change it immediately**).

---

## Redeploying after code changes
A `git push` auto-pulls via the webhook. If a release changes dependencies, migrations, or config, SSH in and run:

```bash
cd ~/domains/<YOUR_DOMAIN>/app
composer install --no-dev --optimize-autoloader
php artisan migrate --path=database/migrations/tenant --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## Notes / limitations on Cloud Startup
- **No Redis / Meilisearch** — using database drivers. Fine for low/medium traffic; consider a VPS if search/cache load grows.
- **Frontend assets are pre-built and committed** (`public/build`), so the server doesn't need `npm`.
  To change the design, run `npm run build` locally and commit the result.
- **Queue is cron-driven** (1-min cadence), not instant. Order emails/Shiprocket pushes may lag up to ~1 minute.
