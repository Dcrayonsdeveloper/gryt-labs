<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Influencer;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = $request->input('per_page', 10);

        $query = Coupon::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->input('status')) {
            match ($status) {
                'active' => $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    }),
                'expired' => $query->where('expires_at', '<', now()),
                'inactive' => $query->where('is_active', false),
                default => null,
            };
        }

        $coupons = $query->latest()->paginate($perPage)->withQueryString();

        // Coupon analytics: usage count and revenue from orders
        $couponIds = $coupons->pluck('id')->toArray();
        $couponAnalytics = [];
        if (!empty($couponIds)) {
            $analytics = DB::table('orders')
                ->whereIn('coupon_id', $couponIds)
                ->where('payment_status', 'paid')
                ->whereNotIn('status', ['cancelled', 'returned'])
                ->select('coupon_id', DB::raw('COUNT(*) as usage_count'), DB::raw('SUM(total) as total_revenue'))
                ->groupBy('coupon_id')
                ->get();
            foreach ($analytics as $row) {
                $couponAnalytics[$row->coupon_id] = [
                    'usage_count' => $row->usage_count,
                    'total_revenue' => $row->total_revenue,
                ];
            }
        }

        $stats = [
            'total' => Coupon::count(),
            'active' => Coupon::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })->count(),
            'expired' => Coupon::where('expires_at', '<', now())->count(),
            'auto_apply' => Coupon::where('auto_apply', true)->where('is_active', true)->count(),
        ];

        // ── Shiprocket Checkout coupons (read-only) ──────────────────────────
        // Discounts configured in Shiprocket's dashboard don't exist in our coupons
        // table — Shiprocket only tells us the code once a customer uses it, stored in
        // orders.metadata->sr_pricing->coupon_codes. So we surface the real codes seen
        // on live orders (with orders/sales attribution) as a read-only reference.
        // Stats use the SAME dual-source query as the influencer module, so figures match.
        $shiprocketCoupons = $this->shiprocketCoupons();

        return view('admin.coupons.index', compact('coupons', 'stats', 'couponAnalytics', 'shiprocketCoupons'));
    }

    /**
     * Distinct Shiprocket-checkout coupon codes seen on real orders, each with its
     * orders/sales/discount totals. Read-only — Shiprocket exposes no API to list the
     * discounts themselves, so this is derived from order metadata.
     *
     * @return array<int, array{code:string,orders:int,sales:float,discount:float,managed:bool}>
     */
    private function shiprocketCoupons(): array
    {
        $codes = Order::query()
            ->whereJsonLength('metadata->sr_pricing->coupon_codes', '>', 0)
            ->get(['metadata'])
            ->flatMap(fn ($o) => (array) data_get($o->metadata, 'sr_pricing.coupon_codes', []))
            ->map(fn ($c) => strtoupper(trim((string) $c)))
            ->filter()
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return [];
        }

        // Which of these codes also exist as managed coupons in our own table.
        $managed = Coupon::whereIn('code', $codes)->pluck('code')->map(fn ($c) => strtoupper($c))->all();

        return $codes->map(function ($code) use ($managed) {
            $row = Influencer::ordersQueryForCode($code)
                ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total),0) as sales, COALESCE(SUM(discount),0) as discount')
                ->first();

            return [
                'code'     => $code,
                'orders'   => (int) ($row->orders ?? 0),
                'sales'    => (float) ($row->sales ?? 0),
                'discount' => (float) ($row->discount ?? 0),
                'managed'  => in_array($code, $managed, true),
            ];
        })->sortByDesc('orders')->values()->all();
    }

    public function create(): View
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();

        return view('admin.coupons.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'code' => 'required|string|max:50|unique:coupons',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed,free_shipping,buy_x_get_y',
            'value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
            'is_stackable' => 'boolean',
            'auto_apply' => 'boolean',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'exists:products,id',
            'applicable_categories' => 'nullable|array',
            'applicable_categories.*' => 'exists:categories,id',
        ];

        // Cap percentage coupons at 100%
        if ($request->input('type') === 'percentage') {
            $rules['value'] = 'required|numeric|min:0|max:100';
        }

        if ($request->input('type') === 'buy_x_get_y') {
            $rules['conditions.buy_qty'] = 'required|integer|min:1';
            $rules['conditions.get_qty'] = 'required|integer|min:1';
        }

        $validated = $request->validate($rules);

        // Ensure boolean defaults
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_stackable'] = $request->boolean('is_stackable');
        $validated['auto_apply'] = $request->boolean('auto_apply');

        // Default nullable numeric fields to 0 (DB has NOT NULL constraint)
        $validated['min_order_amount'] = $validated['min_order_amount'] ?? 0;
        $validated['max_discount'] = $validated['max_discount'] ?? 0;
        $validated['usage_limit'] = $validated['usage_limit'] ?? 0;
        $validated['usage_per_user'] = $validated['usage_per_user'] ?? 0;

        // Build conditions for BOGO
        if ($request->input('type') === 'buy_x_get_y') {
            $validated['conditions'] = [
                'buy_qty' => (int) $request->input('conditions.buy_qty'),
                'get_qty' => (int) $request->input('conditions.get_qty'),
            ];
        } else {
            $validated['conditions'] = null;
        }

        Coupon::create($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully');
    }

    public function edit(Coupon $coupon): View
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();

        // Coupon analytics
        $couponStats = DB::table('orders')
            ->where('coupon_id', $coupon->id)
            ->where('payment_status', 'paid')
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->selectRaw('COUNT(*) as total_uses, COALESCE(SUM(total), 0) as total_revenue, COALESCE(AVG(total), 0) as avg_order_value')
            ->first();

        // Usage over last 30 days
        $usageLast30 = DB::table('orders')
            ->where('coupon_id', $coupon->id)
            ->where('payment_status', 'paid')
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return view('admin.coupons.edit', compact('coupon', 'categories', 'couponStats', 'usageLast30'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $rules = [
            'code' => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed,free_shipping,buy_x_get_y',
            'value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
            'is_stackable' => 'boolean',
            'auto_apply' => 'boolean',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'exists:products,id',
            'applicable_categories' => 'nullable|array',
            'applicable_categories.*' => 'exists:categories,id',
        ];

        // Cap percentage coupons at 100%
        if ($request->input('type') === 'percentage') {
            $rules['value'] = 'required|numeric|min:0|max:100';
        }

        if ($request->input('type') === 'buy_x_get_y') {
            $rules['conditions.buy_qty'] = 'required|integer|min:1';
            $rules['conditions.get_qty'] = 'required|integer|min:1';
        }

        $validated = $request->validate($rules);

        // Ensure boolean defaults
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_stackable'] = $request->boolean('is_stackable');
        $validated['auto_apply'] = $request->boolean('auto_apply');

        // Default nullable numeric fields to 0 (DB has NOT NULL constraint)
        $validated['min_order_amount'] = $validated['min_order_amount'] ?? 0;
        $validated['max_discount'] = $validated['max_discount'] ?? 0;
        $validated['usage_limit'] = $validated['usage_limit'] ?? 0;
        $validated['usage_per_user'] = $validated['usage_per_user'] ?? 0;

        // Build conditions for BOGO
        if ($request->input('type') === 'buy_x_get_y') {
            $validated['conditions'] = [
                'buy_qty' => (int) $request->input('conditions.buy_qty'),
                'get_qty' => (int) $request->input('conditions.get_qty'),
            ];
        } else {
            $validated['conditions'] = null;
        }

        // Clear arrays if not sent (unchecked)
        if (!$request->has('applicable_products')) {
            $validated['applicable_products'] = null;
        }
        if (!$request->has('applicable_categories')) {
            $validated['applicable_categories'] = null;
        }

        $coupon->update($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted successfully');
    }
}
