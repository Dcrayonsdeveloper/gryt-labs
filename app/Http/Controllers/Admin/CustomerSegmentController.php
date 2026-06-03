<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerSegment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerSegmentController extends Controller
{
    public function index(): View
    {
        $segments = CustomerSegment::orderBy('name')->get();

        return view('admin.customers.segments.index', compact('segments'));
    }

    public function show(CustomerSegment $segment, Request $request): View
    {
        $perPage = (int) $request->input('per_page', 25);
        $customers = $segment->matchingCustomersQuery()
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->latest('users.created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.customers.segments.show', compact('segment', 'customers'));
    }

    public function create(): View
    {
        return view('admin.customers.segments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'conditions' => 'required|array',
            'conditions.min_orders' => 'nullable|integer|min:0',
            'conditions.max_orders' => 'nullable|integer|min:0',
            'conditions.min_spent' => 'nullable|numeric|min:0',
            'conditions.min_avg_order' => 'nullable|numeric|min:0',
            'conditions.last_order_within_days' => 'nullable|integer|min:1',
            'conditions.no_order_in_days' => 'nullable|integer|min:1',
            'conditions.registered_within_days' => 'nullable|integer|min:1',
            'conditions.use_or' => 'nullable|boolean',
        ]);

        // Clean out null/empty condition values
        $conditions = array_filter($validated['conditions'] ?? [], fn ($v) => !is_null($v) && $v !== '');

        $segment = CustomerSegment::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'conditions' => $conditions,
            'is_auto' => true,
            'customer_count' => 0,
        ]);

        $segment->refreshCount();

        return redirect()->route('admin.customer-segments.index')
            ->with('success', 'Segment created successfully.');
    }
}
