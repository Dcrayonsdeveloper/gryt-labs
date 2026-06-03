<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Override stancl's GeneratesIds trait — we use manual string IDs.
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * Columns stored directly on the tenants table (not in data JSON).
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'is_active',
            'plan',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * All other tenant config goes into the `data` JSON column:
     * - tenancy_db_name, tenancy_db_username, tenancy_db_password
     * - razorpay_key_id, razorpay_key_secret
     * - delhivery_api_token, delhivery_pickup_location
     * - whatsapp_phone_id, whatsapp_token
     * - instagram_access_token, facebook_page_id, fb_page_access_token
     * - brand_name, logo_url, favicon_url, primary_color, secondary_color
     * - support_email, support_phone
     * - gst_number, legal_name, business_address
     */
}
