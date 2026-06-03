<x-layouts.admin>
    <x-slot name="title">Fraud Case #{{ $fraudLog->id }}</x-slot>

    <!-- Breadcrumb & Back -->
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.fraud.index') }}" class="hover:text-primary-600">Fraud Review</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">Case #{{ $fraudLog->id }}</span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-neutral-900">Fraud Case Details</h1>
            <a href="{{ route('admin.fraud.index') }}" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Queue
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Order Details -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Order Details</h2>
                </div>
                <div class="p-4 space-y-3 text-sm">
                    @if($fraudLog->order)
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Order Number</span>
                            <a href="{{ route('admin.orders.show', $fraudLog->order_id) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                {{ $fraudLog->order->order_number }}
                            </a>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Order Date</span>
                            <span class="font-medium text-neutral-900">{{ $fraudLog->order->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Order Total</span>
                            <span class="font-medium text-neutral-900">@price($fraudLog->order->total)</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Payment Method</span>
                            <span class="font-medium text-neutral-900">{{ ucfirst($fraudLog->order->payment_method ?? '-') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Order Status</span>
                            <span class="font-medium text-neutral-900">{{ ucfirst($fraudLog->order->status ?? '-') }}</span>
                        </div>
                    @else
                        <p class="text-neutral-600">Order has been deleted</p>
                    @endif
                </div>
            </div>

            <!-- Customer Info -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Customer Information</h2>
                </div>
                <div class="p-4 space-y-3 text-sm">
                    @if($fraudLog->user)
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Name</span>
                            <span class="font-medium text-neutral-900">{{ $fraudLog->user->full_name ?? $fraudLog->user->first_name . ' ' . $fraudLog->user->last_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Email</span>
                            <span class="font-medium text-neutral-900">{{ $fraudLog->user->email }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Phone</span>
                            <span class="font-medium text-neutral-900">{{ $fraudLog->user->phone ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Member Since</span>
                            <span class="font-medium text-neutral-900">{{ $fraudLog->user->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">IP Address</span>
                            <span class="font-mono text-xs bg-neutral-100 text-neutral-600 px-2 py-0.5 rounded">{{ $fraudLog->ip_address ?? '-' }}</span>
                        </div>
                    @else
                        <p class="text-neutral-600">Customer has been deleted</p>
                    @endif
                </div>
            </div>

            <!-- Fraud Indicators -->
            <div class="card overflow-hidden">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Fraud Indicators</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-neutral-50 border-b border-neutral-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">Check</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-600 uppercase tracking-wide">Score</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-600 uppercase tracking-wide">Max</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @forelse($fraudLog->indicators ?? [] as $indicator)
                                <tr class="hover:bg-neutral-50/60 transition-colors">
                                    <td class="px-4 py-3">
                                        <span class="text-sm font-medium text-neutral-900">{{ $indicator['name'] ?? '-' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @php
                                            $indicatorScore = $indicator['score'] ?? 0;
                                            $indicatorMax = $indicator['max'] ?? 100;
                                            $indicatorPct = $indicatorMax > 0 ? ($indicatorScore / $indicatorMax) * 100 : 0;
                                            if ($indicatorPct >= 75) {
                                                $indicatorColor = 'text-error-600 font-bold';
                                            } elseif ($indicatorPct >= 50) {
                                                $indicatorColor = 'text-warning-600 font-semibold';
                                            } else {
                                                $indicatorColor = 'text-neutral-700';
                                            }
                                        @endphp
                                        <span class="text-sm {{ $indicatorColor }}">{{ $indicatorScore }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-neutral-600">
                                        {{ $indicatorMax }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-neutral-600">
                                        {{ $indicator['detail'] ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-neutral-600">No fraud indicators recorded</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- User's Past Fraud Logs -->
            <div class="card overflow-hidden">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Customer Fraud History</h2>
                </div>
                @if($userHistory->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-neutral-50 border-b border-neutral-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-600 uppercase tracking-wide">Order</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-600 uppercase tracking-wide">Risk Score</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-600 uppercase tracking-wide">Action</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-neutral-600 uppercase tracking-wide"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-100">
                                @foreach($userHistory as $history)
                                    <tr class="hover:bg-neutral-50/60 transition-colors {{ $history->id === $fraudLog->id ? 'bg-primary-50/50' : '' }}">
                                        <td class="px-4 py-3 text-sm text-neutral-600">
                                            {{ $history->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if($history->order)
                                                <span class="font-medium text-neutral-900">{{ $history->order->order_number }}</span>
                                            @else
                                                <span class="text-neutral-600">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @php
                                                $hScore = $history->risk_score ?? 0;
                                                if ($hScore >= 75) {
                                                    $hScoreClass = 'bg-error-100 text-error-700';
                                                } elseif ($hScore >= 50) {
                                                    $hScoreClass = 'bg-warning-100 text-warning-700';
                                                } elseif ($hScore >= 25) {
                                                    $hScoreClass = 'bg-info-100 text-info-700';
                                                } else {
                                                    $hScoreClass = 'bg-success-100 text-success-700';
                                                }
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $hScoreClass }}">
                                                {{ $hScore }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @php
                                                $hActionClass = match($history->action ?? '') {
                                                    'flagged' => 'badge-warning',
                                                    'blocked' => 'badge-error',
                                                    'allowed' => 'badge-success',
                                                    default => 'badge-neutral',
                                                };
                                            @endphp
                                            <span class="badge {{ $hActionClass }}">
                                                {{ ucfirst($history->action ?? 'Pending') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if($history->id !== $fraudLog->id)
                                                <a href="{{ route('admin.fraud.show', $history) }}" class="text-primary-600 hover:text-primary-700 text-xs font-medium">View</a>
                                            @else
                                                <span class="text-xs text-neutral-600">Current</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-8 text-center text-neutral-600 text-sm">
                        No previous fraud history for this customer.
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Risk Score -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Risk Score</h2>
                </div>
                <div class="p-6 text-center">
                    @php
                        $riskScore = $fraudLog->risk_score ?? 0;
                        if ($riskScore >= 75) {
                            $riskColor = 'text-error-600';
                            $riskBg = 'bg-error-100';
                            $riskLabel = 'High Risk';
                        } elseif ($riskScore >= 50) {
                            $riskColor = 'text-warning-600';
                            $riskBg = 'bg-warning-100';
                            $riskLabel = 'Medium Risk';
                        } elseif ($riskScore >= 25) {
                            $riskColor = 'text-info-600';
                            $riskBg = 'bg-info-100';
                            $riskLabel = 'Low Risk';
                        } else {
                            $riskColor = 'text-success-600';
                            $riskBg = 'bg-success-100';
                            $riskLabel = 'Minimal Risk';
                        }
                    @endphp
                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full {{ $riskBg }} mb-3">
                        <span class="text-4xl font-bold {{ $riskColor }}">{{ $riskScore }}</span>
                    </div>
                    <p class="text-sm font-semibold {{ $riskColor }}">{{ $riskLabel }}</p>
                    <p class="text-xs text-neutral-600 mt-1">out of 100</p>
                </div>
            </div>

            <!-- Current Status -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Status</h2>
                </div>
                <div class="p-4 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Fraud Type</span>
                        <span class="font-medium text-neutral-900">{{ ucfirst(str_replace('_', ' ', $fraudLog->type ?? '-')) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Current Action</span>
                        @php
                            $currentActionClass = match($fraudLog->action ?? '') {
                                'flagged' => 'badge-warning',
                                'blocked' => 'badge-error',
                                'allowed' => 'badge-success',
                                default => 'badge-neutral',
                            };
                        @endphp
                        <span class="badge {{ $currentActionClass }}">
                            {{ ucfirst($fraudLog->action ?? 'Pending') }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Reviewed</span>
                        <span class="font-medium text-neutral-900">{{ $fraudLog->reviewed_at ? 'Yes' : 'No' }}</span>
                    </div>
                    @if($fraudLog->reviewed_at)
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Reviewed At</span>
                            <span class="font-medium text-neutral-900">{{ $fraudLog->reviewed_at->format('M d, Y h:i A') }}</span>
                        </div>
                    @endif
                    @if($fraudLog->reviewed_by)
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Reviewed By</span>
                            <span class="font-medium text-neutral-900">{{ $fraudLog->reviewer->name ?? 'Admin #' . $fraudLog->reviewed_by }}</span>
                        </div>
                    @endif
                    @if($fraudLog->notes)
                        <div class="pt-2 border-t border-neutral-100">
                            <p class="text-neutral-600 mb-1">Notes</p>
                            <p class="text-neutral-700">{{ $fraudLog->notes }}</p>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Logged At</span>
                        <span class="font-medium text-neutral-900">{{ $fraudLog->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                </div>
            </div>

            <!-- Review Form -->
            <div class="card">
                <div class="p-4 border-b border-neutral-200">
                    <h2 class="font-semibold text-neutral-900">Review Action</h2>
                </div>
                <form action="{{ route('admin.fraud.review', $fraudLog) }}" method="POST" class="p-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="form-label form-label-required">Action</label>
                        <select name="action" class="form-input w-full" required>
                            <option value="">Select action...</option>
                            <option value="allowed" {{ old('action', $fraudLog->action) === 'allowed' ? 'selected' : '' }}>Allowed</option>
                            <option value="flagged" {{ old('action', $fraudLog->action) === 'flagged' ? 'selected' : '' }}>Flagged</option>
                            <option value="blocked" {{ old('action', $fraudLog->action) === 'blocked' ? 'selected' : '' }}>Blocked</option>
                        </select>
                        @error('action')
                            <p class="mt-1 text-xs text-error-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="4" class="form-input w-full" placeholder="Add review notes...">{{ old('notes', $fraudLog->notes) }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-xs text-error-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-full">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Submit Review
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>
