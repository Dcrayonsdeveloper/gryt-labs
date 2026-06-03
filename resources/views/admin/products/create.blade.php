<x-layouts.admin>
    <x-slot name="title">Add Product</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 text-sm">
                    <a href="{{ route('admin.products.index') }}" class="text-neutral-500 hover:text-neutral-800 transition-colors">Products</a>
                    <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-neutral-900 font-semibold">Add product</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary text-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div x-data="productForm()" x-ref="productFormRoot">
        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" data-track-changes>
            @csrf

            <div class="flex gap-5" style="align-items: flex-start; flex-wrap: wrap;">
                {{-- ============================================
                     LEFT COLUMN (~65%)
                     ============================================ --}}
                <div class="flex-1 space-y-5" style="min-width: 0;">

                    {{-- Title & Description Card --}}
                    <div class="card overflow-hidden">
                        <div class="px-5 py-4">
                            <h2 class="text-sm font-semibold text-neutral-900">Title</h2>
                        </div>
                        <div class="px-5 pb-5 space-y-4">
                            <div>
                                <label for="name" class="form-label form-label-required">Product name</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                       class="form-input w-full @error('name') form-input-error @enderror"
                                       x-ref="productName"
                                       placeholder="e.g. Cotton Floral Dress"
                                       @input="if(!slugManual) slug = toSlug($event.target.value); autoGenerateSeo($event.target.value)">
                                @error('name')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="slug" class="form-label">Slug</label>
                                <input type="text" name="slug" id="slug" x-model="slug"
                                       class="form-input w-full @error('slug') form-input-error @enderror"
                                       placeholder="auto-generated-from-name"
                                       @input="slugManual = ($event.target.value.trim() !== '')">
                                @error('slug')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="short_description" class="form-label">Short description</label>
                                <textarea name="short_description" id="short_description" rows="2"
                                          class="form-input w-full @error('short_description') form-input-error @enderror"
                                          placeholder="Brief product summary...">{{ old('short_description') }}</textarea>
                                @error('short_description')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div x-ignore>
                                <label for="description" class="form-label form-label-required">Description</label>
                                <div id="description-editor" style="border:1px solid #d4d4d4;border-radius:8px;padding:12px 15px;min-height:250px;font-size:14px;line-height:1.7;background:#fff;">{{ old('description', 'Write product description...') }}</div>
                                <textarea name="description" id="description" rows="6" required style="display:none"
                                          class="form-input w-full @error('description') form-input-error @enderror">{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Media Card --}}
                    <div class="card overflow-hidden" x-data="imageManager()">
                        <div class="px-5 py-4">
                            <h2 class="text-sm font-semibold text-neutral-900">Media</h2>
                        </div>
                        <div class="px-5 pb-5 space-y-5">
                            {{-- Main Image --}}
                            <div>
                                <label class="form-label">Main image (thumbnail)</label>
                                <p class="text-xs text-neutral-500 mb-3">This is the primary display image for the product.</p>
                                <div class="flex items-start gap-4">
                                    {{-- New main image preview --}}
                                    <div x-show="mainPreview" x-transition
                                         class="relative w-32 h-32 rounded-lg overflow-hidden ring-2 ring-neutral-300 shrink-0">
                                        <img :src="mainPreview" class="w-full h-full object-cover">
                                        <button type="button" @click="removeMainImage()"
                                                class="absolute top-1.5 right-1.5 w-6 h-6 bg-white/90 hover:bg-red-50 rounded-full flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                        <span class="absolute bottom-0 left-0 right-0 px-2 py-1 bg-neutral-800 text-white text-center" style="font-size: 10px; font-weight: 600;">Main Image</span>
                                    </div>
                                    {{-- Upload zone --}}
                                    <div class="flex-1 border-2 border-dashed border-neutral-300 rounded-lg p-4 text-center hover:border-neutral-400 transition-colors cursor-pointer"
                                         @click="$refs.mainFileInput.click()"
                                         :class="{ 'border-blue-400 bg-blue-50/50': mainDragOver }"
                                         @dragover.prevent="mainDragOver = true"
                                         @dragleave.prevent="mainDragOver = false"
                                         @drop.prevent="mainDragOver = false; handleMainImage($event.dataTransfer.files[0])">
                                        <input type="file" name="main_image" accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                                               x-ref="mainFileInput" class="hidden" @change="handleMainImage($event.target.files[0])">
                                        <div class="flex flex-col items-center py-2">
                                            <svg class="w-8 h-8 text-neutral-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <p class="text-sm font-medium text-neutral-700" x-text="mainPreview ? 'Click to change image' : 'Click to upload main image'"></p>
                                            <p class="text-xs text-neutral-500 mt-1">JPEG, PNG, WebP, GIF up to 20MB</p>
                                        </div>
                                    </div>
                                </div>
                                @error('main_image')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="border-t border-neutral-100"></div>

                            {{-- Gallery Images --}}
                            <div>
                                <label class="form-label">Gallery images</label>
                                <p class="text-xs text-neutral-500 mb-3">Upload additional product images. 20MB each.</p>

                                <div class="border-2 border-dashed border-neutral-300 rounded-lg p-5 text-center hover:border-neutral-400 transition-colors cursor-pointer"
                                     @click="$refs.galleryInput.click()"
                                     @dragover.prevent="galleryDragOver = true"
                                     @dragleave.prevent="galleryDragOver = false"
                                     @drop.prevent="galleryDragOver = false; handleGalleryFiles($event.dataTransfer.files)"
                                     :class="{ 'border-blue-400 bg-blue-50/50': galleryDragOver }">
                                    <input type="file" name="images[]" multiple accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                                           x-ref="galleryInput" class="hidden" @change="handleGalleryFiles($event.target.files)">
                                    <div class="flex flex-col items-center py-1">
                                        <svg class="w-7 h-7 text-neutral-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        <p class="text-sm font-medium text-neutral-700">Click to upload or drag and drop</p>
                                        <p class="text-xs text-neutral-500 mt-1">JPEG, PNG, WebP, GIF up to 20MB</p>
                                    </div>
                                </div>

                                <div x-show="galleryPreviews.length > 0" x-transition class="mt-4">
                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                                        <template x-for="(preview, index) in galleryPreviews" :key="index">
                                            <div class="relative group rounded-lg overflow-hidden ring-1 ring-neutral-200">
                                                <img :src="preview.url" class="w-full aspect-square object-cover">
                                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors"></div>
                                                <button type="button" @click="removeGalleryImage(index)"
                                                        class="absolute top-1.5 right-1.5 w-6 h-6 bg-white/90 hover:bg-red-50 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                                <div class="absolute bottom-0 left-0 right-0 px-2 py-1 bg-black/50 text-white truncate" style="font-size: 10px;">
                                                    <span x-text="preview.name"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <p class="text-xs text-neutral-500 mt-2">
                                        <span x-text="galleryPreviews.length"></span> gallery image(s) selected
                                    </p>
                                </div>

                                @error('images')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                                @error('images.*')
                                    <p class="form-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Pricing Card --}}
                    <div class="card overflow-hidden">
                        <div class="px-5 py-4">
                            <h2 class="text-sm font-semibold text-neutral-900">Pricing</h2>
                        </div>
                        <div class="px-5 pb-5">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label for="price" class="form-label form-label-required">Price</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 text-sm pointer-events-none">&#8377;</span>
                                        <input type="number" name="price" id="price" value="{{ old('price') }}" required
                                               step="0.01" min="0"
                                               class="form-input w-full !pl-8 @error('price') form-input-error @enderror">
                                    </div>
                                    @error('price')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="mrp" class="form-label">Compare at price / MRP</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 text-sm pointer-events-none">&#8377;</span>
                                        <input type="number" name="mrp" id="mrp" value="{{ old('mrp') }}"
                                               step="0.01" min="0"
                                               class="form-input w-full !pl-8 @error('mrp') form-input-error @enderror">
                                    </div>
                                    @error('mrp')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="cost_price" class="form-label">Cost price</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 text-sm pointer-events-none">&#8377;</span>
                                        <input type="number" name="cost_price" id="cost_price" value="{{ old('cost_price') }}"
                                               step="0.01" min="0"
                                               class="form-input w-full !pl-8 @error('cost_price') form-input-error @enderror">
                                    </div>
                                    @error('cost_price')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Inventory Card --}}
                    <div class="card overflow-hidden">
                        <div class="px-5 py-4">
                            <h2 class="text-sm font-semibold text-neutral-900">Inventory</h2>
                        </div>
                        <div class="px-5 pb-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="sku" class="form-label form-label-required">SKU (Stock Keeping Unit)</label>
                                    <input type="text" name="sku" id="sku" value="{{ old('sku') }}" required
                                           class="form-input w-full @error('sku') form-input-error @enderror"
                                           placeholder="e.g. FK-DRESS-001">
                                    @error('sku')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="stock_quantity" class="form-label form-label-required">Stock quantity</label>
                                    <input type="number" name="stock_quantity" id="stock_quantity" value="{{ old('stock_quantity', 0) }}" required
                                           min="0"
                                           class="form-input w-full @error('stock_quantity') form-input-error @enderror">
                                    @error('stock_quantity')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Attributes / Specifications Card --}}
                    @if($attributes->count())
                    <div class="card overflow-hidden">
                        <div class="px-5 py-4">
                            <h2 class="text-sm font-semibold text-neutral-900">Attributes</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Select applicable attributes for this product. Leave blank to skip.</p>
                        </div>
                        <div class="px-5 pb-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($attributes as $attribute)
                                    <div>
                                        <label class="form-label">
                                            {{ $attribute->name }}
                                        </label>
                                        @if($attribute->type === 'text')
                                            <input type="text" name="product_attributes[{{ $attribute->name }}]"
                                                   value="{{ old('product_attributes.' . $attribute->name) }}"
                                                   class="form-input w-full text-sm"
                                                   placeholder="Enter {{ strtolower($attribute->name) }}">
                                        @else
                                            <select name="product_attributes[{{ $attribute->name }}]" class="form-input w-full text-sm">
                                                <option value="">-- Select --</option>
                                                @foreach($attribute->values as $value)
                                                    <option value="{{ $value->value }}"
                                                            {{ old('product_attributes.' . $attribute->name) === $value->value ? 'selected' : '' }}>
                                                        {{ $value->value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if($attribute->type === 'color' && $attribute->values->count())
                                                <div class="flex flex-wrap gap-1 mt-2">
                                                    @foreach($attribute->values->take(10) as $value)
                                                        @if($value->color_code)
                                                            <div class="w-5 h-5 rounded-full border border-neutral-200 cursor-default" style="background-color: {{ $value->color_code }}" title="{{ $value->value }}"></div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- SEO Card --}}
                    <div class="card overflow-hidden">
                        <div class="px-5 py-4 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-neutral-900">Search engine listing</h2>
                            <button type="button" @click="autoFillSeo()" class="text-xs font-medium text-blue-600 hover:text-blue-800 transition-colors">
                                Auto-generate
                            </button>
                        </div>
                        <div class="px-5 pb-5 space-y-4">
                            {{-- Google Preview Snippet --}}
                            <div class="border border-neutral-200 rounded-lg p-4 bg-neutral-50">
                                <p class="text-xs text-neutral-500 mb-2 font-medium">Search engine preview</p>
                                <div>
                                    <p class="text-blue-700 text-base font-medium truncate font-sans" x-text="seoTitle || 'Page title'"></p>
                                    <p class="text-green-700 text-xs truncate mt-0.5 font-sans">{{ url('/') }}/product/<span x-text="slug || 'product-slug'"></span></p>
                                    <p class="text-neutral-600 text-xs mt-1 line-clamp-2 font-sans" x-text="seoDescription || 'Page description'"></p>
                                </div>
                            </div>

                            <div>
                                <label for="meta_title" class="form-label">Meta title</label>
                                <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title') }}"
                                       class="form-input w-full @error('meta_title') form-input-error @enderror"
                                       x-model="seoTitle"
                                       maxlength="70">
                                <p class="text-xs text-neutral-400 mt-1"><span x-text="seoTitle ? seoTitle.length : 0"></span> / 70 characters</p>
                                @error('meta_title')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="meta_description" class="form-label">Meta description</label>
                                <textarea name="meta_description" id="meta_description" rows="2"
                                          class="form-input w-full @error('meta_description') form-input-error @enderror"
                                          x-model="seoDescription"
                                          maxlength="160">{{ old('meta_description') }}</textarea>
                                <p class="text-xs text-neutral-400 mt-1"><span x-text="seoDescription ? seoDescription.length : 0"></span> / 160 characters</p>
                                @error('meta_description')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Product Benefits Card --}}
                    <div class="card overflow-hidden" x-data="{
                        benefits: [{ icon: 'check', text: '' }],
                        icons: [
                            { value: 'molecule', label: 'Science / Lab' },
                            { value: 'herb', label: 'Herb / Natural' },
                            { value: 'shield', label: 'Shield / Safe' },
                            { value: 'energy', label: 'Energy / Bolt' },
                            { value: 'noside', label: 'No Side Effects' },
                            { value: 'recovery', label: 'Recovery' },
                            { value: 'check', label: 'Checkmark' },
                        ],
                        addBenefit() { this.benefits.push({ icon: 'check', text: '' }); },
                        removeBenefit(i) { this.benefits.splice(i, 1); }
                    }">
                        <div class="px-5 py-4 border-b border-neutral-100 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="text-sm font-semibold text-neutral-900">Product Benefits</h2>
                                <p class="text-xs text-neutral-500 mt-0.5">Shown as the benefits grid on the product page. Leave all rows empty to fall back to the global tenant defaults.</p>
                            </div>
                            <button type="button" @click="addBenefit()" class="shrink-0 inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 rounded-md transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add benefit
                            </button>
                        </div>
                        <div class="px-5 py-4 space-y-3">
                            <template x-for="(b, i) in benefits" :key="i">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center p-3 rounded-lg border border-neutral-200 bg-neutral-50/50 hover:bg-white transition-colors">
                                    <div class="sm:col-span-4">
                                        <label class="block text-[10px] font-medium uppercase tracking-wide text-neutral-500 mb-1 sm:hidden">Icon</label>
                                        <select x-model="benefits[i].icon" :name="'benefits['+i+'][icon]'" class="form-select text-sm w-full">
                                            <template x-for="ic in icons" :key="ic.value">
                                                <option :value="ic.value" x-text="ic.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="sm:col-span-7">
                                        <label class="block text-[10px] font-medium uppercase tracking-wide text-neutral-500 mb-1 sm:hidden">Benefit text</label>
                                        <input type="text" x-model="benefits[i].text" :name="'benefits['+i+'][text]'" class="form-input text-sm w-full" placeholder="e.g. 100% Safe & Effective">
                                    </div>
                                    <div class="sm:col-span-1 flex sm:justify-center">
                                        <button type="button" @click="removeBenefit(i)" class="inline-flex items-center justify-center w-8 h-8 text-neutral-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Remove">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="benefits.length === 0">
                                <div class="text-center py-6 text-xs text-neutral-400 border border-dashed border-neutral-200 rounded-lg">
                                    No benefits added. Global tenant defaults will be shown on the product page.
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Variants Card (optional) --}}
                    <div class="card overflow-hidden" x-data="variantManager({{ json_encode(old('variants', [])) }})">
                        <div class="px-5 py-4 border-b border-neutral-100 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="text-sm font-semibold text-neutral-900">Variants <span class="text-neutral-400 font-normal">(optional)</span></h2>
                                <p class="text-xs text-neutral-500 mt-0.5">Size, colour, or pack-size options. Leave empty if this product has no variants. Blank price inherits the main product price.</p>
                            </div>
                            <button type="button" @click="addRow()" class="shrink-0 inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 rounded-md transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add variant
                            </button>
                        </div>
                        <div class="px-5 py-4 space-y-3">
                            <template x-for="(row, i) in rows" :key="row._key">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-start p-3 rounded-lg border border-neutral-200 bg-neutral-50/50">
                                    <input type="hidden" :name="'variants['+i+'][id]'" :value="row.id || ''">

                                    <div class="sm:col-span-4">
                                        <label class="block text-[10px] font-medium uppercase tracking-wide text-neutral-500 mb-1">Name</label>
                                        <input type="text" x-model="row.name" :name="'variants['+i+'][name]'" class="form-input text-sm w-full" placeholder="e.g. 500ml — Blue" required>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-[10px] font-medium uppercase tracking-wide text-neutral-500 mb-1">SKU</label>
                                        <input type="text" x-model="row.sku" :name="'variants['+i+'][sku]'" class="form-input text-sm w-full" placeholder="optional">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-[10px] font-medium uppercase tracking-wide text-neutral-500 mb-1">Price</label>
                                        <input type="number" step="0.01" min="0" x-model="row.price" :name="'variants['+i+'][price]'" class="form-input text-sm w-full" placeholder="inherit">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-[10px] font-medium uppercase tracking-wide text-neutral-500 mb-1">Stock</label>
                                        <input type="number" min="0" x-model.number="row.stock_quantity" :name="'variants['+i+'][stock_quantity]'" class="form-input text-sm w-full" placeholder="0">
                                    </div>
                                    <div class="sm:col-span-1 flex items-center sm:justify-center sm:pt-5">
                                        <label class="inline-flex items-center gap-1 text-xs text-neutral-600 cursor-pointer">
                                            <input type="hidden" :name="'variants['+i+'][is_active]'" value="0">
                                            <input type="checkbox" :name="'variants['+i+'][is_active]'" value="1" x-model="row.is_active" class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                                            <span>Active</span>
                                        </label>
                                    </div>
                                    <div class="sm:col-span-1 flex sm:justify-center sm:pt-5">
                                        <button type="button" @click="removeRow(i)" class="inline-flex items-center justify-center w-8 h-8 text-neutral-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Remove variant">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="rows.length === 0">
                                <div class="text-center py-6 text-xs text-neutral-400 border border-dashed border-neutral-200 rounded-lg">
                                    No variants. Click <span class="font-medium text-neutral-600">Add variant</span> if this product comes in multiple sizes, colours, or pack sizes.
                                </div>
                            </template>
                            @error('variants')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                            @foreach ($errors->get('variants.*') as $key => $messages)
                                @foreach ($messages as $msg)
                                    <p class="form-error">{{ $key }}: {{ $msg }}</p>
                                @endforeach
                            @endforeach
                        </div>
                    </div>

                </div>

                {{-- ============================================
                     RIGHT COLUMN (~35%)
                     ============================================ --}}
                <div class="space-y-5" style="width: 340px; flex-shrink: 0;">

                    {{-- Status Card --}}
                    <div class="card overflow-hidden">
                        <div class="px-5 py-4">
                            <h2 class="text-sm font-semibold text-neutral-900">Status</h2>
                        </div>
                        <div class="px-5 pb-5">
                            <select name="is_active" class="form-input w-full">
                                <option value="1" {{ old('is_active', true) ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ !old('is_active', true) ? 'selected' : '' }}>Draft</option>
                            </select>
                        </div>
                    </div>

                    {{-- Product Organization Card --}}
                    <div class="card overflow-hidden">
                        <div class="px-5 py-4">
                            <h2 class="text-sm font-semibold text-neutral-900">Product organization</h2>
                        </div>
                        <div class="px-5 pb-5 space-y-4">
                            <div>
                                <label for="category_id" class="form-label form-label-required">Category</label>
                                <select name="category_id" id="category_id" required
                                        class="form-input w-full @error('category_id') form-input-error @enderror">
                                    <option value="">Select category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="brand_id" class="form-label">Brand</label>
                                <select name="brand_id" id="brand_id"
                                        class="form-input w-full @error('brand_id') form-input-error @enderror">
                                    <option value="">Select brand</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('brand_id')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="seller_id" class="form-label">Seller</label>
                                <select name="seller_id" id="seller_id"
                                        class="form-input w-full @error('seller_id') form-input-error @enderror">
                                    <option value="">Select seller</option>
                                    @foreach($sellers as $seller)
                                        <option value="{{ $seller->id }}" {{ old('seller_id') == $seller->id ? 'selected' : '' }}>
                                            {{ $seller->store_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('seller_id')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="border-t border-neutral-100 pt-4">
                                <label for="tags" class="form-label">Tags</label>
                                <input type="text" name="tags" id="tags"
                                       value="{{ old('tags', '') }}"
                                       class="form-input w-full @error('tags') form-input-error @enderror"
                                       placeholder="e.g. bestseller, new-arrival, summer-sale">
                                <p class="text-xs text-neutral-500 mt-1">Comma-separated tags for feed targeting and campaign labels.</p>
                                @error('tags')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Featured Card --}}
                    <div class="card overflow-hidden">
                        <div class="px-5 py-4">
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                                       class="form-checkbox">
                                <span class="text-sm font-semibold text-neutral-900">Featured product</span>
                            </label>
                            <p class="text-xs text-neutral-500 mt-1 ml-6">Show this product in featured sections on the storefront.</p>
                        </div>
                    </div>

                    {{-- New Arrivals Card --}}
                    <div class="card overflow-hidden">
                        <div class="px-5 py-4">
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="is_new_arrival" value="1" {{ old('is_new_arrival') ? 'checked' : '' }}
                                       class="form-checkbox">
                                <span class="text-sm font-semibold text-neutral-900">Show in New Arrivals</span>
                            </label>
                            <p class="text-xs text-neutral-500 mt-1 ml-6">Tick to show this product in the New Arrivals sections (homepage &amp; /new-arrivals page). New Arrivals is manually curated — only ticked products appear.</p>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Save handled by global sticky bar in admin layout --}}
        </form>
    </div>

    <x-slot name="styles">
        @vite('resources/js/admin-editor.js')
        <style>
            .ql-editor { min-height: 250px; font-size: 14px; line-height: 1.7; }
            .ql-toolbar.ql-snow { border-color: #d4d4d4; border-radius: 8px 8px 0 0; background: #fafafa; }
            .ql-container.ql-snow { border-color: #d4d4d4; border-radius: 0 0 8px 8px; }
            .ql-editor:focus { outline: none; }
            .ql-editor img { max-width: 100%; height: auto; border-radius: 8px; margin: 12px 0; }
        </style>
    </x-slot>

    <x-slot name="scripts">
    <script>
        function productForm() {
            return {
                slug: '{{ old("slug", "") }}',
                slugManual: {{ old('slug') ? 'true' : 'false' }},
                seoTitle: '{{ addslashes(old("meta_title", "")) }}',
                seoDescription: '{{ addslashes(old("meta_description", "")) }}',
                seoManualTitle: {{ old('meta_title') ? 'true' : 'false' }},
                seoManualDescription: {{ old('meta_description') ? 'true' : 'false' }},
                toSlug(text) {
                    return text
                        .toLowerCase()
                        .trim()
                        .replace(/[^\w\s-]/g, '')
                        .replace(/[\s_]+/g, '-')
                        .replace(/-+/g, '-')
                        .replace(/^-+|-+$/g, '');
                },
                autoGenerateSeo(title) {
                    if (!this.seoManualTitle || this.seoTitle === '') {
                        this.seoTitle = title ? (title + ' - {{ addslashes(config("app.name")) }}').substring(0, 70) : '';
                        this.seoManualTitle = false;
                    }
                    if (!this.seoManualDescription || this.seoDescription === '') {
                        this.seoDescription = title ? ('Shop ' + title + ' at {{ addslashes(config("app.name")) }}. Great prices, fast shipping, and quality guaranteed.').substring(0, 160) : '';
                        this.seoManualDescription = false;
                    }
                },
                autoFillSeo() {
                    var titleEl = document.getElementById('name');
                    var title = titleEl ? titleEl.value : '';
                    if (!title) {
                        if (typeof toastr !== 'undefined') toastr.warning('Enter a product name first.');
                        return;
                    }
                    this.seoTitle = (title + ' - {{ addslashes(config("app.name")) }}').substring(0, 70);
                    this.seoDescription = ('Shop ' + title + ' at {{ addslashes(config("app.name")) }}. Great prices, fast shipping, and quality guaranteed.').substring(0, 160);
                    this.seoManualTitle = false;
                    this.seoManualDescription = false;
                }
            };
        }

        function imageManager() {
            return {
                mainPreview: null,
                mainDragOver: false,
                galleryPreviews: [],
                galleryDragOver: false,
                galleryFileList: new DataTransfer(),
                handleMainImage(file) {
                    if (!file || !file.type.startsWith('image/')) return;
                    if (file.size > 20 * 1024 * 1024) {
                        toastr.error(file.name + ' exceeds 20MB limit.');
                        return;
                    }
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    this.$refs.mainFileInput.files = dt.files;
                    const reader = new FileReader();
                    reader.onload = (e) => { this.mainPreview = e.target.result; };
                    reader.readAsDataURL(file);
                },
                removeMainImage() {
                    this.mainPreview = null;
                    this.$refs.mainFileInput.value = '';
                },
                handleGalleryFiles(files) {
                    for (const file of files) {
                        if (!file.type.startsWith('image/')) continue;
                        if (file.size > 20 * 1024 * 1024) {
                            toastr.error(file.name + ' exceeds 20MB limit.');
                            continue;
                        }
                        this.galleryFileList.items.add(file);
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            this.galleryPreviews.push({ url: e.target.result, name: file.name });
                        };
                        reader.readAsDataURL(file);
                    }
                    this.$refs.galleryInput.files = this.galleryFileList.files;
                },
                removeGalleryImage(index) {
                    this.galleryPreviews.splice(index, 1);
                    this.galleryFileList.items.remove(index);
                    this.$refs.galleryInput.files = this.galleryFileList.files;
                }
            };
        }

        function variantManager(initial) {
            let nextKey = 0;
            const normalise = (v) => ({
                _key: nextKey++,
                id: v.id ?? null,
                name: v.name ?? '',
                sku: v.sku ?? '',
                price: (v.price === null || v.price === undefined) ? '' : v.price,
                stock_quantity: v.stock_quantity ?? 0,
                is_active: v.is_active === undefined ? true : !!v.is_active,
            });
            return {
                rows: (Array.isArray(initial) ? initial : []).map(normalise),
                addRow() {
                    this.rows.push(normalise({ name: '', sku: '', price: '', stock_quantity: 0, is_active: true }));
                },
                removeRow(index) {
                    this.rows.splice(index, 1);
                },
            };
        }

        // Init Quill rich text editor (wait for module to load)
        function tryInitEditor() {
            if (typeof window.initQuillEditor === 'function') {
                window.initQuillEditor('#description', {
                    uploadUrl: '{{ route("admin.editor.upload-image") }}',
                    placeholder: 'Write product description...'
                });
            } else {
                setTimeout(tryInitEditor, 100);
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', tryInitEditor);
        } else {
            tryInitEditor();
        }
    </script>
    </x-slot>
</x-layouts.admin>
