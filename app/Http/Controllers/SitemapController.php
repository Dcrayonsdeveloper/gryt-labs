<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $content = Cache::remember(request()->getHost() . ':sitemap:index', 3600, function () {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            $sitemaps = [
                'sitemap-pages.xml',
                'sitemap-products.xml',
                'sitemap-categories.xml',
                'sitemap-brands.xml',
            ];

            // Only include blog sitemap if blog posts exist
            if (class_exists(BlogPost::class) && BlogPost::published()->exists()) {
                $sitemaps[] = 'sitemap-blog.xml';
            }

            foreach ($sitemaps as $sitemap) {
                $xml .= '  <sitemap><loc>' . url('/' . $sitemap) . '</loc></sitemap>' . "\n";
            }

            $xml .= '</sitemapindex>';
            return $xml;
        });

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    public function pages(): Response
    {
        $content = Cache::remember(request()->getHost() . ':sitemap:pages', 3600, function () {
            $urls = collect();

            // Homepage — highest priority
            $urls->push(['loc' => url('/'), 'changefreq' => 'daily', 'priority' => '1.0']);

            // High-traffic pages
            $urls->push(['loc' => url('/products'), 'changefreq' => 'daily', 'priority' => '0.9']);
            $urls->push(['loc' => url('/categories'), 'changefreq' => 'weekly', 'priority' => '0.8']);
            $urls->push(['loc' => url('/brands'), 'changefreq' => 'weekly', 'priority' => '0.7']);
            $urls->push(['loc' => url('/deals'), 'changefreq' => 'daily', 'priority' => '0.8']);
            $urls->push(['loc' => url('/new-arrivals'), 'changefreq' => 'daily', 'priority' => '0.8']);
            $urls->push(['loc' => url('/bestsellers'), 'changefreq' => 'weekly', 'priority' => '0.8']);
            $urls->push(['loc' => url('/search'), 'changefreq' => 'weekly', 'priority' => '0.5']);

            // Content pages
            $urls->push(['loc' => url('/blog'), 'changefreq' => 'weekly', 'priority' => '0.7']);
            $urls->push(['loc' => url('/about'), 'changefreq' => 'monthly', 'priority' => '0.5']);
            $urls->push(['loc' => url('/contact'), 'changefreq' => 'monthly', 'priority' => '0.5']);
            $urls->push(['loc' => url('/faq'), 'changefreq' => 'monthly', 'priority' => '0.5']);
            $urls->push(['loc' => url('/careers'), 'changefreq' => 'monthly', 'priority' => '0.4']);
            $urls->push(['loc' => url('/help'), 'changefreq' => 'monthly', 'priority' => '0.4']);
            $urls->push(['loc' => url('/wholesale'), 'changefreq' => 'monthly', 'priority' => '0.5']);
            $urls->push(['loc' => url('/sell'), 'changefreq' => 'monthly', 'priority' => '0.5']);
            $urls->push(['loc' => url('/track-order'), 'changefreq' => 'monthly', 'priority' => '0.4']);

            // Policy & info pages
            $urls->push(['loc' => url('/shipping'), 'changefreq' => 'monthly', 'priority' => '0.3']);
            $urls->push(['loc' => url('/returns-policy'), 'changefreq' => 'monthly', 'priority' => '0.3']);
            $urls->push(['loc' => url('/size-guide'), 'changefreq' => 'monthly', 'priority' => '0.3']);
            $urls->push(['loc' => url('/privacy-policy'), 'changefreq' => 'monthly', 'priority' => '0.2']);
            $urls->push(['loc' => url('/terms-of-service'), 'changefreq' => 'monthly', 'priority' => '0.2']);
            $urls->push(['loc' => url('/cookie-policy'), 'changefreq' => 'monthly', 'priority' => '0.2']);
            $urls->push(['loc' => url('/gdpr'), 'changefreq' => 'monthly', 'priority' => '0.2']);

            // Dynamic CMS pages
            if (class_exists(Page::class)) {
                Page::where('is_published', true)
                    ->select('slug', 'updated_at')
                    ->get()
                    ->each(function ($page) use ($urls) {
                        $urls->push([
                            'loc' => url('/page/' . $page->slug),
                            'lastmod' => $page->updated_at->toW3cString(),
                            'changefreq' => 'monthly',
                            'priority' => '0.4',
                        ]);
                    });
            }

            return $this->generateUrlset($urls);
        });

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    public function products(): Response
    {
        $content = Cache::remember(request()->getHost() . ':sitemap:products', 3600, function () {
            $urls = collect();

            Product::where('is_active', true)
                ->select('id', 'slug', 'name', 'updated_at')
                ->with(['images' => fn ($q) => $q->select('id', 'product_id', 'url')->limit(5)])
                ->orderBy('updated_at', 'desc')
                ->chunk(500, function ($products) use ($urls) {
                    foreach ($products as $product) {
                        $entry = [
                            'loc' => route('products.show', $product->slug),
                            'lastmod' => $product->updated_at->toW3cString(),
                            'changefreq' => 'weekly',
                            'priority' => '0.8',
                        ];

                        // Add image entries for Google Image search
                        if ($product->images && $product->images->count()) {
                            $entry['images'] = $product->images->map(fn ($img) => [
                                'loc' => url($img->url),
                                'title' => $product->name,
                            ])->toArray();
                        }

                        $urls->push($entry);
                    }
                });

            return $this->generateUrlset($urls, true);
        });

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    public function categories(): Response
    {
        $content = Cache::remember(request()->getHost() . ':sitemap:categories', 3600, function () {
            $urls = collect();

            Category::where('is_active', true)
                ->select('slug', 'updated_at')
                ->orderBy('position')
                ->get()
                ->each(function ($category) use ($urls) {
                    $urls->push([
                        'loc' => route('categories.show', $category->slug),
                        'lastmod' => $category->updated_at->toW3cString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.7',
                    ]);
                });

            return $this->generateUrlset($urls);
        });

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    public function brands(): Response
    {
        $content = Cache::remember(request()->getHost() . ':sitemap:brands', 3600, function () {
            $urls = collect();

            Brand::where('is_active', true)
                ->select('slug', 'updated_at')
                ->get()
                ->each(function ($brand) use ($urls) {
                    $urls->push([
                        'loc' => route('brands.show', $brand->slug),
                        'lastmod' => $brand->updated_at->toW3cString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.6',
                    ]);
                });

            return $this->generateUrlset($urls);
        });

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    public function blog(): Response
    {
        $content = Cache::remember(request()->getHost() . ':sitemap:blog', 3600, function () {
            $urls = collect();

            BlogPost::published()
                ->select('slug', 'published_at', 'updated_at', 'featured_image', 'title')
                ->orderBy('published_at', 'desc')
                ->get()
                ->each(function ($post) use ($urls) {
                    $entry = [
                        'loc' => route('blog.show', $post->slug),
                        'lastmod' => $post->updated_at->toW3cString(),
                        'changefreq' => 'monthly',
                        'priority' => '0.6',
                    ];

                    if ($post->featured_image) {
                        $entry['images'] = [[
                            'loc' => asset('storage/' . $post->featured_image),
                            'title' => $post->title,
                        ]];
                    }

                    $urls->push($entry);
                });

            return $this->generateUrlset($urls, true);
        });

        return response($content, 200)->header('Content-Type', 'application/xml');
    }

    private function generateUrlset($urls, bool $includeImages = false): string
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

        if ($includeImages) {
            $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        } else {
            $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        }

        foreach ($urls as $url) {
            $content .= "  <url>\n";
            $content .= "    <loc>" . htmlspecialchars($url['loc'], ENT_XML1) . "</loc>\n";
            if (isset($url['lastmod'])) {
                $content .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
            }
            $content .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
            $content .= "    <priority>{$url['priority']}</priority>\n";

            // Image sitemap entries
            if ($includeImages && isset($url['images'])) {
                foreach ($url['images'] as $image) {
                    $content .= "    <image:image>\n";
                    $content .= "      <image:loc>" . htmlspecialchars($image['loc'], ENT_XML1) . "</image:loc>\n";
                    $content .= "      <image:title>" . htmlspecialchars($image['title'], ENT_XML1) . "</image:title>\n";
                    $content .= "    </image:image>\n";
                }
            }

            $content .= "  </url>\n";
        }

        $content .= '</urlset>';
        return $content;
    }
}
