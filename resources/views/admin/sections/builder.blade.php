<x-layouts.admin>
    <x-slot name="title">Homepage Builder</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Homepage Builder</h1>
                <p class="text-sm mt-0.5 text-gray-600">
                    {{ $sections->where('is_active', true)->count() }} active sections on <strong>{{ ucfirst($page) }}</strong> page
                </p>
            </div>
            <a href="{{ url('/') }}" target="_blank" class="admin-btn admin-btn-outline inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Preview Store
            </a>
        </div>
    </x-slot>

    {{-- Main two-column layout --}}
    <div class="flex gap-5" x-data="sectionBuilder()" x-cloak>

        {{-- LEFT: Active Sections --}}
        <div class="flex-1 min-w-0">
            <div class="admin-card">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-900">Active Sections</h2>
                    <p class="text-xs mt-0.5 text-gray-500">Drag to reorder. Click Edit to change settings.</p>
                </div>

                @if($sections->isEmpty())
                    <div class="px-5 py-12 text-center">
                        <svg class="w-10 h-10 mx-auto mb-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <p class="text-sm font-medium text-gray-600">No sections yet</p>
                        <p class="text-xs mt-1 text-gray-500">Add your first section from the panel on the right.</p>
                    </div>
                @else
                    <div id="sections-list" class="divide-y divide-gray-100">
                        @foreach($sections as $section)
                            <div class="section-item px-5 py-3 transition-colors duration-150"
                                 data-id="{{ $section->id }}"
                                 draggable="true"
                                 @dragstart="dragStart($event, {{ $section->id }})"
                                 @dragover.prevent="dragOver($event)"
                                 @dragenter.prevent="dragEnter($event)"
                                 @dragleave="dragLeave($event)"
                                 @drop="drop($event, {{ $section->id }})"
                                 x-data="{ editing: false, saving: false, sectionName: @js($section->name), settings: @js($section->settings ?? []), isActive: {{ $section->is_active ? 'true' : 'false' }} }">

                                {{-- Section header row --}}
                                <div class="flex items-center gap-3">
                                    {{-- Drag handle --}}
                                    <div class="cursor-grab active:cursor-grabbing flex-shrink-0 text-gray-500">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                                    </div>

                                    {{-- Name + type --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold truncate text-gray-900" x-text="sectionName">{{ $section->name }}</span>
                                            <span class="admin-badge admin-badge-neutral">{{ $availableTypes[$section->type]['name'] ?? ucfirst(str_replace('_', ' ', $section->type)) }}</span>
                                        </div>
                                        <p class="text-xs mt-0.5 text-gray-500">Position {{ $section->position + 1 }}</p>
                                    </div>

                                    {{-- Toggle switch --}}
                                    <button
                                        @click="toggleSection({{ $section->id }}, $el); isActive = !isActive"
                                        class="relative inline-flex h-5 w-9 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                        :class="isActive ? 'bg-green-500' : 'bg-gray-300'"
                                        data-active="{{ $section->is_active ? 'true' : 'false' }}"
                                        title="{{ $section->is_active ? 'Active' : 'Inactive' }}"
                                    >
                                        <span
                                            class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transform transition duration-200 ease-in-out"
                                            :class="isActive ? 'translate-x-4' : 'translate-x-0'"
                                        ></span>
                                    </button>

                                    {{-- Edit button --}}
                                    <button @click="editing = !editing" class="admin-btn admin-btn-outline text-xs">
                                        <span x-text="editing ? 'Close' : 'Edit'">Edit</span>
                                    </button>

                                    {{-- Delete button --}}
                                    <button @click="if(confirm('Delete this section? This cannot be undone.')) deleteSection({{ $section->id }})"
                                            class="admin-btn text-xs bg-transparent border border-red-600 text-red-600 hover:bg-red-50">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>

                                {{-- Inline edit panel --}}
                                <div x-show="editing" x-transition.opacity class="mt-4 pt-4 border-t border-gray-100">
                                    {{-- Section name --}}
                                    <div class="mb-3">
                                        <label class="block text-xs font-medium mb-1 text-gray-600">Section Name</label>
                                        <input type="text" x-model="sectionName" class="admin-input" />
                                    </div>

                                    @switch($section->type)
                                        @case('product_carousel')
                                            <div class="grid grid-cols-2 gap-3 mb-3">
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Heading</label>
                                                    <input type="text" x-model="settings.heading" class="admin-input" placeholder="Featured Products" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Product Count</label>
                                                    <input type="number" x-model.number="settings.count" class="admin-input" min="1" max="50" />
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="block text-xs font-medium mb-1 text-gray-600">Source</label>
                                                <select x-model="settings.source" class="admin-input">
                                                    <option value="bestsellers">Bestsellers</option>
                                                    <option value="new">New Arrivals</option>
                                                    <option value="featured">Featured</option>
                                                    <option value="category">Category</option>
                                                </select>
                                            </div>
                                            @break

                                        @case('trust_badges')
                                            <div class="mb-3">
                                                <label class="block text-xs font-medium mb-1.5 text-gray-600">Badges</label>
                                                <template x-for="(badge, idx) in settings.badges" :key="idx">
                                                    <div class="flex items-center gap-2 mb-2 p-2 rounded bg-gray-50">
                                                        <input type="text" x-model="badge.icon" class="admin-input w-20 flex-shrink-0" placeholder="Icon" />
                                                        <input type="text" x-model="badge.title" class="admin-input" placeholder="Title" />
                                                        <input type="text" x-model="badge.text" class="admin-input" placeholder="Description" />
                                                        <button @click="settings.badges.splice(idx, 1)" class="flex-shrink-0 text-red-600 hover:text-red-700">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        </button>
                                                    </div>
                                                </template>
                                                <button @click="settings.badges.push({icon:'', title:'', text:''})" class="admin-btn admin-btn-outline text-xs mt-1">+ Add Badge</button>
                                            </div>
                                            @break

                                        @case('text_with_image')
                                            <div class="grid grid-cols-2 gap-3 mb-3">
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Heading</label>
                                                    <input type="text" x-model="settings.heading" class="admin-input" placeholder="Section heading" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Image Position</label>
                                                    <select x-model="settings.image_position" class="admin-input">
                                                        <option value="left">Left</option>
                                                        <option value="right">Right</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="block text-xs font-medium mb-1 text-gray-600">Body Text</label>
                                                <textarea x-model="settings.body" class="admin-input" rows="3" placeholder="Write content here..."></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="block text-xs font-medium mb-1 text-gray-600">Image URL</label>
                                                <input type="text" x-model="settings.image" class="admin-input" placeholder="https://..." />
                                            </div>
                                            <div class="grid grid-cols-2 gap-3 mb-3">
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">CTA Text</label>
                                                    <input type="text" x-model="settings.cta_text" class="admin-input" placeholder="Learn More" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">CTA Link</label>
                                                    <input type="text" x-model="settings.cta_link" class="admin-input" placeholder="/page" />
                                                </div>
                                            </div>
                                            @break

                                        @case('newsletter')
                                            <div class="grid grid-cols-2 gap-3 mb-3">
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Heading</label>
                                                    <input type="text" x-model="settings.heading" class="admin-input" placeholder="Stay Updated" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Button Text</label>
                                                    <input type="text" x-model="settings.button_text" class="admin-input" placeholder="Subscribe" />
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="block text-xs font-medium mb-1 text-gray-600">Description</label>
                                                <textarea x-model="settings.description" class="admin-input" rows="2" placeholder="Subscribe for exclusive deals..."></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="block text-xs font-medium mb-1 text-gray-600">Background Color</label>
                                                <select x-model="settings.bg_color" class="admin-input">
                                                    <option value="primary">Primary (Dark)</option>
                                                    <option value="accent">Accent (Blue)</option>
                                                    <option value="light">Light</option>
                                                    <option value="white">White</option>
                                                </select>
                                            </div>
                                            @break

                                        @case('hero_banner')
                                            <div class="grid grid-cols-2 gap-3 mb-3">
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Autoplay</label>
                                                    <select x-model="settings.autoplay" class="admin-input">
                                                        <option :value="true">Yes</option>
                                                        <option :value="false">No</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Interval (ms)</label>
                                                    <input type="number" x-model.number="settings.interval" class="admin-input" min="1000" step="500" />
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="block text-xs font-medium mb-1 text-gray-600">Slides (JSON)</label>
                                                <textarea x-model="settings._slides_json" x-init="settings._slides_json = JSON.stringify(settings.slides || [], null, 2)" class="admin-input font-mono text-xs" rows="5" placeholder='[{"image": "...", "title": "..."}]'></textarea>
                                                <p class="text-xs mt-1 text-gray-500">Edit slides as JSON array. Each slide: {image, title, subtitle, cta_text, cta_link}</p>
                                            </div>
                                            @break

                                        @case('category_grid')
                                            <div class="grid grid-cols-2 gap-3 mb-3">
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Heading</label>
                                                    <input type="text" x-model="settings.heading" class="admin-input" placeholder="Shop by Category" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Columns</label>
                                                    <select x-model.number="settings.columns" class="admin-input">
                                                        <option value="3">3 Columns</option>
                                                        <option value="4">4 Columns</option>
                                                        <option value="5">5 Columns</option>
                                                        <option value="6">6 Columns</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="block text-xs font-medium mb-1 text-gray-600">Style</label>
                                                <select x-model="settings.style" class="admin-input">
                                                    <option value="card">Card</option>
                                                    <option value="circle">Circle</option>
                                                    <option value="overlay">Overlay</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="block text-xs font-medium mb-1 text-gray-600">Category IDs (comma-separated)</label>
                                                <input type="text"
                                                    x-model="settings._category_ids_str"
                                                    x-init="settings._category_ids_str = (settings.category_ids || []).join(', ')"
                                                    class="admin-input" placeholder="1, 2, 3" />
                                                <p class="text-xs mt-1 text-gray-500">Leave empty to show all top-level categories.</p>
                                            </div>
                                            @break

                                        @case('testimonials')
                                            <div class="grid grid-cols-2 gap-3 mb-3">
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Heading</label>
                                                    <input type="text" x-model="settings.heading" class="admin-input" placeholder="What Our Customers Say" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Count</label>
                                                    <input type="number" x-model.number="settings.count" class="admin-input" min="1" max="20" />
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="block text-xs font-medium mb-1 text-gray-600">Source</label>
                                                <select x-model="settings.source" class="admin-input">
                                                    <option value="auto">Auto (Highest Rated)</option>
                                                    <option value="manual">Manual (By Review IDs)</option>
                                                </select>
                                            </div>
                                            @break

                                        @case('instagram_reels')
                                            <div class="grid grid-cols-2 gap-3 mb-3">
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Heading</label>
                                                    <input type="text" x-model="settings.heading" class="admin-input" placeholder="Shop Our Reels" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Count</label>
                                                    <input type="number" x-model.number="settings.count" class="admin-input" min="1" max="20" />
                                                </div>
                                            </div>
                                            @break

                                        @case('countdown_timer')
                                            <div class="grid grid-cols-2 gap-3 mb-3">
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Heading</label>
                                                    <input type="text" x-model="settings.heading" class="admin-input" placeholder="Sale Ends In" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">End Date</label>
                                                    <input type="datetime-local" x-model="settings.end_date" class="admin-input" />
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-3 mb-3">
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Background Color</label>
                                                    <input type="color" x-model="settings.bg_color" class="admin-input p-1 h-9" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">Text Color</label>
                                                    <input type="color" x-model="settings.text_color" class="admin-input p-1 h-9" />
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-3 mb-3">
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">CTA Text</label>
                                                    <input type="text" x-model="settings.cta_text" class="admin-input" placeholder="Shop Now" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium mb-1 text-gray-600">CTA Link</label>
                                                    <input type="text" x-model="settings.cta_link" class="admin-input" placeholder="/products" />
                                                </div>
                                            </div>
                                            @break

                                        @case('brand_logos')
                                            <div class="mb-3">
                                                <label class="block text-xs font-medium mb-1 text-gray-600">Heading</label>
                                                <input type="text" x-model="settings.heading" class="admin-input" placeholder="Our Brands" />
                                            </div>
                                            <div class="mb-3">
                                                <label class="block text-xs font-medium mb-1 text-gray-600">Style</label>
                                                <select x-model="settings.style" class="admin-input">
                                                    <option value="scroll">Scrolling</option>
                                                    <option value="grid">Grid</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="block text-xs font-medium mb-1 text-gray-600">Brand IDs (comma-separated)</label>
                                                <input type="text"
                                                    x-model="settings._brand_ids_str"
                                                    x-init="settings._brand_ids_str = (settings.brand_ids || []).join(', ')"
                                                    class="admin-input" placeholder="1, 2, 3" />
                                                <p class="text-xs mt-1 text-gray-500">Leave empty to show all brands.</p>
                                            </div>
                                            @break

                                        @case('custom_html')
                                            <div class="mb-3">
                                                <label class="block text-xs font-medium mb-1 text-gray-600">HTML Content</label>
                                                <textarea x-model="settings.html" class="admin-input font-mono text-xs" rows="8" placeholder="<div>Your custom HTML...</div>"></textarea>
                                            </div>
                                            @break

                                        @default
                                            {{-- Generic JSON editor for unknown types --}}
                                            <div class="mb-3">
                                                <label class="block text-xs font-medium mb-1 text-gray-600">Settings (JSON)</label>
                                                <textarea
                                                    x-model="settings._raw_json"
                                                    x-init="settings._raw_json = JSON.stringify(settings, null, 2)"
                                                    class="admin-input font-mono text-xs"
                                                    rows="6"
                                                ></textarea>
                                            </div>
                                    @endswitch

                                    {{-- Save button --}}
                                    <div class="flex items-center gap-2 mt-4">
                                        <button
                                            @click="saveSection({{ $section->id }}, '{{ $section->type }}', sectionName, settings, $el); saving = true"
                                            class="admin-btn admin-btn-primary inline-flex items-center gap-1.5"
                                            :disabled="saving"
                                        >
                                            <template x-if="saving">
                                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                            </template>
                                            <span x-text="saving ? 'Saving...' : 'Save Changes'">Save Changes</span>
                                        </button>
                                        <button @click="editing = false" class="admin-btn admin-btn-outline">Cancel</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- RIGHT: Add Section Panel --}}
        <div class="w-80 flex-shrink-0">
            <div class="admin-card sticky top-4">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-900">Add Section</h2>
                    <p class="text-xs mt-0.5 text-gray-500">Click to add to page</p>
                </div>
                <div class="p-4 grid grid-cols-2 gap-2">
                    @foreach($availableTypes as $typeKey => $typeConfig)
                        <form method="POST" action="{{ route('admin.sections.store') }}">
                            @csrf
                            <input type="hidden" name="page" value="{{ $page }}">
                            <input type="hidden" name="type" value="{{ $typeKey }}">
                            <button type="submit"
                                    class="w-full text-left p-3 rounded-lg transition-all hover:shadow-sm border border-gray-200 bg-white hover:bg-gray-50 hover:border-gray-500"
                            >
                                <div class="w-8 h-8 rounded-md flex items-center justify-center mb-2 bg-gray-50">
                                    @switch($typeConfig['icon'] ?? '')
                                        @case('photo')
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            @break
                                        @case('shopping-bag')
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                            @break
                                        @case('grid')
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                            @break
                                        @case('shield-check')
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                            @break
                                        @case('chat-bubble')
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                            @break
                                        @case('document-text')
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            @break
                                        @case('envelope')
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            @break
                                        @case('video-camera')
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            @break
                                        @case('clock')
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @break
                                        @case('building-storefront')
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @break
                                        @case('code-bracket')
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                            @break
                                        @default
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1v-2zm0 8a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1v-2z"/></svg>
                                    @endswitch
                                </div>
                                <span class="text-xs font-medium text-gray-900">{{ $typeConfig['name'] }}</span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <x-slot name="scripts">
        <style>
            [x-cloak] { display: none !important; }
            .section-item.drag-over {
                background: #f9fafb;
                border-top: 2px solid #2563eb;
            }
            .section-item.dragging {
                opacity: 0.4;
            }
        </style>
        <script>
            function sectionBuilder() {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                return {
                    draggedId: null,

                    dragStart(e, id) {
                        this.draggedId = id;
                        e.dataTransfer.effectAllowed = 'move';
                        e.target.closest('.section-item').classList.add('dragging');
                    },

                    dragOver(e) {
                        e.dataTransfer.dropEffect = 'move';
                    },

                    dragEnter(e) {
                        const item = e.target.closest('.section-item');
                        if (item) item.classList.add('drag-over');
                    },

                    dragLeave(e) {
                        const item = e.target.closest('.section-item');
                        if (item && !item.contains(e.relatedTarget)) {
                            item.classList.remove('drag-over');
                        }
                    },

                    drop(e, targetId) {
                        // Clean up
                        document.querySelectorAll('.section-item').forEach(el => {
                            el.classList.remove('drag-over', 'dragging');
                        });

                        if (this.draggedId === targetId) return;

                        const list = document.getElementById('sections-list');
                        const items = [...list.querySelectorAll('.section-item')];
                        const draggedEl = items.find(el => el.dataset.id == this.draggedId);
                        const targetEl = items.find(el => el.dataset.id == targetId);

                        if (draggedEl && targetEl) {
                            // Move DOM element
                            const rect = targetEl.getBoundingClientRect();
                            const midY = rect.top + rect.height / 2;
                            if (e.clientY < midY) {
                                list.insertBefore(draggedEl, targetEl);
                            } else {
                                list.insertBefore(draggedEl, targetEl.nextSibling);
                            }

                            // Save new order
                            this.saveOrder();
                        }

                        this.draggedId = null;
                    },

                    saveOrder() {
                        const list = document.getElementById('sections-list');
                        const order = [...list.querySelectorAll('.section-item')].map(el => parseInt(el.dataset.id));

                        fetch("{{ route('admin.sections.reorder') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ order: order }),
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) toastr.success('Section order saved');
                            else toastr.error('Failed to save order');
                        })
                        .catch(() => toastr.error('Failed to save order'));
                    },

                    toggleSection(id, el) {
                        const btn = el.closest ? el.closest('button[data-active]') || el : el;
                        const currentlyActive = btn.dataset.active === 'true';

                        fetch(`/admin/sections/${id}/toggle`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                btn.dataset.active = data.is_active ? 'true' : 'false';
                                toastr.success(data.is_active ? 'Section activated' : 'Section deactivated');
                            }
                        })
                        .catch(() => toastr.error('Failed to toggle section'));
                    },

                    saveSection(id, type, name, settings, el) {
                        // Build clean settings (strip temp fields)
                        let cleanSettings = JSON.parse(JSON.stringify(settings));

                        // Handle special fields
                        if (type === 'hero_banner' && cleanSettings._slides_json) {
                            try { cleanSettings.slides = JSON.parse(cleanSettings._slides_json); } catch(e) { toastr.error('Invalid slides JSON'); return; }
                            delete cleanSettings._slides_json;
                        }
                        if (type === 'category_grid' && cleanSettings._category_ids_str !== undefined) {
                            cleanSettings.category_ids = cleanSettings._category_ids_str
                                ? cleanSettings._category_ids_str.split(',').map(s => parseInt(s.trim())).filter(n => !isNaN(n))
                                : [];
                            delete cleanSettings._category_ids_str;
                        }
                        if (type === 'brand_logos' && cleanSettings._brand_ids_str !== undefined) {
                            cleanSettings.brand_ids = cleanSettings._brand_ids_str
                                ? cleanSettings._brand_ids_str.split(',').map(s => parseInt(s.trim())).filter(n => !isNaN(n))
                                : [];
                            delete cleanSettings._brand_ids_str;
                        }

                        // Handle raw JSON fallback
                        if (cleanSettings._raw_json) {
                            try { cleanSettings = JSON.parse(cleanSettings._raw_json); } catch(e) { toastr.error('Invalid JSON'); return; }
                        }

                        fetch(`/admin/sections/${id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                name: name,
                                settings: cleanSettings,
                            }),
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                toastr.success('Section saved');
                                // Reset saving state via Alpine
                                const sectionEl = el.closest('.section-item');
                                if (sectionEl && sectionEl.__x) {
                                    sectionEl.__x.$data.saving = false;
                                }
                            } else {
                                toastr.error('Failed to save section');
                            }
                        })
                        .catch(() => toastr.error('Failed to save section'))
                        .finally(() => {
                            // Fallback: reset saving after timeout
                            setTimeout(() => {
                                const sectionEl = el?.closest('.section-item');
                                if (sectionEl?.__x) sectionEl.__x.$data.saving = false;
                            }, 500);
                        });
                    },

                    deleteSection(id) {
                        fetch(`/admin/sections/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                // Remove from DOM
                                const el = document.querySelector(`.section-item[data-id="${id}"]`);
                                if (el) el.remove();
                                toastr.success('Section deleted');

                                // Reload if no sections left
                                if (!document.querySelectorAll('.section-item').length) {
                                    window.location.reload();
                                }
                            }
                        })
                        .catch(() => toastr.error('Failed to delete section'));
                    },
                };
            }
        </script>
    </x-slot>
</x-layouts.admin>
