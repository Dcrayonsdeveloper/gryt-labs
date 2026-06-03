<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Stancl\Tenancy\Database\Models\Domain;

class TenantController extends Controller
{
    /**
     * Reserved subdomains that cannot be used by tenants.
     */
    private const RESERVED_SUBDOMAINS = [
        'admin', 'api', 'app', 'auth', 'billing', 'blog', 'cdn', 'checkout',
        'dashboard', 'dev', 'dns', 'docs', 'email', 'ftp', 'help', 'mail',
        'manage', 'ns1', 'ns2', 'pop', 'smtp', 'ssh', 'ssl', 'staging',
        'status', 'support', 'test', 'web', 'webmail', 'www', 'super-admin',
    ];

    /**
     * Base domain for auto-generated subdomains.
     */
    private function baseDomain(): string
    {
        return config('tenancy.subdomain_base', env('APP_DOMAIN', 'jikra.in'));
    }

    public function index()
    {
        $tenants = Tenant::with('domains')->latest()->get();
        return view('super-admin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        $baseDomain = $this->baseDomain();
        return view('super-admin.tenants.create', compact('baseDomain'));
    }

    public function store(Request $request)
    {
        $baseDomain = $this->baseDomain();

        $validated = $request->validate([
            'id' => [
                'required', 'string', 'alpha_dash', 'max:50', 'unique:tenants,id',
                Rule::notIn(self::RESERVED_SUBDOMAINS),
            ],
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'plan' => 'required|in:free,standard,premium,enterprise',
            'brand_name' => 'nullable|string|max:255',
            'support_email' => 'nullable|email',
            'support_phone' => 'nullable|string|max:20',
            'razorpay_key_id' => 'nullable|string',
            'razorpay_key_secret' => 'nullable|string',
            'delhivery_api_token' => 'nullable|string',
            'instagram_handle' => 'nullable|string|max:100',
            'instagram_access_token' => 'nullable|string',
            'facebook_pixel_id' => 'nullable|string|max:50',
            'ga4_measurement_id' => 'nullable|string|max:50',
            'whatsapp_phone_id' => 'nullable|string|max:50',
        ]);

        // Auto-generate subdomain: {id}.jikra.in
        $subdomain = strtolower($validated['id']) . '.' . $baseDomain;

        // Ensure subdomain is unique
        if (Domain::where('domain', $subdomain)->exists()) {
            return back()->withInput()->withErrors(['id' => "Subdomain '{$subdomain}' is already taken."]);
        }

        // Ensure custom domain is unique (if provided)
        $customDomain = $validated['domain'] ?? null;
        if ($customDomain && Domain::where('domain', $customDomain)->exists()) {
            return back()->withInput()->withErrors(['domain' => "Domain '{$customDomain}' is already in use."]);
        }

        // Create tenant — triggers: CreateDatabase → MigrateDatabase
        $tenant = Tenant::create([
            'id' => $validated['id'],
            'name' => $validated['name'],
            'is_active' => true,
            'plan' => $validated['plan'],
            'brand_name' => $validated['brand_name'] ?? $validated['name'],
            'support_email' => $validated['support_email'] ?? '',
            'support_phone' => $validated['support_phone'] ?? '',
            'razorpay_key_id' => $validated['razorpay_key_id'] ?? '',
            'razorpay_key_secret' => $validated['razorpay_key_secret'] ?? '',
            'delhivery_api_token' => $validated['delhivery_api_token'] ?? '',
            'instagram_handle' => $validated['instagram_handle'] ?? '',
            'instagram_access_token' => $validated['instagram_access_token'] ?? '',
            'facebook_pixel_id' => $validated['facebook_pixel_id'] ?? '',
            'ga4_measurement_id' => $validated['ga4_measurement_id'] ?? '',
            'whatsapp_phone_id' => $validated['whatsapp_phone_id'] ?? '',
            'tenancy_db_name' => $validated['id'], // DB name = tenant ID
        ]);

        // Add auto-generated subdomain
        $tenant->domains()->create(['domain' => $subdomain]);

        // Add custom domain if provided
        if ($customDomain) {
            $tenant->domains()->create(['domain' => $customDomain]);
        }

        // Seed default data for the new tenant
        tenancy()->initialize($tenant);
        $this->seedDefaultData($validated);
        tenancy()->end();

        return redirect()->route('super-admin.tenants.index')
            ->with('success', "Store '{$tenant->name}' created! Subdomain: {$subdomain}");
    }

    public function show(Tenant $tenant)
    {
        $tenant->load('domains');
        return view('super-admin.tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant)
    {
        $tenant->load('domains');
        $baseDomain = $this->baseDomain();
        return view('super-admin.tenants.edit', compact('tenant', 'baseDomain'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'plan' => 'required|in:free,standard,premium,enterprise',
            'brand_name' => 'nullable|string|max:255',
            'support_email' => 'nullable|email',
            'support_phone' => 'nullable|string|max:20',
            'razorpay_key_id' => 'nullable|string',
            'razorpay_key_secret' => 'nullable|string',
            'delhivery_api_token' => 'nullable|string',
            'instagram_handle' => 'nullable|string|max:100',
            'instagram_access_token' => 'nullable|string',
            'facebook_pixel_id' => 'nullable|string|max:50',
            'ga4_measurement_id' => 'nullable|string|max:50',
            'whatsapp_phone_id' => 'nullable|string|max:50',
        ]);

        $tenant->update($validated);

        return redirect()->route('super-admin.tenants.index')
            ->with('success', "Tenant '{$tenant->name}' updated.");
    }

    /**
     * Add a custom domain to an existing tenant.
     */
    public function addDomain(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:255|unique:domains,domain',
        ]);

        $tenant->domains()->create(['domain' => $validated['domain']]);

        return back()->with('success', "Domain '{$validated['domain']}' added to {$tenant->name}.");
    }

    /**
     * Remove a domain from a tenant (cannot remove last domain).
     */
    public function removeDomain(Request $request, Tenant $tenant)
    {
        if ($tenant->domains()->count() <= 1) {
            return back()->with('error', 'Cannot remove the last domain. Each store must have at least one domain.');
        }

        $domain = $tenant->domains()->where('id', $request->input('domain_id'))->first();
        if ($domain) {
            $domain->delete();
            return back()->with('success', "Domain '{$domain->domain}' removed.");
        }

        return back()->with('error', 'Domain not found.');
    }

    public function destroy(Tenant $tenant)
    {
        $tenantName = $tenant->name;
        $tenant->delete();

        return redirect()->route('super-admin.tenants.index')
            ->with('success', "Tenant '{$tenantName}' deleted.");
    }

    public function toggle(Tenant $tenant)
    {
        $tenant->update(['is_active' => !$tenant->is_active]);
        $status = $tenant->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Tenant '{$tenant->name}' {$status}.");
    }

    public function impersonate(Tenant $tenant)
    {
        $domain = $tenant->domains()->first();
        if (!$domain) {
            return back()->with('error', 'No domain configured for this tenant.');
        }

        $token = tenancy()->impersonate($tenant, '/admin', 'web');

        return redirect($token->impersonationUrl);
    }

    public function stats(Tenant $tenant)
    {
        tenancy()->initialize($tenant);

        $stats = [
            'products' => \App\Models\Product::count(),
            'active_products' => \App\Models\Product::where('is_active', true)->count(),
            'orders' => \App\Models\Order::count(),
            'orders_today' => \App\Models\Order::whereDate('created_at', today())->count(),
            'users' => \App\Models\User::count(),
            'revenue_total' => \App\Models\Order::where('payment_status', 'paid')->sum('total'),
            'revenue_today' => \App\Models\Order::where('payment_status', 'paid')->whereDate('created_at', today())->sum('total'),
        ];

        tenancy()->end();

        return response()->json($stats);
    }

    /**
     * Check if a subdomain/tenant ID is available (AJAX).
     */
    public function checkAvailability(Request $request)
    {
        $id = strtolower($request->input('id', ''));

        if (strlen($id) < 3) {
            return response()->json(['available' => false, 'message' => 'Must be at least 3 characters']);
        }

        if (in_array($id, self::RESERVED_SUBDOMAINS)) {
            return response()->json(['available' => false, 'message' => "'{$id}' is reserved"]);
        }

        if (Tenant::find($id)) {
            return response()->json(['available' => false, 'message' => "'{$id}' is already taken"]);
        }

        $subdomain = $id . '.' . $this->baseDomain();
        return response()->json(['available' => true, 'subdomain' => $subdomain, 'message' => "'{$subdomain}' is available"]);
    }

    private function seedDefaultData(array $data): void
    {
        \App\Models\User::create([
            'first_name' => 'Admin',
            'last_name' => $data['name'],
            'email' => $data['support_email'] ?? 'admin@' . $data['id'] . '.com',
            'password' => bcrypt('changeme123'),
            'phone' => $data['support_phone'] ?? null,
            'role' => 'admin',
        ]);

        $defaults = [
            'store_name' => $data['brand_name'] ?? $data['name'],
            'site_name' => $data['brand_name'] ?? $data['name'],
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'cod_enabled' => '1',
            'razorpay_enabled' => '1',
            'free_shipping_threshold' => '499',
            'shipping_flat_rate' => '50',
        ];

        foreach ($defaults as $key => $value) {
            \App\Models\Setting::set($key, $value);
        }

        // Seed default homepage sections
        $sectionDefaults = [
            ['type' => 'hero_banner', 'name' => 'Hero Banner', 'settings' => ['slides' => [], 'autoplay' => true]],
            ['type' => 'category_grid', 'name' => 'Shop by Category', 'settings' => ['heading' => 'Shop by Category', 'columns' => 4, 'style' => 'card']],
            ['type' => 'product_carousel', 'name' => 'Bestsellers', 'settings' => ['heading' => 'Bestsellers', 'source' => 'bestsellers', 'count' => 12]],
            ['type' => 'trust_badges', 'name' => 'Trust Badges', 'settings' => ['badges' => [
                ['icon' => 'truck', 'title' => 'Free Shipping', 'text' => 'On orders above ₹499'],
                ['icon' => 'shield', 'title' => 'Secure Payment', 'text' => '100% protected'],
                ['icon' => 'refresh', 'title' => 'Easy Returns', 'text' => '7-day return policy'],
                ['icon' => 'cash', 'title' => 'COD Available', 'text' => 'Pay on delivery'],
            ]]],
            ['type' => 'newsletter', 'name' => 'Newsletter', 'settings' => ['heading' => 'Stay Updated', 'button_text' => 'Subscribe']],
        ];

        foreach ($sectionDefaults as $i => $section) {
            \App\Models\PageSection::create([
                'page' => 'home',
                'type' => $section['type'],
                'name' => $section['name'],
                'position' => $i + 1,
                'settings' => $section['settings'],
                'is_active' => true,
            ]);
        }
    }
}
