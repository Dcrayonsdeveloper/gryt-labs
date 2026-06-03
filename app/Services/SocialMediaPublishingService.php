<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SocialMediaPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialMediaPublishingService
{
    private string $apiVersion = 'v21.0';
    private string $pageToken;
    private string $pageId;
    private string $igToken;
    private string $igUserId;
    private string $igAccountId;

    public function __construct()
    {
        $this->pageId = Setting::get('meta_fb_page_id', config('services.facebook.page_id', ''));
        $this->igAccountId = Setting::get('meta_ig_account_id', config('services.instagram.business_account_id', ''));

        // Separate IG token (Instagram Login API — supports scheduled publishing)
        $this->igToken = Setting::get('instagram_access_token', config('services.instagram.access_token', ''));
        $this->igUserId = Setting::get('instagram_user_id', config('services.instagram.user_id', ''));

        // Get page token from stored settings or derive from master token
        $this->pageToken = Setting::get('meta_fb_page_token', '');
        if (empty($this->pageToken)) {
            $this->derivePageToken();
        }
    }

    private function derivePageToken(): void
    {
        $masterToken = Setting::get('meta_user_token', config('services.meta.system_user_token', ''));
        if (empty($masterToken) || empty($this->pageId)) {
            return;
        }

        try {
            $response = Http::get("https://graph.facebook.com/{$this->apiVersion}/{$this->pageId}", [
                'fields' => 'access_token',
                'access_token' => $masterToken,
            ]);

            if ($response->successful()) {
                $this->pageToken = $response->json('access_token', '');
                if ($this->pageToken) {
                    Setting::set('meta_fb_page_token', $this->pageToken);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to derive page token: ' . $e->getMessage());
        }
    }

    /**
     * Publish or schedule a social media post to all target platforms.
     * If scheduled_at is in the future, uses native scheduling where available.
     */
    public function publish(SocialMediaPost $post): array
    {
        $results = [];

        // Determine if this should be natively scheduled (future time)
        $scheduleTimestamp = null;
        if ($post->scheduled_at && $post->scheduled_at->isFuture()) {
            $scheduleTimestamp = $post->scheduled_at->timestamp;
        }

        foreach ($post->platforms as $platform) {
            try {
                $result = match ($platform) {
                    'ig_post' => $this->publishInstagramPost($post, $scheduleTimestamp),
                    'ig_reel' => $this->publishInstagramReel($post, $scheduleTimestamp),
                    'ig_story' => $this->publishInstagramStory($post),
                    'fb_post' => $this->publishFacebookPost($post, $scheduleTimestamp),
                    'fb_reel' => $this->publishFacebookReel($post, $scheduleTimestamp),
                    'fb_story' => $this->publishFacebookStory($post),
                    default => ['status' => 'skipped', 'message' => "Unknown platform: {$platform}"],
                };
                $results[$platform] = $result;
            } catch (\Exception $e) {
                Log::error("Social publish failed [{$platform}]: " . $e->getMessage());
                $results[$platform] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        // Determine overall status
        $statuses = collect($results)->pluck('status');
        if ($statuses->every(fn ($s) => $s === 'success')) {
            $overallStatus = 'published';
        } elseif ($statuses->contains('success')) {
            $overallStatus = 'partially_failed';
        } else {
            $overallStatus = 'failed';
        }

        $post->update([
            'status' => $overallStatus,
            'platform_results' => $results,
            'published_at' => $overallStatus !== 'failed' ? now() : null,
            'error_message' => $overallStatus === 'failed'
                ? collect($results)->filter(fn ($r) => $r['status'] !== 'success')->map(fn ($r) => $r['message'] ?? 'Unknown error')->implode('; ')
                : null,
        ]);

        return $results;
    }

    // ─── Instagram (uses separate IG token with scheduled publishing support) ────

    private function publishInstagramPost(SocialMediaPost $post, ?int $scheduleTimestamp = null): array
    {
        $mediaUrls = $post->media_urls ?? [];
        $caption = $post->full_caption;

        if ($post->media_type === 'carousel' && count($mediaUrls) > 1) {
            return $this->publishInstagramCarousel($mediaUrls, $caption, $scheduleTimestamp);
        }

        $imageUrl = $mediaUrls[0] ?? '';
        if (empty($imageUrl)) {
            return ['status' => 'error', 'message' => 'No image URL'];
        }

        $params = [
            'image_url' => $imageUrl,
            'caption' => $caption,
        ];

        $container = $this->igApiPost('media', $params);

        if (!isset($container['id'])) {
            return ['status' => 'error', 'message' => $container['error']['message'] ?? 'Container creation failed'];
        }

        // If scheduling for future, use publish_at parameter
        $publishParams = ['creation_id' => $container['id']];
        if ($scheduleTimestamp) {
            $publishParams['publish_at'] = $scheduleTimestamp;
        }

        $result = $this->igApiPost('media_publish', $publishParams);

        return isset($result['id'])
            ? ['status' => 'success', 'id' => $result['id'], 'scheduled' => (bool) $scheduleTimestamp]
            : ['status' => 'error', 'message' => $result['error']['message'] ?? 'Publish failed'];
    }

    private function publishInstagramCarousel(array $mediaUrls, string $caption, ?int $scheduleTimestamp = null): array
    {
        $childIds = [];

        foreach ($mediaUrls as $url) {
            $child = $this->igApiPost('media', [
                'image_url' => $url,
                'is_carousel_item' => 'true',
            ]);

            if (!isset($child['id'])) {
                return ['status' => 'error', 'message' => 'Carousel child upload failed: ' . ($child['error']['message'] ?? '')];
            }
            $childIds[] = $child['id'];
        }

        $container = $this->igApiPost('media', [
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $childIds),
            'caption' => $caption,
        ]);

        if (!isset($container['id'])) {
            return ['status' => 'error', 'message' => $container['error']['message'] ?? 'Carousel container failed'];
        }

        $publishParams = ['creation_id' => $container['id']];
        if ($scheduleTimestamp) {
            $publishParams['publish_at'] = $scheduleTimestamp;
        }

        $result = $this->igApiPost('media_publish', $publishParams);

        return isset($result['id'])
            ? ['status' => 'success', 'id' => $result['id'], 'scheduled' => (bool) $scheduleTimestamp]
            : ['status' => 'error', 'message' => $result['error']['message'] ?? 'Publish failed'];
    }

    private function publishInstagramReel(SocialMediaPost $post, ?int $scheduleTimestamp = null): array
    {
        $videoUrl = ($post->media_urls ?? [])[0] ?? '';
        if (empty($videoUrl)) {
            return ['status' => 'error', 'message' => 'No video URL'];
        }

        $container = $this->igApiPost('media', [
            'media_type' => 'REELS',
            'video_url' => $videoUrl,
            'caption' => $post->full_caption,
        ]);

        if (!isset($container['id'])) {
            return ['status' => 'error', 'message' => $container['error']['message'] ?? 'Reel container failed'];
        }

        // Poll until processing completes
        $ready = $this->waitForMediaReady($container['id']);
        if (!$ready) {
            return ['status' => 'error', 'message' => 'Video processing timed out'];
        }

        $publishParams = ['creation_id' => $container['id']];
        if ($scheduleTimestamp) {
            $publishParams['publish_at'] = $scheduleTimestamp;
        }

        $result = $this->igApiPost('media_publish', $publishParams);

        return isset($result['id'])
            ? ['status' => 'success', 'id' => $result['id'], 'scheduled' => (bool) $scheduleTimestamp]
            : ['status' => 'error', 'message' => $result['error']['message'] ?? 'Publish failed'];
    }

    private function publishInstagramStory(SocialMediaPost $post): array
    {
        $mediaUrl = ($post->media_urls ?? [])[0] ?? '';
        if (empty($mediaUrl)) {
            return ['status' => 'error', 'message' => 'No media URL'];
        }

        $params = ['media_type' => 'STORIES'];

        if ($post->media_type === 'video') {
            $params['video_url'] = $mediaUrl;
        } else {
            $params['image_url'] = $mediaUrl;
        }

        $container = $this->igApiPost('media', $params);

        if (!isset($container['id'])) {
            return ['status' => 'error', 'message' => $container['error']['message'] ?? 'Story container failed'];
        }

        if ($post->media_type === 'video') {
            $this->waitForMediaReady($container['id']);
        }

        $result = $this->igApiPost('media_publish', ['creation_id' => $container['id']]);

        return isset($result['id'])
            ? ['status' => 'success', 'id' => $result['id']]
            : ['status' => 'error', 'message' => $result['error']['message'] ?? 'Publish failed'];
    }

    // ─── Facebook ─────────────────────────────────────────────

    private function publishFacebookPost(SocialMediaPost $post, ?int $scheduleTimestamp = null): array
    {
        $mediaUrls = $post->media_urls ?? [];
        $message = $post->full_caption;

        if (empty($mediaUrls)) {
            $params = ['message' => $message, 'link' => $post->link];
            if ($scheduleTimestamp) {
                $params['published'] = 'false';
                $params['scheduled_publish_time'] = $scheduleTimestamp;
            }
            $result = $this->fbApiPost('feed', $params);
            return isset($result['id'])
                ? ['status' => 'success', 'id' => $result['id'], 'scheduled' => (bool) $scheduleTimestamp]
                : ['status' => 'error', 'message' => $result['error']['message'] ?? 'Post failed'];
        }

        if ($post->media_type === 'video') {
            return $this->publishFacebookReel($post, $scheduleTimestamp);
        }

        if (count($mediaUrls) === 1) {
            $params = ['url' => $mediaUrls[0], 'message' => $message];
            if ($scheduleTimestamp) {
                $params['published'] = 'false';
                $params['scheduled_publish_time'] = $scheduleTimestamp;
            }
            $result = $this->fbApiPost('photos', $params);
            return isset($result['id'])
                ? ['status' => 'success', 'id' => $result['id'], 'scheduled' => (bool) $scheduleTimestamp]
                : ['status' => 'error', 'message' => $result['error']['message'] ?? 'Photo post failed'];
        }

        // Multi-photo post
        $photoIds = [];
        foreach ($mediaUrls as $url) {
            $photo = $this->fbApiPost('photos', ['url' => $url, 'published' => 'false']);
            if (isset($photo['id'])) {
                $photoIds[] = $photo['id'];
            }
        }

        if (empty($photoIds)) {
            return ['status' => 'error', 'message' => 'No photos uploaded'];
        }

        $params = ['message' => $message];
        foreach ($photoIds as $i => $id) {
            $params["attached_media[{$i}]"] = json_encode(['media_fbid' => $id]);
        }
        if ($scheduleTimestamp) {
            $params['published'] = 'false';
            $params['scheduled_publish_time'] = $scheduleTimestamp;
        }

        $result = $this->fbApiPost('feed', $params);

        return isset($result['id'])
            ? ['status' => 'success', 'id' => $result['id'], 'scheduled' => (bool) $scheduleTimestamp]
            : ['status' => 'error', 'message' => $result['error']['message'] ?? 'Multi-photo failed'];
    }

    private function publishFacebookReel(SocialMediaPost $post, ?int $scheduleTimestamp = null): array
    {
        $videoUrl = ($post->media_urls ?? [])[0] ?? '';
        if (empty($videoUrl)) {
            return ['status' => 'error', 'message' => 'No video URL'];
        }

        $params = [
            'file_url' => $videoUrl,
            'description' => $post->full_caption,
        ];
        if ($scheduleTimestamp) {
            $params['published'] = 'false';
            $params['scheduled_publish_time'] = $scheduleTimestamp;
        }

        $result = $this->fbApiPost('videos', $params);

        return isset($result['id'])
            ? ['status' => 'success', 'id' => $result['id'], 'scheduled' => (bool) $scheduleTimestamp]
            : ['status' => 'error', 'message' => $result['error']['message'] ?? 'FB video failed'];
    }

    private function publishFacebookStory(SocialMediaPost $post): array
    {
        $mediaUrl = ($post->media_urls ?? [])[0] ?? '';
        if (empty($mediaUrl)) {
            return ['status' => 'error', 'message' => 'No media URL'];
        }

        if ($post->media_type === 'video') {
            // Video story — not well supported via API, skip gracefully
            return ['status' => 'skipped', 'message' => 'FB video stories not supported via API'];
        }

        // Photo story — need an unpublished photo first
        $photo = $this->fbApiPost('photos', ['url' => $mediaUrl, 'published' => 'false']);
        if (!isset($photo['id'])) {
            return ['status' => 'error', 'message' => 'Photo upload failed'];
        }

        $result = $this->fbApiPost('photo_stories', ['photo_id' => $photo['id']]);

        return isset($result['success']) || isset($result['post_id'])
            ? ['status' => 'success', 'id' => $result['post_id'] ?? '']
            : ['status' => 'error', 'message' => $result['error']['message'] ?? 'FB story failed'];
    }

    // ─── API Helpers ──────────────────────────────────────────

    private function igApiPost(string $endpoint, array $params): array
    {
        // Use separate IG token (Instagram Login API) — supports scheduled publishing
        // Falls back to page token + IG business account ID if IG token not available
        if (!empty($this->igToken) && !empty($this->igUserId)) {
            $params['access_token'] = $this->igToken;
            $userId = $this->igUserId;
        } else {
            $params['access_token'] = $this->pageToken;
            $userId = $this->igAccountId;
        }

        $response = Http::timeout(60)->asForm()->post(
            "https://graph.instagram.com/{$this->apiVersion}/{$userId}/{$endpoint}",
            $params
        );

        return $response->json() ?? [];
    }

    private function fbApiPost(string $endpoint, array $params): array
    {
        $params['access_token'] = $this->pageToken;

        $response = Http::timeout(30)->asForm()->post(
            "https://graph.facebook.com/{$this->apiVersion}/{$this->pageId}/{$endpoint}",
            $params
        );

        return $response->json() ?? [];
    }

    private function waitForMediaReady(string $containerId, int $maxAttempts = 30): bool
    {
        $token = !empty($this->igToken) ? $this->igToken : $this->pageToken;

        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(5);

            $response = Http::get("https://graph.instagram.com/{$this->apiVersion}/{$containerId}", [
                'fields' => 'status_code',
                'access_token' => $token,
            ]);

            $status = $response->json('status_code');

            if ($status === 'FINISHED') {
                return true;
            }
            if ($status === 'ERROR') {
                return false;
            }
        }

        return false;
    }
}
