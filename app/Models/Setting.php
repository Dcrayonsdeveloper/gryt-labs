<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'is_public',
    ];

    /**
     * Keys that should be encrypted at rest in the database.
     */
    protected static array $encryptedKeys = [
        'razorpay_key_secret',
        'razorpay_webhook_secret',
        'mail_password',
    ];

    /**
     * Bust the settings cache on ANY write.
     *
     * Setting::get() serves from a 5-minute cached array, but only Setting::set()
     * used to clear it — the 17 places that write via updateOrCreate (admin
     * Settings forms included) left the app reading stale values, so a saved
     * setting could appear to have no effect for minutes.
     */
    protected static function booted(): void
    {
        // Pass the row's group so getGroup()'s per-group cache is cleared too.
        $flush = fn (self $setting) => static::flushCache($setting->group ?: null);
        static::saved($flush);
        static::deleted($flush);
    }

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function getValueAttribute($value)
    {
        // Decrypt sensitive values
        if (in_array($this->key, static::$encryptedKeys) && $value) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                // Value may not be encrypted yet (legacy data), return as-is
            }
        }

        return match ($this->type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    public function setValueAttribute($value): void
    {
        $raw = match ($this->type) {
            'json', 'array' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        // Encrypt sensitive values before storing
        if (in_array($this->key, static::$encryptedKeys) && $raw) {
            $raw = Crypt::encryptString($raw);
        }

        $this->attributes['value'] = $raw;
    }

    public static function get(string $key, $default = null)
    {
        // Use the underlying cache store directly (not Stancl's tag-based wrapper)
        // because the file driver doesn't support tags.
        // Tenant isolation is handled by including the DB name in the cache key.
        $store = Cache::store(config('cache.default'));
        $cacheKey = 'settings.all.' . static::tenantCachePrefix();

        $all = $store->remember($cacheKey, 300, function () {
            return static::all(['key', 'value', 'type'])->pluck('value', 'key')->toArray();
        });

        return $all[$key] ?? $default;
    }

    public static function set(string $key, $value, string $type = 'string', string $group = 'general'): self
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group]
        );

        $store = Cache::store(config('cache.default'));
        $store->forget('settings.all.' . static::tenantCachePrefix());

        return $setting;
    }

    public static function getGroup(string $group): array
    {
        $store = Cache::store(config('cache.default'));
        $prefix = static::tenantCachePrefix();
        return $store->remember("settings.group.{$group}.{$prefix}", 3600, function () use ($group) {
            return static::where('group', $group)
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Clear cached settings so a write is visible on the next read.
     *
     * Must resolve the store the same way get()/getGroup() do. The Cache facade
     * resolves to Stancl's tenancy wrapper, which is a *different* store — calling
     * Cache::forget() there clears a key nothing reads and leaves the cached
     * 'settings.all.*' entry that get() actually reads untouched, so saved
     * settings stay invisible until the 300s TTL lapses.
     */
    public static function flushCache(?string $group = null): void
    {
        $store  = Cache::store(config('cache.default'));
        $prefix = static::tenantCachePrefix();

        $store->forget('settings.all.' . $prefix);

        if ($group !== null) {
            $store->forget("settings.group.{$group}.{$prefix}");
        }
    }

    /**
     * Get tenant-scoped cache prefix to prevent cross-tenant cache pollution.
     */
    private static function tenantCachePrefix(): string
    {
        // Use the current database name as cache scope — guaranteed to be correct
        // because DatabaseTenancyBootstrapper switches the connection before controllers run
        try {
            return \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
        } catch (\Exception $e) {
            return 'default';
        }
    }
}
