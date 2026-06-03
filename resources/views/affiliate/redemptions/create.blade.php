<x-layouts.affiliate :title="$title" :breadcrumbs="$breadcrumbs">
    <div class="max-w-2xl">
        <!-- Available Balance -->
        <div class="card p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-neutral-600">Available Balance</p>
                    <p class="text-3xl font-bold text-success-600 mt-1">@price($affiliate->available_balance)</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-neutral-500">Minimum redemption</p>
                    <p class="text-sm font-medium text-neutral-700">@price($minimumRedemption)</p>
                </div>
            </div>
        </div>

        @if($affiliate->available_balance < $minimumRedemption)
            <div class="card p-6">
                <div class="text-center py-8">
                    <svg class="w-16 h-16 text-neutral-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-neutral-900 mb-2">Minimum Balance Not Reached</h3>
                    <p class="text-sm text-neutral-600">You need at least @price($minimumRedemption) to redeem. Keep sharing your link to earn more!</p>
                </div>
            </div>
        @else
            <!-- Redemption Form -->
            <div class="card p-6">
                <h3 class="font-semibold text-neutral-900 mb-6">Request Payout</h3>

                <form method="POST" action="{{ route('affiliate.redemptions.store') }}" class="space-y-6">
                    @csrf

                    <!-- Amount -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-neutral-700 mb-1">Amount (INR)</label>
                        <input type="number" name="amount" id="amount" value="{{ old('amount', $affiliate->available_balance) }}"
                               min="{{ $minimumRedemption }}" max="{{ $affiliate->available_balance }}" step="0.01" required
                               class="form-input w-full @error('amount') border-error-300 @enderror">
                        @error('amount')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-neutral-500">Minimum: @price($minimumRedemption) | Maximum: @price($affiliate->available_balance)</p>
                    </div>

                    <!-- Payout Method -->
                    <div>
                        <label for="payout_method" class="block text-sm font-medium text-neutral-700 mb-1">Payout Method</label>
                        <select name="payout_method" id="payout_method" required
                                class="form-input w-full @error('payout_method') border-error-300 @enderror">
                            <option value="">Select method</option>
                            <option value="bank_transfer" {{ old('payout_method', $affiliate->payout_method) === 'bank_transfer' ? 'selected' : '' }}
                                {{ empty($affiliate->bank_details) ? 'disabled' : '' }}>
                                Bank Transfer {{ empty($affiliate->bank_details) ? '(Not configured)' : '' }}
                            </option>
                            <option value="upi" {{ old('payout_method', $affiliate->payout_method) === 'upi' ? 'selected' : '' }}
                                {{ empty($affiliate->upi_id) ? 'disabled' : '' }}>
                                UPI {{ empty($affiliate->upi_id) ? '(Not configured)' : '' }}
                            </option>
                        </select>
                        @error('payout_method')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                        @if(empty($affiliate->bank_details) && empty($affiliate->upi_id))
                            <p class="mt-1 text-sm text-warning-600">
                                Please <a href="{{ route('affiliate.settings.index') }}" class="underline">add your payout details</a> in Settings first.
                            </p>
                        @endif
                    </div>

                    <!-- Info Box -->
                    <div class="bg-info-50 border border-info-200 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-info-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-info-800">
                                <p class="font-medium">Processing Time</p>
                                <p class="mt-1">Payouts are processed within <strong>2 working days</strong>. You will receive the amount in your selected payout method.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-4">
                        <button type="submit" class="btn-primary">
                            Submit Redemption Request
                        </button>
                        <a href="{{ route('affiliate.redemptions.index') }}" class="text-sm text-neutral-600 hover:text-neutral-900">Cancel</a>
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-layouts.affiliate>
