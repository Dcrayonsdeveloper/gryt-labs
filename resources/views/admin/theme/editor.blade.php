<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Customize Theme - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; margin: 0; overflow: hidden; background: #1a1a1a; color: #e3e3e3; }
        .editor-topbar { height: 56px; background: #1a1a1a; border-bottom: 1px solid #333; display: flex; align-items: center; justify-content: space-between; padding: 0 16px; }
        .editor-topbar a, .editor-topbar button { font-size: 13px; }
        .editor-main { display: flex; height: calc(100vh - 56px); }
        .editor-panel { width: 320px; background: #1e1e1e; border-right: 1px solid #333; overflow-y: auto; flex-shrink: 0; }
        .editor-panel::-webkit-scrollbar { width: 5px; }
        .editor-panel::-webkit-scrollbar-thumb { background: #444; border-radius: 3px; }
        .editor-preview { flex: 1; background: #f1f1f1; display: flex; align-items: flex-start; justify-content: center; padding: 0; position: relative; }
        .editor-preview iframe { width: 100%; height: 100%; border: none; }
        .editor-preview.mobile iframe { width: 375px; margin: 16px auto; height: calc(100% - 32px); border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.3); }
        .editor-preview.tablet iframe { width: 768px; margin: 16px auto; height: calc(100% - 32px); border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.3); }
        .panel-section { border-bottom: 1px solid #333; }
        .panel-header { padding: 12px 16px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; user-select: none; }
        .panel-header:hover { background: #252525; }
        .panel-header h3 { font-size: 13px; font-weight: 600; color: #e3e3e3; margin: 0; }
        .panel-body { padding: 0 16px 16px; }
        .field-label { display: block; font-size: 11px; font-weight: 500; color: #999; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.03em; }
        .field-input { width: 100%; padding: 7px 10px; font-size: 13px; border: 1px solid #444; border-radius: 6px; background: #2a2a2a; color: #e3e3e3; outline: none; transition: border-color 0.15s; }
        .field-input:focus { border-color: #005bd3; }
        .field-input::placeholder { color: #666; }
        select.field-input { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23999'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 8px center; background-size: 14px; padding-right: 28px; }
        .color-field { display: flex; align-items: center; gap: 8px; }
        .color-field input[type="color"] { width: 32px; height: 32px; border: 1px solid #444; border-radius: 6px; cursor: pointer; padding: 2px; background: #2a2a2a; }
        .color-field input[type="text"] { flex: 1; }
        .preset-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
        .preset-btn { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 8px 4px; border-radius: 8px; border: 1px solid #333; background: transparent; cursor: pointer; transition: all 0.15s; }
        .preset-btn:hover { background: #252525; border-color: #555; }
        .preset-dot { width: 24px; height: 24px; border-radius: 50%; border: 2px solid #333; }
        .preset-name { font-size: 9px; color: #888; }
        .section-item { display: flex; align-items: center; gap: 8px; padding: 8px 12px; margin: 0 -4px; border-radius: 6px; cursor: grab; transition: background 0.1s; }
        .section-item:hover { background: #252525; }
        .section-item .handle { color: #555; cursor: grab; }
        .section-item .name { flex: 1; font-size: 13px; color: #ccc; }
        .section-item .type-badge { font-size: 10px; color: #888; background: #2a2a2a; padding: 2px 6px; border-radius: 4px; }
        .toggle-switch { position: relative; width: 32px; height: 18px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-switch .slider { position: absolute; inset: 0; background: #444; border-radius: 9px; transition: 0.2s; cursor: pointer; }
        .toggle-switch .slider:before { content: ''; position: absolute; height: 14px; width: 14px; left: 2px; bottom: 2px; background: #ccc; border-radius: 50%; transition: 0.2s; }
        .toggle-switch input:checked + .slider { background: #2e7d32; }
        .toggle-switch input:checked + .slider:before { transform: translateX(14px); background: #fff; }
        .btn-save { padding: 8px 20px; font-size: 13px; font-weight: 600; border-radius: 6px; border: none; cursor: pointer; transition: all 0.15s; }
        .btn-save-primary { background: #fff; color: #1a1a1a; }
        .btn-save-primary:hover { background: #e3e3e3; }
        .btn-save-outline { background: transparent; color: #ccc; border: 1px solid #555; }
        .btn-save-outline:hover { background: #252525; }
        .device-btn { padding: 6px; border-radius: 6px; border: 1px solid transparent; background: transparent; color: #888; cursor: pointer; transition: all 0.15s; }
        .device-btn.active { background: #333; color: #fff; border-color: #555; }
        .device-btn:hover { color: #fff; }
        .unsaved-dot { width: 8px; height: 8px; background: #f59e0b; border-radius: 50%; display: inline-block; margin-left: 6px; }
    </style>
</head>
<body x-data="themeEditor()">

    {{-- ═══ TOP BAR ═══ --}}
    <div class="editor-topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <a href="{{ route('admin.theme.index') }}" style="color:#999;text-decoration:none;display:flex;align-items:center;gap:6px;font-weight:500;">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Exit
            </a>
            <span style="color:#555;">|</span>
            <span style="font-size:13px;font-weight:600;color:#e3e3e3;">Customize Theme</span>
            <span x-show="hasChanges" class="unsaved-dot" title="Unsaved changes" x-cloak></span>
        </div>

        <div style="display:flex;align-items:center;gap:8px;">
            {{-- Device Switcher --}}
            <button @click="device='desktop'" :class="device==='desktop' && 'active'" class="device-btn" title="Desktop">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </button>
            <button @click="device='tablet'" :class="device==='tablet' && 'active'" class="device-btn" title="Tablet">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </button>
            <button @click="device='mobile'" :class="device==='mobile' && 'active'" class="device-btn" title="Mobile">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </button>

            <span style="color:#333;margin:0 4px;">|</span>

            <button @click="undoChanges()" class="btn-save btn-save-outline" x-show="hasChanges" x-cloak>Discard</button>
            <button @click="saveAll()" :disabled="saving" class="btn-save btn-save-primary">
                <span x-show="!saving">Save</span>
                <span x-show="saving" x-cloak>Saving...</span>
            </button>
        </div>
    </div>

    {{-- ═══ MAIN: Panel + Preview ═══ --}}
    <div class="editor-main">

        {{-- LEFT PANEL --}}
        <div class="editor-panel">

            {{-- Presets --}}
            <div class="panel-section" x-data="{ open: false }">
                <div class="panel-header" @click="open = !open">
                    <h3>Quick Presets</h3>
                    <svg :style="open && 'transform:rotate(180deg)'" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
                <div x-show="open" x-collapse class="panel-body">
                    <div class="preset-grid">
                        @foreach($presets as $key => $preset)
                        <button type="button" class="preset-btn" @click="applyPreset('{{ $key }}')">
                            <div class="preset-dot" style="background:{{ $preset['primary'] }}"></div>
                            <span class="preset-name">{{ explode(' ', $preset['name'])[0] }}</span>
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Colors --}}
            <div class="panel-section" x-data="{ open: true }">
                <div class="panel-header" @click="open = !open">
                    <h3>Colors</h3>
                    <svg :style="open && 'transform:rotate(180deg)'" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
                <div x-show="open" x-collapse class="panel-body" style="display:flex;flex-direction:column;gap:12px;">
                    <div>
                        <label class="field-label">Primary</label>
                        <div class="color-field">
                            <input type="color" x-model="colors.primary" @input="onColorChange()">
                            <input type="text" x-model="colors.primary" @input="onColorChange()" class="field-input" maxlength="7">
                        </div>
                    </div>
                    <div>
                        <label class="field-label">Secondary / CTA</label>
                        <div class="color-field">
                            <input type="color" x-model="colors.secondary" @input="onColorChange()">
                            <input type="text" x-model="colors.secondary" @input="onColorChange()" class="field-input" maxlength="7">
                        </div>
                    </div>
                    <div>
                        <label class="field-label">Links</label>
                        <div class="color-field">
                            <input type="color" x-model="colors.link" @input="onColorChange()">
                            <input type="text" x-model="colors.link" @input="onColorChange()" class="field-input" maxlength="7">
                        </div>
                    </div>
                    {{-- Palette Preview --}}
                    <div>
                        <label class="field-label">Generated Palette</label>
                        <div style="display:flex;gap:2px;border-radius:6px;overflow:hidden;margin-top:4px;">
                            <template x-for="(shade, key) in palette" :key="key">
                                <div style="flex:1;height:24px;position:relative;cursor:pointer;" :style="'background:'+shade" :title="key+': '+shade">
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Typography --}}
            <div class="panel-section" x-data="{ open: false }">
                <div class="panel-header" @click="open = !open">
                    <h3>Typography</h3>
                    <svg :style="open && 'transform:rotate(180deg)'" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
                <div x-show="open" x-collapse class="panel-body" style="display:flex;flex-direction:column;gap:12px;">
                    <div>
                        <label class="field-label">Body Font</label>
                        <select x-model="typo.body" @change="hasChanges=true" class="field-input">
                            <option value="Poppins">Poppins</option><option value="Inter">Inter</option>
                            <option value="Roboto">Roboto</option><option value="DM Sans">DM Sans</option>
                            <option value="Nunito">Nunito</option><option value="Lato">Lato</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Heading Font</label>
                        <select x-model="typo.heading" @change="hasChanges=true" class="field-input">
                            <option value="Fredoka">Fredoka</option><option value="Poppins">Poppins</option>
                            <option value="Playfair Display">Playfair Display</option><option value="DM Serif Display">DM Serif Display</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Button Style</label>
                        <div style="display:flex;gap:6px;">
                            <template x-for="style in ['rounded','pill','square']" :key="style">
                                <button @click="typo.buttonRadius = style; hasChanges = true"
                                        :style="typo.buttonRadius === style ? 'border-color:#005bd3;background:#005bd3/15' : ''"
                                        style="flex:1;padding:6px;font-size:11px;border:1px solid #444;border-radius:6px;background:#2a2a2a;color:#ccc;cursor:pointer;text-transform:capitalize;"
                                        x-text="style" type="button"></button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Branding --}}
            <div class="panel-section" x-data="{ open: false }">
                <div class="panel-header" @click="open = !open">
                    <h3>Branding</h3>
                    <svg :style="open && 'transform:rotate(180deg)'" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
                <div x-show="open" x-collapse class="panel-body" style="display:flex;flex-direction:column;gap:12px;">
                    <div>
                        <label class="field-label">Store Name</label>
                        <input type="text" x-model="branding.name" @input="hasChanges=true" class="field-input">
                    </div>
                    <div>
                        <label class="field-label">Tagline</label>
                        <input type="text" x-model="branding.tagline" @input="hasChanges=true" class="field-input" placeholder="Your everyday store">
                    </div>
                    <div>
                        <label class="field-label">Announcement Bar</label>
                        <input type="text" x-model="branding.announcement" @input="hasChanges=true" class="field-input" placeholder="Free shipping on orders above ₹499!">
                    </div>
                </div>
            </div>

            {{-- Sections --}}
            <div class="panel-section" x-data="{ open: true }">
                <div class="panel-header" @click="open = !open">
                    <h3>Homepage Sections</h3>
                    <svg :style="open && 'transform:rotate(180deg)'" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
                <div x-show="open" x-collapse class="panel-body">
                    <div style="display:flex;flex-direction:column;gap:2px;">
                        <template x-for="(section, idx) in sections" :key="section.id">
                            <div class="section-item"
                                 draggable="true"
                                 @dragstart="dragStart(idx, $event)"
                                 @dragover.prevent="dragOver(idx, $event)"
                                 @drop="drop(idx)"
                                 @dragend="dragEnd()">
                                <span class="handle" title="Drag to reorder">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 10.001 4.001A2 2 0 007 2zm0 6a2 2 0 10.001 4.001A2 2 0 007 8zm0 6a2 2 0 10.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z"/></svg>
                                </span>
                                <span class="name" x-text="section.name"></span>
                                <label class="toggle-switch" @click.stop>
                                    <input type="checkbox" :checked="section.is_active" @change="toggleSection(section)">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </template>
                    </div>
                    <a href="{{ route('admin.sections.index') }}" style="display:inline-flex;align-items:center;gap:4px;margin-top:12px;font-size:12px;color:#5c9cf5;text-decoration:none;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Manage sections
                    </a>
                </div>
            </div>

            {{-- Advanced --}}
            <div class="panel-section" x-data="{ open: false }">
                <div class="panel-header" @click="open = !open">
                    <h3>Advanced</h3>
                    <svg :style="open && 'transform:rotate(180deg)'" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
                <div x-show="open" x-collapse class="panel-body" style="display:flex;flex-direction:column;gap:12px;">
                    <div>
                        <label class="field-label">Custom CSS</label>
                        <textarea x-model="advanced.customCss" @input="hasChanges=true" class="field-input font-mono text-xs" rows="6" style="resize:vertical;" placeholder="/* Custom styles */"></textarea>
                    </div>
                    <div style="display:flex;gap:6px;">
                        <a href="{{ route('admin.theme.export') }}" class="btn-save btn-save-outline" style="flex:1;text-align:center;text-decoration:none;font-size:11px;">Export</a>
                        <button @click="resetTheme()" class="btn-save btn-save-outline" style="flex:1;font-size:11px;color:#f87171;">Reset</button>
                    </div>
                </div>
            </div>

        </div>

        {{-- RIGHT: IFRAME PREVIEW --}}
        <div class="editor-preview" :class="device">
            <iframe x-ref="preview" src="{{ url('/') }}" @load="injectPreviewStyles()"></iframe>
        </div>

    </div>

    @vite(['resources/js/app.js'])
    <script>
    function themeEditor() {
        return {
            device: 'desktop',
            saving: false,
            hasChanges: false,
            _previewTimeout: null,
            _originalColors: null,

            colors: {
                primary: '{{ $settings["primary_color"] }}',
                secondary: '{{ $settings["secondary_color"] }}',
                link: '{{ $settings["link_color"] }}',
            },
            palette: {},
            typo: {
                body: '{{ $settings["font_family"] }}',
                heading: '{{ $settings["heading_font"] }}',
                buttonRadius: '{{ $settings["button_radius"] }}',
            },
            branding: {
                name: '{{ $settings["store_name"] }}',
                tagline: '{{ $settings["store_tagline"] }}',
                announcement: '{{ $settings["header_announcement"] }}',
            },
            advanced: {
                customCss: `{{ $settings["custom_css"] }}`,
            },
            sections: {!! json_encode($sections->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'type' => $s->type, 'is_active' => $s->is_active, 'position' => $s->position])->values()) !!},

            // Drag state
            dragIdx: null,

            init() {
                this._originalColors = { ...this.colors };
                this.fetchPalette();
            },

            onColorChange() {
                this.hasChanges = true;
                clearTimeout(this._previewTimeout);
                this._previewTimeout = setTimeout(() => {
                    this.fetchPalette();
                    this.injectPreviewStyles();
                }, 200);
            },

            fetchPalette() {
                if (!/^#[0-9a-fA-F]{6}$/.test(this.colors.primary)) return;
                fetch('{{ route("admin.theme.preview") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ primary_color: this.colors.primary, secondary_color: this.colors.secondary, link_color: this.colors.link }),
                })
                .then(r => r.json())
                .then(data => {
                    this.palette = data.palette || {};
                    this._cssVars = data.css || '';
                    this.injectPreviewStyles();
                }).catch(() => {});
            },

            _cssVars: '',

            injectPreviewStyles() {
                try {
                    const iframe = this.$refs.preview;
                    const doc = iframe.contentDocument || iframe.contentWindow.document;
                    if (!doc || !doc.head) return;

                    // Inject CSS variables
                    let styleEl = doc.getElementById('editor-override');
                    if (!styleEl) {
                        styleEl = doc.createElement('style');
                        styleEl.id = 'editor-override';
                        doc.head.appendChild(styleEl);
                    }
                    styleEl.textContent = this._cssVars + '\n' + this.advanced.customCss;
                } catch(e) {
                    // Cross-origin — can't inject
                }
            },

            async saveAll() {
                this.saving = true;
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                formData.append('primary_color', this.colors.primary);
                formData.append('secondary_color', this.colors.secondary);
                formData.append('link_color', this.colors.link);
                formData.append('font_family', this.typo.body);
                formData.append('heading_font', this.typo.heading);
                formData.append('button_radius', this.typo.buttonRadius);
                formData.append('store_name', this.branding.name);
                formData.append('store_tagline', this.branding.tagline);
                formData.append('header_announcement', this.branding.announcement);
                formData.append('custom_css', this.advanced.customCss);

                try {
                    const resp = await fetch('{{ route("admin.theme.update") }}', { method: 'POST', body: formData });
                    if (resp.ok || resp.status === 302) {
                        this.hasChanges = false;
                        this._originalColors = { ...this.colors };
                        // Reload preview to show saved changes
                        this.$refs.preview.src = this.$refs.preview.src;
                    }
                } catch(e) {}
                this.saving = false;
            },

            undoChanges() {
                if (!confirm('Discard all unsaved changes?')) return;
                this.colors = { ...this._originalColors };
                this.hasChanges = false;
                this.fetchPalette();
                this.$refs.preview.src = this.$refs.preview.src;
            },

            async applyPreset(key) {
                const resp = await fetch('{{ route("admin.theme.apply-preset") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ preset: key }),
                });
                if (resp.ok) location.reload();
            },

            async resetTheme() {
                if (!confirm('Reset theme to defaults? This cannot be undone.')) return;
                await fetch('{{ route("admin.theme.reset") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
                location.reload();
            },

            async toggleSection(section) {
                section.is_active = !section.is_active;
                await fetch('/admin/sections/' + section.id + '/toggle', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                });
                // Reload preview to reflect section change
                setTimeout(() => { this.$refs.preview.src = this.$refs.preview.src; }, 300);
            },

            // Drag-and-drop reordering
            dragStart(idx, e) { this.dragIdx = idx; e.dataTransfer.effectAllowed = 'move'; },
            dragOver(idx, e) { e.dataTransfer.dropEffect = 'move'; },
            drop(targetIdx) {
                if (this.dragIdx === null || this.dragIdx === targetIdx) return;
                const item = this.sections.splice(this.dragIdx, 1)[0];
                this.sections.splice(targetIdx, 0, item);
                this.dragIdx = null;
                // Save new order
                const order = this.sections.map(s => s.id);
                fetch('{{ route("admin.sections.reorder") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ order }),
                }).then(() => {
                    setTimeout(() => { this.$refs.preview.src = this.$refs.preview.src; }, 300);
                });
            },
            dragEnd() { this.dragIdx = null; },
        };
    }
    </script>
</body>
</html>
