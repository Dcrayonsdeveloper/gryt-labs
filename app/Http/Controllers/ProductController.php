<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Category;
use App\Models\Setting;
use App\Models\ProductQuestion;
use App\Models\ProductView;
use App\Services\AnalyticsService;
use App\Services\ReviewSchemaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = Product::query()
            ->where('is_active', true)
            ->with(['category', 'brand', 'primaryImage']);

        // Category filter
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Subcategory filter
        if ($request->filled('subcategory')) {
            $subSlugs = (array) $request->subcategory;
            $subIds = Category::whereIn('slug', $subSlugs)->pluck('id');
            if ($subIds->isNotEmpty()) {
                $query->whereIn('category_id', $subIds);
            }
        }

        // Brand filter
        if ($request->filled('brand')) {
            $brandSlugs = (array) $request->brand;
            $brandIds = \App\Models\Brand::whereIn('slug', $brandSlugs)->pluck('id');
            if ($brandIds->isNotEmpty()) {
                $query->whereIn('brand_id', $brandIds);
            }
        }

        // Price filter
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        // Rating filter
        if ($request->filled('rating')) {
            $query->where('rating', '>=', $request->rating);
        }

        // In stock filter
        if ($request->boolean('in_stock')) {
            $query->where('stock_quantity', '>', 0);
        }

        // On sale filter (price less than mrp)
        if ($request->boolean('on_sale')) {
            $query->whereNotNull('mrp')->whereColumn('price', '<', 'mrp');
        }

        // Sorting
        $sortBy = $request->get('sort', 'newest');
        match ($sortBy) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating' => $query->orderBy('rating', 'desc'),
            'bestselling' => $query->orderBy('sales_count', 'desc'),
            'name' => $query->orderBy('name', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $products = $query->paginate(24)->withQueryString();

        // AJAX infinite scroll
        if ($request->ajax()) {
            $html = '';
            foreach ($products as $product) {
                $html .= view('components.product-card', ['product' => $product])->render();
            }
            return response()->json([
                'html' => $html,
                'hasMore' => $products->hasMorePages(),
            ]);
        }

        // Get categories, subcategories, and brands for filters
        $categories = Category::whereNull('parent_id')->where('is_active', true)->get();
        $subcategories = Category::whereNotNull('parent_id')->where('is_active', true)->orderBy('name')->get();
        $brands = \App\Models\Brand::orderBy('name')->get();

        return view('products.index', compact('products', 'categories', 'subcategories', 'brands'));
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category',
            'brand',
            'seller',
            'images',
            'variants',
            'approvedReviews.user',
            'questions' => fn ($q) => $q->where('is_answered', true)->latest()->take(5),
            'questions.answers',
        ]);

        // Record product view
        if (auth()->check()) {
            ProductView::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'product_id' => $product->id,
                ],
                ['viewed_at' => now()]
            );
        }

        // Related products
        $relatedProducts = Product::query()
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->where(function ($query) use ($product) {
                $query->where('category_id', $product->category_id)
                      ->orWhere('brand_id', $product->brand_id);
            })
            ->with(['category', 'brand', 'primaryImage'])
            ->inRandomOrder()
            ->take(8)
            ->get();

        // Compare with similar items (same category or brand, limit 4)
        $compareProducts = Product::query()
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->where(function ($query) use ($product) {
                $query->where('category_id', $product->category_id)
                      ->orWhere('brand_id', $product->brand_id);
            })
            ->with(['brand', 'primaryImage'])
            ->inRandomOrder()
            ->take(3)
            ->get();

        // Breadcrumbs
        $breadcrumbs = [];
        if ($product->category) {
            $breadcrumbs[] = ['label' => $product->category->name, 'url' => route('category.show', $product->category)];
        }
        $breadcrumbs[] = ['label' => $product->name, 'url' => null];

        // Breadcrumb JSON-LD Schema
        $breadcrumbItems = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
        ];
        $position = 2;
        if ($product->category) {
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $product->category->name,
                'item' => url('/collections/' . $product->category->slug),
            ];
        }
        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $product->name,
        ];
        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbItems,
        ];

        // Rating distribution from all approved reviews
        $ratingDistribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($product->approvedReviews as $r) {
            if (isset($ratingDistribution[$r->rating])) {
                $ratingDistribution[$r->rating]++;
            }
        }

        // Latest 10 reviews for display (all loaded for schema)
        $displayReviews = $product->approvedReviews->sortByDesc('created_at')->take(10);

        // JSON-LD structured data for SEO
        $schemaService = app(ReviewSchemaService::class);
        $productSchema = $schemaService->getProductSchema($product);
        $faqSchema = $schemaService->getFaqSchema($product);

        // Available coupons — show all valid public offers on product page
        $availableCoupons = Coupon::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereColumn('times_used', '<', 'usage_limit');
            })
            ->where('type', '!=', 'free_shipping')
            ->where('code', 'NOT LIKE', 'COMEBACK%')
            ->where('code', 'NOT LIKE', 'THANKS-%')
            ->orderBy('value', 'desc')
            ->take(4)
            ->get();

        // Frequently bought together - products from same category
        $frequentlyBought = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->orderByDesc('sales_count')
            ->limit(3)
            ->with(['primaryImage', 'brand'])
            ->get();

        // Facebook CAPI: ViewContent
        $fbEventId = AnalyticsService::generateEventId('vc');
        app(AnalyticsService::class)->trackViewContent($product, request(), $fbEventId);

        // Pre-compute display data (Shopify-like: views are pure templates)
        $pdpData = [
            'certImage' => Setting::get('product_certifications_image', ''),
            'sliderItems' => array_filter(array_map('trim', explode('|', Setting::get('pdp_text_slider', '')))),
            'dealBadge' => Setting::get('pdp_deal_badge_text', ''),
            'loyaltyEnabled' => (bool) Setting::get('loyalty_enabled', false),
            'loyaltyEarnRate' => (float) Setting::get('loyalty_earn_rate', 1),
            'loyaltyPoints' => (int) floor($product->price * (float) Setting::get('loyalty_earn_rate', 1)),
            'lowStockThreshold' => (int) Setting::get('low_stock_threshold', 5),
            'freeShipThreshold' => (float) Setting::get('free_shipping_threshold', 499),
            'deliveryDays' => Setting::get('pdp_delivery_days', ''),
            'fastestDelivery' => Setting::get('pdp_fastest_delivery_text', ''),
            'taxText' => Setting::get('pdp_tax_text', ''),
            'benefits' => json_decode(Setting::get('product_benefits', ''), true) ?: [],
            'trustBadges' => json_decode(Setting::get('pdp_trust_badges', ''), true) ?: [],
            'paymentMethods' => Setting::get('pdp_payment_methods', ''),
            'statsCarousel' => json_decode(Setting::get('product_stats_carousel', ''), true) ?: [],
            'showCompare' => (bool) Setting::get('show_product_compare', false),
            'showCoupons' => (bool) Setting::get('show_product_coupons', true),
            'pdpLayout' => Setting::get('pdp_layout', ''),
            'shortDescPoints' => $product->short_description
                ? array_filter(array_map('trim', preg_split('/[\.\n]+/', html_entity_decode(strip_tags($product->short_description)), -1, PREG_SPLIT_NO_EMPTY)))
                : [],
        ];

        return view('products.show', compact(
            'product', 'relatedProducts', 'compareProducts', 'breadcrumbs',
            'breadcrumbSchema', 'productSchema', 'faqSchema', 'fbEventId',
            'ratingDistribution', 'displayReviews', 'availableCoupons',
            'frequentlyBought', 'pdpData'
        ));
    }

    public function quickView(Product $product): JsonResponse
    {
        abort_unless($product->is_active, 404);

        $product->load(['brand', 'images', 'category']);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'url' => route('products.show', $product),
            'brand' => $product->brand?->name,
            'category' => $product->category?->name,
            'price' => (float) $product->price,
            'mrp' => (float) $product->mrp,
            'discount_percentage' => $product->discount_percentage,
            'short_description' => $product->short_description,
            'rating' => (float) ($product->rating ?? 0),
            'review_count' => (int) ($product->review_count ?? 0),
            'in_stock' => $product->isInStock(),
            'stock_quantity' => $product->stock_quantity,
            'images' => $product->images->pluck('url')->values(),
            'primary_image' => $product->primary_image_url,
        ]);
    }

    public function newArrivals(Request $request): View|JsonResponse
    {
        $products = Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->where('is_new_arrival', true)
            ->with(['category', 'brand', 'primaryImage'])
            ->orderBy('created_at', 'desc')
            ->paginate(24);

        if ($request->ajax()) {
            $html = '';
            foreach ($products as $product) {
                $html .= view('components.product-card', ['product' => $product])->render();
            }
            return response()->json(['html' => $html, 'hasMore' => $products->hasMorePages()]);
        }

        return view('products.new-arrivals', compact('products'));
    }

    public function bestsellers(Request $request): View|JsonResponse
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with(['category', 'brand', 'primaryImage'])
            ->orderBy('sales_count', 'desc')
            ->paginate(24);

        if ($request->ajax()) {
            $html = '';
            foreach ($products as $product) {
                $html .= view('components.product-card', ['product' => $product])->render();
            }
            return response()->json(['html' => $html, 'hasMore' => $products->hasMorePages()]);
        }

        return view('products.bestsellers', compact('products'));
    }

    public function reviewsJson(Product $product): JsonResponse
    {
        $reviews = $product->reviews()
            ->where('is_approved', true)
            ->where('created_at', '<=', now())
            ->with('user:id,first_name,last_name')
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    public function askQuestion(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'question' => 'required|string|min:10|max:1000',
            'guest_name' => 'nullable|string|max:100',
            'guest_email' => 'nullable|email|max:255',
        ]);

        ProductQuestion::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'question' => $request->question,
        ]);

        return response()->json(['message' => 'Question submitted successfully!']);
    }
}
