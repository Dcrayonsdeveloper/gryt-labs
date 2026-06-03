<x-layouts.admin>
    <x-slot name="title">Navigation Menus</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Navigation</h1>
                <p class="text-sm text-neutral-600 mt-1">Manage header and footer navigation links</p>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 p-3 bg-success-50 text-success-700 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <div x-data="{
            editing: false,
            editId: null,
            location: 'header',
            parentId: '',
            label: '',
            url: '',
            newTab: false,
            submitting: false,
            edit(item) {
                this.editing = true;
                this.editId = item.id;
                this.location = item.location;
                this.parentId = item.parent_id || '';
                this.label = item.label;
                this.url = item.url;
                this.newTab = !!item.open_in_new_tab;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            resetForm() {
                this.editing = false;
                this.editId = null;
                this.location = 'header';
                this.parentId = '';
                this.label = '';
                this.url = '';
                this.newTab = false;
            }
         }"
         class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Add / Edit Form --}}
        <div class="lg:col-span-1">
            <div class="card p-5 sticky top-4">
                <h2 class="text-base font-semibold text-neutral-900 mb-4" x-text="editing ? 'Edit Menu Item' : 'Add Menu Item'"></h2>

                <form :action="editing ? '{{ url('admin/homepage/navigation') }}/' + editId : '{{ route('admin.homepage.navigation.store') }}'"
                      method="POST" @submit="submitting = true">
                    @csrf
                    <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>

                    <div class="space-y-3">
                        <div>
                            <label class="form-label form-label-required">Location</label>
                            <select name="location" x-model="location" class="form-select" required>
                                <option value="header">Header Navigation</option>
                                <option value="footer_col1">Footer - Quick Links</option>
                                <option value="footer_col2">Footer - Customer Service</option>
                                <option value="footer_col3">Footer - Policies</option>
                            </select>
                        </div>

                        {{-- Parent menu (only for header) --}}
                        <div x-show="location === 'header'">
                            <label class="form-label">Parent Menu</label>
                            <select name="parent_id" x-model="parentId" class="form-select">
                                <option value="">— Top Level —</option>
                                @foreach($headerMenus as $item)
                                    <option value="{{ $item->id }}">{{ $item->label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label form-label-required">Label</label>
                            <input type="text" name="label" x-model="label" required class="form-input" placeholder="e.g. About Us">
                        </div>

                        <div>
                            <label class="form-label form-label-required">URL</label>
                            <input type="text" name="url" x-model="url" required class="form-input" placeholder="/about or https://...">
                            <p class="text-xs text-neutral-500 mt-1">Use relative paths like /about or full URLs</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="open_in_new_tab" x-model="newTab" id="nav_new_tab" class="rounded border-neutral-300" :value="newTab ? 1 : 0">
                            <label for="nav_new_tab" class="text-sm text-neutral-700">Open in new tab</label>
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary flex-1" :disabled="submitting">
                                <span x-text="editing ? 'Update' : 'Add Item'"></span>
                            </button>
                            <button x-show="editing" type="button" @click="resetForm()" class="btn btn-secondary">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Menu Lists --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Header Navigation --}}
            <div class="card p-5">
                <h3 class="font-semibold text-neutral-900 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    Header Navigation
                </h3>
                <div class="space-y-1.5">
                    @forelse($headerMenus as $item)
                        @include('admin.homepage._nav-item', ['item' => $item, 'indent' => false])
                        @foreach($item->children as $child)
                            @include('admin.homepage._nav-item', ['item' => $child, 'indent' => true])
                        @endforeach
                    @empty
                        <p class="text-sm text-neutral-500 py-3">No header menu items. Add one to get started.</p>
                    @endforelse
                </div>
            </div>

            {{-- Footer Sections --}}
            @foreach(['footer_col1' => 'Quick Links', 'footer_col2' => 'Customer Service', 'footer_col3' => 'Policies'] as $loc => $label)
                <div class="card p-5">
                    <h3 class="font-semibold text-neutral-900 mb-3">Footer: {{ $label }}</h3>
                    <div class="space-y-1.5">
                        @forelse($footerMenus[$loc] ?? collect() as $item)
                            @include('admin.homepage._nav-item', ['item' => $item, 'indent' => false])
                        @empty
                            <p class="text-sm text-neutral-500 py-2">No items</p>
                        @endforelse
                    </div>
                </div>
            @endforeach

        </div>
    </div>
</x-layouts.admin>
