<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerSegment extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'conditions',
        'is_auto',
        'customer_count',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'is_auto' => 'boolean',
            'customer_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CustomerSegment $segment) {
            if (empty($segment->slug)) {
                $segment->slug = Str::slug($segment->name);
            }
        });
    }

    /**
     * Evaluate whether a user matches this segment's conditions.
     */
    public function evaluateCustomer(User $user): bool
    {
        $conditions = $this->conditions ?? [];

        if (empty($conditions)) {
            return false;
        }

        // min_orders: user must have at least X orders
        if (isset($conditions['min_orders'])) {
            $orderCount = $user->orders()->whereNotIn('status', ['cancelled'])->count();
            if ($orderCount < $conditions['min_orders']) {
                return false;
            }
        }

        // max_orders: user must have at most X orders
        if (isset($conditions['max_orders'])) {
            $orderCount = $user->orders()->whereNotIn('status', ['cancelled'])->count();
            if ($orderCount > $conditions['max_orders']) {
                return false;
            }
        }

        // min_spent: user must have spent at least X total
        if (isset($conditions['min_spent'])) {
            $totalSpent = $user->orders()
                ->where('payment_status', 'paid')
                ->whereNotIn('status', ['cancelled', 'returned'])
                ->sum('total');
            if ($totalSpent < $conditions['min_spent']) {
                return false;
            }
        }

        // min_avg_order: user's average order value must be at least X
        if (isset($conditions['min_avg_order'])) {
            $avg = $user->orders()
                ->where('payment_status', 'paid')
                ->whereNotIn('status', ['cancelled', 'returned'])
                ->avg('total');
            if (!$avg || $avg < $conditions['min_avg_order']) {
                return false;
            }
        }

        // last_order_within_days: user must have placed an order within X days
        if (isset($conditions['last_order_within_days'])) {
            $lastOrder = $user->orders()->whereNotIn('status', ['cancelled'])->latest()->first();
            if (!$lastOrder || $lastOrder->created_at->lt(now()->subDays($conditions['last_order_within_days']))) {
                return false;
            }
        }

        // no_order_in_days: user must NOT have ordered in X days (but has previous orders)
        if (isset($conditions['no_order_in_days'])) {
            $lastOrder = $user->orders()->whereNotIn('status', ['cancelled'])->latest()->first();
            if (!$lastOrder) {
                return false; // No orders at all — doesn't qualify
            }
            if ($lastOrder->created_at->gt(now()->subDays($conditions['no_order_in_days']))) {
                return false; // Ordered too recently
            }
        }

        // registered_within_days: user registered within X days
        if (isset($conditions['registered_within_days'])) {
            if ($user->created_at->lt(now()->subDays($conditions['registered_within_days']))) {
                return false;
            }
        }

        // Use OR logic for min_orders/min_spent when use_or is set
        if (!empty($conditions['use_or']) && isset($conditions['min_orders']) && isset($conditions['min_spent'])) {
            $orderCount = $user->orders()->whereNotIn('status', ['cancelled'])->count();
            $totalSpent = $user->orders()
                ->where('payment_status', 'paid')
                ->whereNotIn('status', ['cancelled', 'returned'])
                ->sum('total');

            // Already passed individual checks above (AND logic), but for OR we re-check
            // This is handled by returning true if either condition matches
            return $orderCount >= $conditions['min_orders'] || $totalSpent >= $conditions['min_spent'];
        }

        return true;
    }

    /**
     * Get the query builder for customers matching this segment.
     * Single-SQL implementation — safe for unlimited customer counts.
     */
    public function matchingCustomersQuery(): Builder
    {
        $c = $this->conditions ?? [];

        $orderStats = DB::table('orders')
            ->select('user_id')
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw("SUM(CASE WHEN payment_status = 'paid' AND status NOT IN ('cancelled', 'returned') THEN total ELSE 0 END) as paid_total")
            ->selectRaw("AVG(CASE WHEN payment_status = 'paid' AND status NOT IN ('cancelled', 'returned') THEN total END) as paid_avg")
            ->selectRaw('MAX(created_at) as last_order_at')
            ->whereNotIn('status', ['cancelled'])
            ->groupBy('user_id');

        $query = User::query()
            ->where('role', 'customer')
            ->where('is_active', true)
            ->leftJoinSub($orderStats, 'order_stats', 'order_stats.user_id', '=', 'users.id')
            ->select('users.*');

        if (empty($c)) {
            // No conditions set — match nothing (same behaviour as the old PHP filter)
            return $query->whereRaw('1 = 0');
        }

        if (isset($c['min_orders'])) {
            $query->whereRaw('COALESCE(order_stats.total_orders, 0) >= ?', [(int) $c['min_orders']]);
        }

        if (isset($c['max_orders'])) {
            $query->whereRaw('COALESCE(order_stats.total_orders, 0) <= ?', [(int) $c['max_orders']]);
        }

        if (isset($c['min_spent'])) {
            $query->whereRaw('COALESCE(order_stats.paid_total, 0) >= ?', [(float) $c['min_spent']]);
        }

        if (isset($c['min_avg_order'])) {
            $query->whereRaw('COALESCE(order_stats.paid_avg, 0) >= ?', [(float) $c['min_avg_order']]);
        }

        if (isset($c['last_order_within_days'])) {
            $query->where('order_stats.last_order_at', '>=', now()->subDays((int) $c['last_order_within_days']));
        }

        if (isset($c['no_order_in_days'])) {
            $query->whereNotNull('order_stats.last_order_at')
                ->where('order_stats.last_order_at', '<', now()->subDays((int) $c['no_order_in_days']));
        }

        if (isset($c['registered_within_days'])) {
            $query->where('users.created_at', '>=', now()->subDays((int) $c['registered_within_days']));
        }

        return $query;
    }

    /**
     * Refresh the cached customer count using SQL.
     */
    public function refreshCount(): int
    {
        $count = $this->matchingCustomersQuery()->count();
        $this->update(['customer_count' => $count]);

        return $count;
    }
}
