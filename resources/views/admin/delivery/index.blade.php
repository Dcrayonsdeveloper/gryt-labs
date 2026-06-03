<x-layouts.admin>
    <x-slot name="title">Delivery Dashboard</x-slot>

    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold text-gray-900">Delivery Dashboard</h1>
        <div class="flex gap-2">
            <form action="{{ route('admin.delivery.pickup') }}" method="POST" class="inline" x-data>
                @csrf
                <input type="hidden" name="package_count" value="{{ $stats['pending'] }}">
                <button type="submit" class="btn btn-outline text-sm">Request Pickup</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">{{ session('error') }}</div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-amber-600">{{ $stats['pending'] }}</div>
            <div class="text-xs text-gray-600">Pending Booking</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $stats['shipped'] }}</div>
            <div class="text-xs text-gray-600">In Transit</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-purple-600">{{ $stats['out_for_delivery'] }}</div>
            <div class="text-xs text-gray-600">Out for Delivery</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $stats['delivered'] }}</div>
            <div class="text-xs text-gray-600">Delivered (7d)</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-red-600">{{ $stats['rto'] }}</div>
            <div class="text-xs text-gray-600">RTO (30d)</div>
        </div>
    </div>

    {{-- Analytics (30-day) --}}
    @if(isset($analytics))
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 mb-1">Total Shipped (30d)</div>
            <div class="text-xl font-bold text-gray-900">{{ $analytics['total_shipped'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 mb-1">Delivery Rate</div>
            <div class="text-xl font-bold {{ $analytics['delivery_rate'] >= 90 ? 'text-green-600' : ($analytics['delivery_rate'] >= 75 ? 'text-amber-600' : 'text-red-600') }}">{{ $analytics['delivery_rate'] }}%</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 mb-1">RTO Rate</div>
            <div class="text-xl font-bold {{ $analytics['rto_rate'] <= 5 ? 'text-green-600' : ($analytics['rto_rate'] <= 15 ? 'text-amber-600' : 'text-red-600') }}">{{ $analytics['rto_rate'] }}%</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs text-gray-500 mb-1">Avg Delivery Days</div>
            <div class="text-xl font-bold text-gray-900">{{ round($analytics['avg_delivery_days'], 1) }}</div>
        </div>
    </div>
    @endif

    {{-- Pincode Checker --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6" x-data="pincodeChecker()">
        <h2 class="font-semibold text-gray-900 mb-3">Pincode Serviceability & Cost Calculator</h2>
        <div class="flex gap-3 items-end">
            <div>
                <label class="text-xs font-medium text-gray-600">Delivery Pincode</label>
                <input type="text" x-model="pincode" maxlength="6" class="form-input w-40" placeholder="110001" @keyup.enter="check()">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600">Weight (grams)</label>
                <input type="number" x-model="weight" class="form-input w-28" value="500">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600">Payment</label>
                <select x-model="paymentType" class="form-select w-32">
                    <option value="Pre-paid">Prepaid</option>
                    <option value="COD">COD</option>
                </select>
            </div>
            <button @click="check()" class="btn btn-primary text-sm" :disabled="loading">
                <span x-show="!loading">Check</span>
                <span x-show="loading">Checking...</span>
            </button>
        </div>

        <div x-show="result" x-cloak class="mt-3 p-3 rounded-lg" :class="result?.serviceable ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
            <template x-if="result?.serviceable">
                <div class="text-sm">
                    <p class="font-medium text-green-700">Serviceable - <span x-text="result.city"></span>, <span x-text="result.state_code"></span></p>
                    <div class="flex gap-4 mt-1 text-xs text-gray-600">
                        <span>COD: <span x-text="result.cod ? 'Yes' : 'No'" :class="result.cod ? 'text-green-600 font-medium' : 'text-red-600'"></span></span>
                        <span>Prepaid: <span x-text="result.prepaid ? 'Yes' : 'No'" :class="result.prepaid ? 'text-green-600 font-medium' : 'text-red-600'"></span></span>
                        <span x-show="result.zone">Zone: <span x-text="result.zone" class="font-medium"></span></span>
                        <span x-show="result.estimated_cost">Est. Cost: <span x-text="'₹' + result.estimated_cost" class="font-bold text-gray-900"></span></span>
                    </div>
                </div>
            </template>
            <template x-if="result && !result.serviceable">
                <p class="text-sm text-red-700" x-text="result.message || 'Not serviceable'"></p>
            </template>
        </div>
    </div>

    {{-- Pending Orders — Ready to Ship --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Ready to Ship ({{ $stats['pending'] }})</h2>
            @if($stats['pending'] > 0 && count($activeProviders) > 0)
                <div class="flex gap-2">
                    @foreach($activeProviders as $provName => $prov)
                        <form action="{{ route('admin.delivery.bulk-book') }}" method="POST" class="inline" onsubmit="return confirm('Book ALL {{ $stats['pending'] }} pending orders via {{ $prov->label() }}?')">
                            @csrf
                            <input type="hidden" name="carrier" value="{{ $provName }}">
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg {{ $provName === 'bluedart' ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700' }} text-white">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Book All via {{ $prov->label() }}
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Order</th>
                        <th class="px-4 py-2 text-left">Customer</th>
                        <th class="px-4 py-2 text-left">Items</th>
                        <th class="px-4 py-2 text-left">Total</th>
                        <th class="px-4 py-2 text-left">Payment</th>
                        <th class="px-4 py-2 text-left">Date</th>
                        <th class="px-4 py-2 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pendingOrders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-primary-600 hover:underline">{{ $order->order_number }}</a>
                        </td>
                        <td class="px-4 py-2 text-gray-700">{{ $order->customer_name ?? $order->user?->full_name ?? 'Guest' }}</td>
                        <td class="px-4 py-2 text-gray-600">{{ $order->items->count() }} item(s)</td>
                        <td class="px-4 py-2 font-medium">@price($order->total)</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full {{ $order->payment_method === 'cod' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }}">
                                {{ strtoupper($order->payment_method) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $order->created_at->format('M d, h:i A') }}</td>
                        <td class="px-4 py-2 text-right">
                            <form action="{{ route('admin.delivery.book', $order) }}" method="POST" class="inline" onsubmit="return confirm('Book shipment for {{ $order->order_number }}?')">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25"/></svg>
                                    Book
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No orders pending shipment</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Shipped Orders — Track --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-200">
            <h2 class="font-semibold text-gray-900">In Transit ({{ $stats['shipped'] + $stats['out_for_delivery'] }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Order</th>
                        <th class="px-4 py-2 text-left">AWB</th>
                        <th class="px-4 py-2 text-left">Customer</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Shipped</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($shippedOrders as $order)
                    <tr class="hover:bg-gray-50" x-data="{ tracking: null, showTracking: false }">
                        <td class="px-4 py-2">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-primary-600 hover:underline">{{ $order->order_number }}</a>
                        </td>
                        <td class="px-4 py-2 font-mono text-xs text-gray-700">{{ $order->tracking_number }}</td>
                        <td class="px-4 py-2 text-gray-700">{{ $order->customer_name ?? $order->user?->full_name ?? 'Guest' }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full {{ $order->status === 'shipped' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $order->shipped_at?->format('M d') ?? '-' }}</td>
                        <td class="px-4 py-2 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="fetch('{{ route('admin.delivery.track', $order) }}').then(r=>r.json()).then(d=>{tracking=d;showTracking=true})"
                                        class="text-xs text-blue-600 hover:underline">Track</button>
                                <a href="{{ route('admin.delivery.label', $order) }}" class="text-xs text-gray-600 hover:underline">Label</a>
                                <form action="{{ route('admin.delivery.cancel', $order) }}" method="POST" class="inline" onsubmit="return confirm('Cancel this shipment?')">
                                    @csrf
                                    <button type="submit" class="text-xs text-red-500 hover:underline">Cancel</button>
                                </form>
                            </div>
                            {{-- Tracking details --}}
                            <div x-show="showTracking" x-cloak class="mt-2 text-left p-3 bg-gray-50 rounded-lg text-xs">
                                <template x-if="tracking?.success">
                                    <div>
                                        <p class="font-medium" x-text="'Status: ' + tracking.status"></p>
                                        <p class="text-gray-500" x-text="'Location: ' + tracking.status_location"></p>
                                        <p x-show="tracking.expected_date" class="text-gray-500" x-text="'Expected: ' + tracking.expected_date"></p>
                                        <template x-if="tracking.scans?.length">
                                            <div class="mt-2 space-y-1 max-h-40 overflow-y-auto">
                                                <template x-for="scan in tracking.scans" :key="scan.datetime">
                                                    <div class="flex gap-2">
                                                        <span class="text-gray-400 whitespace-nowrap" x-text="scan.datetime"></span>
                                                        <span x-text="scan.status + ' - ' + scan.location"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="tracking && !tracking.success">
                                    <p class="text-red-600" x-text="tracking.message"></p>
                                </template>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No shipments in transit</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
    <script>
    function pincodeChecker() {
        return {
            pincode: '', weight: 500, paymentType: 'Pre-paid', loading: false, result: null,
            async check() {
                if (this.pincode.length !== 6) return;
                this.loading = true;
                try {
                    const res = await fetch(`{{ route('admin.delivery.check-pincode') }}?pincode=${this.pincode}&weight=${this.weight}&payment_type=${this.paymentType}`);
                    this.result = await res.json();
                } catch(e) { this.result = { serviceable: false, message: 'Error' }; }
                this.loading = false;
            }
        };
    }
    </script>
    @endpush
</x-layouts.admin>
