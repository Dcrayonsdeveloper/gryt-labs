<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SocialMediaPost;
use App\Services\SocialMediaPublishingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialMediaPostController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->get('status');

        $posts = SocialMediaPost::query()
            ->with('product')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('scheduled_at')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all' => SocialMediaPost::count(),
            'draft' => SocialMediaPost::where('status', 'draft')->count(),
            'scheduled' => SocialMediaPost::where('status', 'scheduled')->count(),
            'published' => SocialMediaPost::where('status', 'published')->count(),
            'failed' => SocialMediaPost::whereIn('status', ['failed', 'partially_failed'])->count(),
        ];

        return view('admin.social-calendar.index', compact('posts', 'counts', 'status'));
    }

    public function calendarData(Request $request): JsonResponse
    {
        $start = $request->get('start', now()->startOfMonth()->toDateString());
        $end = $request->get('end', now()->endOfMonth()->toDateString());

        $posts = SocialMediaPost::whereBetween('scheduled_at', [$start, $end])
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'start' => $p->scheduled_at?->toIso8601String(),
                'color' => match ($p->status) {
                    'published' => '#10b981',
                    'scheduled' => '#3b82f6',
                    'failed', 'partially_failed' => '#ef4444',
                    'draft' => '#6b7280',
                    default => '#f59e0b',
                },
                'status' => $p->status,
                'platforms' => $p->platforms,
                'url' => route('admin.social-calendar.edit', $p),
            ]);

        return response()->json($posts);
    }

    public function create(): View
    {
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.social-calendar.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'caption' => 'required|string|max:2200',
            'media_type' => 'required|in:image,video,carousel',
            'media_urls' => 'nullable|string',
            'thumbnail_url' => 'nullable|string',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'in:ig_post,ig_reel,ig_story,fb_post,fb_reel,fb_story',
            'product_id' => 'nullable|exists:products,id',
            'scheduled_at' => 'nullable|date',
            'hashtags' => 'nullable|string',
            'link' => 'nullable|url',
            'action' => 'required|in:draft,schedule,publish_now',
        ]);

        $mediaUrls = array_filter(array_map('trim', explode("\n", $validated['media_urls'] ?? '')));
        $hashtags = !empty($validated['hashtags'])
            ? array_filter(array_map('trim', explode(' ', str_replace(',', ' ', $validated['hashtags']))))
            : [];

        $status = match ($validated['action']) {
            'schedule' => 'scheduled',
            'publish_now' => 'scheduled',
            default => 'draft',
        };

        $post = SocialMediaPost::create([
            'user_id' => auth()->id(),
            'product_id' => $validated['product_id'] ?? null,
            'title' => $validated['title'],
            'caption' => $validated['caption'],
            'media_type' => $validated['media_type'],
            'media_urls' => $mediaUrls,
            'thumbnail_url' => $validated['thumbnail_url'] ?? null,
            'link' => $validated['link'] ?? null,
            'hashtags' => $hashtags,
            'platforms' => $validated['platforms'],
            'status' => $status,
            'scheduled_at' => $validated['action'] === 'publish_now'
                ? now()
                : ($validated['scheduled_at'] ?? null),
        ]);

        if ($validated['action'] === 'publish_now') {
            $service = app(SocialMediaPublishingService::class);
            $service->publish($post);

            return redirect()->route('admin.social-calendar.index')
                ->with('success', "Post published! Status: {$post->fresh()->status}");
        }

        return redirect()->route('admin.social-calendar.index')
            ->with('success', $status === 'scheduled' ? 'Post scheduled!' : 'Draft saved!');
    }

    public function edit(SocialMediaPost $post): View
    {
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.social-calendar.edit', compact('post', 'products'));
    }

    public function update(Request $request, SocialMediaPost $post)
    {
        if (in_array($post->status, ['publishing', 'published'])) {
            return back()->with('error', 'Cannot edit a published or publishing post.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'caption' => 'required|string|max:2200',
            'media_type' => 'required|in:image,video,carousel',
            'media_urls' => 'nullable|string',
            'thumbnail_url' => 'nullable|string',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'in:ig_post,ig_reel,ig_story,fb_post,fb_reel,fb_story',
            'product_id' => 'nullable|exists:products,id',
            'scheduled_at' => 'nullable|date',
            'hashtags' => 'nullable|string',
            'link' => 'nullable|url',
            'action' => 'required|in:draft,schedule,publish_now',
        ]);

        $mediaUrls = array_filter(array_map('trim', explode("\n", $validated['media_urls'] ?? '')));
        $hashtags = !empty($validated['hashtags'])
            ? array_filter(array_map('trim', explode(' ', str_replace(',', ' ', $validated['hashtags']))))
            : [];

        $status = match ($validated['action']) {
            'schedule' => 'scheduled',
            'publish_now' => 'scheduled',
            default => 'draft',
        };

        $post->update([
            'product_id' => $validated['product_id'] ?? null,
            'title' => $validated['title'],
            'caption' => $validated['caption'],
            'media_type' => $validated['media_type'],
            'media_urls' => $mediaUrls,
            'thumbnail_url' => $validated['thumbnail_url'] ?? null,
            'link' => $validated['link'] ?? null,
            'hashtags' => $hashtags,
            'platforms' => $validated['platforms'],
            'status' => $status,
            'scheduled_at' => $validated['action'] === 'publish_now'
                ? now()
                : ($validated['scheduled_at'] ?? null),
        ]);

        if ($validated['action'] === 'publish_now') {
            $service = app(SocialMediaPublishingService::class);
            $service->publish($post);

            return redirect()->route('admin.social-calendar.index')
                ->with('success', "Post published! Status: {$post->fresh()->status}");
        }

        return redirect()->route('admin.social-calendar.index')
            ->with('success', 'Post updated!');
    }

    public function destroy(SocialMediaPost $post)
    {
        $post->delete();

        return redirect()->route('admin.social-calendar.index')
            ->with('success', 'Post deleted.');
    }

    public function publishNow(SocialMediaPost $post)
    {
        $post->update(['status' => 'scheduled', 'scheduled_at' => now()]);

        $service = app(SocialMediaPublishingService::class);
        $service->publish($post);

        return back()->with('success', "Published! Status: {$post->fresh()->status}");
    }

    public function retry(SocialMediaPost $post)
    {
        $post->update([
            'status' => 'scheduled',
            'scheduled_at' => now(),
            'retry_count' => $post->retry_count + 1,
            'error_message' => null,
        ]);

        return back()->with('success', 'Post queued for retry.');
    }
}
