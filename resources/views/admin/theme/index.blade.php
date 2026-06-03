<x-layouts.admin>
    <x-slot name="title">Theme Customizer</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-bold text-neutral-800">Theme Customizer</h1>
        </div>
    </x-slot>

<div x-data="themeCustomizer()" class="flex gap-6" style="height:calc(100vh - 140px)">

    {{-- Left Panel: Settings --}}
    <div class="w-80 shrink-0 overflow-y-auto bg-white rounded-xl border border-neutral-200 shadow-sm">

        {{-- Preset Themes --}}
        <div class="p-4 border-b border-neutral-100">
            <h3 class="text-xs font-bold text-neutral-500 uppercase tracking-wider mb-3">Quick Presets</h3>
            <div class="grid grid-cols-4 gap-2">
                @foreach($presets as $key => $preset)
                <button type="button" @click="applyPreset('{{ $key }}')"
                        class="group flex flex-col items-center gap-1 p-1.5 rounded-lg hover:bg-neutral-50 transition-colors"
                        title="{{ $preset['name'] }}">
                    <div class="w-8 h-8 rounded-full border-2 border-white shadow-sm" style="background: {{ $preset['primary'] }}"></div>
                    <span class="text-[9px] text-neutral-500 group-hover:text-neutral-700">{{ explode(' ', $preset['name'])[0] }}</span>
                </button>
                @endforeach
            </div>
        </div>

        <form method="POST" action="{{ route('admin.theme.update') }}" enctype="multipart/form-data" id="theme-form">
            @csrf

            {{-- Brand Identity --}}
            <div class="border-b border-neutral-100" x-data="{ open: true }">
                <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-4 text-sm font-semibold text-neutral-900 hover:bg-neutral-50">
                    Brand Identity
                    <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-collapse class="px-4 pb-4 space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1">Store Name</label>
                        <input type="text" name="store_name" value="{{ $settings['store_name'] }}" class="w-full px-3 py-2 text-sm border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1">Tagline</label>
                        <input type="text" name="store_tagline" value="{{ $settings['store_tagline'] }}" class="w-full px-3 py-2 text-sm border border-neutral-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Your everyday store">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1">Logo</label>
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <img :src="logoPreview || '{{ asset($settings['store_logo']) }}'" class="h-10 max-w-[120px] object-contain bg-neutral-50 rounded border p-1" alt="Logo">
                                <span class="text-[10px] text-neutral-400">Max 2 MB, PNG/JPG/SVG</span>
                            </div>
                            <input type="file" name="store_logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" @change="previewLogo($event)" class="w-full text-xs text-neutral-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-neutral-100 file:text-neutral-700 hover:file:bg-neutral-200">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1">Favicon</label>
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                @if($settings['store_favicon'])
                                <img src="{{ asset($settings['store_favicon']) }}" class="h-8 w-8 object-contain bg-neutral-50 rounded border p-0.5" alt="Favicon">
                                @else
                                <div class="h-8 w-8 bg-neutral-100 rounded border flex items-center justify-center text-neutral-400 text-[10px]">ico</div>
                                @endif
                                <span class="text-[10px] text-neutral-400">Max 512 KB, PNG/ICO</span>
                            </div>
                            <input type="file" name="store_favicon" accept="image/png,image/x-icon,image/svg+xml" class="w-full text-xs text-neutral-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-neutral-100 file:text-neutral-700 hover:file:bg-neutral-200">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Colors --}}
            <div class="border-b border-neutral-100" x-data="{ open: true }">
                <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-4 text-sm font-semibold text-neutral-900 hover:bg-neutral-50">
                    Colors
                    <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-collapse class="px-4 pb-4 space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1">Primary Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="primary_color" x-model="colors.primary" @input="debouncedPreview()" class="w-10 h-10 rounded border border-neutral-200 cursor-pointer p-0.5">
                            <input type="text" x-model="colors.primary" @input="debouncedPreview()" class="flex-1 px-3 py-2 text-sm border border-neutral-200 rounded-lg font-mono" maxlength="7" pattern="^#[0-9a-fA-F]{6}$">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1">Secondary / CTA Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="secondary_color" x-model="colors.secondary" @input="debouncedPreview()" class="w-10 h-10 rounded border border-neutral-200 cursor-pointer p-0.5">
                            <input type="text" x-model="colors.secondary" @input="debouncedPreview()" class="flex-1 px-3 py-2 text-sm border border-neutral-200 rounded-lg font-mono" maxlength="7" pattern="^#[0-9a-fA-F]{6}$">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1">Link Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="link_color" x-model="colors.link" @input="debouncedPreview()" class="w-10 h-10 rounded border border-neutral-200 cursor-pointer p-0.5">
                            <input type="text" x-model="colors.link" @input="debouncedPreview()" class="flex-1 px-3 py-2 text-sm border border-neutral-200 rounded-lg font-mono" maxlength="7" pattern="^#[0-9a-fA-F]{6}$">
                        </div>
                    </div>

                    {{-- Generated Palette Preview --}}
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1.5">Generated Palette</label>
                        <div class="flex gap-0.5 rounded-lg overflow-hidden">
                            <template x-for="(shade, key) in palette" :key="key">
                                <div class="flex-1 h-8 relative group cursor-pointer" :style="'background:' + shade" :title="key + ': ' + shade">
                                    <span class="absolute inset-0 flex items-center justify-center text-[8px] font-bold opacity-0 group-hover:opacity-100 transition-opacity" :class="parseInt(key) > 400 ? 'text-white' : 'text-black'" x-text="key"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Typography --}}
            <div class="border-b border-neutral-100" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-4 text-sm font-semibold text-neutral-900 hover:bg-neutral-50">
                    Typography
                    <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-collapse class="px-4 pb-4 space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1">Body Font</label>
                        <select name="font_family" class="w-full px-3 py-2 text-sm border border-neutral-200 rounded-lg">
                            @foreach(['Poppins', 'Inter', 'Roboto', 'Open Sans', 'Lato', 'Nunito', 'Montserrat', 'Raleway', 'DM Sans', 'Plus Jakarta Sans'] as $font)
                            <option value="{{ $font }}" {{ $settings['font_family'] === $font ? 'selected' : '' }}>{{ $font }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1">Heading Font</label>
                        <select name="heading_font" class="w-full px-3 py-2 text-sm border border-neutral-200 rounded-lg">
                            @foreach(['Fredoka', 'Poppins', 'Inter', 'Playfair Display', 'Libre Baskerville', 'Merriweather', 'Lora', 'DM Serif Display'] as $font)
                            <option value="{{ $font }}" {{ $settings['heading_font'] === $font ? 'selected' : '' }}>{{ $font }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Layout & Style --}}
            <div class="border-b border-neutral-100" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-4 text-sm font-semibold text-neutral-900 hover:bg-neutral-50">
                    Layout & Style
                    <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-collapse class="px-4 pb-4 space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1">Button Style</label>
                        <div class="flex gap-2">
                            @foreach(['rounded' => 'Rounded', 'pill' => 'Pill', 'square' => 'Square'] as $val => $label)
                            <label class="flex-1">
                                <input type="radio" name="button_radius" value="{{ $val }}" {{ $settings['button_radius'] === $val ? 'checked' : '' }} class="sr-only peer">
                                <div class="text-center py-2 px-3 text-xs border border-neutral-200 cursor-pointer peer-checked:border-primary-500 peer-checked:bg-primary-600/5 peer-checked:text-primary-700 {{ $val === 'rounded' ? 'rounded-lg' : ($val === 'pill' ? 'rounded-full' : 'rounded-none') }}">{{ $label }}</div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1">Header Style</label>
                        <select name="header_style" class="w-full px-3 py-2 text-sm border border-neutral-200 rounded-lg">
                            <option value="centered" {{ $settings['header_style'] === 'centered' ? 'selected' : '' }}>Centered Logo</option>
                            <option value="left" {{ $settings['header_style'] === 'left' ? 'selected' : '' }}>Left Aligned</option>
                            <option value="mega" {{ $settings['header_style'] === 'mega' ? 'selected' : '' }}>Mega Menu</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Announcement Bar --}}
            <div class="border-b border-neutral-100" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-4 text-sm font-semibold text-neutral-900 hover:bg-neutral-50">
                    Announcement Bar
                    <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-collapse class="px-4 pb-4 space-y-3">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="header_announcement_active" value="1" {{ $settings['header_announcement_active'] === '1' ? 'checked' : '' }} class="rounded border-neutral-300 text-primary-600">
                        <span class="text-xs text-neutral-600">Show announcement bar</span>
                    </label>
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1">Text</label>
                        <input type="text" name="header_announcement" value="{{ $settings['header_announcement'] }}" class="w-full px-3 py-2 text-sm border border-neutral-200 rounded-lg" placeholder="Free shipping on orders above ₹499!">
                    </div>
                </div>
            </div>

            {{-- Advanced --}}
            <div class="border-b border-neutral-100" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-4 text-sm font-semibold text-neutral-900 hover:bg-neutral-50">
                    Advanced
                    <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-collapse class="px-4 pb-4 space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1">Footer Text</label>
                        <input type="text" name="footer_text" value="{{ $settings['footer_text'] }}" class="w-full px-3 py-2 text-sm border border-neutral-200 rounded-lg" placeholder="Custom footer text (optional)">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1">Custom CSS</label>
                        <textarea name="custom_css" rows="6" class="w-full px-3 py-2 text-xs border border-neutral-200 rounded-lg font-mono" placeholder="/* Add custom styles here */">{{ $settings['custom_css'] }}</textarea>
                        <p class="text-[10px] text-neutral-400 mt-1">Script tags and imports are stripped for security.</p>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <a href="{{ route('admin.theme.export') }}" class="flex-1 py-2 text-xs font-medium text-center text-neutral-600 bg-neutral-100 rounded-lg hover:bg-neutral-200 transition-colors">Export JSON</a>
                        <button type="button" @click="$refs.importFile.click()" class="flex-1 py-2 text-xs font-medium text-center text-neutral-600 bg-neutral-100 rounded-lg hover:bg-neutral-200 transition-colors">Import JSON</button>
                    </div>
                </div>
            </div>

            {{-- Save / Reset --}}
            <div class="p-4 flex gap-2 sticky bottom-0 bg-white border-t border-neutral-100">
                <button type="submit" class="flex-1 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-lg transition-colors">
                    Save Changes
                </button>
                <button type="button" @click="if(confirm('Reset to default theme? This cannot be undone.')) $refs.resetForm.submit()" class="px-4 py-2.5 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                    Reset
                </button>
            </div>
        </form>

        {{-- Hidden forms --}}
        <form x-ref="resetForm" method="POST" action="{{ route('admin.theme.reset') }}">@csrf</form>
        <form x-ref="importForm" method="POST" action="{{ route('admin.theme.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" x-ref="importFile" name="file" accept=".json" @change="$refs.importForm.submit()" class="hidden">
        </form>
    </div>

    {{-- Right Panel: Live Preview --}}
    <div class="flex-1 min-w-0 bg-white rounded-xl border border-neutral-200 shadow-sm overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-4 py-2.5 bg-neutral-50 border-b border-neutral-200">
            <div class="flex items-center gap-2">
                <div class="flex gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-red-400"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                    <div class="w-3 h-3 rounded-full bg-green-400"></div>
                </div>
                <span class="text-xs text-neutral-400 ml-2" x-text="previewUrl"></span>
            </div>
            <div class="flex gap-1">
                <button type="button" @click="previewDevice = 'desktop'" :class="previewDevice === 'desktop' ? 'bg-white shadow-sm' : 'hover:bg-neutral-200'" class="p-1.5 rounded transition-colors">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </button>
                <button type="button" @click="previewDevice = 'tablet'" :class="previewDevice === 'tablet' ? 'bg-white shadow-sm' : 'hover:bg-neutral-200'" class="p-1.5 rounded transition-colors">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </button>
                <button type="button" @click="previewDevice = 'mobile'" :class="previewDevice === 'mobile' ? 'bg-white shadow-sm' : 'hover:bg-neutral-200'" class="p-1.5 rounded transition-colors">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </button>
            </div>
        </div>
        <div class="flex-1 bg-neutral-100 flex items-start justify-center p-4 overflow-auto">
            <iframe x-ref="preview"
                    :src="previewUrl"
                    :class="{
                        'w-full': previewDevice === 'desktop',
                        'w-[768px]': previewDevice === 'tablet',
                        'w-[375px]': previewDevice === 'mobile'
                    }"
                    class="h-full bg-white rounded-lg shadow-lg border border-neutral-200 transition-all duration-300"
                    sandbox="allow-same-origin allow-scripts allow-forms"
                    loading="lazy"></iframe>
        </div>
    </div>
</div>

@push('scripts')
<script>
function themeCustomizer() {
    return {
        colors: {
            primary: '{{ $settings["primary_color"] }}',
            secondary: '{{ $settings["secondary_color"] }}',
            link: '{{ $settings["link_color"] }}',
        },
        palette: {},
        logoPreview: null,
        previewDevice: 'desktop',
        previewUrl: '{{ url("/") }}',
        _previewTimeout: null,

        init() {
            this.fetchPreview();
        },

        debouncedPreview() {
            clearTimeout(this._previewTimeout);
            this._previewTimeout = setTimeout(() => this.fetchPreview(), 350);
        },

        fetchPreview() {
            if (!/^#[0-9a-fA-F]{6}$/.test(this.colors.primary)) return;

            fetch('{{ route("admin.theme.preview") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    primary_color: this.colors.primary,
                    secondary_color: this.colors.secondary,
                    link_color: this.colors.link,
                }),
            })
            .then(r => r.json())
            .then(data => {
                this.palette = data.palette || {};
                try {
                    const iframe = this.$refs.preview;
                    const doc = iframe.contentDocument || iframe.contentWindow.document;
                    let style = doc.getElementById('theme-preview-override');
                    if (!style) {
                        style = doc.createElement('style');
                        style.id = 'theme-preview-override';
                        doc.head.appendChild(style);
                    }
                    style.textContent = data.css;
                } catch(e) { /* cross-origin — preview just won't live-update */ }
            })
            .catch(() => {});
        },

        previewLogo(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) {
                alert('Logo must be under 2 MB');
                event.target.value = '';
                return;
            }
            this.logoPreview = URL.createObjectURL(file);
        },

        applyPreset(key) {
            if (!confirm('Apply this preset? Your current colors will be replaced.')) return;
            fetch('{{ route("admin.theme.apply-preset") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ preset: key }),
            }).then(() => location.reload());
        },
    };
}
</script>
@endpush

</x-layouts.admin>
