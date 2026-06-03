# Tenant Launch QA Checklist

> **Spec'd by:** Aarav (`aarav_ba_jikra`) — Business Analyst
> **Approved by:** Diksha (`diksha`) — Account Head
> **Audience:** Every new merchant onboarding to the Dcrayons multi-tenant platform
> **Rule:** No tenant goes live until **every box** below is checked. No exceptions. Diksha signs off Section 9 in writing.

---

## Who Owns What

| Section | Lane | Owner Slug |
|---------|------|------------|
| 1. Pre-launch infrastructure | DevOps | `karan_jikra` |
| 2. SEO + structured data | SEO & Content | `tanvi_jikra` |
| 3. Tracking pixels + analytics | Analytics + Performance Mkt | `mira_jikra`, `rohit_jikra` |
| 4. Marketplace feeds | Marketplace & Feed | `vihaan_jikra` |
| 5. Payment & checkout | CRO + Finance | `anaya_jikra`, `aryan_jikra` |
| 6. Storefront content + branding | Frontend + Email & Retention | `riya_jikra`, `naina_jikra` |
| 7. Performance + SEO health | DevOps + SEO | `karan_jikra`, `tanvi_jikra` |
| 8. Security & compliance | Tech Lead | `aditya_jikra` |
| 9. Final go-live sign-off | Account Head | `diksha` |

---

## 1. Pre-launch Infrastructure (Karan — `karan_jikra`)

- [ ] **DNS A-record** points to `13.205.162.30` for the apex domain — verify via `dig +short {domain}` (must return the AWS Lightsail IP).
- [ ] **Wildcard SSL** provisioned and valid for `*.{domain}` and apex — verify with `curl -vI https://{domain} 2>&1 | grep -E "(SSL|expire)"` and confirm expiry > 30 days out.
- [ ] **Tenant central registration** complete — confirm row exists in `jikra_central.tenants` table with `id`, `domain`, and `data` JSON populated.
- [ ] **Tenant DB created + migrated** — run `php artisan tenants:migrate --tenants={tenant_id}` and confirm 0 pending migrations via `php artisan tenants:list`.
- [ ] **Tenant frontend folder created** at `frontends/{name}/{views,css,js}` — copied from `frontends/default/` as starting point. Confirm via `ls frontends/{name}/views/` returns non-empty.
- [ ] **Vite entries added** to `vite.config.js` for `frontends/{name}/css/app.css` and `frontends/{name}/js/app.js` — built via `npm run build` and `public/build/manifest.json` contains the new tenant's hashed assets.
- [ ] **Nginx config verified** — either custom server block at `/etc/nginx/sites-enabled/{tenant}.conf` or wildcard handler — test via `sudo nginx -t` (must return `syntax is ok`) and reload with `sudo systemctl reload nginx`.
- [ ] **Tenant resolves on browser** — visit `https://{domain}/` and confirm 200 response with the tenant's branding (not the default Jikra fallback).

---

## 2. SEO + Structured Data (Tanvi — `tanvi_jikra`)

- [ ] **Sitemap accessible** at `https://{domain}/sitemap.xml` and returns 200 with all active product/category URLs — verify via `curl -I https://{domain}/sitemap.xml`. Submit to Google Search Console under Sitemaps tab.
- [ ] **robots.txt present** at `https://{domain}/robots.txt` with `Sitemap:` directive pointing to the sitemap above — verify via `curl https://{domain}/robots.txt`.
- [ ] **JSON-LD `Organization` schema** on homepage — view source, find `<script type="application/ld+json">`, paste into [validator.schema.org](https://validator.schema.org/) — must return 0 errors.
- [ ] **JSON-LD `WebSite` schema** with `SearchAction` on homepage — same validation flow as above.
- [ ] **JSON-LD `Product` schema** on every product page with `name`, `image`, `sku`, `offers.price`, `offers.priceCurrency` populated — pick 3 random products and validate each.
- [ ] **JSON-LD `BreadcrumbList` schema** on category and product pages — validate via Schema.org validator.
- [ ] **JSON-LD `AggregateRating` schema** on product pages with reviews — validate via Schema.org validator (only required where `review_count > 0`).
- [ ] **`og:title`, `og:image`, `og:description`, `twitter:card`** present on every product page — view source on 3 random products and grep for these meta tags. Test image renders via [opengraph.xyz](https://www.opengraph.xyz/).
- [ ] **Canonical URL** present on every page — `<link rel="canonical" href="...">` in `<head>`. Verify on home, category, product, and a paginated listing.
- [ ] **`meta_title` + `meta_description` backfilled** — run `SELECT COUNT(*) FROM products WHERE meta_title IS NULL OR meta_description IS NULL;` against tenant DB; result must be 0.
- [ ] **Product image `alt_text` populated** — run `SELECT COUNT(*) FROM product_images WHERE alt_text IS NULL OR alt_text = '';`; result must be 0.
- [ ] **Title length ≤ 70 chars** — run `SELECT id, title FROM products WHERE LENGTH(meta_title) > 70 LIMIT 20;`; result must be empty (Google truncates beyond 70).

---

## 3. Tracking Pixels + Analytics (Mira — `mira_jikra` + Rohit — `rohit_jikra`)

- [ ] **GA4 Measurement ID** set in admin Settings — check `Setting::get('ga4_measurement_id')` returns non-empty value (format `G-XXXXXXX`). Admin path: `/admin/settings → Analytics`.
- [ ] **Meta Pixel ID** set — `Setting::get('meta_pixel_id')` non-empty (15-16 digit numeric).
- [ ] **Meta CAPI access token + test event code** set — `Setting::get('meta_capi_token')` and `Setting::get('meta_capi_test_code')` non-empty. Admin path: `/admin/settings → Pixels`.
- [ ] **TikTok Pixel ID** set if applicable — `Setting::get('tiktok_pixel_id')`.
- [ ] **Pinterest tag ID** set if applicable — `Setting::get('pinterest_tag_id')`.
- [ ] **Snap Pixel ID** set if applicable — `Setting::get('snap_pixel_id')`.
- [ ] **`PageView` fires on `/`** — open Chrome with [Meta Pixel Helper](https://chromewebstore.google.com/detail/meta-pixel-helper/fdgfkebogiimcoedlicjlajpkdmockpc) and GA4 DebugView; visit homepage; confirm event in both panels.
- [ ] **`ViewContent` fires on product page** — visit any product, confirm in Pixel Helper + GA4 DebugView with `content_ids` populated.
- [ ] **`AddToCart` fires** — click Add to Cart, confirm event with `value` and `currency` (INR).
- [ ] **`InitiateCheckout` fires** — proceed to checkout, confirm event.
- [ ] **`Purchase` fires** — complete a test order (see Section 5), confirm `Purchase` event with correct `value` in both Pixel Helper and GA4 DebugView Realtime.

---

## 4. Marketplace Feeds (Vihaan — `vihaan_jikra`)

- [ ] **Google Merchant feed returns 200** at `https://{domain}/feeds/google-merchant.xml` — verify via `curl -I` and confirm content-type is `application/xml`.
- [ ] **Google feed contains all active products** — `curl -s https://{domain}/feeds/google-merchant.xml | grep -c "<item>"` should equal `SELECT COUNT(*) FROM products WHERE is_active = true AND stock_status = 'in_stock';`.
- [ ] **Google feed validated** via [GMC Feed Diagnostics](https://merchants.google.com) — upload feed URL, confirm 0 critical errors, all warnings reviewed.
- [ ] **Facebook Catalog feed returns 200** at `https://{domain}/feeds/facebook-catalog.xml` — same `curl -I` check.
- [ ] **Zero `.webp` references in feeds** — `curl -s https://{domain}/feeds/google-merchant.xml | grep -ic "\.webp"` must be 0 (Google rejects WebP in feeds).
- [ ] **No Amazon CDN URLs in feeds** — `curl -s https://{domain}/feeds/google-merchant.xml | grep -ic "amazonaws\|m.media-amazon"` must be 0 (use tenant CDN only).
- [ ] **Every `<g:image_link>` HEAD-200** — extract all image URLs from feed and HEAD-check; zero non-200 responses.
- [ ] **Every `<g:title>` < 150 chars** — parse feed, assert max title length < 150 (Google's hard limit).
- [ ] **Every `<g:description>` non-empty** — parse feed, assert no empty `<g:description>` tags.

---

## 5. Payment & Checkout (Anaya — `anaya_jikra` + Aryan — `aryan_jikra`)

- [ ] **Razorpay credentials set** — `Setting::get('razorpay_key_id')`, `Setting::get('razorpay_key_secret')`, `Setting::get('razorpay_webhook_secret')` all non-empty. Admin path: `/admin/settings → Payments`.
- [ ] **Razorpay credentials verified via test order** — place INR 1 order on staging or with test mode toggle; confirm payment captured in Razorpay dashboard.
- [ ] **Razorpay webhook URL registered** at `https://{domain}/webhook/razorpay` in Razorpay dashboard with events: `payment.captured`, `payment.failed`, `order.paid`, `refund.processed`.
- [ ] **COD enabled by default** — `Setting::get('cod_enabled')` returns truthy.
- [ ] **`cod_advance_amount = 0` (default)** — direct COD path: customer pays nothing now, full amount at delivery. Verify by adding any product to cart, selecting COD, confirming no Razorpay redirect.
- [ ] **`cod_advance_amount > 0` (e.g. 100)** — partial COD path: customer pays the advance via Razorpay/Cashfree now + rest at delivery. Set advance to 100, place order, confirm Razorpay popup for ₹100 only and order metadata stores remaining balance.
- [ ] **`cod_minimum_amount = 199` (default)** — COD option **hidden** if cart total below this threshold. Verify by adding a single ₹99 product; COD radio button must be absent on checkout.
- [ ] **Test full purchase flow — Razorpay success** — complete a real ₹1 order; order status `paid`, payment row in `payments` table, confirmation email + WhatsApp delivered.
- [ ] **Test full purchase flow — Razorpay failure** — trigger card decline; order status `payment_failed`, abandoned-cart hook fires.
- [ ] **Test full purchase flow — Partial COD** — `cod_advance_amount = 100`, cart total ₹500; advance ₹100 captured via Razorpay, ₹400 marked as `cod_balance_due`.
- [ ] **Test full purchase flow — Pure COD** — `cod_advance_amount = 0`, cart total ₹500; order status `confirmed`, no payment captured, COD due `₹500`.
- [ ] **Order confirmation email delivered** — check inbox of test customer email; confirm template matches tenant branding.
- [ ] **Order confirmation WhatsApp delivered** — if WhatsApp Cloud API configured, confirm template message lands on test phone.

---

## 6. Storefront Content + Branding (Riya — `riya_jikra` + Naina — `naina_jikra`)

- [ ] **Logo uploaded** — `Setting::get('site_logo')` returns valid path; renders in header on `/`.
- [ ] **Favicon uploaded** — `Setting::get('site_favicon')` returns valid path; visible in browser tab.
- [ ] **Brand colors set in theme** — `Setting::get('primary_color')` and `Setting::get('secondary_color')` non-empty hex; verify via DevTools that CSS custom properties resolve correctly.
- [ ] **Hero banner / homepage sections configured** — admin path `/admin/homepage-sections` lists ≥ 1 active section; render on `/` matches.
- [ ] **About page populated** — `https://{domain}/about` returns 200 with non-placeholder content.
- [ ] **Contact page populated** — `https://{domain}/contact` returns 200 with real address, phone, email.
- [ ] **Privacy page populated** — `https://{domain}/privacy` returns 200 with full policy text (not Lorem Ipsum).
- [ ] **Terms page populated** — `https://{domain}/terms` returns 200.
- [ ] **Refund page populated** — `https://{domain}/refund-policy` returns 200.
- [ ] **Newsletter signup wired up** — submit a test email on homepage; confirm row appears in `newsletter_subscribers` table and double-opt-in email fires (if enabled).
- [ ] **Abandoned cart 3-reminder email templates customized** via `/admin/abandoned-cart-templates` — all 3 templates (1hr, 24hr, 72hr) have tenant-branded subject + body, no placeholder copy.
- [ ] **WhatsApp templates approved** — if using WhatsApp Cloud API, confirm at least `order_confirmation`, `shipping_update`, `abandoned_cart` templates show **APPROVED** status in Meta Business Manager.

---

## 7. Performance + SEO Health (Karan — `karan_jikra` + Tanvi — `tanvi_jikra`)

- [ ] **Lighthouse mobile score ≥ 80** — run [PageSpeed Insights](https://pagespeed.web.dev/) on homepage and a product page; both Performance scores ≥ 80 on mobile profile.
- [ ] **LCP < 2.5s** — same PageSpeed report; LCP metric under 2.5 seconds on mobile.
- [ ] **CLS < 0.1** — same report; Cumulative Layout Shift under 0.1.
- [ ] **All below-fold images have `loading="lazy"`** — view source on a long product listing; assert `loading="lazy"` on every `<img>` past the first viewport.
- [ ] **Vite build artifacts deployed** — confirm `public/build/manifest.json` exists and every hashed file referenced in it exists in `public/build/assets/`. Run `php artisan view:clear && php artisan config:cache` post-deploy.

---

## 8. Security & Compliance (Aditya — `aditya_jikra`)

- [ ] **HTTPS enforced** with HSTS — `curl -sI https://{domain} | grep -i strict-transport-security` returns `Strict-Transport-Security: max-age=31536000; includeSubDomains`.
- [ ] **HTTP redirects to HTTPS** — `curl -sI http://{domain}` returns 301/302 to `https://`.
- [ ] **CSP configured per tenant** — `curl -sI https://{domain} | grep -i content-security-policy` returns a CSP header; verify in browser DevTools Console that no third-party scripts (pixels, fonts, payment SDKs) are blocked.
- [ ] **Privacy Policy mentions GDPR + DPDP compliance** — open `https://{domain}/privacy` and grep for "GDPR" and "DPDP" / "Digital Personal Data Protection".
- [ ] **Cookie consent banner present** if EU traffic expected — visit homepage in incognito with EU geo (or VPN), confirm consent banner renders.
- [ ] **Admin 2FA enabled for owner account** — log into `/admin`, navigate to Profile → Security; confirm `two_factor_secret` is set on the owner's `users` row (`SELECT id, email, two_factor_secret IS NOT NULL AS has_2fa FROM users WHERE role = 'owner';`).

---

## 9. Final Go-Live (Diksha — `diksha` sign-off)

- [ ] **Smoke test — Razorpay INR 1 test order** — Diksha (or her delegate) places a real ₹1 order via Razorpay live mode; order completes, payment captured, email + WhatsApp delivered.
- [ ] **Smoke test — Real COD order** — Diksha places a real COD order; confirmation flow runs, order appears in `/admin/orders` with status `confirmed`, customer receives confirmation message.
- [ ] **Watchdog runs clean for 5 min** post-deploy — `bash scripts/deploy.sh` exits with `Watchdog: HEALTHY` and no auto-rollback triggered.
- [ ] **Karan posts deploy log** to the team Git log (`ai_team_git_log` table) with commit SHA, timestamp, and tenant ID.
- [ ] **Diksha approves go-live in writing** — recorded in `ai_team_conversations` table with `from_member = 'diksha'`, `message_type = 'approval'`, referencing this checklist's completion.

---

## Sign-off Block

| Field | Value |
|-------|-------|
| Tenant name | |
| Tenant ID | |
| Domain | |
| Launch date | |
| Aarav (BA) — checklist scoped | ☐ |
| Karan (DevOps) — Sections 1, 7 verified | ☐ |
| Tanvi (SEO) — Sections 2, 7 verified | ☐ |
| Mira + Rohit — Section 3 verified | ☐ |
| Vihaan — Section 4 verified | ☐ |
| Anaya + Aryan — Section 5 verified | ☐ |
| Riya + Naina — Section 6 verified | ☐ |
| Aditya — Section 8 verified | ☐ |
| **Diksha — Section 9 final approval** | ☐ |
