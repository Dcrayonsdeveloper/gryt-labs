# New Tenant Creation Runbook — Dcrayons Multi-Tenant Laravel Platform

**Owner:** Aditya (Tech Lead) · **Deploy owner:** Karan · **Sign-off:** Diksha
**Stack:** Laravel 12 · `stancl/tenancy v3.10` · PostgreSQL · Redis · Meilisearch · Nginx · AWS Lightsail
**Production host:** `15.207.133.144` (referred to below as `prod`)
**SSH alias used in this runbook:**

```bash
# Add to ~/.ssh/config once, or just expand inline
alias prod='ssh -i "C:/Users/Rahul yadav/Downloads/Dcrayons.pem" ubuntu@15.207.133.144'
# Wherever you see `ssh prod '...'` below, that means:
# ssh -i "C:/Users/Rahul yadav/Downloads/Dcrayons.pem" ubuntu@15.207.133.144 '...'
```

**Related runbooks (read these alongside this one):**
- `.ai-memory/reference_deployment.md` — production paths, deploy commands, php-fpm restart
- `docs/multi-tenant/AI-CONTEXT.md` — multi-tenancy architecture (READ FIRST if you've never touched the platform)
- `docs/multi-tenant/ARCHITECTURE.md` — request flow, tenant resolver, DB switching
- `docs/multi-tenant/TENANT-CHECKLIST.md` — older per-tenant checklist (this runbook supersedes for the provisioning flow)
- `docs/tenant-guide/COMPLETE-GUIDE.md` — tenant operational guide (post-launch)
- `docs/WILDCARD-SUBDOMAIN.md` — how `*.dcrayons.app` routing works
- `docs/WILDCARD-SSL-CREDENTIALS.md` — wildcard cert location + renewal
- `docs/tenant-launch-checklist.md` — Diksha's QA checklist (Phase 8)

**Workflow rule reminder:** Code/migration/config changes follow the Git pipeline (`feature/* → PR → CI → review → Diksha approves → Karan deploys`). Direct prod edits are limited to the bypasses listed in `.ai-memory/team/README.md` Section 4c. **Never push to `main` directly.**

---

## Phase 1 — Decide Tenant Identity (Aarav + Diksha)

Before touching any infra, lock down four values. Write them at the top of the launch decision file (`.ai-memory/decisions/tenant-launch-{name}-{date}.md`):

| Value | Rule | Example |
|-------|------|---------|
| `tenant_id` (slug) | Lowercase alphanumeric + underscores only. No dashes (Postgres-friendly). Becomes the central `tenants.id` row, the DB name, and the cache prefix. **Cannot be renamed later without a full migration.** | `mybrand` |
| `domain` | One of: (a) custom apex `mybrand.com` (recommended for paid/branded tenants), or (b) wildcard subdomain `mybrand.dcrayons.app` (free, instant). Both work — wildcard is faster to launch. | `mybrand.com` or `mybrand.dcrayons.app` |
| Database name | **Must equal** `tenant_id`. The tenant resolver computes the DB name from the slug. Tenant #1 (Jikra) uses legacy name `jikra`; new tenants use `{tenant_id}`. | `mybrand` |
| Frontend theme | Copy of one of `frontends/default`, `frontends/ayurvexa`, `frontends/getsetnova`, `frontends/natually`. **Default starter is `frontends/default/`.** | `frontends/mybrand/` |

**Validation checklist before moving to Phase 2:**
- [ ] Slug is unique — `SELECT id FROM tenants WHERE id = '{tenant_id}'` returns 0 rows on `jikra_central`
- [ ] Domain ownership confirmed (registrar access for custom; nothing for wildcard)
- [ ] Brand assets ready (logo, favicon, primary colors hex codes)
- [ ] Tenant decision logged in `.ai-memory/decisions/`

---

## Phase 2 — Provision Infrastructure (Karan)

Karan owns this phase. All commands run against `prod`.

### 2.1 Create the PostgreSQL database

The DB owner is always `jikra_pg` (the shared app role — the multi-tenant connection uses its credentials and switches the `database` at runtime).

```bash
ssh prod 'sudo -u postgres psql -c "CREATE DATABASE {tenant_id} OWNER jikra_pg;"'

# Verify
ssh prod 'sudo -u postgres psql -l | grep {tenant_id}'
```

### 2.2 Run tenant migrations

This creates every `database/migrations/tenant/*` table in the new DB. Forgetting this is the #1 cause of "white screen on first request".

```bash
ssh prod 'cd /var/www/jikra && sudo -u www-data php artisan tenants:migrate --tenants={tenant_id} --force'
```

Expect ~80+ migrations. Watch for any `ERROR` lines — if one migration fails, the DB is left in a partial state. Fix the migration locally, push via Git, redeploy, then re-run only the failing migration with `--path=...`.

### 2.3 DNS

**Wildcard subdomain (`*.dcrayons.app`):** nothing to do. The wildcard A-record already routes every subdomain to `15.207.133.144`. Skip to 2.4.

**Custom domain:** at the customer's registrar (GoDaddy / Namecheap / Cloudflare), add:

```
Type: A     Name: @       Value: 15.207.133.144   TTL: 300
Type: A     Name: www     Value: 15.207.133.144   TTL: 300
```

Verify propagation before moving on:

```bash
dig +short mybrand.com    # must return 15.207.133.144
dig +short www.mybrand.com
```

### 2.4 SSL

**Wildcard:** already covered by the `*.dcrayons.app` Let's Encrypt cert. See `docs/WILDCARD-SSL-CREDENTIALS.md` for renewal cron details. Skip to 2.5.

**Custom domain:** issue a certificate via certbot (DNS must be propagated first):

```bash
ssh prod 'sudo certbot --nginx -d mybrand.com -d www.mybrand.com'
```

Certbot will edit the Nginx config in-place and add the redirect from HTTP → HTTPS. Auto-renewal cron (`/etc/cron.d/certbot`) is already installed.

### 2.5 Nginx

**Wildcard:** nothing — the existing `*.dcrayons.app` server block routes every subdomain to the same Laravel index. Skip to Phase 3.

**Custom domain:** create a server block by copying the canonical template:

```bash
ssh prod '
  sudo cp /etc/nginx/sites-available/jikra.in /etc/nginx/sites-available/mybrand.com
  sudo sed -i "s/jikra\.in/mybrand.com/g" /etc/nginx/sites-available/mybrand.com
  sudo ln -s /etc/nginx/sites-available/mybrand.com /etc/nginx/sites-enabled/mybrand.com
  sudo nginx -t && sudo systemctl reload nginx
'
```

If `nginx -t` fails, **do not reload** — fix the config first. The previous server block is still serving traffic.

---

## Phase 3 — Register Tenant in Central DB (Aditya)

Two rows, two tables, both in `jikra_central`. The `Tenant::create()` call also fires the `TenantCreated` event which (depending on listener config) can auto-run migrations — but we already did that in Phase 2 explicitly to control timing.

**Option A — Super-admin UI (preferred):**
Navigate to `/super-admin/tenants/create` while logged in as a super-admin. Fill: tenant slug, primary domain. The form writes both rows in a single transaction.

**Option B — Tinker / seeder (fallback):**

```bash
ssh prod 'cd /var/www/jikra && sudo -u www-data php artisan tinker'
```

```php
use Stancl\Tenancy\Database\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;

Tenant::create([
    'id'   => 'mybrand',
    'data' => ['domain' => 'mybrand.com'],
]);

Domain::create([
    'tenant_id' => 'mybrand',
    'domain'    => 'mybrand.com',
]);

// For wildcard tenants, register the subdomain instead:
// Domain::create(['tenant_id' => 'mybrand', 'domain' => 'mybrand.dcrayons.app']);
```

**Verify:**

```bash
ssh prod 'cd /var/www/jikra && sudo -u www-data php artisan tinker --execute="
  echo \App\Models\Tenant::find(\"mybrand\")?->id . PHP_EOL;
  echo \Stancl\Tenancy\Database\Models\Domain::where(\"tenant_id\", \"mybrand\")->value(\"domain\") . PHP_EOL;
"'
```

---

## Phase 4 — Frontend Setup (Riya)

Themes live in `frontends/{tenant_id}/`. Each theme has its own CSS bundle, JS bundle, and (optionally) Blade overrides under `resources/views/themes/{tenant_id}/`.

### 4.1 Copy starter theme

```bash
cp -r frontends/default frontends/mybrand
```

### 4.2 Brand the CSS

Edit `frontends/mybrand/css/app.css` — the `@theme` block at the top defines the design tokens (Tailwind v4 syntax):

```css
@theme {
  --color-primary: #ff5733;        /* brand primary */
  --color-primary-dark: #cc4628;
  --color-accent: #ffd23f;
  --color-bg: #ffffff;
  --color-text: #1a1a1a;
  --font-display: "Inter", sans-serif;
}
```

Also touch `frontends/mybrand/css/home.css` for any hero / above-the-fold overrides.

### 4.3 Register Vite entries

Edit `vite.config.js`. Find the `input: [...]` array and append three lines:

```js
input: [
  // ... existing entries ...
  'frontends/mybrand/css/app.css',
  'frontends/mybrand/css/home.css',
  'frontends/mybrand/js/app.js',
],
```

### 4.4 Build

```bash
npm run build
```

Successful build writes hashed assets to `public/build/assets/` and updates `public/build/manifest.json`.

### 4.5 Verify the manifest

```bash
grep -E "frontends/mybrand/(css|js)" public/build/manifest.json
```

Must return three matches. If any is missing, the entry didn't make it into `vite.config.js` — re-check, rebuild.

### 4.6 Commit + ship via Git

This is a code change → goes through the standard pipeline:

```bash
git checkout -b feature/jikra-tenant-mybrand-frontend-2026-04-21
git add frontends/mybrand vite.config.js public/build
git commit -m "[tenant] Add mybrand frontend theme + Vite entries"
git push origin feature/jikra-tenant-mybrand-frontend-2026-04-21
# Open PR → CI → review → Diksha merges → Karan deploys
```

---

## Phase 5 — Tenant Settings Seed (Aarav)

Once the tenant DB exists and is migrated, populate the `settings` table. Two paths:

**Path A — Admin UI:** log in to `https://{domain}/admin` as the seeded super-admin and fill the Settings page section by section.

**Path B — Seeder (faster for bulk launches):** create a one-shot artisan tinker block. Run inside the tenant context:

```bash
ssh prod 'cd /var/www/jikra && sudo -u www-data php artisan tenants:run --tenants=mybrand "tinker --execute=\"
  \App\Models\Setting::set(\"store_name\", \"My Brand\");
  \App\Models\Setting::set(\"store_email\", \"hello@mybrand.com\");
  // ... etc
\""'
```

### Required keys (none of these have safe Jikra-specific defaults — `Setting::get()` is fail-closed and returns `''` if missing)

| Key | Default | Notes |
|-----|---------|-------|
| `store_name` | — | Brand display name. Used in emails, page titles, schema.org. |
| `store_email` | — | From address fallback + customer support |
| `store_phone` | — | Shown in footer, contact page, WhatsApp link |
| `store_address` | — | GST invoice, footer, schema.org `LocalBusiness` |
| `currency` | `INR` | ISO code |
| `currency_symbol` | `₹` | Display symbol |
| `cod_advance_amount` | **`0`** | `0` = direct COD (no advance). `>0` = partial COD (customer pays this much now). See Phase 6. |
| `cod_minimum_amount` | **`199`** | Cart total below this → COD option hidden in checkout |
| `flat_rate_amount` | — | Default shipping fee per order |
| `free_shipping_threshold` | — | Cart total at which shipping becomes free |
| `low_stock_threshold` | **`10`** | Triggers low-stock badge + admin alerts |
| `ga4_measurement_id` | — | Format `G-XXXXXXXXXX` |
| `meta_pixel_id` | — | 15-digit numeric |
| `meta_capi_token` | — | Conversions API token from Meta Events Manager |
| `razorpay_key_id` | — | `rzp_live_*` (or `rzp_test_*` for sandbox) |
| `razorpay_key_secret` | — | Matching secret |
| `razorpay_webhook_secret` | — | Set in Razorpay dashboard → Webhooks → matches signature header verification in `WebhookController` |
| `email_from_address` | — | e.g. `orders@mybrand.com` (must be SES-verified or SPF-aligned) |
| `email_signature` | — | HTML or plain block appended to all transactional emails |

**Validation:**

```bash
ssh prod 'cd /var/www/jikra && sudo -u www-data php artisan tenants:run --tenants=mybrand "tinker --execute=\"
  foreach ([\"store_name\",\"currency\",\"cod_advance_amount\",\"cod_minimum_amount\",\"flat_rate_amount\",\"razorpay_key_id\"] as \$k) {
    echo \$k . \" => \" . (\App\Models\Setting::get(\$k) ?: \"!!! MISSING !!!\") . PHP_EOL;
  }
\""'
```

Any `!!! MISSING !!!` line is a launch blocker.

---

## Phase 6 — Payment Gateway Defaults (Anaya + Aryan)

This phase is where most launches silently break weeks later. Document each tenant's choice **explicitly** in the launch decision file.

### 6.1 COD behaviour matrix

| `cod_advance_amount` | `cod_minimum_amount` | Customer experience |
|----------------------|----------------------|---------------------|
| **`0`** (default) | `199` | Direct COD only. No advance. Razorpay is **not** invoked on the COD path. COD button shown only if cart ≥ ₹199. |
| `> 0` (e.g. `100`) | `199` | Partial COD. Customer pays ₹100 now via Razorpay/Cashfree, balance on delivery. Razorpay credentials **must** be set or COD silently 500s. |
| any | `0` | COD always available regardless of cart total. Use only for low-AOV brands. |
| any | very high (e.g. `999`) | COD hidden for small carts. Forces prepaid for low-value orders → reduces RTO. |

**Default for new tenants: `cod_advance_amount = 0`, `cod_minimum_amount = 199`.** This is the safest setup — no payment-gateway dependency on the COD path.

### 6.2 Razorpay webhook setup

1. Log in to Razorpay dashboard → Settings → Webhooks → **+ Add New Webhook**
2. URL: `https://{domain}/webhook/razorpay`
3. Secret: paste the same value you used for the `razorpay_webhook_secret` Setting key in Phase 5. **They must match exactly** — mismatched secret = signature verification fails = silent failed payments.
4. Subscribe to events: `payment.captured`, `payment.failed`, `order.paid`, `refund.processed`
5. Save → click "Send test webhook" → verify in Laravel log:

```bash
ssh prod 'sudo tail -f /var/www/jikra/storage/logs/laravel.log' | grep -i razorpay
```

You should see `Razorpay webhook signature verified` followed by event handling logs. If you see `Invalid signature` — the secret is wrong (most common cause: extra whitespace pasted into the Setting).

### 6.3 Test transaction (live mode, refund immediately)

Run a real ₹1 order through the new tenant's checkout in live mode → verify in Razorpay dashboard → refund. This proves: gateway keys correct, webhook reachable, order state machine transitions to `paid`.

---

## Phase 7 — Cache + Verify (Karan)

After Phases 2–6 are complete, the tenant exists but the running php-fpm processes have stale cached config / views.

### 7.1 Clear caches and restart php-fpm

```bash
ssh prod '
  cd /var/www/jikra
  sudo -u www-data php artisan view:clear
  sudo -u www-data php artisan cache:clear
  sudo -u www-data php artisan config:clear
  sudo -u www-data php artisan route:clear
  sudo systemctl restart php8.3-fpm
'
```

> Note: do **not** `redis-cli FLUSHALL` — that would nuke every other tenant's cache. The deploy pipeline already uses tenant-scoped scan-delete (see README Section 11d).

### 7.2 Health check

```bash
curl -fsS https://{domain}/health
# Expect: {"status":"ok","tenant":"mybrand","db":"ok","cache":"ok","time":"..."}
```

If `db` is `error` → DB doesn't exist or migrations didn't run (Phase 2). If `tenant` is `null` → Domain row missing (Phase 3).

### 7.3 Smoke test

```bash
ssh prod 'cd /var/www/jikra && bash scripts/smoke-test-all-tenants.sh'
```

This runs ~20 HTTP checks per tenant: home, category, product, cart, checkout init, search, sitemap, robots, /admin login page, etc. Any FAIL is a launch blocker.

### 7.4 Manual eyeball pass

Open in a real browser:
- `https://{domain}/` — hero loads, theme colors correct, no 404s in DevTools network
- `https://{domain}/products` — product listing renders
- `https://{domain}/cart` — empty state renders
- `https://{domain}/admin` — login form renders, can log in

---

## Phase 8 — Sign-off (Diksha)

Diksha runs the launch QA checklist (`docs/tenant-launch-checklist.md`) and only then announces the tenant as live.

**Diksha's gate items:**
- [ ] All Phase 1–7 boxes ticked
- [ ] Real ₹1 transaction completed and refunded (Phase 6.3)
- [ ] Smoke test green (Phase 7.3)
- [ ] Mobile rendering verified on iOS Safari + Android Chrome
- [ ] GA4 receiving page_view events (Mira confirms)
- [ ] Meta Pixel firing (verified via Meta Pixel Helper Chrome extension)
- [ ] Email transactional smoke: place order → receive order_confirmed email
- [ ] WhatsApp template smoke (if WhatsApp wired): order_confirmed template delivers
- [ ] Backup runs at least once: `php artisan backup:database --tenant=mybrand --tag=launch`

**Documentation deliverables:**
- [ ] `.ai-memory/decisions/tenant-launch-{name}-{YYYY-MM-DD}.md` written, including: chosen identity (Phase 1), payment configuration (Phase 6), any deviations from the default
- [ ] Tenant added to `docs/multi-tenant/TENANT-CHECKLIST.md` register
- [ ] Karan adds the new domain to `scripts/smoke-test-all-tenants.sh` so it's checked on every future deploy
- [ ] Mira adds the tenant to the GA4 dashboard / weekly KPI report

Once all boxes are ticked, Diksha posts the launch announcement and the tenant is officially live.

---

## Common Pitfalls (Real Gotchas — Read Before Provisioning)

1. **Forgot `php artisan tenants:migrate`** — the DB exists but is empty. First request 500s with `relation "products" does not exist`. Always run Phase 2.2 immediately after 2.1; never assume the `TenantCreated` event listener handled it.

2. **`razorpay_webhook_secret` mismatch** — copy-paste from Razorpay dashboard often grabs trailing whitespace. The signature verification then fails for every webhook, so successful payments never transition orders to `paid`. Customers complain "I paid but order says pending". Fix: set the Setting via tinker (not the admin UI textarea) and re-test with a webhook from Razorpay dashboard. Tail the log during the test (Phase 6.2).

3. **Vite build forgotten or manifest not deployed** — theme CSS 404s in browser, page renders unstyled (FOUC then nothing). Symptom: `https://{domain}/build/assets/app-*.css 404`. Cause: either `npm run build` wasn't run, or `public/build/` wasn't part of the deploy. Always grep manifest after build (Phase 4.5) and confirm `public/build/manifest.json` is in the deployed commit.

4. **Wildcard tenant uses dot-separated DB name** — slugs with dots (`my.brand.dcrayons.app` typed into `tenant_id`) break Postgres. Slug = `mybrand` only; the `.dcrayons.app` portion goes into the `domains` row, never into `tenant_id` or DB name.

5. **`cod_advance_amount` left blank vs. set to `0`** — `Setting::get('cod_advance_amount')` returns `''` (empty string) when missing, which casts to `0` in PHP — usually fine, but the admin "Edit COD" UI shows it as blank, leading staff to think it's unconfigured and guess values like `100`. Always explicitly seed `0` (not blank) so intent is unambiguous.

6. **Custom domain SSL issued before DNS propagated** — certbot's HTTP-01 challenge fails with `Failed authorization procedure` if DNS hasn't reached Let's Encrypt's resolver yet. Fix: `dig +short mybrand.com` from the prod box must return `15.207.133.144` BEFORE running certbot. Wait 5 minutes after the registrar change; don't retry certbot in a tight loop or you'll hit the Let's Encrypt rate limit (5 failures/hour/domain).

7. **php-fpm not restarted after config changes** — Laravel's `config:cache` (run by deploy pipeline) bakes config into `bootstrap/cache/config.php`. New tenant Settings live in the DB, not the config file, so they don't need a restart — BUT if you edit `.env` (rare for tenant work) or push a code change, opcache will hold the old code until php-fpm restarts. Phase 7.1 is non-negotiable.

8. **Forgetting to add the new domain to `smoke-test-all-tenants.sh`** — every future deploy's safety net is the smoke test. If the new tenant isn't in the script, regressions on its routes won't trigger auto-rollback. Karan must edit the script (Phase 8 deliverable) before the next deploy goes out.

9. **Domain row missing in central DB** — request hits the box, Nginx routes to Laravel, tenancy resolver looks up the host in `domains` table, finds nothing, throws `TenantCouldNotBeIdentifiedOnDomainException` → 404 Tenant Not Found. Symptom: health check returns `tenant: null`. Fix: re-do Phase 3.

10. **Themes overriding shared `_core` accidentally** — `frontends/_core/` contains shared partials. New themes must extend, not duplicate. If you `cp -r _core` into `mybrand`, future shared-component fixes won't propagate. Only copy from `frontends/default/` (which is the reference theme) — it already references `_core` correctly.

---

## Quick Reference — Full Command Sequence (Wildcard Tenant, Happy Path)

```bash
# Phase 2 — Karan
ssh prod 'sudo -u postgres psql -c "CREATE DATABASE mybrand OWNER jikra_pg;"'
ssh prod 'cd /var/www/jikra && sudo -u www-data php artisan tenants:migrate --tenants=mybrand --force'

# Phase 3 — Aditya (run on prod)
ssh prod 'cd /var/www/jikra && sudo -u www-data php artisan tinker --execute="
  \Stancl\Tenancy\Database\Models\Tenant::create([\"id\"=>\"mybrand\",\"data\"=>[\"domain\"=>\"mybrand.dcrayons.app\"]]);
  \Stancl\Tenancy\Database\Models\Domain::create([\"tenant_id\"=>\"mybrand\",\"domain\"=>\"mybrand.dcrayons.app\"]);
"'

# Phase 4 — Riya (local, then PR)
cp -r frontends/default frontends/mybrand
# edit css/app.css + vite.config.js
npm run build
git checkout -b feature/jikra-tenant-mybrand-2026-04-21
git add frontends/mybrand vite.config.js public/build
git commit -m "[tenant] Add mybrand theme"
git push -u origin feature/jikra-tenant-mybrand-2026-04-21
# Open PR → CI → Diksha merges → Karan deploys

# Phase 5–6 — Aarav + Aryan (settings seed via admin UI or tinker)

# Phase 7 — Karan
ssh prod 'cd /var/www/jikra && sudo -u www-data php artisan view:clear && sudo -u www-data php artisan cache:clear && sudo systemctl restart php8.3-fpm'
curl -fsS https://mybrand.dcrayons.app/health
ssh prod 'cd /var/www/jikra && bash scripts/smoke-test-all-tenants.sh'

# Phase 8 — Diksha signs off, decision file logged
```

---

**Last updated:** 2026-04-21 · **Maintainer:** Aditya (`aditya_jikra`) · **Review cadence:** every 6 months or after any change to the tenancy package
