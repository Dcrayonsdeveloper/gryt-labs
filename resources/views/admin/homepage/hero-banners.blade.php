<x-layouts.admin>
    <x-slot name="title">Hero Banners</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Hero Banners</h1>
                <p class="text-sm text-neutral-600 mt-1">Manage homepage hero slider banners. Drag to reorder.</p>
            </div>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary">Back to Homepage</a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add New Banner -->
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-neutral-900 mb-4">Add New Banner</h2>

            <form action="{{ route('admin.homepage.hero-banners.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-input" placeholder="Banner name">
                    </div>
                    <div>
                        <label class="form-label form-label-required">Image</label>
                        <input type="file" name="image" accept="image/*" required class="form-input">
                        <p class="form-help">Recommended: 1920x700px, JPG/PNG</p>
                    </div>
                    <div>
                        <label class="form-label">Heading Text</label>
                        <input type="text" name="title" class="form-input" placeholder="Banner heading">
                    </div>
                    <div>
                        <label class="form-label">Subtitle</label>
                        <input type="text" name="subtitle" class="form-input" placeholder="Banner subtitle">
                    </div>
                    <div>
                        <label class="form-label">Button Text</label>
                        <input type="text" name="button_text" class="form-input" placeholder="Shop Now">
                    </div>
                    <div>
                        <label class="form-label">Link URL</label>
                        <input type="text" name="link" class="form-input" placeholder="/products">
                    </div>
                    <div>
                        <label class="form-label">Overlay Style</label>
                        <select name="overlay_style" class="form-input">
                            @foreach(\App\Models\Banner::OVERLAY_STYLES as $key => $label)
                                <option value="{{ $key }}" {{ $key === 'left-dark' ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Add Banner</button>
                </div>
            </form>
        </div>

        <!-- Existing Banners -->
        <div class="lg:col-span-2" x-data="bannerSorter()">
            <div class="space-y-3" x-ref="list" x-on:dragover.prevent>
                @forelse($banners as $index => $banner)
                    <div class="card p-4 transition-all"
                         x-data="{ editing: false }"
                         draggable="true"
                         data-id="{{ $banner->id }}"
                         x-on:dragstart="onDragStart($event)"
                         x-on:dragover.prevent="onDragOver($event)"
                         x-on:dragend="onDragEnd()"
                         :class="{ 'opacity-50 scale-95': draggingId == {{ $banner->id }}, 'border-t-2 border-purple-500': dropTargetId == {{ $banner->id }} && draggingId != {{ $banner->id }} }">

                        <!-- Display Mode -->
                        <div x-show="!editing">
                            <div class="flex items-start gap-3">
                                <!-- Drag Handle + Position -->
                                <div class="flex flex-col items-center gap-1 shrink-0 pt-1">
                                    <span class="pos-badge text-xs font-bold text-neutral-600 w-6 h-6 flex items-center justify-center bg-neutral-100 rounded">#{{ $index + 1 }}</span>
                                    <div class="cursor-grab active:cursor-grabbing text-neutral-600 hover:text-neutral-600" title="Drag to reorder">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                        </svg>
                                    </div>
                                    <button type="button" @click="moveUp($el)" class="text-neutral-600 hover:text-purple-600" title="Move up">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    </button>
                                    <button type="button" @click="moveDown($el)" class="text-neutral-600 hover:text-purple-600" title="Move down">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                </div>

                                <!-- Thumbnail -->
                                <div class="w-44 h-24 bg-neutral-100 rounded-lg overflow-hidden shrink-0">
                                    <img src="{{ asset('storage/' . $banner->image_url) }}" alt="{{ $banner->name }}" class="w-full h-full object-cover">
                                </div>

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <h3 class="font-semibold text-neutral-900">{{ $banner->name ?: $banner->title ?: 'Banner #' . $banner->id }}</h3>
                                        <span class="badge {{ $banner->is_active ? 'badge-success' : 'badge-neutral' }}">
                                            {{ $banner->is_active ? 'Active' : 'Hidden' }}
                                        </span>
                                    </div>
                                    @if($banner->title)
                                        <p class="text-sm text-neutral-700 truncate" title="{{ $banner->title }}">{{ $banner->title }}</p>
                                    @endif
                                    @if($banner->subtitle)
                                        <p class="text-xs text-neutral-600 truncate" title="{{ $banner->subtitle }}">{{ $banner->subtitle }}</p>
                                    @endif
                                    <div class="flex items-center gap-3 mt-1 text-xs text-neutral-600">
                                        @if($banner->button_text)
                                            <span>Button: {{ $banner->button_text }}</span>
                                        @endif
                                        @if($banner->link)
                                            <span>Link: {{ $banner->link }}</span>
                                        @endif
                                        <span>Overlay: {{ \App\Models\Banner::OVERLAY_STYLES[$banner->overlay_style] ?? 'Default' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-3">
                                        <button @click="editing = true" type="button" class="btn btn-sm btn-primary">Edit</button>
                                        <form action="{{ route('admin.homepage.hero-banners.toggle', $banner) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm {{ $banner->is_active ? 'btn-secondary' : 'btn-success' }}">
                                                {{ $banner->is_active ? 'Hide' : 'Show' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.homepage.hero-banners.destroy', $banner) }}" method="POST" class="inline" onsubmit="return confirm('Delete this banner?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Mode -->
                        <div x-show="editing" x-cloak>
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-semibold text-neutral-900">Edit Banner</h3>
                                <button @click="editing = false" type="button" class="text-neutral-600 hover:text-neutral-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <form action="{{ route('admin.homepage.hero-banners.update', $banner) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">Name</label>
                                        <input type="text" name="name" value="{{ $banner->name }}" class="form-input">
                                    </div>
                                    <div>
                                        <label class="form-label">Link URL</label>
                                        <input type="text" name="link" value="{{ $banner->link }}" class="form-input" placeholder="/products">
                                    </div>
                                    <div>
                                        <label class="form-label">Heading Text</label>
                                        <input type="text" name="title" value="{{ $banner->title }}" class="form-input" placeholder="Banner heading">
                                    </div>
                                    <div>
                                        <label class="form-label">Subtitle</label>
                                        <input type="text" name="subtitle" value="{{ $banner->subtitle }}" class="form-input" placeholder="Banner subtitle">
                                    </div>
                                    <div>
                                        <label class="form-label">Button Text</label>
                                        <input type="text" name="button_text" value="{{ $banner->button_text }}" class="form-input" placeholder="Shop Now">
                                    </div>
                                    <div>
                                        <label class="form-label">Overlay Style</label>
                                        <select name="overlay_style" class="form-input">
                                            @foreach(\App\Models\Banner::OVERLAY_STYLES as $key => $label)
                                                <option value="{{ $key }}" {{ $banner->overlay_style === $key ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="form-label">Replace Image</label>
                                        <input type="file" name="image" accept="image/*" class="form-input">
                                        <p class="form-help">Leave empty to keep current image</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                    <button @click="editing = false" type="button" class="btn btn-secondary">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="card p-12 text-center">
                        <p class="text-neutral-600">No hero banners yet. Add your first banner.</p>
                    </div>
                @endforelse
            </div>

            <!-- Status indicators -->
            <div x-show="saving" x-cloak class="mt-3 text-sm text-purple-600 font-medium flex items-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Saving order...
            </div>
            <div x-show="saved" x-cloak x-transition class="mt-3 text-sm text-success-600 font-medium">Order saved.</div>
        </div>
    </div>

    @push('scripts')
    <script>
        function bannerSorter() {
            return {
                draggingId: null,
                dropTargetId: null,
                saving: false,
                saved: false,

                getItems() {
                    return Array.from(this.$refs.list.querySelectorAll('[data-id]'));
                },

                getCard(el) {
                    return el.closest('[data-id]');
                },

                indexOf(card) {
                    return this.getItems().indexOf(card);
                },

                onDragStart(e) {
                    const card = this.getCard(e.target);
                    this.draggingId = card.dataset.id;
                    e.dataTransfer.effectAllowed = 'move';
                },

                onDragOver(e) {
                    const card = this.getCard(e.target);
                    if (!card || this.draggingId === null || card.dataset.id === this.draggingId) {
                        return;
                    }
                    this.dropTargetId = card.dataset.id;
                },

                onDragEnd() {
                    if (this.draggingId && this.dropTargetId && this.draggingId !== this.dropTargetId) {
                        const items = this.getItems();
                        const fromEl = items.find(el => el.dataset.id === this.draggingId);
                        const toEl = items.find(el => el.dataset.id === this.dropTargetId);
                        if (fromEl && toEl) {
                            this.moveDom(fromEl, toEl);
                        }
                    }
                    this.draggingId = null;
                    this.dropTargetId = null;
                },

                moveUp(btnEl) {
                    const card = this.getCard(btnEl);
                    const index = this.indexOf(card);
                    if (index <= 0) return;
                    const items = this.getItems();
                    this.moveDom(card, items[index - 1]);
                },

                moveDown(btnEl) {
                    const card = this.getCard(btnEl);
                    const items = this.getItems();
                    const index = items.indexOf(card);
                    if (index >= items.length - 1) return;
                    this.moveDom(card, items[index + 1]);
                },

                moveDom(fromEl, toEl) {
                    const list = this.$refs.list;
                    const fromIndex = this.indexOf(fromEl);
                    const toIndex = this.indexOf(toEl);

                    if (fromIndex < toIndex) {
                        list.insertBefore(fromEl, toEl.nextSibling);
                    } else {
                        list.insertBefore(fromEl, toEl);
                    }

                    this.updateBadges();
                    this.saveOrder();
                },

                updateBadges() {
                    this.getItems().forEach((el, i) => {
                        const badge = el.querySelector('.pos-badge');
                        if (badge) badge.textContent = '#' + (i + 1);
                    });
                },

                saveOrder() {
                    const order = this.getItems().map(el => parseInt(el.dataset.id));

                    this.saving = true;
                    this.saved = false;

                    fetch('{{ route("admin.homepage.hero-banners.reorder") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ order }),
                    })
                    .then(r => r.json())
                    .then(() => {
                        this.saving = false;
                        this.saved = true;
                        setTimeout(() => this.saved = false, 2000);
                    })
                    .catch(() => {
                        this.saving = false;
                    });
                },
            };
        }
    </script>
    @endpush
</x-layouts.admin>
