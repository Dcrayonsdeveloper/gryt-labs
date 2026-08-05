<?php

namespace App\Services\ShiprocketCheckout;

use App\Models\Setting;

/**
 * Persistent list of Shiprocket order IDs that must NEVER be recreated by
 * shiprocket:reconcile-orders.
 *
 * A Shiprocket Checkout order keeps living on Shiprocket's side as SUCCESS even
 * after we delete it locally, so the every-15-min reconcile would otherwise pull
 * it straight back. orders:delete records the deleted order's Shiprocket id here,
 * and reconcile skips anything on the list — so a deleted (test) order stays
 * deleted.
 *
 * Stored per-tenant in the settings table (key: reconcile_ignored_sr_ids), so it
 * is naturally scoped when reconcile initializes each tenant.
 */
class ReconcileIgnoreList
{
    private const KEY = 'reconcile_ignored_sr_ids';

    /**
     * @return string[]
     *
     * Stored as a plain JSON *string* (type 'string'), NOT a 'json'-typed setting:
     * Setting::set() assigns `value` before `type`, so on a first-time row a 'json'
     * value would be cast with the stale type and saved as the literal "Array".
     * Decoding a string here avoids that quirk and still reads legacy array values.
     */
    public static function all(): array
    {
        $raw = Setting::get(self::KEY, '[]');

        $arr = is_array($raw) ? $raw : json_decode((string) $raw, true);

        return is_array($arr)
            ? array_values(array_filter(array_map('strval', $arr)))
            : [];
    }

    public static function has(?string $id): bool
    {
        $id = trim((string) $id);

        return $id !== '' && in_array($id, self::all(), true);
    }

    /** Add one or more Shiprocket ids so reconcile never recreates them. */
    public static function add(array $ids): void
    {
        $ids = array_filter(array_map(fn ($v) => trim((string) $v), $ids));
        if (! $ids) {
            return;
        }

        $merged = array_values(array_unique(array_merge(self::all(), $ids)));
        Setting::set(self::KEY, json_encode($merged), 'string');
    }

    /** Remove ids from the list (e.g. when you DO want an order re-imported). */
    public static function remove(array $ids): void
    {
        $ids = array_map(fn ($v) => trim((string) $v), $ids);
        $remaining = array_values(array_diff(self::all(), $ids));
        Setting::set(self::KEY, json_encode($remaining), 'string');
    }
}
