<x-layouts.admin>
    <x-slot name="title">Edit {{ $product->name }}</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 text-sm">
                    <a href="{{ route('admin.products.index') }}" class="text-neutral-500 hover:text-neutral-800 transition-colors">Products</a>
                    <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-neutral-900 font-semibold">{{ $product->name }}</span>
                </div>
                @if($product->is_active)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-neutral-100 text-neutral-600">Draft</span>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary text-sm">
                    Duplicate
                </a>
                <a href="{{ route('products.show', $product) }}" target="_blank" class="btn btn-secondary text-sm">
                    View
                </a>
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button" class="btn btn-secondary text-sm">
                        More actions
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition
                         class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-neutral-200 py-1 z-50">
                        <a href="{{ route('admin.products.index') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50">Back to products</a>
                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                              onsubmit="return confirm('Are you sure you want to delete this product?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Delete product</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div x-data="productForm()" x-ref="productFormRoot">
        <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" data-track-changes>
            @csrf
            @method('PUT')

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
                                <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" required
                                       class="form-input w-full @error('name') form-input-error @enderror"
                                       x-ref="productName"
                                       @input="if(!slugManual) slug = toSlug($event.target.value); autoGenerateSeo($event.target.value)">
                                @error('name')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="slug" class="form-label">Slug</label>
                                <input type="text" name="slug" id="slug" x-model="slug"
                                       class="form-input w-full @error('slug') form-input-error @enderror"
                                       @input="slugManual = ($event.target.value.trim() !== '')">
                                @error('slug')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="short_description" class="form-label">Short description</label>
                                <textarea name="short_description" id="short_description" rows="2"
                                          class="form-input w-full @error('short_description') form-input-error @enderror">{{ old('short_description', $product->short_description) }}</textarea>
                                @error('short_description')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div x-ignore>
                                <label for="description" class="form-label form-label-required">Description</label>
                                {{-- Server-rendered preview (shows formatted content while Quill JS loads, then replaced) --}}
                                <div id="description-editor" style="border:1px solid #d4d4d4;border-radius:8px;padding:12px 15px;min-height:250px;font-size:14px;line-height:1.7;background:#fff;overflow:auto;max-height:400px;">{!! \App\Helpers\HtmlSanitizer::clean(old('description', $product->description)) !!}</div>
                                <textarea name="description" id="description" rows="6" required style="display:none"
                                          class="form-input w-full @error('description') form-input-error @enderror">{!! \App\Helpers\HtmlSanitizer::clean(old('description', $product->description)) !!}</textarea>
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
                                    @php $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first(); @endphp
                                    {{-- Current main image --}}
                                    <div class="relative w-32 h-32 rounded-lg overflow-hidden ring-2 ring-neutral-300 shrink-0"
                                         x-show="!mainImageChanged && !mainImageDeleted">
                                        @if($primaryImage)
                                            <img src="{{ $primaryImage->full_url }}" class="w-full h-full object-cover">
                                            <span class="absolute bottom-0 left-0 right-0 px-2 py-1 bg-neutral-800 text-white text-center" style="font-size: 10px; font-weight: 600;">Main Image</span>
                                        @else
                                            <div class="w-full h-full bg-neutral-100 flex items-center justify-center">
                                                <svg class="w-8 h-8 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    {{-- New main image preview --}}
                                    <div x-show="mainPreview" x-transition
                                         class="relative w-32 h-32 rounded-lg overflow-hidden ring-2 ring-neutral-300 shrink-0">
                                        <img :src="mainPreview" class="w-full h-full object-cover">
                                        <button type="button" @click="removeNewMainImage()"
                                                class="absolute top-1.5 right-1.5 w-6 h-6 bg-white/90 hover:bg-red-50 rounded-full flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                        <span class="absolute bottom-0 left-0 right-0 px-2 py-1 bg-neutral-800 text-white text-center" style="font-size: 10px; font-weight: 600;">New Main</span>
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
                                            <p class="text-sm font-medium text-neutral-700">Click to replace main image</p>
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

                                @php $galleryImages = $product->images->where('id', '!=', $primaryImage?->id)->sortBy('position'); @endphp
                                @if($galleryImages->count())
                                    <p class="text-xs text-neutral-500 mb-3">Current gallery images</p>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 mb-5">
                                        @foreach($galleryImages as $image)
                                            <div class="relative group rounded-lg overflow-hidden ring-1 ring-neutral-200"
                                                 x-show="!deletedIds.includes({{ $image->id }})">
                                                <img src="{{ $image->full_url }}" alt="{{ $image->alt_text }}" class="w-full aspect-square object-cover">
                                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors"></div>
                                                <button type="button" @click="markForDelete({{ $image->id }})"
                                                        class="absolute top-1.5 right-1.5 w-6 h-6 bg-white/90 hover:bg-red-50 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity" title="Delete">
                                                    <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <template x-for="id in deletedIds" :key="id">
                                    <input type="hidden" name="delete_images[]" :value="id">
                                </template>

                                <p class="text-xs text-neutral-500 mb-3">Add more gallery images. 20MB each.</p>
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
                                                <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 bg-green-600 text-white rounded" style="font-size: 10px; font-weight: 600;">New</span>
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
                                        <span x-text="galleryPreviews.length"></span> new image(s) to upload
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
                                        <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}" required
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
                                        <input type="number" name="mrp" id="mrp" value="{{ old('mrp', $product->mrp) }}"
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
                                        <input type="number" name="cost_price" id="cost_price" value="{{ old('cost_price', $product->cost_price) }}"
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
                                    <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}" required
                                           class="form-input w-full @error('sku') form-input-error @enderror">
                                    @error('sku')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="stock_quantity" class="form-label form-label-required">Stock quantity</label>
                                    <input type="number" name="stock_quantity" id="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" required
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
                            @php $productAttrs = $product->attributes ?? []; @endphp
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($attributes as $attribute)
                                    <div>
                                        <label class="form-label">
                                            {{ $attribute->name }}
                                            @if(isset($productAttrs[$attribute->name]))
                                                <span class="inline-flex items-center justify-center w-1.5 h-1.5 rounded-full bg-green-500 ml-1"></span>
                                            @endif
                                        </label>
                                        @if($attribute->type === 'text')
                                            <input type="text" name="product_attributes[{{ $attribute->name }}]"
                                                   value="{{ old('product_attributes.' . $attribute->name, $productAttrs[$attribute->name] ?? '') }}"
                                                   class="form-input w-full text-sm"
                                                   placeholder="Enter {{ strtolower($attribute->name) }}">
                                        @else
                                            <select name="product_attributes[{{ $attribute->name }}]" class="form-input w-full text-sm">
                                                <option value="">-- Select --</option>
                                                @foreach($attribute->values as $value)
                                                    <option value="{{ $value->value }}"
                                                            {{ old('product_attributes.' . $attribute->name, $productAttrs[$attribute->name] ?? '') === $value->value ? 'selected' : '' }}>
                                                        {{ $value->value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if($attribute->type === 'color' && $attribute->values->count())
                                                <div class="flex flex-wrap gap-1 mt-2">
                                                    @foreach($attribute->values->take(10) as $value)
                                                        @if($value->color_code)
                                                            <div class="w-5 h-5 rounded-full border border-neutral-200 cursor-default {{ isset($productAttrs[$attribute->name]) && $productAttrs[$attribute->name] === $value->value ? 'ring-2 ring-neutral-800' : '' }}" style="background-color: {{ $value->color_code }}" title="{{ $value->value }}"></div>
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

                    {{-- Product Benefits Card --}}
                    @php $productBenefits = $product->attributes['benefits'] ?? []; @endphp
                    <div class="card overflow-hidden" x-data="{
                        benefits: {{ json_encode(collect($productBenefits)->values()->toArray() ?: [['icon' => 'check', 'text' => '']]) }},
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

                    {{-- Testimonial Videos Card --}}
                    <div class="card overflow-hidden">
                        <div class="px-5 py-4">
                            <h2 class="text-sm font-semibold text-neutral-900">Testimonial Videos</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Customer testimonial videos shown on this product's detail page</p>
                        </div>
                        <div class="px-5 pb-5">
                            <input type="hidden" name="testimonial_videos" :value="JSON.stringify(testimonialVideos)">

                            <div class="space-y-2 mb-3">
                                <template x-for="(vid, i) in testimonialVideos" :key="i">
                                    <div class="flex items-center gap-3 p-2.5 bg-neutral-50 rounded-lg border border-neutral-200">
                                        <div class="w-7 h-7 bg-neutral-200 rounded flex items-center justify-center shrink-0">
                                            <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <input type="text" x-model="testimonialVideos[i]" class="form-input flex-1 text-sm" placeholder="video.mp4 or https://...">
                                        <span class="text-[10px] text-neutral-400 shrink-0" x-text="'#' + (i + 1)"></span>
                                        <button type="button" @click="removeTestimonialVideo(i)" class="p-1 text-neutral-400 hover:text-red-500 transition-colors shrink-0" title="Remove">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <div class="flex gap-2 mb-2">
                                <input type="text" x-model="newTestimonialVideo" @keydown.enter.prevent="addTestimonialVideo()" class="form-input flex-1 text-sm" placeholder="Paste video URL...">
                                <button type="button" @click="addTestimonialVideo()" class="btn btn-secondary text-xs px-3">Add URL</button>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="btn btn-primary text-xs px-3 cursor-pointer" :class="videoUploading ? 'opacity-50 pointer-events-none' : ''">
                                    <svg class="w-3.5 h-3.5 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    <span x-text="videoUploading ? 'Uploading...' : 'Upload Video'"></span>
                                    <input type="file" accept="video/mp4,video/mov,video/webm" @change="uploadTestimonialVideo($event)" class="hidden" :disabled="videoUploading">
                                </label>
                                <span class="text-xs text-neutral-400">MP4, MOV, WebM — max 50MB</span>
                            </div>
                            <p class="text-xs text-neutral-500 mt-2">If empty, global testimonial videos from site settings are shown.</p>
                        </div>
                    </div>

                    {{-- Marketplace Links Card --}}
                    <div class="card overflow-hidden">
                        <div class="px-5 py-4">
                            <h2 class="text-sm font-semibold text-neutral-900">Marketplace Links</h2>
                        </div>
                        <div class="px-5 pb-5 space-y-4">
                            <div>
                                <label for="amazon_url" class="form-label">Amazon URL</label>
                                <input type="url" name="amazon_url" id="amazon_url" value="{{ old('amazon_url', $product->amazon_url) }}"
                                       class="form-input w-full @error('amazon_url') form-input-error @enderror"
                                       placeholder="https://www.amazon.in/dp/...">
                                @error('amazon_url')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="flipkart_url" class="form-label">Flipkart URL</label>
                                <input type="url" name="flipkart_url" id="flipkart_url" value="{{ old('flipkart_url', $product->flipkart_url) }}"
                                       class="form-input w-full @error('flipkart_url') form-input-error @enderror"
                                       placeholder="https://www.flipkart.com/...">
                                @error('flipkart_url')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

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
                                    <p class="text-blue-700 text-base font-medium truncate font-sans" x-text="seoTitle || '{{ addslashes($product->meta_title ?: $product->name) }}'"></p>
                                    <p class="text-green-700 text-xs truncate mt-0.5 font-sans">{{ url('/') }}/product/<span x-text="slug || '{{ $product->slug }}'"></span></p>
                                    <p class="text-neutral-600 text-xs mt-1 line-clamp-2 font-sans" x-text="seoDescription || '{{ addslashes($product->meta_description ?: Str::limit(strip_tags($product->description), 160)) }}'"></p>
                                </div>
                            </div>

                            <div>
                                <label for="meta_title" class="form-label">Meta title</label>
                                <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title', $product->meta_title) }}"
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
                                          maxlength="160">{{ old('meta_description', $product->meta_description) }}</textarea>
                                <p class="text-xs text-neutral-400 mt-1"><span x-text="seoDescription ? seoDescription.length : 0"></span> / 160 characters</p>
                                @error('meta_description')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Variants Card (optional) --}}
                    @php
                        $initialVariants = old('variants', $product->variants->map(fn($v) => [
                            'id' => $v->id,
                            'name' => $v->name,
                            'sku' => $v->sku,
                            'price' => $v->price,
                            'stock_quantity' => $v->stock_quantity,
                            'is_active' => (bool) $v->is_active,
                        ])->values()->toArray());
                    @endphp
                    <div class="card overflow-hidden" x-data="variantManager({{ json_encode($initialVariants) }})">
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
                                <option value="1" {{ old('is_active', $product->is_active) ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ !old('is_active', $product->is_active) ? 'selected' : '' }}>Draft</option>
                            </select>
                        </div>
                    </div>

                    {{-- Social Proof Card --}}
                    <div class="card overflow-hidden">
                        <div class="px-5 py-4">
                            <h2 class="text-sm font-semibold text-neutral-900">Social Proof</h2>
                        </div>
                        <div class="px-5 pb-5 space-y-3">
                            <div>
                                <label for="social_proof_text" class="form-label">Social Proof Text</label>
                                <input type="text" name="social_proof_text" id="social_proof_text" value="{{ old('social_proof_text', $product->social_proof_text ?? '') }}" class="form-input w-full" placeholder="e.g. {30-50}+ bought this month">
                                <p class="text-xs text-neutral-500 mt-1">Use <code class="bg-neutral-100 px-1 rounded">{min-max}</code> for a number that changes daily. E.g. <code class="bg-neutral-100 px-1 rounded">{30-50}+ bought</code></p>
                            </div>
                        </div>
                    </div>

                    {{-- Stats Carousel Card --}}
                    <div class="card overflow-hidden" x-data="{
                        stats: @js($product->stats_carousel ?? []),
                        add() { this.stats.push({ value: '', unit: '', label: '' }); },
                        remove(i) { this.stats.splice(i, 1); }
                    }">
                        <div class="px-5 py-4">
                            <div class="flex items-center justify-between">
                                <h2 class="text-sm font-semibold text-neutral-900">Stats Carousel</h2>
                                <button type="button" @click="add()" class="text-xs font-medium text-primary-600 hover:text-primary-800 flex items-center gap-1 shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Add
                                </button>
                            </div>
                            <p class="text-xs text-neutral-500 mt-0.5">Leave empty to use global stats.</p>
                        </div>
                        <input type="hidden" name="stats_carousel" :value="JSON.stringify(stats)">
                        <div class="px-5 pb-5 space-y-2">
                            <template x-for="(stat, index) in stats" :key="index">
                                <div class="p-3 rounded-lg border border-neutral-200 bg-neutral-50">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-semibold text-neutral-500">Stat <span x-text="index + 1"></span></span>
                                        <button type="button" @click="remove(index)" class="w-6 h-6 flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-4 gap-2">
                                        <input type="text" x-model="stat.value" placeholder="Value (e.g. 44)" class="form-input text-sm text-center font-bold">
                                        <input type="text" x-model="stat.unit" placeholder="Unit (e.g. %)" class="form-input text-sm text-center">
                                        <input type="text" x-model="stat.label" placeholder="Label (e.g. Better Work-Life Balance)" class="form-input text-sm col-span-2">
                                    </div>
                                </div>
                            </template>
                            <p x-show="stats.length === 0" class="text-xs text-neutral-400 italic py-2">No product-specific stats. Global stats (from Settings &gt; Features) will be used instead.</p>
                            <div x-show="stats.length > 0" class="mt-3 p-3 rounded-lg bg-green-50 border border-green-200">
                                <p class="text-xs font-medium text-green-800 mb-1">Preview:</p>
                                <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1 text-green-900">
                                    <template x-for="(stat, i) in stats" :key="'preview-'+i">
                                        <span class="text-sm">
                                            <strong x-text="stat.value"></strong><span x-text="stat.unit"></span>
                                            <span class="text-green-700 text-xs" x-text="stat.label"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Pack Config Card --}}
                    @php $pc = $product->pack_config ?? []; @endphp
                    <div class="card overflow-hidden">
                        <div class="px-5 py-4">
                            <h2 class="text-sm font-semibold text-neutral-900">Pack Config</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Leave empty to use global settings.</p>
                        </div>
                        <div class="px-5 pb-5 space-y-3">
                            <div>
                                <label for="pack_unit_label" class="form-label">Unit Label</label>
                                <input type="text" name="pack_unit_label" id="pack_unit_label" value="{{ old('pack_unit_label', $pc['unit_label'] ?? '') }}" class="form-input w-full" placeholder="e.g. Capsules, Tablets">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="pack_units_per_qty" class="form-label">Units/Pack</label>
                                    <input type="number" name="pack_units_per_qty" id="pack_units_per_qty" value="{{ old('pack_units_per_qty', $pc['units_per_qty'] ?? '') }}" class="form-input w-full" placeholder="e.g. 30" min="1">
                                </div>
                                <div>
                                    <label for="pack_months_per_qty" class="form-label">Months/Pack</label>
                                    <input type="number" name="pack_months_per_qty" id="pack_months_per_qty" value="{{ old('pack_months_per_qty', $pc['months_per_qty'] ?? '') }}" class="form-input w-full" placeholder="e.g. 1" min="1">
                                </div>
                            </div>
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
                                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            @if(\Illuminate\Support\Facades\Schema::hasTable('category_product'))
                            @php
                                $currentExtraIds = old('additional_categories', $product->categories->pluck('id')->toArray());
                            @endphp
                            <div>
                                <label class="form-label">Also show in</label>
                                <div class="space-y-1.5 max-h-40 overflow-y-auto border border-neutral-200 rounded-lg p-2.5">
                                    @foreach($categories as $category)
                                        <label class="flex items-center gap-2 cursor-pointer text-sm text-neutral-700 hover:text-neutral-900">
                                            <input type="checkbox" name="additional_categories[]" value="{{ $category->id }}"
                                                   {{ in_array($category->id, $currentExtraIds) ? 'checked' : '' }}
                                                   class="form-checkbox rounded text-primary-600">
                                            {{ $category->name }}
                                        </label>
                                    @endforeach
                                </div>
                                <p class="text-xs text-neutral-500 mt-1">Product will appear in all selected categories.</p>
                            </div>
                            @endif

                            <div>
                                <label for="brand_id" class="form-label">Brand</label>
                                <select name="brand_id" id="brand_id"
                                        class="form-input w-full @error('brand_id') form-input-error @enderror">
                                    <option value="">Select brand</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>
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
                                        <option value="{{ $seller->id }}" {{ old('seller_id', $product->seller_id) == $seller->id ? 'selected' : '' }}>
                                            {{ $seller->store_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('seller_id')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="tags" class="form-label">Tags</label>
                                <input type="text" name="tags" id="tags"
                                       value="{{ old('tags', is_array($product->tags) ? implode(', ', $product->tags) : '') }}"
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
                                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}
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
                                <input type="checkbox" name="is_new_arrival" value="1" {{ old('is_new_arrival', $product->is_new_arrival) ? 'checked' : '' }}
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

        {{-- Danger Zone --}}
        <div class="mt-8 card overflow-hidden" style="border-color: #fca5a5;">
            <div class="px-5 py-4">
                <h2 class="text-sm font-semibold text-red-700">Danger zone</h2>
            </div>
            <div class="px-5 pb-5 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-900">Delete this product</p>
                    <p class="text-xs text-neutral-500">Once deleted, this action cannot be undone.</p>
                </div>
                <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                      onsubmit="return confirm('Are you sure you want to delete &quot;{{ $product->name }}&quot;? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete product</button>
                </form>
            </div>
        </div>
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
                testimonialVideos: @js($product->testimonial_videos ?? []),
                newTestimonialVideo: '',
                videoUploading: false,
                addTestimonialVideo() {
                    if (this.newTestimonialVideo.trim()) {
                        this.testimonialVideos.push(this.newTestimonialVideo.trim());
                        this.newTestimonialVideo = '';
                    }
                },
                removeTestimonialVideo(index) {
                    this.testimonialVideos.splice(index, 1);
                },
                async uploadTestimonialVideo(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    if (file.size > 50 * 1024 * 1024) {
                        alert('Video must be under 50MB');
                        event.target.value = '';
                        return;
                    }
                    this.videoUploading = true;
                    const formData = new FormData();
                    formData.append('video', file);
                    try {
                        const res = await fetch('{{ route("admin.upload-video") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            credentials: 'same-origin',
                            body: formData,
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.testimonialVideos.push(data.url);
                        } else {
                            alert(data.message || 'Upload failed');
                        }
                    } catch (e) {
                        alert('Upload failed. Please try again.');
                    }
                    this.videoUploading = false;
                    event.target.value = '';
                },
                slug: '{{ old("slug", $product->slug) }}',
                slugManual: true,
                seoTitle: '{{ addslashes(old("meta_title", $product->meta_title ?? "")) }}',
                seoDescription: '{{ addslashes(old("meta_description", $product->meta_description ?? "")) }}',
                seoManualTitle: {{ old('meta_title', $product->meta_title) ? 'true' : 'false' }},
                seoManualDescription: {{ old('meta_description', $product->meta_description) ? 'true' : 'false' }},
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
                deletedIds: [],
                mainPreview: null,
                mainImageChanged: false,
                mainImageDeleted: false,
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
                    this.mainImageChanged = true;
                    const reader = new FileReader();
                    reader.onload = (e) => { this.mainPreview = e.target.result; };
                    reader.readAsDataURL(file);
                },
                removeNewMainImage() {
                    this.mainPreview = null;
                    this.mainImageChanged = false;
                    this.$refs.mainFileInput.value = '';
                },
                markForDelete(id) {
                    if (!confirm('Mark this image for deletion?')) return;
                    this.deletedIds.push(id);
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
