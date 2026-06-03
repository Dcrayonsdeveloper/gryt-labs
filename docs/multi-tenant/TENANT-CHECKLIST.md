# New Tenant Onboarding Checklist

## Before Creating

- [ ] Domain name purchased and DNS pointing to server IP `15.207.133.144`
- [ ] Razorpay account created → Key ID + Secret ready
- [ ] Delhivery account (optional) → API token ready
- [ ] Logo file (PNG, min 200x60px)
- [ ] Brand name, support email, support phone decided

## Create Tenant

```php
$tenant = Tenant::create([
    'id' => 'SLUG',           // lowercase, no spaces, e.g. 'mystore'
    'name' => 'STORE NAME',
    'is_active' => true,
    'plan' => 'standard',
    'brand_name' => 'DISPLAY NAME',
    'support_email' => 'EMAIL',
    'support_phone' => 'PHONE',
    'razorpay_key_id' => 'KEY',
    'razorpay_key_secret' => 'SECRET',
]);
$tenant->domains()->create(['domain' => 'DOMAIN.IN']);
```

## After Creating

- [ ] SSL certificate: `sudo certbot --nginx -d DOMAIN.IN -d www.DOMAIN.IN`
- [ ] Nginx config: add server block or verify wildcard works
- [ ] Login to `/admin` with default admin: `admin@SLUG.com` / `changeme123`
- [ ] Change admin password immediately
- [ ] Upload logo via admin panel
- [ ] Configure settings: store name, shipping rates, payment methods
- [ ] Add products (or import from shared catalog)
- [ ] Add categories
- [ ] Test checkout flow end-to-end
- [ ] Configure WhatsApp (optional): add phone_id + token to tenant data
- [ ] Configure social media (optional): add IG/FB tokens to tenant data
- [ ] Set up Delhivery warehouse (optional)

## Verification

```bash
# Test the store is live
curl -I https://DOMAIN.IN

# Check tenant DB exists
mysql -e "SHOW DATABASES LIKE 'tenant_SLUG'"

# Check tables created
php artisan tenants:run 'migrate:status' --tenants=SLUG
```
