<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // Homepage display settings (all from single cached query now)
        $featuredCount    = (int) Setting::get('homepage_featured_count', 5);
        $newArrivalsCount = (int) Setting::get('homepage_new_arrivals_count', 5);
        $bestsellersCount = (int) Setting::get('homepage_bestsellers_count', 5);
        $dealsCount       = (int) Setting::get('homepage_deals_count', 5);
        $testimonialsCount = (int) Setting::get('homepage_testimonials_count', 6);
        $newArrivalsDays  = (int) Setting::get('homepage_new_arrivals_days', 30);

        // Shared scope for active products with eager loads
        $productEager = ['category:id,name,slug', 'brand:id,name,slug', 'primaryImage'];

        // Featured products
        $featuredProducts = Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->where('is_featured', true)
            ->with($productEager)
            ->orderBy('created_at', 'desc')
            ->take($featuredCount)
            ->get();

        // Too few products carry the "featured" flag to fill the row, which leaves the section
        // looking half-empty. Top it up with the newest other in-stock products.
        if ($featuredProducts->count() < $featuredCount) {
            $featuredProducts = $featuredProducts->concat(
                Product::query()
                    ->where('is_active', true)
                    ->where('stock_quantity', '>', 0)
                    ->whereNotIn('id', $featuredProducts->pluck('id'))
                    ->with($productEager)
                    ->orderBy('created_at', 'desc')
                    ->take($featuredCount - $featuredProducts->count())
                    ->get()
            );
        }

        // New arrivals — manually curated by admins via the "Show in New Arrivals" flag
        $newArrivals = Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->where('is_new_arrival', true)
            ->with($productEager)
            ->orderBy('created_at', 'desc')
            ->take($newArrivalsCount)
            ->get();

        // Bestsellers
        $bestsellers = Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->with($productEager)
            ->orderBy('sales_count', 'desc')
            ->take($bestsellersCount)
            ->get();

        // Deal products (where price < mrp)
        $deals = Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->whereColumn('price', '<', 'mrp')
            ->with($productEager)
            ->orderByRaw('(mrp - price) / mrp DESC')
            ->take($dealsCount)
            ->get();

        // Category carousel — select only needed columns, single product image per category
        $carouselCategories = Category::query()
            ->select('id', 'name', 'slug', 'image_url', 'icon')
            ->where('is_active', true)
            ->withCount(['products' => fn($q) => $q->where('is_active', true)])
            ->with(['products' => fn($q) => $q->where('is_active', true)
                ->select('id', 'category_id')
                ->with('primaryImage')
                ->limit(1)])
            ->orderBy('position')
            ->get()
            ->filter(fn($c) => $c->products_count > 0)
            ->take(15);

        // Categories for homepage sections (with children + product counts)
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with([
                'children' => fn ($q) => $q->where('is_active', true)
                    ->withCount(['products' => fn ($q2) => $q2->where('is_active', true)]),
                'products' => fn ($q) => $q->where('is_active', true)->with('primaryImage')->limit(1),
            ])
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('position')
            ->get()
            ->map(function ($category) {
                $category->total_products_count = $category->products_count
                    + $category->children->sum('products_count');
                return $category;
            })
            ->filter(fn ($c) => $c->total_products_count > 0);

        // Banners
        $banners = Banner::query()
            ->where('is_active', true)
            ->where('position', 'hero')
            ->orderBy('priority')
            ->get();

        // Homepage sections (load all so template can check is_active for disabled ones)
        $sections = HomepageSection::ordered()->get()->keyBy('key');

        // Testimonials
        $testimonials = Testimonial::active()->ordered()->take($testimonialsCount)->get();

        // Active flash sale for popup
        $flashSale = FlashSale::active()
            ->withCount('products')
            ->first();

        // Themed product collection (configurable per tenant)
        // Falls back to coffee/frother keywords for backward compat with existing tenants
        $themedCollectionTitle = Setting::get('themed_collection_title', 'Love Over Coffee');
        $themedKeywords = Setting::get('themed_collection_keywords', 'coffee,frother');
        $themedProducts = collect();
        if ($themedKeywords) {
            $keywords = array_map('trim', explode(',', $themedKeywords));
            $themedProducts = Product::query()
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->where(function ($q) use ($keywords) {
                    foreach ($keywords as $kw) {
                        if ($kw !== '') {
                            $q->orWhere('name', 'like', "%{$kw}%");
                        }
                    }
                })
                ->with($productEager)
                ->orderBy('sales_count', 'desc')
                ->take(12)
                ->get();
        }

        // Latest blog posts for homepage
        $latestBlogs = BlogPost::query()
            ->where('is_published', true)
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        // Site settings (all from batch cache — no extra queries)
        $siteSettings = [
            'site_name' => Setting::get('site_name', config('app.name')),
            'site_tagline' => Setting::get('site_tagline', ''),
            'site_logo' => Setting::get('site_logo', ''),
            'footer_about' => Setting::get('footer_about', ''),
        ];

        // Backward compat: existing frontend themes reference $coffeeProducts
        $coffeeProducts = $themedProducts;

        return view('home', compact(
            'featuredProducts',
            'newArrivals',
            'bestsellers',
            'deals',
            'categories',
            'carouselCategories',
            'banners',
            'sections',
            'testimonials',
            'siteSettings',
            'flashSale',
            'themedProducts',
            'themedCollectionTitle',
            'coffeeProducts',
            'latestBlogs'
        ));
    }
}
