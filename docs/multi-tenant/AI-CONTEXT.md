# AI Context — Multi-Tenant Development Guide

> Read this before making ANY changes to the Jikra codebase.

## Critical Rules

### 1. This is a Multi-Tenant App
Every request on `jikra.in`, `store2.in`, etc. runs inside tenant context. The database connection is automatically switched by `InitializeTenancyByDomain` middleware.

### 2. NEVER Hardcode Database Names
```php
// WRONG
DB::connection('mysql')->table('products')...

// RIGHT — uses current tenant's database automatically
Product::all()
DB::table('products')...
```

### 3. Central vs Tenant Context
```php
// Tenant context (inside any route on jikra.in, store2.in, etc.)
$products = Product::all(); // Queries tenant's database

// Central context (inside super admin routes or artisan commands)
$tenants = Tenant::all(); // Queries jikra_central database

// Switching context manually
tenancy()->initialize($tenant);
// ... now all queries go to this tenant's DB
tenancy()->end();
// ... back to central
```

### 4. Setting::get() is Already Tenant-Aware
Each tenant has their own `settings` table in their own database. The 107 existing `Setting::get()` calls automatically return the correct tenant's value. **Do NOT change this pattern.**

### 5. Adding New Features
When you add a new feature (controller, view, service):
- It automatically works for ALL tenants
- No `tenant_id` filtering needed
- Just write normal Laravel code
- The database connection handles isolation

### 6. Adding New Database Tables
```bash
# Create migration in tenant directory
php artisan make:migration create_new_table --path=database/migrations/tenant

# Run for all existing tenants
php artisan tenants:run migrate

# New tenants get it automatically on creation
```

### 7. Artisan Commands for Tenants
```bash
# Run command for ALL tenants
php artisan tenants:run reviews:drip-daily

# Run for specific tenant
php artisan tenants:run reviews:drip-daily --tenants=jikra

# In scheduler (routes/console.php)
Schedule::command('tenants:run delhivery:sync-tracking')->everyThirtyMinutes();
```

### 8. Assets are Global
- `asset('images/logo.png')` → shared across all tenants
- CSS/JS builds are shared
- `asset_helper_tenancy = false` in config
- For tenant-specific files (product images), use direct URLs stored in DB

### 9. Testing Changes
```bash
# Always test the site still works after changes
curl -s -o /dev/null -w "%{http_code}" https://jikra.in/
curl -s -o /dev/null -w "%{http_code}" https://jikra.in/products
curl -s -o /dev/null -w "%{http_code}" https://jikra.in/login

# Check no errors in log
tail -5 /var/www/jikra/storage/logs/laravel.log | grep ERROR
```

### 10. Config That Varies Per Tenant
Use `Setting::get()` for business config (stored in tenant DB):
```php
Setting::get('store_name')
Setting::get('razorpay_key_id')
Setting::get('free_shipping_threshold')
```

Use tenant data for platform-level config (stored in central DB):
```php
tenant('brand_name')
tenant('razorpay_key_id')
```

The `ConfigBootstrapper` maps tenant data → Laravel config at request start.

## File Locations

```
config/tenancy.php                              ← Tenancy config
config/database.php                             ← Central + mysql connections
config/auth.php                                 ← super_admin guard
app/Models/Tenant.php                           ← Tenant model
app/Models/SuperAdmin.php                       ← Super admin model
app/Tenancy/ConfigBootstrapper.php              ← Per-tenant config overrides
app/Providers/TenancyServiceProvider.php        ← Events + route mapping
app/Http/Controllers/SuperAdmin/                ← Super admin controllers
routes/tenant.php                               ← All store routes (with middleware)
routes/central.php                              ← Super admin routes
database/migrations/tenant/                     ← Tenant migrations (96 files)
database/migrations/2019_09_15_*                ← Central migrations
resources/views/super-admin/                    ← Super admin views
docs/multi-tenant/                              ← This documentation
```

## Common Mistakes to Avoid

| Mistake | Why It Breaks | Fix |
|---------|--------------|-----|
| Adding migration to `database/migrations/` | Won't run for new tenants | Put in `database/migrations/tenant/` |
| Using `DB::connection('mysql')` directly | Bypasses tenant context | Use `DB::table()` or Eloquent |
| Caching without tenant context | Data leaks between tenants | Cache bootstrapper handles this automatically |
| Running `php artisan migrate` | Only migrates central DB | Use `php artisan tenants:run migrate` |
| Querying `Tenant::all()` inside tenant route | Queries tenant DB (wrong table) | Use `DB::connection('central')` or run outside tenancy |
| Hardcoding `jikra` database name | Breaks for other tenants | Let the framework handle connections |
