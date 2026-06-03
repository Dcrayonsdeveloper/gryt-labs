# Jikra Multi-Tenant Architecture

> One codebase → multiple stores → separate databases → full isolation

## Quick Start

### Create a New Store (30 seconds)

```php
use App\Models\Tenant;

$tenant = Tenant::create([
    'id' => 'mystore',           // Unique slug (used as DB prefix: tenant_mystore)
    'name' => 'My Store',
    'is_active' => true,
    'plan' => 'standard',        // free | standard | premium | enterprise
    // All below go into `data` JSON column automatically:
    'brand_name' => 'My Store',
    'support_email' => 'support@mystore.in',
    'support_phone' => '+919876543210',
    'razorpay_key_id' => 'rzp_live_xxxxx',
    'razorpay_key_secret' => 'xxxxx',
    'delhivery_api_token' => 'xxxxx',
]);

// Add domain(s)
$tenant->domains()->create(['domain' => 'mystore.in']);
$tenant->domains()->create(['domain' => 'www.mystore.in']);

// Done! Database `tenant_mystore` auto-created with 120 tables.
```

### Via Super Admin Panel

1. Go to `admin.jikra.in/super-admin/login`
2. Login with super admin credentials
3. Click "New Tenant" → fill form → submit
4. Store is live immediately

### Delete a Store

```php
$tenant = Tenant::find('mystore');
$tenant->delete(); // Auto-drops database + removes domain records
```

## Architecture Overview

```
┌─────────────────────────────────────────────────────┐
│                    NGINX / Server                    │
│                                                      │
│  Request: mystore.in/products                        │
│      ↓                                               │
│  InitializeTenancyByDomain middleware                │
│      ↓                                               │
│  Looks up 'mystore.in' in central.domains table      │
│      ↓                                               │
│  Finds tenant_id = 'mystore'                         │
│      ↓                                               │
│  Switches DB connection to 'tenant_mystore'          │
│      ↓                                               │
│  All queries now hit tenant_mystore database          │
│      ↓                                               │
│  Same controllers, views, services — different data   │
└─────────────────────────────────────────────────────┘
```

## Databases

| Database | Purpose | Tables |
|----------|---------|--------|
| `jikra_central` | Tenant management (central) | tenants, domains, super_admins |
| `jikra` | Jikra store (tenant #1) | 120 tables (products, orders, users, etc.) |
| `tenant_mystore` | New store (auto-created) | 120 tables (same schema) |
| `tenant_store3` | Another store (auto-created) | 120 tables (same schema) |

## Key Files

| File | Purpose |
|------|---------|
| `config/tenancy.php` | Tenancy configuration (central connection, bootstrappers, prefix) |
| `config/database.php` | Database connections (`mysql` + `central`) |
| `config/auth.php` | Auth guards (includes `super_admin` guard) |
| `app/Models/Tenant.php` | Tenant model with string IDs, custom columns, data JSON |
| `app/Models/SuperAdmin.php` | Super admin model (uses `central` connection) |
| `app/Providers/TenancyServiceProvider.php` | Events, routes, middleware priority |
| `app/Tenancy/ConfigBootstrapper.php` | Per-tenant config overrides (Razorpay, branding, mail) |
| `routes/tenant.php` | All store routes with tenancy middleware |
| `routes/central.php` | Super admin panel routes |
| `database/migrations/tenant/` | 96 migrations auto-run for new tenants |
| `bootstrap/providers.php` | Registers TenancyServiceProvider |

## Route Structure

### Tenant Routes (every store)
All existing routes run inside tenant context:

```
routes/tenant.php
├── routes/web.php          → Storefront (home, products, cart, checkout, auth)
├── routes/admin.php        → Tenant admin panel (/admin/*)
├── routes/seller.php       → Seller dashboard (/seller/*)
├── routes/delivery.php     → Delivery partner (/delivery/*)
├── routes/pos.php          → Point of sale (/pos/*)
├── routes/affiliate.php    → Affiliate system (/affiliate/*)
└── routes/api.php          → API endpoints (/api/*)
```

### Central Routes (platform admin only)
```
routes/central.php
└── /super-admin/
    ├── login               → Super admin authentication
    ├── /                   → Dashboard (cross-tenant stats)
    ├── tenants             → CRUD tenants
    ├── tenants/{id}/toggle → Activate/deactivate
    └── tenants/{id}/stats  → Per-tenant analytics (JSON)
```

## Tenant Data Model

The `tenants` table has a `data` JSON column that stores all tenant-specific config:

```json
{
    "tenancy_db_name": "tenant_mystore",
    "brand_name": "My Store",
    "support_email": "support@mystore.in",
    "support_phone": "+919876543210",
    "razorpay_key_id": "rzp_live_xxxxx",
    "razorpay_key_secret": "xxxxx",
    "delhivery_api_token": "xxxxx",
    "delhivery_pickup_location": "My Warehouse",
    "whatsapp_phone_id": "xxxxx",
    "whatsapp_token": "xxxxx",
    "instagram_access_token": "xxxxx",
    "facebook_page_id": "xxxxx",
    "logo_url": "/images/mystore-logo.png",
    "favicon_url": "/images/mystore-favicon.ico",
    "primary_color": "#205258",
    "secondary_color": "#F8931D",
    "gst_number": "GSTIN12345",
    "legal_name": "My Store Pvt Ltd",
    "business_address": "123 Main St"
}
```

Access in code: `$tenant->brand_name`, `$tenant->razorpay_key_id`, etc.

Custom columns (stored directly on table): `id`, `name`, `is_active`, `plan`, `created_at`, `updated_at`

## How Isolation Works

### Database Isolation
- Each tenant gets a **physically separate MySQL database**
- The `DatabaseTenancyBootstrapper` switches the default connection
- ALL models automatically query the correct tenant database
- **No `tenant_id` columns needed** — isolation is at the connection level

### Cache Isolation
- `CacheTenancyBootstrapper` adds tenant-specific tags to all cache keys
- `Cache::get('settings.all')` becomes `tenant_jikra_settings.all` internally
- `Setting::get()` (107 usages) works automatically per tenant

### Queue Isolation
- `QueueTenancyBootstrapper` serializes tenant ID with each job
- When a job runs, it re-initializes tenancy for that tenant
- Each tenant's jobs are processed in their correct database context

### Filesystem
- `asset_helper_tenancy = false` — shared assets (logo, CSS, JS) are global
- `suffix_storage_path = false` — product images use direct URLs/CDN
- For tenant-specific uploads: use `tenant_asset()` helper

### Search (Meilisearch)
- `Product::searchableAs()` returns `{tenant_id}_products`
- Each tenant gets isolated search indices

## Config Bootstrapper

`app/Tenancy/ConfigBootstrapper.php` overrides Laravel config when tenancy initializes:

| Tenant Data Key | Laravel Config Key |
|----------------|-------------------|
| `brand_name` | `app.name`, `mail.from.name` |
| `support_email` | `mail.from.address` |
| `razorpay_key_id` | `services.razorpay.key` |
| `razorpay_key_secret` | `services.razorpay.secret` |
| Primary domain | `app.url` |

All config is **automatically reverted** when tenancy ends (central context restores).

## MySQL Permissions

The `jikra` MySQL user has:
- `ALL PRIVILEGES ON jikra.*` (existing Jikra store)
- `ALL PRIVILEGES ON jikra_central.*` (central database)
- `ALL PRIVILEGES ON tenant_%.*` (all tenant databases)
- `CREATE, DROP ON *.*` (auto-provisioning new tenant DBs)

Granted via `/tmp/grant_perms.sql` using `debian-sys-maint` user.

## Scheduled Commands

For commands that need to run per-tenant (reviews drip, tracking sync, etc.):

```bash
# Run for ALL tenants
php artisan tenants:run reviews:drip-daily

# Run for specific tenant
php artisan tenants:run reviews:drip-daily --tenants=jikra
```

In `routes/console.php` scheduler:
```php
Schedule::command('tenants:run reviews:drip-daily')->daily();
Schedule::command('tenants:run delhivery:sync-tracking')->everyThirtyMinutes();
```

## Environment Variables

Add to `.env`:
```
CENTRAL_DB_DATABASE=jikra_central
TENANCY_CENTRAL_CONNECTION=central
```

## DNS Setup for New Tenants

1. Point the tenant's domain to the server IP: `A record → 15.207.133.144`
2. Add SSL certificate: `sudo certbot --nginx -d mystore.in -d www.mystore.in`
3. Add Nginx server block (or use wildcard config)

## Troubleshooting

### "Tenant could not be identified"
- Domain not in `domains` table
- Fix: `$tenant->domains()->create(['domain' => 'mystore.in']);`

### "Table already exists" during migration
- Duplicate migration in `database/migrations/tenant/`
- Fix: Remove the duplicate file

### Assets loading with `/tenancy/assets/` prefix
- `asset_helper_tenancy` is `true` in config
- Fix: Set to `false` in `config/tenancy.php` (we use shared global assets)

### Tenant DB not created
- MySQL user lacks CREATE privilege
- Fix: `GRANT CREATE, DROP ON *.* TO 'jikra'@'localhost';`

### Config not tenant-specific
- Value read from `.env` via `config()` instead of `Setting::get()`
- Fix: Use `Setting::get()` for tenant-specific values, or add mapping in `ConfigBootstrapper`
