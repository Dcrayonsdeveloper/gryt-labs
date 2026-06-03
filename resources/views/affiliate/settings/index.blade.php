<x-layouts.affiliate :title="$title">
    <div class="max-w-2xl space-y-8">
        <!-- Referral Link -->
        <div class="card p-6" x-data="{ copied: false }">
            <h3 class="font-semibold text-neutral-900 mb-4">Your Referral Link</h3>
            <div class="flex items-center gap-3">
                <input type="text" readonly value="{{ $affiliate->getReferralUrl() }}"
                       class="form-input flex-1 bg-neutral-50 text-sm">
                <button @click="navigator.clipboard.writeText('{{ $affiliate->getReferralUrl() }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="btn-primary whitespace-nowrap">
                    <span x-show="!copied">Copy</span>
                    <span x-show="copied" x-cloak>Copied!</span>
                </button>
            </div>
            <p class="text-xs text-neutral-500 mt-2">Referral Code: <code class="bg-neutral-100 px-1.5 py-0.5 rounded">{{ $affiliate->referral_code }}</code></p>
        </div>

        <!-- Profile Settings -->
        <div class="card p-6">
            <h3 class="font-semibold text-neutral-900 mb-4">Profile</h3>
            <form method="POST" action="{{ route('affiliate.settings.profile') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Name</label>
                    <input type="text" disabled value="{{ $user->full_name }}"
                           class="form-input w-full bg-neutral-50">
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Email</label>
                    <input type="text" disabled value="{{ $user->email }}"
                           class="form-input w-full bg-neutral-50">
                </div>

                <div>
                    <label for="channel" class="block text-sm font-medium text-neutral-700 mb-1">Primary Platform</label>
                    <select name="channel" id="channel" class="form-input w-full @error('channel') border-error-300 @enderror">
                        <option value="youtube" {{ $affiliate->channel === 'youtube' ? 'selected' : '' }}>YouTube</option>
                        <option value="instagram" {{ $affiliate->channel === 'instagram' ? 'selected' : '' }}>Instagram</option>
                        <option value="tiktok" {{ $affiliate->channel === 'tiktok' ? 'selected' : '' }}>TikTok</option>
                        <option value="blog" {{ $affiliate->channel === 'blog' ? 'selected' : '' }}>Blog / Website</option>
                        <option value="other" {{ $affiliate->channel === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('channel')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="channel_url" class="block text-sm font-medium text-neutral-700 mb-1">Channel / Profile URL</label>
                    <input type="url" name="channel_url" id="channel_url" value="{{ old('channel_url', $affiliate->channel_url) }}"
                           class="form-input w-full @error('channel_url') border-error-300 @enderror"
                           placeholder="https://instagram.com/yourhandle">
                    @error('channel_url')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="bio" class="block text-sm font-medium text-neutral-700 mb-1">Bio</label>
                    <textarea name="bio" id="bio" rows="3"
                              class="form-input w-full @error('bio') border-error-300 @enderror">{{ old('bio', $affiliate->bio) }}</textarea>
                    @error('bio')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-primary">Save Profile</button>
            </form>
        </div>

        <!-- Payout Settings -->
        <div class="card p-6" x-data="{ method: '{{ old('payout_method', $affiliate->payout_method ?? 'bank_transfer') }}' }">
            <h3 class="font-semibold text-neutral-900 mb-4">Payout Settings</h3>
            <form method="POST" action="{{ route('affiliate.settings.payout') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="payout_method" class="block text-sm font-medium text-neutral-700 mb-1">Payout Method</label>
                    <select name="payout_method" id="payout_method" x-model="method"
                            class="form-input w-full @error('payout_method') border-error-300 @enderror">
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="upi">UPI</option>
                    </select>
                    @error('payout_method')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Bank Details -->
                <div x-show="method === 'bank_transfer'" x-transition class="space-y-4">
                    <div>
                        <label for="bank_holder_name" class="block text-sm font-medium text-neutral-700 mb-1">Account Holder Name</label>
                        <input type="text" name="bank_holder_name" id="bank_holder_name"
                               value="{{ old('bank_holder_name', $affiliate->bank_details['holder_name'] ?? '') }}"
                               class="form-input w-full @error('bank_holder_name') border-error-300 @enderror">
                        @error('bank_holder_name')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="bank_name" class="block text-sm font-medium text-neutral-700 mb-1">Bank Name</label>
                        <input type="text" name="bank_name" id="bank_name"
                               value="{{ old('bank_name', $affiliate->bank_details['bank_name'] ?? '') }}"
                               class="form-input w-full @error('bank_name') border-error-300 @enderror">
                        @error('bank_name')
                            <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="bank_account" class="block text-sm font-medium text-neutral-700 mb-1">Account Number</label>
                            <input type="text" name="bank_account" id="bank_account"
                                   value="{{ old('bank_account', $affiliate->bank_details['account_number'] ?? '') }}"
                                   class="form-input w-full @error('bank_account') border-error-300 @enderror">
                            @error('bank_account')
                                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="bank_ifsc" class="block text-sm font-medium text-neutral-700 mb-1">IFSC Code</label>
                            <input type="text" name="bank_ifsc" id="bank_ifsc"
                                   value="{{ old('bank_ifsc', $affiliate->bank_details['ifsc_code'] ?? '') }}"
                                   class="form-input w-full @error('bank_ifsc') border-error-300 @enderror">
                            @error('bank_ifsc')
                                <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- UPI Details -->
                <div x-show="method === 'upi'" x-transition>
                    <label for="upi_id" class="block text-sm font-medium text-neutral-700 mb-1">UPI ID</label>
                    <input type="text" name="upi_id" id="upi_id"
                           value="{{ old('upi_id', $affiliate->upi_id) }}"
                           class="form-input w-full @error('upi_id') border-error-300 @enderror"
                           placeholder="yourname@paytm">
                    @error('upi_id')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-primary">Save Payout Settings</button>
            </form>
        </div>
    </div>
</x-layouts.affiliate>
