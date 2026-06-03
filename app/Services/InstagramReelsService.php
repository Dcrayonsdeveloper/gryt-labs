<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramReelsService
{
    private string $accessToken;
    private string $igUserId;
    private string $apiVersion = 'v21.0';

    public function __construct()
    {
        // DB setting takes priority (per-tenant, auto-refreshed), fallback to .env
        $this->accessToken = Setting::get('instagram_access_token') ?: (config('services.instagram.access_token') ?? '');
        $this->igUserId = Setting::get('instagram_user_id') ?: (config('services.instagram.user_id') ?? '');
    }

    /**
     * Get latest reels from Instagram, cached for 1 hour.
     */
    public function getLatestReels(int $limit = 10): array
    {
        $tenantId = tenant('id') ?? 'central';

        // If no API token, still try collab reels from shortcodes
        if (empty($this->accessToken) || empty($this->igUserId)) {
            $collabSetting = Setting::get('instagram_collab_reels', '');
            if (empty($collabSetting)) {
                return [];
            }
            return Cache::remember("instagram_reels_{$tenantId}", 3600, function () use ($limit) {
                return $this->fetchCollabReelsOnly($limit);
            });
        }

        return Cache::remember("instagram_reels_{$tenantId}", 3600, function () use ($limit) {
            return $this->fetchReelsFromApi($limit);
        });
    }

    /**
     * Fetch reels from Instagram Graph API + manual collab reels.
     */
    private function fetchReelsFromApi(int $limit): array
    {
        try {
            $fields = 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count';

            // 1. Fetch own media
            $ownResponse = Http::timeout(15)->get(
                "https://graph.instagram.com/{$this->apiVersion}/{$this->igUserId}/media",
                [
                    'fields' => $fields,
                    'limit' => 50,
                    'access_token' => $this->accessToken,
                ]
            );

            $ownData = [];
            if ($ownResponse->successful()) {
                $ownData = $ownResponse->json('data', []);
            } else {
                Log::warning('Instagram own media API failed', [
                    'status' => $ownResponse->status(),
                    'body' => $ownResponse->body(),
                ]);
            }

            // 2. Fetch manual collab reels by shortcode (stored in settings)
            $collabData = $this->fetchCollabReels($fields);

            // 3. Separate own videos (have media_url, can autoplay) from collab (thumbnail only)
            $ownVideos = collect($ownData)->filter(fn($item) => in_array($item['media_type'] ?? '', ['VIDEO', 'REELS']) && !empty($item['media_url']));
            $ownOthers = collect($ownData)->filter(fn($item) => !in_array($item['media_type'] ?? '', ['VIDEO', 'REELS']) || empty($item['media_url']));
            $collabItems = collect($collabData);

            // 4. Own autoplay videos first, then collab reels by order, then other own media
            $combined = $ownVideos
                ->sortByDesc(fn($item) => ($item['like_count'] ?? 0) + ($item['comments_count'] ?? 0))
                ->concat($collabItems)
                ->concat($ownOthers)
                ->unique('id')
                ->take($limit);

            $reels = $combined
                ->map(fn($item) => [
                    'id' => $item['id'],
                    'shortcode' => $this->extractShortcode($item['permalink'] ?? ''),
                    'permalink' => $item['permalink'] ?? '',
                    'caption' => $item['caption'] ?? '',
                    'thumbnail_url' => $item['thumbnail_url'] ?? ($item['media_url'] ?? ''),
                    'media_url' => $item['media_url'] ?? '',
                    'media_type' => $item['media_type'] ?? 'VIDEO',
                    'timestamp' => $item['timestamp'] ?? '',
                    'like_count' => $item['like_count'] ?? 0,
                    'comments_count' => $item['comments_count'] ?? 0,
                ])
                ->values()
                ->toArray();

            return $reels;
        } catch (\Exception $e) {
            Log::error('Instagram Reels fetch failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch only collab reels (no API token needed).
     */
    private function fetchCollabReelsOnly(int $limit): array
    {
        $collabSetting = Setting::get('instagram_collab_reels', '');
        if (empty($collabSetting)) {
            return [];
        }

        $shortcodes = array_filter(array_map('trim', explode(',', $collabSetting)));
        $results = [];

        foreach (array_slice($shortcodes, 0, $limit) as $index => $shortcode) {
            $permalink = "https://www.instagram.com/reel/{$shortcode}/";
            $thumbnail = $this->getCollabThumbnail($shortcode);

            $results[] = [
                'id' => 'collab_' . $shortcode,
                'shortcode' => $shortcode,
                'permalink' => $permalink,
                'caption' => '',
                'thumbnail_url' => $thumbnail,
                'media_url' => '',
                'media_type' => 'VIDEO',
                'timestamp' => now()->subMinutes($index)->toIso8601String(),
                'like_count' => 0,
                'comments_count' => 0,
            ];
        }

        return $results;
    }

    /**
     * Fetch collab reels from stored shortcodes.
     * Downloads and caches thumbnails locally via Instagram's GraphQL.
     */
    private function fetchCollabReels(string $fields): array
    {
        $collabSetting = Setting::get('instagram_collab_reels', '');
        if (empty($collabSetting)) {
            return [];
        }

        $shortcodes = array_filter(array_map('trim', explode(',', $collabSetting)));
        $results = [];

        foreach ($shortcodes as $index => $shortcode) {
            try {
                $permalink = "https://www.instagram.com/reel/{$shortcode}/";
                $thumbnail = $this->getCollabThumbnail($shortcode);

                $results[] = [
                    'id' => 'collab_' . $shortcode,
                    'caption' => '',
                    'media_type' => 'VIDEO',
                    'media_url' => '',
                    'thumbnail_url' => $thumbnail,
                    'permalink' => $permalink,
                    'timestamp' => now()->subMinutes($index)->toIso8601String(),
                    'like_count' => 999 - $index,
                    'comments_count' => 0,
                ];
            } catch (\Exception $e) {
                Log::warning("Failed to add collab reel {$shortcode}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Get collab reel thumbnail — download from Instagram and cache locally.
     */
    private function getCollabThumbnail(string $shortcode): string
    {
        $dir = public_path('images/reels');
        $localPath = "{$dir}/{$shortcode}.jpg";
        $publicUrl = asset("images/reels/{$shortcode}.jpg");

        // Return cached file if it exists and is less than 7 days old
        if (file_exists($localPath) && (time() - filemtime($localPath)) < 604800) {
            return $publicUrl;
        }

        // Ensure directory exists
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Try fetching the page HTML and extracting og:image
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                    'Accept' => 'text/html',
                ])
                ->get("https://www.instagram.com/p/{$shortcode}/");

            if ($response->successful()) {
                $html = $response->body();
                // Extract og:image from meta tags
                if (preg_match('/<meta\s+(?:property|name)=["\']og:image["\']\s+content=["\']([^"\']+)["\']/i', $html, $m)) {
                    $imageUrl = html_entity_decode($m[1]);
                    $imgData = Http::timeout(10)->get($imageUrl)->body();
                    if ($imgData && strlen($imgData) > 1000) {
                        file_put_contents($localPath, $imgData);
                        return $publicUrl;
                    }
                }
                // Also try content="" before property="" order
                if (preg_match('/<meta\s+content=["\']([^"\']+)["\']\s+(?:property|name)=["\']og:image["\']/i', $html, $m)) {
                    $imageUrl = html_entity_decode($m[1]);
                    $imgData = Http::timeout(10)->get($imageUrl)->body();
                    if ($imgData && strlen($imgData) > 1000) {
                        file_put_contents($localPath, $imgData);
                        return $publicUrl;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch thumbnail for {$shortcode}: " . $e->getMessage());
        }

        return ''; // No thumbnail available
    }

    /**
     * Extract shortcode from Instagram permalink.
     * e.g. https://www.instagram.com/reel/ABC123/ → ABC123
     */
    private function extractShortcode(string $permalink): string
    {
        if (preg_match('#/(?:reel|p)/([A-Za-z0-9_-]+)#', $permalink, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Clear the cached reels.
     */
    public function clearCache(): void
    {
        $tenantId = tenant('id') ?? 'central';
        Cache::forget("instagram_reels_{$tenantId}");
    }
}
