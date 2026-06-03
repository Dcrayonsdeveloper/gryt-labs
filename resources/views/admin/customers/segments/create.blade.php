<x-layouts.admin>
    <x-slot name="title">Create Segment</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.customer-segments.index') }}" class="hover:text-primary-600">Segments</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">Create Segment</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Create Customer Segment</h1>
    </div>

    <form action="{{ route('admin.customer-segments.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Info --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Segment Details</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label for="name" class="form-label">Name <span class="text-error-500">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                   class="form-input w-full" placeholder="e.g. VIP Customers">
                            @error('name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" rows="2"
                                      class="form-input w-full" placeholder="Describe this segment...">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Conditions --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Conditions</h2>
                        <p class="text-xs text-gray-500 mt-1">Define the rules that determine which customers belong to this segment. All non-empty conditions must match (AND logic) unless OR is enabled.</p>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="conditions_min_orders" class="form-label">Minimum Orders</label>
                                <input type="number" name="conditions[min_orders]" id="conditions_min_orders"
                                       value="{{ old('conditions.min_orders') }}" min="0"
                                       class="form-input w-full" placeholder="e.g. 3">
                            </div>
                            <div>
                                <label for="conditions_max_orders" class="form-label">Maximum Orders</label>
                                <input type="number" name="conditions[max_orders]" id="conditions_max_orders"
                                       value="{{ old('conditions.max_orders') }}" min="0"
                                       class="form-input w-full" placeholder="e.g. 1">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="conditions_min_spent" class="form-label">Minimum Total Spent (INR)</label>
                                <input type="number" name="conditions[min_spent]" id="conditions_min_spent"
                                       value="{{ old('conditions.min_spent') }}" min="0" step="0.01"
                                       class="form-input w-full" placeholder="e.g. 5000">
                            </div>
                            <div>
                                <label for="conditions_min_avg_order" class="form-label">Minimum Avg Order Value (INR)</label>
                                <input type="number" name="conditions[min_avg_order]" id="conditions_min_avg_order"
                                       value="{{ old('conditions.min_avg_order') }}" min="0" step="0.01"
                                       class="form-input w-full" placeholder="e.g. 2000">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="conditions_last_order_within_days" class="form-label">Last Order Within (days)</label>
                                <input type="number" name="conditions[last_order_within_days]" id="conditions_last_order_within_days"
                                       value="{{ old('conditions.last_order_within_days') }}" min="1"
                                       class="form-input w-full" placeholder="e.g. 90">
                            </div>
                            <div>
                                <label for="conditions_no_order_in_days" class="form-label">No Order In (days)</label>
                                <input type="number" name="conditions[no_order_in_days]" id="conditions_no_order_in_days"
                                       value="{{ old('conditions.no_order_in_days') }}" min="1"
                                       class="form-input w-full" placeholder="e.g. 90">
                                <p class="text-xs text-gray-500 mt-1">Customer must have previous orders but none within this period.</p>
                            </div>
                        </div>

                        <div>
                            <label for="conditions_registered_within_days" class="form-label">Registered Within (days)</label>
                            <input type="number" name="conditions[registered_within_days]" id="conditions_registered_within_days"
                                   value="{{ old('conditions.registered_within_days') }}" min="1"
                                   class="form-input w-full max-w-xs" placeholder="e.g. 30">
                        </div>

                        <div class="pt-2 border-t border-gray-200">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="conditions[use_or]" value="1"
                                       {{ old('conditions.use_or') ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                <span class="text-sm text-gray-700">Use OR logic for min orders / min spent (match either condition)</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <div class="card">
                    <div class="p-4">
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                            Create Segment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
