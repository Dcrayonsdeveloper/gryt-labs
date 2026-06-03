<?php

namespace App\Services\Checkout\Gateway;

use App\Models\Setting;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Per-tenant resolver for {@see PaymentGatewayInterface} implementations.
 *
 * Bound as `app()->scoped()` (per-request, not singleton) so that a worker
 * that handles request 1 for `jikra.in` then request 2 for `ayurvexa.com`
 * gets a freshly-resolved registry on the second request. This is the same
 * isolation discipline the rest of the per-tenant services follow.
 *
 * Resolution rules:
 *   1. Slug must be in the `enabled` list from `config/checkout.php` (which
 *      a tenant can narrow via `Setting::get('checkout_enabled_gateways')`).
 *   2. Implementation class must be registered in `config/checkout.php`
 *      under `gateway_implementations`.
 *   3. Unknown slug -> {@see InvalidArgumentException}, so the orchestrator
 *      fails fast rather than silently falling through to a default.
 */
class GatewayRegistry
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $resolved = [];

    public function __construct(private readonly Container $container)
    {
    }

    /**
     * Resolve a gateway implementation by its slug for the current tenant.
     *
     * @throws InvalidArgumentException when the slug is unknown or disabled
     */
    public function for(string $slug): PaymentGatewayInterface
    {
        if (isset($this->resolved[$slug])) {
            return $this->resolved[$slug];
        }

        if (!in_array($slug, $this->enabledForTenant(), true)) {
            throw new InvalidArgumentException("Gateway '{$slug}' is not enabled for this tenant.");
        }

        $implementations = config('checkout.gateway_implementations', []);
        if (!isset($implementations[$slug])) {
            throw new InvalidArgumentException("No implementation registered for gateway '{$slug}'.");
        }

        $instance = $this->container->make($implementations[$slug]);
        if (!$instance instanceof PaymentGatewayInterface) {
            throw new InvalidArgumentException(
                "Implementation for '{$slug}' must implement " . PaymentGatewayInterface::class . '.'
            );
        }

        if ($instance->slug() !== $slug) {
            throw new InvalidArgumentException(
                "Implementation for '{$slug}' returned mismatched slug '{$instance->slug()}'."
            );
        }

        return $this->resolved[$slug] = $instance;
    }

    /**
     * The default gateway for this tenant (per-tenant Setting overrides
     * config default). Used by checkout-page rendering when no explicit
     * choice has been made.
     */
    public function primary(): PaymentGatewayInterface
    {
        $slug = Setting::get('checkout_primary_gateway', config('checkout.gateways.primary', 'razorpay'));
        return $this->for($slug);
    }

    /**
     * @return list<string> slugs the current tenant accepts
     */
    public function enabledForTenant(): array
    {
        $configured = config('checkout.gateways.enabled', []);
        $tenantOverride = Setting::get('checkout_enabled_gateways', '');
        if (is_string($tenantOverride) && trim($tenantOverride) !== '') {
            $tenantList = array_filter(array_map('trim', explode(',', $tenantOverride)));
            return array_values(array_intersect($configured, $tenantList));
        }
        return array_values($configured);
    }
}
