<x-layouts.app>
    @push('meta')
        <meta name="robots" content="noindex, nofollow">
    @endpush

    <x-slot name="title">Product Image Manager</x-slot>

    @push('styles')
    <style>
        .img-mgr svg { width: auto !important; height: auto !important; max-width: none !important; max-height: none !important; display: inline-block !important; }
        .img-mgr * { box-sizing: border-box; }
    </style>
    @endpush

    <div class="img-mgr" style="max-width:1100px;margin:0 auto;padding:24px 16px">

        {{-- Header --}}
        <div style="margin-bottom:20px">
            <h1 style="font-size:22px;font-weight:700;color:#1a1a1a;margin:0">Product Image Manager</h1>
            <p style="font-size:13px;color:#888;margin-top:4px">Manage product images & titles. Products with fewest images shown first.</p>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div style="margin-bottom:16px;padding:12px 16px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;font-size:13px;color:#065f46">
                &#10004; {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div style="margin-bottom:16px;padding:12px 16px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;font-size:13px;color:#991b1b">
                @foreach($errors->all() as $error)<p style="margin:0">{{ $error }}</p>@endforeach
            </div>
        @endif

        {{-- Stats --}}
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:18px">
            @php
                $statItems = [
                    ['label' => 'Total Products', 'value' => $stats['total'], 'bg' => '#f1f5f9', 'color' => '#334155'],
                    ['label' => 'Inactive', 'value' => $stats['inactive'], 'bg' => '#fef2f2', 'color' => '#dc2626'],
                    ['label' => 'No Images', 'value' => $stats['no_images'], 'bg' => '#fffbeb', 'color' => '#d97706'],
                    ['label' => '1 Image', 'value' => $stats['single_image'], 'bg' => '#fff7ed', 'color' => '#ea580c'],
                ];
            @endphp
            @foreach($statItems as $stat)
                <div style="background:{{ $stat['bg'] }};border-radius:10px;padding:14px 16px;text-align:center">
                    <div style="font-size:24px;font-weight:700;color:{{ $stat['color'] }}">{{ number_format($stat['value']) }}</div>
                    <div style="font-size:10px;color:#888;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-top:2px">{{ $stat['label'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Filters --}}
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:12px 16px;margin-bottom:18px">
            <form method="GET" action="{{ route('tools.image-manager') }}" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or SKU..."
                       style="flex:1;min-width:200px;padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#f9fafb">
                <select name="images" style="padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;background:#f9fafb;outline:none">
                    <option value="">All Images</option>
                    <option value="0" {{ request('images') === '0' ? 'selected' : '' }}>No images</option>
                    <option value="1" {{ request('images') === '1' ? 'selected' : '' }}>1 image only</option>
                    <option value="multiple" {{ request('images') === 'multiple' ? 'selected' : '' }}>Multiple images</option>
                </select>
                <button type="submit" style="padding:8px 18px;background:#1a1a1a;color:#fff;font-size:13px;font-weight:600;border-radius:8px;border:none;cursor:pointer">Filter</button>
                @if(request()->hasAny(['search', 'images']))
                    <a href="{{ route('tools.image-manager') }}" style="font-size:13px;color:#6366f1;text-decoration:none;font-weight:500">Clear</a>
                @endif
            </form>
        </div>

        {{-- Count --}}
        <p style="margin-bottom:8px;font-size:12px;color:#999">
            Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ number_format($products->total()) }} products
        </p>

        {{-- Product List --}}
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
            @forelse($products as $product)
                @php
                    $savedUrls = $product->specifications['amazon_image_urls'] ?? [];
                    $initImages = array_pad(array_slice($savedUrls, 0, 7), 7, '');
                @endphp
                <div x-data="{
                        open: false,
                        amazonTitle: '',
                        amazonPrice: '{{ $product->cost_price ? number_format($product->cost_price, 2, '.', '') : '' }}',
                        amazonImages: {{ json_encode($initImages) }}
                     }"
                     style="{{ !$loop->last ? 'border-bottom:1px solid #f3f4f6' : '' }}">

                    {{-- Row --}}
                    <div @click="open = !open"
                         style="display:flex;align-items:center;gap:12px;padding:10px 16px;cursor:pointer"
                         onmouseover="this.style.background='#fafbfc'" onmouseout="this.style.background='transparent'">

                        {{-- # --}}
                        <span style="font-size:11px;color:#bbb;font-family:monospace;width:20px;text-align:right;flex-shrink:0">
                            {{ $loop->iteration + ($products->currentPage() - 1) * $products->perPage() }}
                        </span>

                        {{-- Thumb --}}
                        <div style="width:96px;height:96px;border-radius:8px;overflow:hidden;flex-shrink:0;background:#f5f5f5;border:1px solid #eee">
                            <img src="{{ $product->primary_image_url }}" alt="" loading="lazy"
                                 style="width:96px;height:96px;object-fit:cover;display:block"
                                 onerror="this.src='/images/no-product-image.svg'">
                        </div>

                        {{-- Info --}}
                        <div style="flex:1;min-width:0;overflow:hidden" onclick="event.stopPropagation()">
                            <div onclick="navigator.clipboard.writeText(this.innerText).then(()=>{let t=this;t.dataset.orig=t.style.color;t.style.color='#059669';setTimeout(()=>t.style.color=t.dataset.orig,600)})"
                                 title="Click to copy title"
                                 style="font-size:13px;font-weight:600;color:#1a1a1a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:copy">{{ $product->name }}</div>
                            <div style="font-size:11px;color:#999;margin-top:2px">
                                {{ $product->sku ?? '—' }}
                                &middot;
                                <span style="font-weight:600;color:{{ $product->images_count === 0 ? '#ef4444' : ($product->images_count === 1 ? '#f59e0b' : '#10b981') }}">{{ $product->images_count }} {{ Str::plural('img', $product->images_count) }}</span>
                                &middot;
                                <span style="font-weight:500">₹{{ number_format($product->price, 0) }}</span>
                                <span style="color:#ddd">|</span>
                                MRP: ₹{{ number_format($product->mrp, 0) }}
                                @if($product->cost_price)
                                    <span style="color:#ddd">|</span>
                                    <span style="font-weight:600;color:#6366f1">Amz: ₹{{ number_format($product->cost_price, 0) }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Badge --}}
                        <span style="font-size:10px;padding:3px 10px;border-radius:20px;font-weight:700;text-transform:uppercase;letter-spacing:0.3px;flex-shrink:0;white-space:nowrap;
                            {{ $product->is_active ? 'background:#ecfdf5;color:#059669;border:1px solid #a7f3d0' : 'background:#fef2f2;color:#dc2626;border:1px solid #fecaca' }}">
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                        </span>

                        {{-- Toggle --}}
                        <form method="POST" action="{{ route('tools.image-manager.toggle', $product) }}" onclick="event.stopPropagation()" style="flex-shrink:0">
                            @csrf @method('PUT')
                            <button type="submit" style="font-size:11px;font-weight:600;padding:5px 12px;border-radius:6px;border:none;cursor:pointer;white-space:nowrap;
                                {{ $product->is_active ? 'color:#dc2626;background:#fef2f2' : 'color:#059669;background:#ecfdf5' }}">
                                {{ $product->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>

                        {{-- Delete --}}
                        <form method="POST" action="{{ route('tools.image-manager.destroy', $product) }}" onclick="event.stopPropagation()" style="flex-shrink:0"
                              onsubmit="return confirm('DELETE permanently?\n\n{{ addslashes($product->name) }}\n\nThis cannot be undone.')">
                            @csrf @method('DELETE')
                            <button type="submit" style="font-size:11px;font-weight:600;padding:5px 10px;border-radius:6px;border:none;cursor:pointer;color:#fff;background:#ef4444;white-space:nowrap"
                                    onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                                &#10005; Remove
                            </button>
                        </form>

                        {{-- Arrow (text-based, no SVG) --}}
                        <span :style="open && 'transform:rotate(180deg)'" style="font-size:14px;color:#ccc;transition:transform 0.2s;flex-shrink:0;display:inline-block;line-height:1">&#9660;</span>
                    </div>

                    {{-- Expanded --}}
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         style="background:#f9fafb;border-top:1px solid #f0f0f0;padding:16px 16px 16px 50px">

                        {{-- Current Images --}}
                        <div style="margin-bottom:16px">
                            <div style="margin-bottom:8px">
                                <span style="font-size:11px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:0.5px">Current Images</span>
                                <span style="font-size:10px;background:#e5e7eb;color:#666;padding:1px 7px;border-radius:10px;font-weight:600;margin-left:6px">{{ $product->images_count }}</span>
                            </div>
                            <div style="display:flex;flex-wrap:wrap;gap:8px">
                                @forelse($product->images as $image)
                                    <div style="position:relative;width:200px;height:200px;border-radius:8px;overflow:visible"
                                         onmouseover="this.querySelector('.del-btn').style.display='block'" onmouseout="this.querySelector('.del-btn').style.display='none'">
                                        <div style="width:200px;height:200px;border-radius:8px;overflow:hidden;border:2px solid #e5e7eb;background:#fff;cursor:copy"
                                             title="Click to copy image URL"
                                             onclick="var u=this.querySelector('img').src;navigator.clipboard.writeText(u).then(()=>{this.style.borderColor='#059669';var s=this;setTimeout(()=>s.style.borderColor='#e5e7eb',600)})">
                                            <img src="{{ str_starts_with($image->url, 'http') ? $image->url : asset('storage/' . ltrim($image->url, '/storage/')) }}"
                                                 alt="{{ $image->alt_text }}" loading="lazy"
                                                 style="width:200px;height:200px;object-fit:cover;display:block;pointer-events:none"
                                                 onerror="this.src='/images/no-product-image.svg'">
                                        </div>
                                        @if($image->is_primary)
                                            <span style="position:absolute;top:2px;left:2px;font-size:8px;background:#6366f1;color:#fff;padding:1px 5px;border-radius:4px;font-weight:700">P</span>
                                        @endif
                                        <form method="POST" action="{{ route('tools.image-manager.delete-image', $image) }}"
                                              class="del-btn" style="position:absolute;top:-4px;right:-4px;display:none">
                                            @csrf @method('DELETE')
                                            <button type="submit" onclick="return confirm('Delete this image?')"
                                                    style="width:18px;height:18px;border-radius:50%;background:#ef4444;color:#fff;border:2px solid #fff;cursor:pointer;font-size:10px;line-height:1;display:flex;align-items:center;justify-content:center;font-weight:700;padding:0">&times;</button>
                                        </form>
                                    </div>
                                @empty
                                    <div style="padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:12px;color:#92400e;font-weight:500">
                                        &#9888; No images — add Amazon URLs below
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Saved Amazon URLs --}}
                        @if(!empty($product->specifications['amazon_image_urls'] ?? []))
                            <div style="margin-bottom:16px">
                                <div style="margin-bottom:8px">
                                    <span style="font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.5px">Saved Amazon URLs</span>
                                    <span style="font-size:10px;background:#e0e7ff;color:#4338ca;padding:1px 7px;border-radius:10px;font-weight:600;margin-left:6px">{{ count($product->specifications['amazon_image_urls']) }}</span>
                                </div>
                                <div style="display:flex;flex-wrap:wrap;gap:6px">
                                    @foreach($product->specifications['amazon_image_urls'] as $i => $url)
                                        <div style="font-size:11px;background:#f0f0ff;border:1px solid #c7d2fe;border-radius:6px;padding:4px 8px;font-family:monospace;max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:copy"
                                             title="{{ $url }}"
                                             onclick="navigator.clipboard.writeText('{{ $url }}').then(()=>{this.style.borderColor='#059669';this.style.background='#ecfdf5';var s=this;setTimeout(()=>{s.style.borderColor='#c7d2fe';s.style.background='#f0f0ff'},600)})">
                                            {{ $i + 1 }}. {{ $url }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Form --}}
                        <form method="POST" action="{{ route('tools.image-manager.update', $product) }}"
                              style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px">
                            @csrf @method('PUT')

                            {{-- Title + Amazon Price row --}}
                            <div style="display:grid;grid-template-columns:1fr auto;gap:20px;margin-bottom:16px">
                                {{-- Title --}}
                                <div>
                                    <label style="display:block;font-size:11px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px">New Title</label>
                                    <input type="text" name="amazon_title" x-model="amazonTitle"                                           placeholder="Paste Amazon product title..."
                                           style="width:100%;padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#f9fafb">
                                    <p style="font-size:10px;color:#bbb;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">Current: {{ $product->name }}</p>
                                </div>

                                {{-- Amazon Price --}}
                                <div>
                                    <label style="display:block;font-size:11px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px">Amazon Price (₹)</label>
                                    <input type="number" name="amazon_price" x-model="amazonPrice"                                           step="0.01" min="0" placeholder="Amazon price"
                                           style="width:130px;padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#f9fafb">
                                    <p style="font-size:10px;color:#bbb;margin-top:4px">Our: ₹{{ number_format($product->price, 0) }} | MRP: ₹{{ number_format($product->mrp, 0) }}</p>
                                </div>
                            </div>

                            {{-- Images --}}
                            <div>
                                <label style="display:block;font-size:11px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px">Amazon Image URLs (up to 7)</label>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px 10px">
                                    <template x-for="(img, index) in amazonImages" :key="index">
                                        <div style="display:flex;align-items:center;gap:6px">
                                            <span style="font-size:10px;color:#ccc;font-family:monospace;width:14px;text-align:right" x-text="index + 1"></span>
                                            <input type="url" :name="'amazon_images[' + index + ']'" x-model="amazonImages[index]"
                                                                                                     placeholder="https://m.media-amazon.com/images/I/..."
                                                   style="flex:1;padding:6px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:11px;outline:none;background:#f9fafb;font-family:monospace">
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Status bar --}}
                            <div style="display:flex;align-items:center;justify-content:flex-end;margin-top:14px;padding-top:14px;border-top:1px solid #f3f4f6">
                                <button type="submit"
                                        style="padding:9px 28px;background:#6366f1;color:#fff;font-size:13px;font-weight:600;border-radius:8px;border:none;cursor:pointer"
                                        onmouseover="this.style.background='#4f46e5'" onmouseout="this.style.background='#6366f1'">
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div style="padding:50px;text-align:center">
                    <p style="font-size:14px;color:#999">No products found. Try adjusting your filters.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
            <div style="margin-top:20px;display:flex;justify-content:center;align-items:center;gap:4px">
                @if($products->onFirstPage())
                    <span style="padding:8px 14px;font-size:13px;color:#ccc;border-radius:8px">&#8592; Prev</span>
                @else
                    <a href="{{ $products->previousPageUrl() }}" style="padding:8px 14px;font-size:13px;color:#555;border-radius:8px;text-decoration:none;font-weight:500;background:#fff;border:1px solid #e5e7eb">&#8592; Prev</a>
                @endif

                @foreach($products->getUrlRange(max(1, $products->currentPage() - 3), min($products->lastPage(), $products->currentPage() + 3)) as $page => $url)
                    @if($page == $products->currentPage())
                        <span style="padding:8px 14px;font-size:13px;background:#1a1a1a;color:#fff;border-radius:8px;font-weight:600">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" style="padding:8px 14px;font-size:13px;color:#555;border-radius:8px;text-decoration:none;background:#fff;border:1px solid #e5e7eb">{{ $page }}</a>
                    @endif
                @endforeach

                @if($products->hasMorePages())
                    <a href="{{ $products->nextPageUrl() }}" style="padding:8px 14px;font-size:13px;color:#555;border-radius:8px;text-decoration:none;font-weight:500;background:#fff;border:1px solid #e5e7eb">Next &#8594;</a>
                @else
                    <span style="padding:8px 14px;font-size:13px;color:#ccc;border-radius:8px">Next &#8594;</span>
                @endif
            </div>
            <p style="text-align:center;font-size:11px;color:#bbb;margin-top:6px">Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</p>
        @endif
    </div>
</x-layouts.app>
