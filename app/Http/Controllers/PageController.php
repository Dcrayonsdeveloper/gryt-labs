<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Enquiry;
use App\Models\Notification;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use App\Models\Setting;
use App\Services\AnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        $brands = Brand::active()->featured()
            ->whereNotNull('logo_url')
            ->orderBy('position')
            ->limit(12)
            ->get();

        // Tenant-scoped products (each tenant only sees its own catalog)
        $products = Product::query()
            ->where('is_active', true)
            ->with(['category', 'brand', 'primaryImage', 'images'])
            ->orderByDesc('sales_count')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('pages.about', compact('brands', 'products'));
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function bmi(): View
    {
        // Restrict to ayurvexa tenant only
        $tenantId = function_exists('tenant') && tenant() ? tenant()->getTenantKey() : null;
        abort_if($tenantId !== 'ayurvexa', 404);

        return view('pages.bmi-calculator');
    }

    public function sendContact(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        // Honeypot: if bot filled the hidden "website" field, silently reject
        if ($request->filled('website')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Thank you!'])
                : back()->with('success', 'Your message has been sent successfully!');
        }

        // Time check: reject if submitted in under 3 seconds (bot speed)
        $ts = (int) $request->input('_ts', 0);
        if ($ts > 0 && (time() - $ts) < 3) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Thank you!'])
                : back()->with('success', 'Your message has been sent successfully!');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $validated['email'] = $validated['email'] ?: ($validated['phone'] . '@noemail.local');

        $enquiry = Enquiry::create($validated);

        // Email notification — owner + customer
        $storeName = Setting::get('store_name', config('app.name'));
        $adminEmail = Setting::get('admin_email', '') ?: Setting::get('mail_from_address', '');
        if ($adminEmail) {
            try {
                $body = "New Enquiry / Consultation Request\n\n"
                      . "Name: {$enquiry->name}\n"
                      . "Email: {$enquiry->email}\n"
                      . "Phone: " . ($enquiry->phone ?: '-') . "\n"
                      . "Subject: {$enquiry->subject}\n\n"
                      . "Message:\n{$enquiry->message}";
                Mail::raw($body, function ($m) use ($adminEmail, $enquiry, $storeName) {
                    $m->to($adminEmail)
                      ->subject("[{$storeName}] {$enquiry->subject} — {$enquiry->name}")
                      ->replyTo($enquiry->email, $enquiry->name);
                });
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Enquiry admin email failed', ['error' => $e->getMessage()]);
            }
        }
        if ($enquiry->email && !str_ends_with($enquiry->email, '@noemail.local')) {
            try {
                $custBody = "Hi {$enquiry->name},\n\n"
                          . "Thank you for reaching out to {$storeName}. We've received your message and our team will get back to you shortly.\n\n"
                          . "Your message:\n{$enquiry->message}\n\n"
                          . "Warm regards,\n{$storeName} Team";
                Mail::raw($custBody, function ($m) use ($enquiry, $storeName) {
                    $m->to($enquiry->email, $enquiry->name)
                      ->subject("We received your message — {$storeName}");
                });
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Enquiry customer email failed', ['error' => $e->getMessage()]);
            }
        }

        // Facebook CAPI: Contact
        try {
            app(AnalyticsService::class)->trackContact($request);
        } catch (\Exception $e) {
            // Don't block contact form for analytics
        }

        // Notify all admin users
        try {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'new_enquiry',
                    'title' => 'New Enquiry',
                    'content' => "New enquiry from {$enquiry->name}: {$enquiry->subject}",
                    'data' => [
                        'enquiry_id' => $enquiry->id,
                        'name' => $enquiry->name,
                        'email' => $enquiry->email,
                        'subject' => $enquiry->subject,
                    ],
                    'channel' => 'database',
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Contact notification failed', ['error' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Thank you! We will contact you soon.']);
        }

        return back()->with('success', 'Thank you for your message. We will get back to you soon.');
    }

    public function faq(): View
    {
        return view('pages.faq');
    }

    public function gallery(): View
    {
        $ids = array_filter(explode(',', Setting::get('youtube_gallery_videos', '')));
        $videos = collect($ids)->map(fn($id) => [
            'url'   => 'https://www.youtube.com/watch?v=' . trim($id),
            'thumb' => 'https://img.youtube.com/vi/' . trim($id) . '/hqdefault.jpg',
            'title' => '',
        ]);

        return view('pages.gallery', compact('videos'));
    }

    public function sitemap(): View
    {
        $categories = \App\Models\Category::where('is_active', true)->orderBy('position')->select('name', 'slug')->get();
        $brands = \App\Models\Brand::where('is_active', true)->orderBy('name')->select('name', 'slug')->get();

        return view('pages.sitemap', compact('categories', 'brands'));
    }

    public function blog(): View
    {
        $posts = BlogPost::published()
            ->when(request('tag'), fn($q, $t) => $q->whereJsonContains('tags', $t))
            ->when(request('search'), fn($q, $s) => $q->where('title', 'like', "%{$s}%")
                ->orWhere('excerpt', 'like', "%{$s}%"))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        $tags = BlogPost::published()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('pages.blog', compact('posts', 'tags'));
    }

    public function blogShow(string $slug): View
    {
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();
        $post->incrementViews();

        $related = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->when($post->category, fn($q) => $q->where('category', $post->category))
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('pages.blog-show', compact('post', 'related'));
    }

    public function careers(): View
    {
        return view('pages.careers');
    }

    public function help(): View
    {
        return view('pages.help');
    }

    public function returns(): View
    {
        $page = \App\Models\Page::where('slug', 'returns-policy')->first();
        if ($page && $page->is_published && !empty(strip_tags($page->content))) {
            return view('pages.legal-page', compact('page'));
        }

        return view('pages.returns');
    }

    public function shipping(): View
    {
        return view('pages.shipping');
    }

    public function sizeGuide(): View
    {
        return view('pages.size-guide');
    }

    public function privacy(): View
    {
        $page = Page::where('slug', 'privacy-policy')->firstOrFail();

        return view('pages.legal-page', compact('page'));
    }

    public function terms(): View
    {
        $page = Page::where('slug', 'terms-of-service')->firstOrFail();

        return view('pages.legal-page', compact('page'));
    }

    public function cookiePolicy(): View
    {
        $page = Page::where('slug', 'cookie-policy')->firstOrFail();

        return view('pages.legal-page', compact('page'));
    }

    public function gdpr(): View
    {
        $page = Page::where('slug', 'gdpr')->firstOrFail();

        return view('pages.legal-page', compact('page'));
    }

    public function shippingPolicy(): View
    {
        $page = Page::where('slug', 'shipping-policy')->firstOrFail();

        return view('pages.legal-page', compact('page'));
    }

    public function show(Page $page): View
    {
        abort_unless($page->is_published, 404);

        return view('pages.legal-page', compact('page'));
    }
}
