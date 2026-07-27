@php $isEdit = $influencer->exists; @endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="form-label">Full Name <span class="text-error-500">*</span></label>
        <input type="text" name="full_name" value="{{ old('full_name', $influencer->full_name) }}" class="form-input w-full" required>
        @error('full_name')<p class="text-xs text-error-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="form-label">Username <span class="text-error-500">*</span></label>
        <input type="text" name="username" value="{{ old('username', $influencer->username) }}" class="form-input w-full" required autocomplete="off">
        @error('username')<p class="text-xs text-error-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="form-label">Password @if($isEdit)<span class="text-neutral-400 text-xs font-normal">(leave blank to keep)</span>@else<span class="text-error-500">*</span>@endif</label>
        <input type="password" name="password" class="form-input w-full" @unless($isEdit) required @endunless autocomplete="new-password">
        @error('password')<p class="text-xs text-error-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="form-label">Email</label>
        <input type="email" name="email" value="{{ old('email', $influencer->email) }}" class="form-input w-full">
        @error('email')<p class="text-xs text-error-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="form-label">Mobile Number</label>
        <input type="text" name="mobile" value="{{ old('mobile', $influencer->mobile) }}" class="form-input w-full">
        @error('mobile')<p class="text-xs text-error-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="form-label">Coupon Code <span class="text-error-500">*</span></label>
        <div class="relative" x-data="influencerCouponPicker('{{ old('coupon_code', $influencer->coupon_code) }}', '{{ route('admin.influencers.coupon-suggestions') }}')" @click.outside="open = false">
            <input type="text" name="coupon_code" x-model="value" @focus="load(); open = true" @input="open = true"
                   autocomplete="off" class="form-input w-full" style="text-transform:uppercase" required>
            <div x-show="open" x-cloak
                 class="absolute z-30 mt-1 w-full bg-white ring-1 ring-neutral-200 rounded-lg shadow-lg max-h-56 overflow-auto">
                <template x-for="c in filtered" :key="c">
                    <button type="button" @click="pick(c)" class="block w-full text-left px-3 py-2 text-sm font-mono hover:bg-neutral-50" x-text="c"></button>
                </template>
                <div x-show="loaded && !filtered.length" class="px-3 py-2 text-xs text-neutral-400">No matching coupon — type to add a new one.</div>
                <div x-show="!loaded" class="px-3 py-2 text-xs text-neutral-400">Loading…</div>
            </div>
        </div>
        <p class="text-xs text-neutral-400 mt-1">Pick a coupon used on real orders, or type a new one. A % coupon with this code is auto-created &amp; kept in sync.</p>
        @error('coupon_code')<p class="text-xs text-error-600 mt-1">{{ $message }}</p>@enderror
    </div>

    @once
        @push('styles')<style>[x-cloak]{display:none!important}</style>@endpush
        @push('scripts')
        <script>
            window.influencerCouponPicker = function (initial, url) {
                return {
                    value: initial || '',
                    open: false,
                    all: [],
                    loaded: false,
                    get filtered() {
                        const q = (this.value || '').toUpperCase().trim();
                        const list = q ? this.all.filter(c => c.includes(q)) : this.all;
                        return list.slice(0, 50);
                    },
                    load() {
                        if (this.loaded) return; // cached for the page session
                        fetch(url, { headers: { 'Accept': 'application/json' } })
                            .then(r => r.json())
                            .then(d => { this.all = Array.isArray(d) ? d : []; this.loaded = true; })
                            .catch(() => {});
                    },
                    pick(c) { this.value = c; this.open = false; },
                };
            };
        </script>
        @endpush
    @endonce

    <div>
        <label class="form-label">Coupon Discount % <span class="text-error-500">*</span></label>
        <input type="number" step="0.01" min="0" max="100" name="coupon_discount" value="{{ old('coupon_discount', $influencer->coupon_discount ?? 10) }}" class="form-input w-full" required>
        <p class="text-xs text-neutral-400 mt-1">Discount customers receive when using this coupon.</p>
        @error('coupon_discount')<p class="text-xs text-error-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="form-label">Commission % <span class="text-neutral-400 text-xs font-normal">(what the influencer earns — optional)</span></label>
        <input type="number" step="0.01" min="0" max="100" name="commission_percentage" value="{{ old('commission_percentage', $influencer->commission_percentage) }}" class="form-input w-full">
        @error('commission_percentage')<p class="text-xs text-error-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="form-label">Instagram Handle</label>
        <input type="text" name="instagram" value="{{ old('instagram', $influencer->instagram) }}" class="form-input w-full" placeholder="@handle">
    </div>

    <div>
        <label class="form-label">YouTube Channel <span class="text-neutral-400 text-xs font-normal">(optional)</span></label>
        <input type="text" name="youtube" value="{{ old('youtube', $influencer->youtube) }}" class="form-input w-full">
    </div>

    <div>
        <label class="form-label">Status <span class="text-error-500">*</span></label>
        <select name="status" class="form-select w-full" required>
            <option value="active" @selected(old('status', $influencer->status) === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $influencer->status) === 'inactive')>Inactive</option>
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="form-label">Notes</label>
        <textarea name="notes" rows="3" class="form-textarea w-full">{{ old('notes', $influencer->notes) }}</textarea>
    </div>
</div>
