<?php

namespace App\DTOs;

use App\Models\Setting;

/**
 * Immutable value object for tenant theme settings.
 *
 * Centralises every theme-related Setting key so controllers, views,
 * email templates, and services never hard-code colour / font defaults.
 *
 * Usage:
 *   $dto = ThemeDTO::fromSettings();
 *   $dto->badgeColor;          // '#ff82c0' (Natually) or '#CC0C39' (default)
 *   $dto->toArray();           // pass to JS / JSON / Blade
 */
class ThemeDTO
{
    public function __construct(
        // Brand colours
        public readonly string $primaryColor,
        public readonly string $primaryColorDark,
        public readonly string $secondaryColor,
        public readonly string $secondaryColorDark,

        // Link colours
        public readonly string $linkColor,
        public readonly string $linkHoverColor,

        // Badge / accent colours (discount tags, sale ribbons, urgency text)
        public readonly string $badgeColor,
        public readonly string $badgeTextColor,

        // Typography
        public readonly string $fontFamily,
        public readonly string $headingFont,

        // UI chrome
        public readonly string $buttonRadius,
        public readonly string $headerStyle,

        // Store identity
        public readonly string $storeName,
        public readonly string $storeLogo,
        public readonly string $storeFavicon,
        public readonly string $storeTagline,

        // Currency
        public readonly string $currencyCode,
        public readonly string $currencySymbol,
    ) {}

    /**
     * Build from the current tenant's Settings table.
     */
    public static function fromSettings(): self
    {
        return new self(
            primaryColor:      Setting::get('primary_color', '')      ?: '#334155',
            primaryColorDark:  Setting::get('primary_color_dark', '') ?: '#1e293b',
            secondaryColor:    Setting::get('secondary_color', '')    ?: '#f59e0b',
            secondaryColorDark: Setting::get('secondary_color_dark', '') ?: '#d97706',

            linkColor:         Setting::get('link_color', '')         ?: '#2563eb',
            linkHoverColor:    Setting::get('link_hover_color', '')   ?: '#1d4ed8',

            badgeColor:        Setting::get('badge_color', '')        ?: '#CC0C39',
            badgeTextColor:    Setting::get('badge_text_color', '')   ?: '#ffffff',

            fontFamily:        Setting::get('font_family', '')        ?: 'Poppins',
            headingFont:       Setting::get('heading_font', '')       ?: 'Fredoka',

            buttonRadius:      Setting::get('button_radius', '')      ?: 'rounded',
            headerStyle:       Setting::get('header_style', '')       ?: 'centered',

            storeName:         Setting::get('store_name', '')         ?: config('app.name', 'Store'),
            storeLogo:         Setting::get('store_logo', ''),
            storeFavicon:      Setting::get('store_favicon', ''),
            storeTagline:      Setting::get('store_tagline', ''),

            currencyCode:      Setting::get('currency', '')           ?: config('app.currency', 'INR'),
            currencySymbol:    Setting::get('currency_symbol', '')    ?: config('app.currency_symbol', '₹'),
        );
    }

    /**
     * Serialize for JS config / Blade @json.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
