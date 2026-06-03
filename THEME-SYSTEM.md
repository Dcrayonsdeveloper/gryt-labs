# Jikra Theme System — Ready to Deploy

## What Changed

### 1. CSS Architecture (Tailwind v4 + Runtime Variables)

**File:** `resources/css/app.css`

The `@theme` block now uses `var(--brand-*, default)` pattern:
```css
--color-primary-600: var(--brand-600, #205258);
```

This compiles to CSS that uses runtime variables with fallbacks. Each tenant sets their colors via `:root` in the layout `<head>`.

### 2. 815 Color Replacements (60 files)

All hardcoded hex colors replaced with semantic Tailwind classes:

| Before (hardcoded) | After (semantic) |
|---------------------|-----------------|
| `bg-[#205258]` | `bg-primary-600` |
| `bg-[#1b454a]` | `bg-primary-700` |
| `bg-[#F8931D]` | `bg-accent-500` |
| `bg-[#E07E0A]` | `bg-accent-600` |
| `text-[#007185]` | `text-link` |
| `text-[#C7511F]` | `text-link-hover` |
| `hover:bg-[#205258]` | `hover:bg-primary-600` |
| `border-[#007185]` | `border-link` |
| + all hover, focus, ring, opacity variants |

### 3. 25 Branding Files Made Dynamic

All hardcoded "Jikra" text, logos, business info now use `Setting::get()`:

| What | Setting Key | Default |
|------|-------------|---------|
| Logo | `store_logo` | `images/jikra-logo.png` |
| Store name | `store_name` | `Jikra` |
| Legal name | `legal_name` | `Enormous Technology` |
| Theme color | `primary_color` | `#205258` |
| PWA name | `store_name` | `Jikra` |

### 4. Layout `<head>` CSS Variables

`resources/views/components/layouts/app.blade.php` injects tenant colors:

```html
<style>
  :root {
    --brand-600: {{ Setting::get('primary_color', '#205258') }};
    --brand-700: {{ Setting::get('primary_color_dark', '#1b454a') }};
    --brand-accent: {{ Setting::get('secondary_color', '#F8931D') }};
    --brand-link: {{ Setting::get('link_color', '#007185') }};
    /* ... full palette */
  }
</style>
```

## How to Deploy

### Step 1: Upload changed files to production
```bash
# Upload CSS
scp resources/css/app.css server:/var/www/jikra/resources/css/

# Upload all views
tar czf /tmp/views.tar.gz resources/views/
scp /tmp/views.tar.gz server:/tmp/
ssh server "cd /var/www/jikra && sudo tar xzf /tmp/views.tar.gz --overwrite"

# Upload scripts
scp scripts/replace-colors.php server:/var/www/jikra/scripts/
```

### Step 2: Rebuild CSS on server
```bash
ssh server "cd /var/www/jikra && sudo npm run build"
```

### Step 3: Clear caches
```bash
ssh server "cd /var/www/jikra && sudo php artisan optimize:clear && sudo rm -rf storage/framework/views/* && sudo systemctl restart php8.3-fpm"
```

### Step 4: Set tenant colors
For each tenant, set these in their `settings` table:
```
primary_color       = #205258    (or tenant's color)
primary_color_dark  = #1b454a
secondary_color     = #F8931D
secondary_color_dark = #E07E0A
link_color          = #007185
link_hover_color    = #C7511F
color_50 through color_950 = full palette
store_name          = Jikra
store_logo          = images/jikra-logo.png
legal_name          = Enormous Technology
```

## File List (Changed)

```
resources/css/app.css                                    ← @theme with var() chain
resources/views/components/layouts/app.blade.php         ← :root CSS vars + dynamic meta
resources/views/partials/header.blade.php                ← Dynamic logo
resources/views/partials/footer.blade.php                ← Dynamic logo
resources/views/partials/mobile-nav.blade.php            ← Dynamic branding
resources/views/home.blade.php                           ← Semantic colors
resources/views/products/show.blade.php                  ← Semantic colors (67 replacements)
resources/views/checkout/index.blade.php                 ← Semantic colors (73 replacements)
resources/views/pages/about.blade.php                    ← Dynamic business info
resources/views/pages/privacy.blade.php                  ← Dynamic business info
resources/views/pages/contact.blade.php                  ← Dynamic branding
resources/views/emails/order-confirmation.blade.php      ← Dynamic logo
resources/views/emails/abandoned-cart.blade.php           ← Dynamic logo
+ 47 more files (see scripts/replace-colors.php --dry-run for full list)
```

## Natually Tenant Color Palette

Set these settings for Natually:
```
primary_color       = #e91e8c
primary_color_dark  = #be185d
secondary_color     = #d4145a
secondary_color_dark = #b91048
link_color          = #e91e8c
link_hover_color    = #be185d
color_50            = #fff5f9
color_100           = #ffe0ef
color_200           = #ffd2e8
color_300           = #f9a8d4
color_400           = #f472b6
color_500           = #ec4899
color_800           = #9d174d
color_900           = #831843
color_950           = #500724
store_name          = Natually
store_logo          = images/natually-logo.png
```
