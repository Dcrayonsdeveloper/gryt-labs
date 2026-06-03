# Multi-Tenant Architecture Reference

## System Diagram

```
                    ┌──────────────────────────────┐
                    │          NGINX               │
                    │   (SSL termination, routing)  │
                    └──────────┬───────────────────┘
                               │
              ┌────────────────┼────────────────┐
              │                │                │
     admin.jikra.in      jikra.in        store2.in
              │                │                │
              ▼                ▼                ▼
    ┌─────────────┐  ┌──────────────┐  ┌──────────────┐
    │   Central    │  │   Tenant     │  │   Tenant     │
    │   Routes     │  │   Routes     │  │   Routes     │
    │ (central.php)│  │ (tenant.php) │  │ (tenant.php) │
    └──────┬──────┘  └──────┬───────┘  └──────┬───────┘
           │                │                  │
           ▼                ▼                  ▼
    ┌─────────────┐  ┌──────────────┐  ┌──────────────┐
    │  jikra_     │  │   jikra      │  │  tenant_     │
    │  central    │  │ (tenant DB)  │  │  store2      │
    │             │  │              │  │              │
    │ - tenants   │  │ - products   │  │ - products   │
    │ - domains   │  │ - orders     │  │ - orders     │
    │ - super_    │  │ - users      │  │ - users      │
    │   admins    │  │ - settings   │  │ - settings   │
    │             │  │ - reviews    │  │ - reviews    │
    │             │  │ - 120 tables │  │ - 120 tables │
    └─────────────┘  └──────────────┘  └──────────────┘
```

## Request Lifecycle

```
1. HTTP Request arrives at NGINX
2. NGINX forwards to PHP-FPM
3. Laravel boots → TenancyServiceProvider registered
4. Request hits route → InitializeTenancyByDomain middleware fires
5. Middleware queries central.domains WHERE domain = request host
6. Finds tenant → TenancyInitialized event fires
7. Bootstrappers execute:
   a. DatabaseTenancyBootstrapper → switches DB connection
   b. CacheTenancyBootstrapper → prefixes cache keys
   c. FilesystemTenancyBootstrapper → scopes storage
   d. QueueTenancyBootstrapper → tags jobs with tenant
   e. ConfigBootstrapper → overrides app.name, Razorpay keys, etc.
8. Controller executes → all DB queries go to tenant database
9. Response sent → TenancyEnded event fires
10. Bootstrappers revert to central context
```

## Data Flow

### What's Shared (one codebase)
- PHP controllers, services, middleware
- Blade views (templates)
- CSS/JS assets (Vite build)
- Logo & static images in `/public/images/`
- Vendor packages

### What's Isolated (per tenant database)
- Products, categories, brands
- Orders, carts, payments
- Users, admins, customers
- Settings (via Setting::get())
- Reviews, questions
- Coupons, promotions
- Blog posts
- Social media posts
- Support tickets
- Analytics data
- Audit logs

### What's in Tenant JSON Data (central DB)
- Razorpay credentials
- Delhivery API token
- WhatsApp tokens
- Instagram/Facebook tokens
- Branding (logo URL, colors)
- Business info (GST, legal name, address)

## Bootstrapper Details

### DatabaseTenancyBootstrapper
- Switches `config('database.default')` to tenant connection
- Tenant connection config: same as mysql but with tenant's database name
- Auto-reverts on request end

### CacheTenancyBootstrapper
- Tags all cache operations with `tenant_{id}`
- `Cache::get('key')` internally becomes scoped to current tenant
- Prevents cross-tenant cache pollution

### QueueTenancyBootstrapper
- Serializes `tenant_id` into job payload
- When worker picks up job, it initializes tenancy before processing
- Ensures queued jobs (emails, notifications) run in correct tenant context

### ConfigBootstrapper (Custom)
- Reads tenant's `data` JSON column
- Overrides: `app.name`, `app.url`, `mail.from.*`, `services.razorpay.*`
- Saves original config for revert

## Event Pipeline

When `Tenant::create()` is called:
```
CreatingTenant → (validation hooks)
TenantCreated → JobPipeline:
  1. CreateDatabase → creates MySQL database
  2. MigrateDatabase → runs all tenant migrations
  3. (SeedDatabase → optional, seeds defaults)
```

When `$tenant->delete()` is called:
```
DeletingTenant → (cleanup hooks)
TenantDeleted → JobPipeline:
  1. DeleteDatabase → drops MySQL database
```

## Security Considerations

1. **Database isolation** — physically separate databases, not row-level filtering
2. **No global scopes needed** — connection-level isolation eliminates data leak risk
3. **Central domain protection** — `PreventAccessFromCentralDomains` middleware blocks tenant routes on admin.jikra.in
4. **Super admin separate auth** — `super_admin` guard uses `super_admins` table in central DB, completely separate from tenant user tables
5. **MySQL user permissions** — `jikra` user can only access `jikra`, `jikra_central`, and `tenant_*` databases
6. **Config revert** — all overridden config values restored after each request
