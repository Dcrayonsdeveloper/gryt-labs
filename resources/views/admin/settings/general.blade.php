<x-layouts.admin>
    <x-slot name="title">General Settings</x-slot>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-neutral-900">Settings</h1>
        <p class="text-neutral-600">Manage your store configuration</p>
    </div>

    <!-- Settings Navigation -->
    @include('admin.settings.partials.nav', ['active' => 'general'])

    <form action="{{ route('admin.settings.general.update') }}" method="POST" data-track-changes>
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Store Information -->
            <div class="card">
                <div class="px-5 py-3.5 border-b border-neutral-200">
                    <h2 class="text-sm font-semibold text-neutral-900">Store Information</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="form-label form-label-required">Site Name</label>
                        <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name'] ?? '') }}" required class="form-input">
                        @error('site_name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Tagline</label>
                        <input type="text" name="site_tagline" value="{{ old('site_tagline', $settings['site_tagline'] ?? '') }}" class="form-input">
                        @error('site_tagline') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label form-label-required">Email Address</label>
                        <input type="email" name="site_email" value="{{ old('site_email', $settings['site_email'] ?? '') }}" required class="form-input">
                        @error('site_email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="site_phone" value="{{ old('site_phone', $settings['site_phone'] ?? '') }}" class="form-input">
                        @error('site_phone') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Address</label>
                        <textarea name="site_address" rows="3" class="form-textarea">{{ old('site_address', $settings['site_address'] ?? '') }}</textarea>
                        @error('site_address') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Regional Settings -->
            <div class="card">
                <div class="px-5 py-3.5 border-b border-neutral-200">
                    <h2 class="text-sm font-semibold text-neutral-900">Regional Settings</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="form-label form-label-required">Timezone</label>
                        <select name="timezone" required class="form-select">
                            @foreach(timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}" @selected(($settings['timezone'] ?? '') === $tz)>{{ $tz }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label form-label-required">Date Format</label>
                        <select name="date_format" required class="form-select">
                            <option value="M d, Y" @selected(($settings['date_format'] ?? 'M d, Y') === 'M d, Y')>{{ now()->format('M d, Y') }}</option>
                            <option value="d/m/Y" @selected(($settings['date_format'] ?? '') === 'd/m/Y')>{{ now()->format('d/m/Y') }}</option>
                            <option value="m/d/Y" @selected(($settings['date_format'] ?? '') === 'm/d/Y')>{{ now()->format('m/d/Y') }}</option>
                            <option value="Y-m-d" @selected(($settings['date_format'] ?? '') === 'Y-m-d')>{{ now()->format('Y-m-d') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label form-label-required">Currency</label>
                        <select name="currency" required class="form-select">
                            <option value="USD" @selected(($settings['currency'] ?? 'USD') === 'USD')>USD - US Dollar</option>
                            <option value="EUR" @selected(($settings['currency'] ?? '') === 'EUR')>EUR - Euro</option>
                            <option value="GBP" @selected(($settings['currency'] ?? '') === 'GBP')>GBP - British Pound</option>
                            <option value="INR" @selected(($settings['currency'] ?? '') === 'INR')>INR - Indian Rupee</option>
                            <option value="CAD" @selected(($settings['currency'] ?? '') === 'CAD')>CAD - Canadian Dollar</option>
                            <option value="AUD" @selected(($settings['currency'] ?? '') === 'AUD')>AUD - Australian Dollar</option>
                            <option value="JPY" @selected(($settings['currency'] ?? '') === 'JPY')>JPY - Japanese Yen</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label form-label-required">Currency Symbol</label>
                        <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '$') }}" required maxlength="5" class="form-input" placeholder="e.g. $, €, £, ₹, ¥">
                        <p class="text-xs text-neutral-600 mt-1">The symbol displayed with prices (e.g. $, €, £, ₹, ¥)</p>
                    </div>
                    <div>
                        <label class="form-label form-label-required">Currency Position</label>
                        <select name="currency_position" required class="form-select">
                            <option value="before" @selected(($settings['currency_position'] ?? 'before') === 'before')>Before amount ($99.99)</option>
                            <option value="after" @selected(($settings['currency_position'] ?? '') === 'after')>After amount (99.99$)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="btn btn-primary"
                    x-data="{ loading: false }"
                    @click="loading = true; setTimeout(() => loading = false, 5000)"
                    :disabled="loading">
                <span x-show="!loading">Save Settings</span>
                <span x-show="loading" x-cloak class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Saving...
                </span>
            </button>
        </div>
    </form>
</x-layouts.admin>
