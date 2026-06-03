<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\HomepageSection;
use App\Models\NavigationMenu;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class HomepageController extends Controller
{
    public function index()
    {
        $sections = HomepageSection::ordered()->get();
        $banners = Banner::where('position', 'hero')->ordered()->get();
        $testimonials = Testimonial::ordered()->get();

        return view('admin.homepage.index', compact('sections', 'banners', 'testimonials'));
    }

    // Site Settings (Logo, Brand Name, etc.)
    public function siteSettings()
    {
        $settings = [
            'site_logo' => Setting::get('site_logo', ''),
            'site_name' => Setting::get('site_name', config('app.name')),
            'site_tagline' => Setting::get('site_tagline', ''),
            'site_description' => Setting::get('site_description', ''),
            'footer_about' => Setting::get('footer_about', ''),
            'footer_copyright' => Setting::get('footer_copyright', ''),
            // About Us page
            'about_story' => Setting::get('about_story', ''),
            'about_html_content' => Setting::get('about_html_content', ''),
            'social_facebook' => Setting::get('social_facebook', ''),
            'social_instagram' => Setting::get('social_instagram', ''),
            'social_twitter' => Setting::get('social_twitter', ''),
            'social_youtube' => Setting::get('social_youtube', ''),
            'social_tiktok' => Setting::get('social_tiktok', ''),
            'social_pinterest' => Setting::get('social_pinterest', ''),
            'contact_email' => Setting::get('contact_email', ''),
            'contact_phone' => Setting::get('contact_phone', ''),
            'contact_address' => Setting::get('contact_address', ''),
            'announcement_text' => Setting::get('announcement_text', ''),
            'marquee_text' => Setting::get('marquee_text', ''),
            'pdp_text_slider' => Setting::get('pdp_text_slider', ''),
            // Homepage display counts
            'homepage_featured_count' => Setting::get('homepage_featured_count', 10),
            'homepage_new_arrivals_count' => Setting::get('homepage_new_arrivals_count', 10),
            'homepage_bestsellers_count' => Setting::get('homepage_bestsellers_count', 10),
            'homepage_deals_count' => Setting::get('homepage_deals_count', 10),
            'homepage_testimonials_count' => Setting::get('homepage_testimonials_count', 6),
            'homepage_new_arrivals_days' => Setting::get('homepage_new_arrivals_days', 30),
            // Homepage appearance
            'homepage_alt_bg_color' => Setting::get('homepage_alt_bg_color', '#fefae0'),
            'newsletter_heading' => Setting::get('newsletter_heading', ''),
            'newsletter_subtitle' => Setting::get('newsletter_subtitle', ''),
            'newsletter_badge_text' => Setting::get('newsletter_badge_text', ''),
            'flash_sale_label' => Setting::get('flash_sale_label', ''),
            'flash_sale_button_text' => Setting::get('flash_sale_button_text', ''),
            // Consultation
            'consultation_types' => Setting::get('consultation_types', ''),
            'consultation_time_slots' => Setting::get('consultation_time_slots', ''),
            // Testimonial videos
            'testimonial_videos' => Setting::get('testimonial_videos', ''),
            // Amazon store
            'amazon_store_url' => Setting::get('amazon_store_url', ''),
            'amazon_banner_title' => Setting::get('amazon_banner_title', ''),
            'amazon_banner_subtitle' => Setting::get('amazon_banner_subtitle', ''),
            'amazon_banner_button_text' => Setting::get('amazon_banner_button_text', ''),
            // Store localization
            'store_country' => Setting::get('store_country', ''),
            'store_country_code' => Setting::get('store_country_code', ''),
            'store_locale' => Setting::get('store_locale', ''),
            'store_languages' => Setting::get('store_languages', ''),
        ];

        return view('admin.homepage.site-settings', compact('settings'));
    }

    public function updateSiteSettings(Request $request)
    {
        $fields = [
            'site_name', 'site_tagline', 'site_description',
            'footer_about', 'footer_copyright',
            // About Us page
            'about_story', 'about_html_content',
            'social_facebook', 'social_instagram', 'social_twitter',
            'social_youtube', 'social_tiktok', 'social_pinterest',
            'contact_email', 'contact_phone', 'contact_address',
            'announcement_text',
            'marquee_text',
            'pdp_text_slider',
            // Homepage appearance
            'homepage_alt_bg_color',
            'newsletter_heading', 'newsletter_subtitle', 'newsletter_badge_text',
            'flash_sale_label', 'flash_sale_button_text',
            // Consultation
            'consultation_types', 'consultation_time_slots',
            // Testimonial videos
            'testimonial_videos',
            // Amazon store
            'amazon_store_url', 'amazon_banner_title', 'amazon_banner_subtitle', 'amazon_banner_button_text',
            // Store localization
            'store_country', 'store_country_code', 'store_locale', 'store_languages',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                Setting::set($field, $request->input($field), 'string', 'homepage');
            }
        }

        // Homepage display count settings
        $integerFields = [
            'homepage_featured_count',
            'homepage_new_arrivals_count',
            'homepage_bestsellers_count',
            'homepage_deals_count',
            'homepage_testimonials_count',
            'homepage_new_arrivals_days',
        ];

        foreach ($integerFields as $field) {
            if ($request->has($field)) {
                Setting::set($field, (int) $request->input($field), 'integer', 'homepage');
            }
        }

        if ($request->hasFile('site_logo')) {
            $path = $request->file('site_logo')->store('branding', 'public');
            Setting::set('site_logo', $path, 'string', 'homepage');
        }

        Cache::flush();

        return back()->with('success', 'Site settings updated successfully.');
    }

    public function uploadVideo(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'video' => ['required', 'file', 'mimes:mp4,mov,webm,avi', 'max:51200'],
        ]);

        $path = $request->file('video')->store('videos', 'public');

        return response()->json(['path' => 'storage/' . $path]);
    }

    // Hero Banners
    public function heroBanners()
    {
        $banners = Banner::where('position', 'hero')->ordered()->get();
        return view('admin.homepage.hero-banners', compact('banners'));
    }

    public function storeHeroBanner(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'image' => 'required|image|max:5120',
            'link' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'overlay_style' => 'nullable|string|in:' . implode(',', array_keys(Banner::OVERLAY_STYLES)),
        ]);

        $imagePath = $request->file('image')->store('banners', 'public');

        Banner::create([
            'name' => $request->name,
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'button_text' => $request->button_text,
            'image_url' => $imagePath,
            'link' => $request->link,
            'overlay_style' => $request->overlay_style ?? 'left-dark',
            'position' => 'hero',
            'priority' => Banner::where('position', 'hero')->max('priority') + 1,
            'is_active' => true,
        ]);

        Cache::flush();

        return back()->with('success', 'Hero banner added successfully.');
    }

    public function updateHeroBanner(Request $request, Banner $banner)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:5120',
            'link' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'overlay_style' => 'nullable|string|in:' . implode(',', array_keys(Banner::OVERLAY_STYLES)),
        ]);

        $data = $request->only(['name', 'title', 'subtitle', 'button_text', 'link', 'overlay_style']);

        if ($request->hasFile('image')) {
            if ($banner->image_url) {
                Storage::disk('public')->delete($banner->image_url);
            }
            $data['image_url'] = $request->file('image')->store('banners', 'public');
        }

        $banner->update($data);
        Cache::flush();

        return back()->with('success', 'Hero banner updated successfully.');
    }

    public function deleteHeroBanner(Banner $banner)
    {
        if ($banner->image_url) {
            Storage::disk('public')->delete($banner->image_url);
        }
        $banner->delete();
        Cache::flush();

        return back()->with('success', 'Hero banner deleted successfully.');
    }

    public function reorderHeroBanners(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:banners,id',
        ]);

        foreach ($request->order as $position => $id) {
            Banner::where('id', $id)->update(['priority' => $position]);
        }

        Cache::flush();

        return response()->json(['success' => true]);
    }

    public function toggleHeroBanner(Banner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);
        Cache::flush();
        return back()->with('success', 'Banner status updated.');
    }

    // Homepage Sections
    public function sections()
    {
        $sections = HomepageSection::ordered()->get();
        return view('admin.homepage.sections', compact('sections'));
    }

    public function editSection(HomepageSection $section)
    {
        return view('admin.homepage.edit-section', compact('section'));
    }

    public function updateSection(Request $request, HomepageSection $section)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'button_link' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $data = $request->only(['title', 'subtitle', 'button_text', 'button_link']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->has('background_color')) {
            $data['background_color'] = $request->input('background_color');
        }

        if ($request->has('text_color')) {
            $data['text_color'] = $request->input('text_color');
        }

        if ($request->has('content')) {
            $data['content'] = $request->input('content');
        }

        if ($request->hasFile('image')) {
            if ($section->image_url) {
                Storage::disk('public')->delete($section->image_url);
            }
            $data['image_url'] = $request->file('image')->store('sections', 'public');
        }

        $section->update($data);
        Cache::flush();

        return back()->with('success', 'Section updated successfully.');
    }

    public function toggleSection(HomepageSection $section)
    {
        $section->update(['is_active' => !$section->is_active]);
        Cache::flush();
        return back()->with('success', 'Section visibility updated.');
    }

    public function reorderSections(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:homepage_sections,id',
        ]);

        foreach ($request->order as $position => $id) {
            HomepageSection::where('id', $id)->update(['position' => $position]);
        }

        Cache::flush();
        return response()->json(['success' => true]);
    }

    // Testimonials
    public function testimonials()
    {
        $testimonials = Testimonial::ordered()->get();
        return view('admin.homepage.testimonials', compact('testimonials'));
    }

    public function storeTestimonial(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
            'product_name' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['name', 'title', 'content', 'rating', 'product_name']);
        $data['position'] = Testimonial::max('position') + 1;
        $data['is_active'] = true;

        if ($request->hasFile('avatar')) {
            $data['avatar_url'] = $request->file('avatar')->store('testimonials', 'public');
        }

        Testimonial::create($data);

        return back()->with('success', 'Testimonial added successfully.');
    }

    public function updateTestimonial(Request $request, Testimonial $testimonial)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
            'product_name' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['name', 'title', 'content', 'rating', 'product_name']);

        if ($request->hasFile('avatar')) {
            if ($testimonial->avatar_url) {
                Storage::disk('public')->delete($testimonial->avatar_url);
            }
            $data['avatar_url'] = $request->file('avatar')->store('testimonials', 'public');
        }

        $testimonial->update($data);

        return back()->with('success', 'Testimonial updated successfully.');
    }

    public function deleteTestimonial(Testimonial $testimonial)
    {
        if ($testimonial->avatar_url) {
            Storage::disk('public')->delete($testimonial->avatar_url);
        }
        $testimonial->delete();

        return back()->with('success', 'Testimonial deleted successfully.');
    }

    public function toggleTestimonial(Testimonial $testimonial)
    {
        $testimonial->update(['is_active' => !$testimonial->is_active]);
        return back()->with('success', 'Testimonial visibility updated.');
    }

    // Navigation Menus
    public function navigation()
    {
        // Admin sees ALL items (including inactive) — not getByLocation which filters active only
        $headerMenus = NavigationMenu::where('location', 'header')
            ->whereNull('parent_id')
            ->with(['children' => fn($q) => $q->orderBy('position')])
            ->orderBy('position')
            ->get();

        $footerMenus = [];
        foreach (['footer_col1', 'footer_col2', 'footer_col3'] as $loc) {
            $footerMenus[$loc] = NavigationMenu::where('location', $loc)
                ->orderBy('position')
                ->get();
        }

        return view('admin.homepage.navigation', compact('headerMenus', 'footerMenus'));
    }

    public function storeNavItem(Request $request)
    {
        $request->validate([
            'location' => 'required|string',
            'label' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:navigation_menus,id',
        ]);

        NavigationMenu::create([
            'location' => $request->location,
            'label' => $request->label,
            'url' => $request->url,
            'parent_id' => $request->parent_id,
            'position' => NavigationMenu::where('location', $request->location)->max('position') + 1,
            'is_active' => true,
        ]);

        $cacheKey = 'theme_nav.' . \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
        \Illuminate\Support\Facades\Cache::forget($cacheKey);

        return back()->with('success', 'Menu item added.');
    }

    public function updateNavItem(Request $request, NavigationMenu $menu)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'location' => 'nullable|string',
            'parent_id' => 'nullable|exists:navigation_menus,id',
            'is_active' => 'nullable',
            'open_in_new_tab' => 'nullable',
        ]);

        $data = $request->only(['label', 'url']);

        if ($request->has('location')) {
            $data['location'] = $request->location;
        }
        if ($request->has('parent_id')) {
            $data['parent_id'] = $request->parent_id ?: null;
        }
        if ($request->has('is_active')) {
            $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
        }
        if ($request->has('open_in_new_tab')) {
            $data['open_in_new_tab'] = filter_var($request->open_in_new_tab, FILTER_VALIDATE_BOOLEAN);
        }

        $menu->update($data);

        // Clear navigation cache
        $cacheKey = 'theme_nav.' . \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
        \Illuminate\Support\Facades\Cache::forget($cacheKey);

        return back()->with('success', 'Menu item updated.');
    }

    public function deleteNavItem(NavigationMenu $menu)
    {
        $menu->delete();

        $cacheKey = 'theme_nav.' . \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
        \Illuminate\Support\Facades\Cache::forget($cacheKey);

        return back()->with('success', 'Menu item removed.');
    }
}
