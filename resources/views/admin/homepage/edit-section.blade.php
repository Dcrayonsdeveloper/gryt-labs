<x-layouts.admin>
    <x-slot name="title">Edit Section: {{ $section->title }}</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Edit: {{ $section->title }}</h1>
                <p class="text-sm text-neutral-600 mt-1">Section type: {{ ucfirst($section->type) }} &middot; Key: {{ $section->key }}</p>
            </div>
            <a href="{{ route('admin.homepage.sections') }}" class="btn btn-secondary">Back to Sections</a>
        </div>
    </x-slot>

    {{-- Type-specific help text --}}
    <div class="mb-6 p-4 bg-primary-50 border border-primary-100 rounded-lg">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-primary-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="text-sm text-primary-800">
                @switch($section->type)
                    @case('products')
                        <strong>Product Section</strong> &mdash; Controls the title and visibility of the "{{ $section->title }}" product slider on the homepage. Products are automatically loaded from the database.
                        @break
                    @case('benefits')
                        <strong>Benefits Section</strong> &mdash; Displays feature cards highlighting your brand's strengths. Add, edit, or remove benefit items below.
                        @break
                    @case('cta')
                        <strong>Promo Banner</strong> &mdash; A full-width promotional call-to-action banner displayed between product sections. Upload a background image for a visual banner, or set a background color.
                        @break
                    @case('testimonials')
                        <strong>Testimonials Section</strong> &mdash; Controls the heading and subtitle of the testimonials carousel. To manage individual reviews, go to <a href="{{ route('admin.homepage.testimonials') }}" class="underline font-medium">Testimonials Management</a>.
                        @break
                    @case('newsletter')
                        <strong>Newsletter Section</strong> &mdash; Controls the heading and subtitle of the email subscription section at the bottom of the homepage.
                        @break
                    @case('categories')
                        @if($section->key === 'shop_by_category')
                            <strong>Shop by Category Grid</strong> &mdash; Displays categories in a visual grid layout with images. Set the badge text via Subtitle, heading via Title, and description via the content section below.
                        @else
                            <strong>Categories Section</strong> &mdash; Controls visibility of the category collection grids on the homepage. Category names and images are managed from <a href="{{ route('admin.categories.index') }}" class="underline font-medium">Categories Management</a>.
                        @endif
                        @break
                    @case('product_banner')
                        <strong>Product Banner</strong> &mdash; A full-width clickable banner image linking to a product page. Upload a banner image and set the product link URL.
                        @break
                    @default
                        <strong>Content Section</strong> &mdash; Controls the display of this content block on the homepage.
                @endswitch
            </div>
        </div>
    </div>

    <form action="{{ route('admin.homepage.sections.update', $section) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-neutral-900 mb-4">Section Content</h2>
                <div class="space-y-4">
                    <div>
                        <label class="form-label form-label-required">Title</label>
                        <input type="text" name="title" value="{{ $section->title }}" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Subtitle</label>
                        <textarea name="subtitle" rows="2" class="form-input">{{ $section->subtitle }}</textarea>
                    </div>
                    @if($section->image_url !== null || in_array($section->type, ['cta', 'promo', 'product_banner']))
                        <div>
                            <label class="form-label">Background Image</label>
                            @if($section->image_url)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $section->image_url) }}" alt="{{ $section->title }}" class="h-32 object-cover rounded-lg">
                                </div>
                            @endif
                            <input type="file" name="image" accept="image/*" class="form-input">
                        </div>
                    @endif
                </div>
            </div>

            <div class="card p-6">
                <h2 class="text-lg font-semibold text-neutral-900 mb-4">Display Options</h2>
                <div class="space-y-4">
                    @if(in_array($section->type, ['products', 'benefits', 'cta', 'product_banner']))
                        <div>
                            <label class="form-label">Button Text</label>
                            <input type="text" name="button_text" value="{{ $section->button_text }}" class="form-input" placeholder="e.g. View All, Shop Now">
                        </div>
                        <div>
                            <label class="form-label">Button Link</label>
                            <input type="text" name="button_link" value="{{ $section->button_link }}" class="form-input" placeholder="e.g. /products, /categories/boys">
                        </div>
                    @endif

                    @if($section->type === 'cta')
                        <div>
                            <label class="form-label">Background Color</label>
                            <div class="flex items-center gap-2">
                                <input type="color" name="background_color" value="{{ $section->background_color ?? (\App\Models\Setting::get('primary_color', '') ?: '#334155') }}" class="w-10 h-10 rounded border border-neutral-200 cursor-pointer">
                                <input type="text" value="{{ $section->background_color ?? (\App\Models\Setting::get('primary_color', '') ?: '#334155') }}" class="form-input flex-1" readonly>
                            </div>
                            <p class="form-help">Used when no background image is set</p>
                        </div>
                        <div>
                            <label class="form-label">Text Color</label>
                            <div class="flex items-center gap-2">
                                <input type="color" name="text_color" value="{{ $section->text_color ?? '#ffffff' }}" class="w-10 h-10 rounded border border-neutral-200 cursor-pointer">
                                <input type="text" value="{{ $section->text_color ?? '#ffffff' }}" class="form-input flex-1" readonly>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center gap-3 pt-2 border-t border-neutral-100">
                        <input type="checkbox" name="is_active" value="1" {{ $section->is_active ? 'checked' : '' }} class="form-checkbox">
                        <label class="form-label mb-0">Section is visible on homepage</label>
                    </div>
                </div>
            </div>

            @if($section->type === 'benefits')
                <div class="card p-6 lg:col-span-2">
                    <h2 class="text-lg font-semibold text-neutral-900 mb-4">Benefits Items</h2>
                    <p class="text-sm text-neutral-600 mb-4">Available icons: shield, comfort, wash, colors, tagless, heart, shipping, return</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-data="{ items: {{ json_encode($section->content ?? []) }} }">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="p-4 bg-neutral-50 rounded-lg relative">
                                <button type="button" @click="items.splice(index, 1)" class="absolute top-2 right-2 text-red-400 hover:text-red-600 transition-colors" title="Remove item">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                <div class="space-y-2 pr-6">
                                    <input type="text" :name="'content['+index+'][title]'" x-model="item.title" class="form-input" placeholder="Title">
                                    <input type="text" :name="'content['+index+'][description]'" x-model="item.description" class="form-input" placeholder="Description">
                                    <input type="text" :name="'content['+index+'][icon]'" x-model="item.icon" class="form-input" placeholder="Icon name (e.g. shield, heart)">
                                </div>
                            </div>
                        </template>
                        <div class="p-4 bg-neutral-50 rounded-lg border-2 border-dashed border-neutral-300 flex items-center justify-center min-h-30">
                            <button type="button" @click="items.push({title: '', description: '', icon: ''})" class="btn btn-sm btn-secondary">
                                + Add Item
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Consultation CTA: trust items + description --}}
            @if($section->key === 'consultation_cta')
                <div class="card p-6 lg:col-span-2">
                    <h2 class="text-lg font-semibold text-neutral-900 mb-4">Consultation CTA Content</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="form-label">Description Text</label>
                            <input type="text" name="content[description]" value="{{ $section->content['description'] ?? '' }}" class="form-input" placeholder="Browse our range of products...">
                        </div>
                        <div x-data="{ items: {{ json_encode($section->content['trust_items'] ?? []) }} }">
                            <label class="form-label">Trust Items (checkmarks shown below CTA button)</label>
                            <div class="space-y-2 mb-3">
                                <template x-for="(item, index) in items" :key="index">
                                    <div class="flex items-center gap-2">
                                        <input type="text" :name="'content[trust_items]['+index+']'" x-model="items[index]" class="form-input flex-1" placeholder="e.g. Certified Experts">
                                        <button type="button" @click="items.splice(index, 1)" class="text-red-400 hover:text-red-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <button type="button" @click="items.push('')" class="btn btn-sm btn-secondary">+ Add Trust Item</button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Shop by Category: description --}}
            @if($section->key === 'shop_by_category')
                <div class="card p-6 lg:col-span-2">
                    <h2 class="text-lg font-semibold text-neutral-900 mb-4">Category Grid Content</h2>
                    <div>
                        <label class="form-label">Description</label>
                        <input type="text" name="content[description]" value="{{ $section->content['description'] ?? '' }}" class="form-input" placeholder="Browse our range of products crafted for your specific needs">
                    </div>
                </div>
            @endif

            {{-- Video Testimonials: video list --}}
            @if($section->key === 'video_testimonials')
                <div class="card p-6 lg:col-span-2">
                    <h2 class="text-lg font-semibold text-neutral-900 mb-4">Testimonial Videos</h2>
                    <p class="text-sm text-neutral-600 mb-4">Add video filenames (from <code>public/images/</code>) or full URLs</p>
                    <div x-data="{ videos: {{ json_encode($section->content['videos'] ?? []) }} }">
                        <div class="space-y-2 mb-3">
                            <template x-for="(vid, index) in videos" :key="index">
                                <div class="flex items-center gap-2">
                                    <input type="text" :name="'content[videos]['+index+']'" x-model="videos[index]" class="form-input flex-1" placeholder="e.g. video1.mp4 or https://...">
                                    <button type="button" @click="videos.splice(index, 1)" class="text-red-400 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <button type="button" @click="videos.push('')" class="btn btn-sm btn-secondary">+ Add Video</button>
                    </div>
                </div>
            @endif
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.homepage.sections') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</x-layouts.admin>
