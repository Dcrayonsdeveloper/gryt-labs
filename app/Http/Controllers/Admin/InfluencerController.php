<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\DbCompat;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Influencer;
use App\Models\Order;
use App\Services\InfluencerAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InfluencerController extends Controller
{
    /** List + global coupon analytics (per-influencer orders/sales/discount). */
    public function index(Request $request): View
    {
        $like = DbCompat::ilike();

        $query = Influencer::query()
            ->when($request->query('search'), function ($q, $search) use ($like) {
                $q->where(function ($x) use ($search, $like) {
                    foreach (['full_name', 'username', 'coupon_code', 'email', 'mobile'] as $col) {
                        $x->orWhere($col, $like, "%{$search}%");
                    }
                });
            })
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s));

        $influencers = $query->latest()->paginate(20)->withQueryString();

        // Optional date range for the per-row stats (doubles as global coupon analytics).
        $hasRange = $request->filled('range') || $request->filled('from') || $request->filled('to');
        $bounds = $hasRange
            ? InfluencerAnalyticsService::dateRange($request->query('range'), $request->query('from'), $request->query('to'))
            : null;

        $statsByCode = $this->statsByCouponCodes($influencers->pluck('coupon_code'), $bounds);

        $totals = [
            'total'    => Influencer::count(),
            'active'   => Influencer::where('status', 'active')->count(),
            'inactive' => Influencer::where('status', 'inactive')->count(),
        ];

        return view('admin.influencers.index', [
            'influencers' => $influencers,
            'statsByCode' => $statsByCode,
            'totals'      => $totals,
            'search'      => $request->query('search'),
            'status'      => $request->query('status'),
            'range'       => $request->query('range'),
            'from'        => $request->query('from'),
            'to'          => $request->query('to'),
        ]);
    }

    public function create(): View
    {
        return view('admin.influencers.create', [
            'influencer' => new Influencer(['status' => 'active', 'coupon_discount' => 10]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $influencer = Influencer::create($data); // password auto-hashed via model cast
            $this->syncCoupon($influencer);
        });

        return redirect()->route('admin.influencers.index')->with('success', 'Influencer created and coupon activated.');
    }

    public function edit(Influencer $influencer): View
    {
        return view('admin.influencers.edit', compact('influencer'));
    }

    public function update(Request $request, Influencer $influencer): RedirectResponse
    {
        $data = $this->validated($request, $influencer);

        if (empty($data['password'])) {
            unset($data['password']); // password optional on edit
        }

        $oldCode = $influencer->coupon_code;

        DB::transaction(function () use ($influencer, $data, $oldCode) {
            $influencer->update($data);
            $this->syncCoupon($influencer, $oldCode);
        });

        return redirect()->route('admin.influencers.index')->with('success', 'Influencer updated.');
    }

    public function destroy(Influencer $influencer): RedirectResponse
    {
        // Deactivate the coupon (keep it — historical orders still reference it), then delete the account.
        Coupon::where('code', $influencer->coupon_code)->update(['is_active' => false]);
        $influencer->delete();

        return back()->with('success', 'Influencer deleted.');
    }

    public function toggleStatus(Influencer $influencer): RedirectResponse
    {
        $influencer->update(['status' => $influencer->isActive() ? 'inactive' : 'active']);
        Coupon::where('code', $influencer->coupon_code)->update(['is_active' => $influencer->isActive()]);

        return back()->with('success', 'Influencer ' . ($influencer->isActive() ? 'enabled' : 'disabled') . '.');
    }

    public function resetPassword(Request $request, Influencer $influencer): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string', 'min:6', 'max:100']]);
        $influencer->update(['password' => $request->input('password')]); // auto-hashed

        return back()->with('success', "Password reset for {$influencer->username}.");
    }

    /** Detailed analytics for one influencer (admin view). */
    public function analytics(Request $request, Influencer $influencer): View
    {
        [$start, $end] = InfluencerAnalyticsService::dateRange(
            $request->query('range'), $request->query('from'), $request->query('to')
        );

        $scoped = $influencer->ordersQuery();
        $a = InfluencerAnalyticsService::compute($scoped, $start, $end);

        $orders = (clone $scoped)
            ->whereBetween('created_at', [$start, $end])
            ->with('user')->latest()->paginate(20)->withQueryString();

        return view('admin.influencers.analytics', [
            'influencer' => $influencer,
            'a'          => $a,
            'orders'     => $orders,
            'range'      => $request->query('range', '30days'),
            'from'       => $request->query('from'),
            'to'         => $request->query('to'),
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    private function validated(Request $request, ?Influencer $influencer = null): array
    {
        $id = $influencer?->id;

        $data = $request->validate([
            'full_name'             => ['required', 'string', 'max:150'],
            'username'              => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('influencers', 'username')->ignore($id)],
            'password'              => [$influencer ? 'nullable' : 'required', 'string', 'min:6', 'max:100'],
            'email'                 => ['nullable', 'email', 'max:150'],
            'mobile'                => ['nullable', 'string', 'max:20'],
            'coupon_code'           => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('influencers', 'coupon_code')->ignore($id)],
            'coupon_discount'       => ['required', 'numeric', 'min:0', 'max:100'],
            'instagram'             => ['nullable', 'string', 'max:150'],
            'youtube'               => ['nullable', 'string', 'max:200'],
            'commission_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'                 => ['nullable', 'string', 'max:2000'],
            'status'                => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $data['coupon_code'] = strtoupper($data['coupon_code']);

        return $data;
    }

    /** Auto-create / update the discount coupon linked to this influencer. */
    private function syncCoupon(Influencer $influencer, ?string $oldCode = null): void
    {
        // Code changed — retire the old coupon (don't delete: past orders reference it).
        if ($oldCode && $oldCode !== $influencer->coupon_code) {
            Coupon::where('code', $oldCode)->update(['is_active' => false]);
        }

        Coupon::updateOrCreate(
            ['code' => $influencer->coupon_code],
            [
                'name'      => $influencer->full_name . ' (Influencer)',
                'type'      => 'percentage',
                'value'     => $influencer->coupon_discount,
                'is_active' => $influencer->isActive(),
            ]
        );
    }

    /**
     * Aggregate orders/sales/discount per coupon code in one query.
     * @param  \Illuminate\Support\Collection  $codes
     * @param  array|null  $bounds  [Carbon $start, Carbon $end] to scope by date
     * @return array<string, array{orders:int,sales:float,discount:float}>
     */
    private function statsByCouponCodes($codes, ?array $bounds = null): array
    {
        $codes = collect($codes)->filter()->unique()->values();
        if ($codes->isEmpty()) {
            return [];
        }

        $codeToId = Coupon::whereIn('code', $codes)->pluck('id', 'code'); // [code => id]
        if ($codeToId->isEmpty()) {
            return [];
        }
        $idToCode = $codeToId->flip();

        $agg = Order::whereIn('coupon_id', $codeToId->values())
            ->when($bounds, fn ($q) => $q->whereBetween('created_at', $bounds))
            ->selectRaw('coupon_id, COUNT(*) as orders, COALESCE(SUM(total),0) as sales, COALESCE(SUM(discount),0) as discount')
            ->groupBy('coupon_id')
            ->get();

        $out = [];
        foreach ($agg as $row) {
            $code = $idToCode[$row->coupon_id] ?? null;
            if ($code !== null) {
                $out[$code] = [
                    'orders'   => (int) $row->orders,
                    'sales'    => (float) $row->sales,
                    'discount' => (float) $row->discount,
                ];
            }
        }

        return $out;
    }
}
