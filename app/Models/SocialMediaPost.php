<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialMediaPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'product_id', 'title', 'caption', 'media_type',
        'media_urls', 'thumbnail_url', 'link', 'hashtags', 'platforms',
        'status', 'scheduled_at', 'published_at', 'platform_results',
        'error_message', 'retry_count',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'hashtags' => 'array',
        'platforms' => 'array',
        'platform_results' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public const PLATFORMS = [
        'ig_post' => 'Instagram Post',
        'ig_reel' => 'Instagram Reel',
        'ig_story' => 'Instagram Story',
        'fb_post' => 'Facebook Post',
        'fb_reel' => 'Facebook Reel',
        'fb_story' => 'Facebook Story',
    ];

    public const STATUSES = ['draft', 'scheduled', 'publishing', 'published', 'failed', 'partially_failed'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeDueForPublishing($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function getFullCaptionAttribute(): string
    {
        $caption = $this->caption;
        if (!empty($this->hashtags)) {
            $caption .= "\n\n" . collect($this->hashtags)->map(fn ($h) => str_starts_with($h, '#') ? $h : "#{$h}")->implode(' ');
        }
        return $caption;
    }

    public function getPlatformLabelsAttribute(): array
    {
        return collect($this->platforms ?? [])->map(fn ($p) => self::PLATFORMS[$p] ?? $p)->toArray();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'scheduled' => 'blue',
            'publishing' => 'yellow',
            'published' => 'green',
            'failed' => 'red',
            'partially_failed' => 'orange',
            default => 'gray',
        };
    }
}
