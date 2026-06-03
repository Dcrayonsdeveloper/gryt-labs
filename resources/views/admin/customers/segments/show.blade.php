<x-layouts.admin>
    <x-slot name="title">{{ $segment->name }}</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.customer-segments.index') }}" class="hover:text-primary-600">Segments</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">{{ $segment->name }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-neutral-900">{{ $segment->name }}</h1>
            <span class="text-sm text-gray-500">{{ $customers->count() }} customers</span>
        </div>
        @if($segment->description)
            <p class="text-sm text-gray-500 mt-1">{{ $segment->description }}</p>
        @endif
    </div>

    {{-- Conditions --}}
    @if($segment->conditions)
        <div class="mb-6 flex flex-wrap gap-2">
            @foreach($segment->conditions as $key => $value)
                @if($key !== 'use_or')
                    <span class="inline-flex px-3 py-1 text-sm rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                        {{ str_replace('_', ' ', $key) }}: {{ $value }}
                    </span>
                @endif
            @endforeach
            @if(!empty($segment->conditions['use_or']))
                <span class="inline-flex px-3 py-1 text-sm rounded-full bg-yellow-50 text-yellow-700 border border-yellow-200">
                    OR logic
                </span>
            @endif
        </div>
    @endif

    {{-- Customer Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="border-bottom:1px solid #e1e1e1">
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Orders</th>
                        <th class="px-4 py-3">Total Spent</th>
                        <th class="px-4 py-3">Joined</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium text-gray-900 hover:text-blue-600">
                                    {{ $customer->first_name }} {{ $customer->last_name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $customer->email }}</td>
                            <td class="px-4 py-3 text-gray-900">{{ $customer->orders_count }}</td>
                            <td class="px-4 py-3 text-gray-900">{{ number_format($customer->orders_sum_total ?? 0, 0) }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $customer->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No customers match this segment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.admin>
