<?php

namespace App\Services;

use App\Contracts\ShippingProviderInterface;
use App\Models\Setting;

class ShippingManager
{
    private array $providers = [];

    public function __construct()
    {
        $this->providers = [
            'delhivery' => fn() => app(DelhiveryService::class),
            'bluedart'  => fn() => app(BlueDartService::class),
        ];
    }

    /**
     * Get a specific provider by name, or the tenant's default.
     */
    public function provider(?string $name = null): ShippingProviderInterface
    {
        $name = $name ?: $this->defaultProviderName();

        if (!isset($this->providers[$name])) {
            throw new \InvalidArgumentException("Unknown shipping provider: {$name}");
        }

        return ($this->providers[$name])();
    }

    /**
     * Get the provider for a specific order (based on its carrier field).
     */
    public function forOrder(\App\Models\Order $order): ShippingProviderInterface
    {
        $carrier = strtolower($order->carrier ?? '');

        return match (true) {
            str_contains($carrier, 'bluedart'), str_contains($carrier, 'blue dart') => $this->provider('bluedart'),
            str_contains($carrier, 'delhivery') => $this->provider('delhivery'),
            default => $this->defaultProvider(),
        };
    }

    /**
     * All providers that are enabled AND configured for the current tenant.
     */
    public function activeProviders(): array
    {
        $active = [];

        foreach ($this->providers as $name => $factory) {
            $instance = $factory();
            if ($this->isEnabled($name) && $instance->isConfigured()) {
                $active[$name] = $instance;
            }
        }

        return $active;
    }

    /**
     * All registered provider instances (regardless of config status).
     */
    public function allProviders(): array
    {
        $all = [];
        foreach ($this->providers as $name => $factory) {
            $all[$name] = $factory();
        }
        return $all;
    }

    /**
     * The tenant's default provider instance.
     */
    public function defaultProvider(): ShippingProviderInterface
    {
        return $this->provider($this->defaultProviderName());
    }

    /**
     * The tenant's default provider name from settings.
     */
    public function defaultProviderName(): string
    {
        $default = Setting::get('shipping_default_provider', '');

        if ($default && isset($this->providers[$default])) {
            return $default;
        }

        // Auto-detect: return the first configured provider
        foreach ($this->providers as $name => $factory) {
            $instance = $factory();
            if ($this->isEnabled($name) && $instance->isConfigured()) {
                return $name;
            }
        }

        // Fallback to delhivery (maintains backward compat)
        return 'delhivery';
    }

    /**
     * Check if a provider is enabled via tenant settings.
     */
    private function isEnabled(string $name): bool
    {
        // Delhivery is enabled by default for backward compat (existing tenants never set this)
        if ($name === 'delhivery') {
            return Setting::get('delhivery_enabled', '1') !== '0';
        }

        return Setting::get("{$name}_enabled", '') === '1';
    }

    /**
     * Check if ANY shipping provider is configured for the current tenant.
     */
    public function hasAnyProvider(): bool
    {
        return !empty($this->activeProviders());
    }

    /**
     * Register a new provider (for extensibility — DTDC, Ecom Express, etc.)
     */
    public function register(string $name, callable $factory): void
    {
        $this->providers[$name] = $factory;
    }
}
