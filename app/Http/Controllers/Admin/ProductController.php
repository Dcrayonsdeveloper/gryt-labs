<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Seller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::with(['category', 'seller']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('sku', 'ilike', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by seller
        if ($request->filled('seller')) {
            $query->where('seller_id', $request->seller);
        }

        $lowStockThreshold = (int) Setting::get('low_stock_threshold', 10);

        // Filter by stock status
        if ($request->filled('stock')) {
            if ($request->stock === 'out') {
                $query->where('stock_quantity', '<=', 0);
            } elseif ($request->stock === 'low') {
                $query->whereBetween('stock_quantity', [1, $lowStockThreshold]);
            }
        }

        $perPage = min((int) $request->input('per_page', 10), 100);
        $products = $query->latest()->paginate($perPage)->withQueryString();

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $sellers = Seller::with('user')->orderBy('store_name')->get();

        // Stats
        $stats = [
            'total' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'inactive' => Product::where('is_active', false)->count(),
            'out_of_stock' => Product::where('stock_quantity', '<=', 0)->count(),
        ];

        return view('admin.products.index', compact('products', 'categories', 'sellers', 'stats', 'lowStockThreshold'));
    }

    public function bulkEdit(Request $request): View|RedirectResponse
    {
        $ids = array_filter(explode(',', $request->query('ids', '')));

        if (empty($ids)) {
            return redirect()->route('admin.products.index')
                ->with('error', 'No products selected for bulk editing.');
        }

        $products = Product::whereIn('id', $ids)->with('category')->orderBy('name')->get();

        if ($products->isEmpty()) {
            return redirect()->route('admin.products.index')
                ->with('error', 'No valid products found.');
        }

        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.bulk-edit', compact('products', 'categories'));
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.name' => 'required|string|max:255',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.mrp' => 'nullable|numeric|min:0',
            'products.*.stock_quantity' => 'required|integer|min:0',
            'products.*.category_id' => 'required|exists:categories,id',
            'products.*.is_active' => 'nullable',
        ]);

        $updated = 0;

        foreach ($request->input('products') as $id => $data) {
            $product = Product::find($id);
            if (!$product) {
                continue;
            }

            $product->update([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'price' => $data['price'],
                'mrp' => $data['mrp'] ?: null,
                'stock_quantity' => $data['stock_quantity'],
                'category_id' => $data['category_id'],
                'is_active' => !empty($data['is_active']),
            ]);

            $updated++;
        }

        \App\Http\Middleware\CacheResponse::bustAll();

        return redirect()->route('admin.products.index')
            ->with('success', "{$updated} product(s) updated successfully.");
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,approve,delete',
            'ids' => 'required|string',
        ]);

        $ids = json_decode($validated['ids'], true);

        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'No products selected.');
        }

        $action = $validated['action'];

        if ($action === 'delete') {
            $count = Product::whereIn('id', $ids)->count();
            Product::whereIn('id', $ids)->delete();
        } else {
            // Update per-model (not a mass query-builder update) so ProductObserver
            // fires and each change is pushed to Shiprocket Checkout. A bulk
            // ->update() skips model events and would leave Shiprocket stale.
            $changes = match ($action) {
                'activate'   => ['is_active' => true, 'status' => 'approved'],
                'deactivate' => ['is_active' => false],
                'approve'    => ['status' => 'approved'],
            };

            $products = Product::whereIn('id', $ids)->get();
            $count = $products->count();

            foreach ($products as $product) {
                $product->update($changes);
            }
        }

        $actionLabel = match ($validated['action']) {
            'activate' => 'activated',
            'deactivate' => 'deactivated',
            'approve' => 'approved',
            'delete' => 'deleted',
        };

        \App\Http\Middleware\CacheResponse::bustAll();

        return back()->with('success', "{$count} product(s) {$actionLabel} successfully.");
    }

    public function create(): View
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $sellers = Seller::with('user')->orderBy('store_name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $attributes = Attribute::with('values')->orderBy('name')->get();

        return view('admin.products.create', compact('categories', 'sellers', 'brands', 'attributes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'sku' => 'required|string|max:100|unique:products',
            'price' => 'required|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0|gte:price',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'additional_categories' => 'nullable|array',
            'additional_categories.*' => 'integer|exists:categories,id',
            'seller_id' => 'nullable|exists:sellers,id',
            'brand_id' => 'nullable|exists:brands,id',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_new_arrival' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'main_image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:20480',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,jpg,png,webp,gif|max:20480',
            'product_attributes' => 'nullable|array',
            'product_attributes.*' => 'nullable|string|max:255',
            'amazon_url' => 'nullable|url|max:500',
            'flipkart_url' => 'nullable|url|max:500',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.sku' => 'nullable|string|max:50',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.stock_quantity' => 'nullable|integer|min:0',
            'variants.*.is_active' => 'nullable|boolean',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_new_arrival'] = $request->boolean('is_new_arrival');
        $validated['seller_id'] = ($validated['seller_id'] ?? null) ?: null;
        $validated['brand_id'] = ($validated['brand_id'] ?? null) ?: null;
        $validated['amazon_url'] = ($validated['amazon_url'] ?? null) ?: null;
        $validated['flipkart_url'] = ($validated['flipkart_url'] ?? null) ?: null;
        $additionalCategories = $validated['additional_categories'] ?? [];
        unset($validated['additional_categories']);

        // Save attributes as JSON
        $productAttributes = collect($request->input('product_attributes', []))
            ->filter(fn($value) => $value !== null && $value !== '')
            ->toArray();

        // Merge per-product benefits into attributes
        $benefits = collect($request->input('benefits', []))
            ->filter(fn($b) => !empty($b['text']))
            ->values()
            ->toArray();
        if (!empty($benefits)) {
            $productAttributes['benefits'] = $benefits;
        }

        $validated['attributes'] = !empty($productAttributes) ? $productAttributes : null;

        // Per-product stats carousel
        $statsCarousel = json_decode($request->input('stats_carousel', '[]'), true);
        if (is_array($statsCarousel)) {
            $statsCarousel = array_values(array_filter($statsCarousel, fn($s) => !empty($s['value']) || !empty($s['label'])));
        }
        $validated['stats_carousel'] = !empty($statsCarousel) ? $statsCarousel : null;

        unset($validated['images'], $validated['main_image'], $validated['product_attributes']);

        // Collect SEO fields into seo_data JSON column
        $validated['seo_data'] = array_filter([
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ]);
        unset($validated['meta_title'], $validated['meta_description']);

        // Parse comma-separated tags into JSON array
        $tagsInput = trim($request->input('tags', ''));
        $validated['tags'] = !empty($tagsInput)
            ? array_values(array_filter(array_map('trim', explode(',', $tagsInput))))
            : null;

        $validated['status'] = 'approved';
        $validated['published_at'] = now();

        $product = Product::create($validated);

        if (\Illuminate\Support\Facades\Schema::hasTable('category_product')) {
            $syncIds = collect($additionalCategories)->push($validated['category_id'])->unique()->values()->all();
            $product->categories()->sync($syncIds);
        }

        // Handle main image upload
        if ($request->hasFile('main_image')) {
            $path = $request->file('main_image')->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'url' => $path,
                'is_primary' => true,
                'position' => 0,
            ]);
        }

        // Handle gallery image uploads
        if ($request->hasFile('images')) {
            $startPosition = $product->images()->max('position') ?? 0;
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => $path,
                    'is_primary' => false,
                    'position' => $startPosition + $index + 1,
                ]);
            }
        }

        $this->syncVariants($product, $request->input('variants', []));

        \App\Http\Middleware\CacheResponse::bustAll();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product): RedirectResponse
    {
        return redirect()->route('admin.products.edit', $product);
    }

    public function edit(Product $product): View
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $sellers = Seller::with('user')->orderBy('store_name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $attributes = Attribute::with('values')->orderBy('name')->get();
        $product->load(['images', 'variants']);

        return view('admin.products.edit', compact('product', 'categories', 'sellers', 'brands', 'attributes'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $product->id,
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'sku' => 'required|string|max:100|unique:products,sku,' . $product->id,
            'price' => 'required|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0|gte:price',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'additional_categories' => 'nullable|array',
            'additional_categories.*' => 'integer|exists:categories,id',
            'seller_id' => 'nullable|exists:sellers,id',
            'brand_id' => 'nullable|exists:brands,id',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_new_arrival' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'main_image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:20480',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,jpg,png,webp,gif|max:20480',
            'delete_images' => 'nullable|array',
            'delete_images.*' => 'integer|exists:product_images,id',
            'product_attributes' => 'nullable|array',
            'product_attributes.*' => 'nullable|string|max:255',
            'social_proof_text' => 'nullable|string|max:255',
            'amazon_url' => 'nullable|url|max:500',
            'flipkart_url' => 'nullable|url|max:500',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.sku' => 'nullable|string|max:50',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.stock_quantity' => 'nullable|integer|min:0',
            'variants.*.is_active' => 'nullable|boolean',
        ]);

        $validated['slug'] = $validated['slug'] ?? $product->slug ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_new_arrival'] = $request->boolean('is_new_arrival');
        $validated['seller_id'] = ($validated['seller_id'] ?? null) ?: null;
        $validated['brand_id'] = ($validated['brand_id'] ?? null) ?: null;
        $validated['social_proof_text'] = ($validated['social_proof_text'] ?? null) ?: null;
        $validated['amazon_url'] = ($validated['amazon_url'] ?? null) ?: null;
        $validated['flipkart_url'] = ($validated['flipkart_url'] ?? null) ?: null;
        $additionalCategories = $validated['additional_categories'] ?? [];
        unset($validated['additional_categories']);

        // Save attributes as JSON
        $productAttributes = collect($request->input('product_attributes', []))
            ->filter(fn($value) => $value !== null && $value !== '')
            ->toArray();

        // Merge per-product benefits into attributes
        $benefits = collect($request->input('benefits', []))
            ->filter(fn($b) => !empty($b['text']))
            ->values()
            ->toArray();
        if (!empty($benefits)) {
            $productAttributes['benefits'] = $benefits;
        }

        $validated['attributes'] = !empty($productAttributes) ? $productAttributes : null;

        // Per-product stats carousel
        $statsCarousel = json_decode($request->input('stats_carousel', '[]'), true);
        if (is_array($statsCarousel)) {
            $statsCarousel = array_values(array_filter($statsCarousel, fn($s) => !empty($s['value']) || !empty($s['label'])));
        }
        $validated['stats_carousel'] = !empty($statsCarousel) ? $statsCarousel : null;

        // Per-product pack config
        $packConfig = array_filter([
            'unit_label' => $request->input('pack_unit_label') ?: null,
            'units_per_qty' => $request->filled('pack_units_per_qty') ? (int) $request->input('pack_units_per_qty') : null,
            'months_per_qty' => $request->filled('pack_months_per_qty') ? (int) $request->input('pack_months_per_qty') : null,
        ]);
        $validated['pack_config'] = !empty($packConfig) ? $packConfig : null;

        unset($validated['images'], $validated['main_image'], $validated['delete_images'], $validated['product_attributes']);

        // Collect SEO fields into seo_data JSON column
        $validated['seo_data'] = array_filter([
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ]);
        unset($validated['meta_title'], $validated['meta_description']);

        // Parse comma-separated tags into JSON array
        $tagsInput = trim($request->input('tags', ''));
        $validated['tags'] = !empty($tagsInput)
            ? array_values(array_filter(array_map('trim', explode(',', $tagsInput))))
            : null;

        // Testimonial videos (JSON from Alpine.js) — filter out invalid entries
        $testimonialVideos = json_decode($request->input('testimonial_videos', '[]'), true);
        if (is_array($testimonialVideos)) {
            $testimonialVideos = array_values(array_filter($testimonialVideos, function ($entry) {
                if (!is_string($entry) || trim($entry) === '') {
                    return false;
                }
                $entry = trim($entry);
                // Allow valid URLs
                if (filter_var($entry, FILTER_VALIDATE_URL)) {
                    return true;
                }
                // Allow simple filenames (no spaces, ends with video extension)
                if (preg_match('/^[^\s]+\.(mp4|mov|webm|avi|mkv)$/i', $entry)) {
                    return true;
                }
                return false;
            }));
        } else {
            $testimonialVideos = [];
        }
        $validated['testimonial_videos'] = !empty($testimonialVideos) ? $testimonialVideos : null;

        $product->update($validated);

        if (\Illuminate\Support\Facades\Schema::hasTable('category_product')) {
            $syncIds = collect($additionalCategories)->push($validated['category_id'])->unique()->values()->all();
            $product->categories()->sync($syncIds);
        }

        // Delete selected gallery images
        if ($request->filled('delete_images')) {
            $imagesToDelete = ProductImage::whereIn('id', $request->delete_images)
                ->where('product_id', $product->id)
                ->get();

            foreach ($imagesToDelete as $image) {
                $storagePath = str_replace('/storage/', '', $image->url);
                Storage::disk('public')->delete($storagePath);
                $image->delete();
            }
        }

        // Replace main image if new one uploaded
        if ($request->hasFile('main_image')) {
            // Delete old primary image
            $oldPrimary = $product->images()->where('is_primary', true)->first();
            if ($oldPrimary) {
                $storagePath = str_replace('/storage/', '', $oldPrimary->url);
                Storage::disk('public')->delete($storagePath);
                $oldPrimary->delete();
            }

            $path = $request->file('main_image')->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'url' => $path,
                'is_primary' => true,
                'position' => 0,
            ]);
        }

        // Upload new gallery images
        if ($request->hasFile('images')) {
            $maxPosition = $product->images()->max('position') ?? 0;
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => $path,
                    'is_primary' => false,
                    'position' => $maxPosition + $index + 1,
                ]);
            }
        }

        $this->syncVariants($product, $request->input('variants', []));

        \App\Http\Middleware\CacheResponse::bustAll();

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Sync the variants submitted from the product form.
     * - Rows with an id: update the existing variant row.
     * - Rows without an id: create new variants.
     * - Existing variants NOT in the submitted payload: delete.
     */
    protected function syncVariants(Product $product, array $submitted): void
    {
        // Drop rows with no name (defensive — validation already enforces name required)
        $submitted = array_values(array_filter($submitted, fn($v) => !empty(trim($v['name'] ?? ''))));

        $keepIds = collect($submitted)
            ->pluck('id')
            ->filter(fn($id) => !empty($id))
            ->map(fn($id) => (int) $id)
            ->all();

        // Delete variants that were removed from the UI
        $product->variants()
            ->when(!empty($keepIds), fn($q) => $q->whereNotIn('id', $keepIds))
            ->delete();

        foreach ($submitted as $index => $row) {
            $priceInput = $row['price'] ?? null;
            $payload = [
                'name' => trim($row['name']),
                'sku' => !empty($row['sku'])
                    ? trim($row['sku'])
                    : 'V-' . $product->id . '-' . time() . '-' . $index,
                'price' => ($priceInput === null || $priceInput === '') ? null : (float) $priceInput,
                'stock_quantity' => (int) ($row['stock_quantity'] ?? 0),
                'is_active' => (bool) ($row['is_active'] ?? false),
            ];

            if (!empty($row['id'])) {
                $existing = $product->variants()->find($row['id']);
                if ($existing) {
                    // Preserve the existing SKU if the user left the field blank
                    if (empty($row['sku'])) {
                        $payload['sku'] = $existing->sku;
                    }
                    $existing->update($payload);
                    continue;
                }
            }

            $product->variants()->create($payload);
        }
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();
        \App\Http\Middleware\CacheResponse::bustAll();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update(['is_active' => !$product->is_active]);
        \App\Http\Middleware\CacheResponse::bustAll();

        $status = $product->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Product {$status} successfully.");
    }

    public function toggleFeatured(Product $product): RedirectResponse
    {
        $product->update(['is_featured' => !$product->is_featured]);
        \App\Http\Middleware\CacheResponse::bustAll();

        $status = $product->is_featured ? 'marked as featured' : 'removed from featured';

        return back()->with('success', "Product {$status}.");
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Product::with(['category', 'seller', 'images']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('sku', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('seller')) {
            $query->where('seller_id', $request->seller);
        }

        $products = $query->orderBy('name')->get();

        $filename = 'products-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'name', 'sku', 'slug', 'category', 'seller', 'price', 'sale_price',
                'cost_price', 'stock_quantity', 'short_description', 'description',
                'is_active', 'is_featured', 'image_url', 'meta_title', 'meta_description',
            ]);

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->name,
                    $product->sku,
                    $product->slug,
                    $product->category->name ?? '',
                    $product->seller->store_name ?? '',
                    $product->price,
                    $product->sale_price,
                    $product->cost_price,
                    $product->stock_quantity,
                    $product->short_description,
                    strip_tags($product->description),
                    $product->is_active ? '1' : '0',
                    $product->is_featured ? '1' : '0',
                    $product->primary_image_url ?? '',
                    $product->meta_title,
                    $product->meta_description,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return back()->with('error', 'Could not read the uploaded file.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'CSV file is empty or has no header row.');
        }

        $header = array_map(fn ($col) => strtolower(trim($col)), $header);

        $requiredColumns = ['name', 'sku', 'price'];
        $missingColumns = array_diff($requiredColumns, $header);
        if (!empty($missingColumns)) {
            fclose($handle);
            return back()->with('error', 'Missing required columns: ' . implode(', ', $missingColumns));
        }

        $categories = Category::pluck('id', 'name')->toArray();
        $categoriesLower = [];
        foreach ($categories as $name => $id) {
            $categoriesLower[strtolower($name)] = $id;
        }

        $sellers = Seller::pluck('id', 'store_name')->toArray();
        $sellersLower = [];
        foreach ($sellers as $name => $id) {
            $sellersLower[strtolower($name)] = $id;
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $row = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $row++;

            if (count($data) !== count($header)) {
                $errors[] = "Row {$row}: Column count mismatch.";
                $skipped++;
                continue;
            }

            $record = array_combine($header, $data);

            $name = trim($record['name'] ?? '');
            $sku = trim($record['sku'] ?? '');
            $price = $record['price'] ?? '';

            if (empty($name) || empty($sku) || !is_numeric($price)) {
                $errors[] = "Row {$row}: Missing name, SKU, or invalid price.";
                $skipped++;
                continue;
            }

            if (Product::where('sku', $sku)->exists()) {
                $errors[] = "Row {$row}: SKU '{$sku}' already exists.";
                $skipped++;
                continue;
            }

            $categoryId = null;
            if (!empty($record['category'])) {
                $categoryId = $categoriesLower[strtolower(trim($record['category']))] ?? null;
            }

            $sellerId = null;
            if (!empty($record['seller'])) {
                $sellerId = $sellersLower[strtolower(trim($record['seller']))] ?? null;
            }

            $product = Product::create([
                'name' => $name,
                'sku' => $sku,
                'slug' => !empty($record['slug']) ? trim($record['slug']) : Str::slug($name),
                'price' => (float) $price,
                'sale_price' => is_numeric($record['sale_price'] ?? null) ? (float) $record['sale_price'] : null,
                'cost_price' => is_numeric($record['cost_price'] ?? null) ? (float) $record['cost_price'] : null,
                'stock_quantity' => (int) ($record['stock_quantity'] ?? 0),
                'category_id' => $categoryId,
                'seller_id' => $sellerId,
                'short_description' => $record['short_description'] ?? null,
                'description' => $record['description'] ?? $name,
                'is_active' => (bool) ($record['is_active'] ?? 1),
                'is_featured' => (bool) ($record['is_featured'] ?? 0),
                'seo_data' => array_filter([
                    'meta_title' => $record['meta_title'] ?? null,
                    'meta_description' => $record['meta_description'] ?? null,
                ]),
            ]);

            // Handle image URL
            $imageUrl = trim($record['image_url'] ?? '');
            if (!empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $imageUrl)) {
                try {
                    $context = stream_context_create([
                        'http' => ['timeout' => 5, 'max_redirects' => 3],
                        'https' => ['timeout' => 5, 'max_redirects' => 3],
                    ]);
                    $imageContents = @file_get_contents($imageUrl, false, $context);
                    if ($imageContents && strlen($imageContents) <= 5 * 1024 * 1024) {
                        $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                        $extension = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp', 'gif']) ? $extension : 'jpg';
                        $path = 'products/' . Str::uuid() . '.' . $extension;
                        Storage::disk('public')->put($path, $imageContents);

                        ProductImage::create([
                            'product_id' => $product->id,
                            'url' => asset('storage/' . $path),
                            'is_primary' => true,
                            'position' => 0,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Image download failed, skip silently
                }
            }

            $imported++;
        }

        fclose($handle);

        \App\Http\Middleware\CacheResponse::bustAll();

        $message = "{$imported} product(s) imported successfully.";
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) skipped.";
        }

        if (!empty($errors)) {
            $errorSummary = implode(' | ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $errorSummary .= ' ... and ' . (count($errors) - 5) . ' more.';
            }
            return back()
                ->with('warning', $message)
                ->with('error', $errorSummary);
        }

        return back()->with('success', $message);
    }

    public function uploadVideo(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,webm,avi|max:51200', // 50MB max
        ]);

        $tenantPrefix = function_exists('tenant') && tenant() ? tenant()->getTenantKey() . '/' : '';
        $path = $request->file('video')->store('videos/' . $tenantPrefix . 'testimonials', 'public');

        return response()->json([
            'success' => true,
            'url' => asset('storage/' . $path),
            'path' => 'storage/' . $path,
        ]);
    }
}
