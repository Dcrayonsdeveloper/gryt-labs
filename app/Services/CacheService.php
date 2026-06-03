<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    private const PREFIX = 'fvk_';

    /**
     * Get tenant-scoped cache prefix to prevent cross-tenant data leakage.
     */
    private function tenantPrefix(): string
    {
        $tenantId = '';
        try {
            $tenantId = tenant('id') ?? '';
        } catch (\Throwable) {
            // Central context — no tenant
        }
        return self::PREFIX . ($tenantId ? "{$tenantId}_" : '');
    }

    public function getProduct(int $productId, callable $callback, int $ttl = 1800)
    {
        return Cache::remember($this->tenantPrefix() . "product.{$productId}", $ttl, $callback);
    }

    public function getProductList(string $key, callable $callback, int $ttl = 300)
    {
        return Cache::remember($this->tenantPrefix() . "products.{$key}", $ttl, $callback);
    }

    public function getCategoryTree(callable $callback, int $ttl = 3600)
    {
        return Cache::remember($this->tenantPrefix() . 'category_tree', $ttl, $callback);
    }

    public function getHomepageSections(callable $callback, int $ttl = 900)
    {
        return Cache::remember($this->tenantPrefix() . 'homepage_sections', $ttl, $callback);
    }

    public function getSearchResults(string $query, array $filters, callable $callback, int $ttl = 300)
    {
        $key = $this->tenantPrefix() . 'search.' . md5($query . serialize($filters));
        return Cache::remember($key, $ttl, $callback);
    }

    public function invalidateProduct(int $productId): void
    {
        $prefix = $this->tenantPrefix();
        Cache::forget($prefix . "product.{$productId}");
        Cache::forget($prefix . 'recommendations.similar.' . $productId);
        Cache::forget($prefix . 'recommendations.fbt.' . $productId);
        Cache::forget($prefix . 'recommendations.popular');
    }

    public function invalidateCategory(): void
    {
        Cache::forget($this->tenantPrefix() . 'category_tree');
    }

    public function invalidateHomepage(): void
    {
        Cache::forget($this->tenantPrefix() . 'homepage_sections');
    }

    public function invalidateAll(): void
    {
        // Only flush keys for current tenant using Redis prefix scan
        // Fallback to full flush if pattern delete not available
        $prefix = $this->tenantPrefix();
        $redis = Cache::getStore();

        if (method_exists($redis, 'getRedis')) {
            try {
                $connection = $redis->getRedis();
                $cachePrefix = config('cache.prefix', 'laravel_cache');
                $keys = $connection->keys("{$cachePrefix}:{$prefix}*");
                if (!empty($keys)) {
                    $connection->del($keys);
                }
                // Also clear old non-prefixed recommendation keys
                $oldKeys = $connection->keys("{$cachePrefix}:recommendations.*");
                if (!empty($oldKeys)) {
                    $connection->del($oldKeys);
                }
                return;
            } catch (\Throwable) {
                // Fallback to flush
            }
        }

        Cache::flush();
    }

    public function warmProducts(array $productIds): void
    {
        $prefix = $this->tenantPrefix();
        foreach ($productIds as $id) {
            Cache::forget($prefix . "product.{$id}");
        }
    }
}
