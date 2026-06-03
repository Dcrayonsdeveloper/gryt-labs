<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Review extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'order_item_id',
        'guest_name',
        'guest_email',
        'rating',
        'title',
        'content',
        'pros',
        'cons',
        'is_verified_purchase',
        'is_approved',
        'is_featured',
        'helpful_count',
        'unhelpful_count',
        'status',
        'is_generated',
        'generated_from_order_item_id',
        'moderated_by',
        'moderated_at',
        'admin_reply',
        'admin_replied_at',
    ];

    protected function casts(): array
    {
        return [
            'pros' => 'array',
            'cons' => 'array',
            'is_verified_purchase' => 'boolean',
            'is_approved' => 'boolean',
            'is_featured' => 'boolean',
            'is_generated' => 'boolean',
            'moderated_at' => 'datetime',
            'admin_replied_at' => 'datetime',
        ];
    }

    public function getReviewerNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->full_name;
        }

        return $this->guest_name ?? 'Anonymous';
    }

    public function getReviewerInitialAttribute(): string
    {
        if ($this->user) {
            return strtoupper(substr($this->user->first_name, 0, 1));
        }

        return strtoupper(substr($this->guest_name ?? 'A', 0, 1));
    }

    protected static function booted(): void
    {
        static::created(function ($review) {
            $review->product->updateRating();

            if (!$review->is_generated) {
                // Send coupon reward if enabled, otherwise send simple thank-you
                if (Setting::get('review_coupon_enabled', true)) {
                    app(\App\Listeners\SendCouponAfterReview::class)->handle($review);
                } else {
                    // Simple thank-you email (no discount)
                    $email = $review->user?->email ?? $review->guest_email;
                    if ($email) {
                        $storeName = Setting::get('store_name', config('app.name'));
                        $productName = $review->product->name ?? 'our product';
                        try {
                            \Illuminate\Support\Facades\Mail::send([], [], function ($m) use ($email, $review, $storeName, $productName) {
                                $name = $review->user?->first_name ?? $review->guest_name ?? 'Customer';
                                $m->to($email)
                                  ->subject("Thank you for your review — {$storeName}")
                                  ->html("
                                    <div style='font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;max-width:500px;margin:0 auto;'>
                                        <div style='padding:32px 24px;background:#fff;'>
                                            <h1 style='color:#111;font-size:20px;margin:0 0 12px;'>Thank You, {$name}!</h1>
                                            <p style='color:#555;font-size:14px;line-height:1.6;margin:0 0 16px;'>
                                                Thank you for sharing your experience with <strong>{$productName}</strong>. Your review helps other shoppers make great choices!
                                            </p>
                                            <p style='color:#555;font-size:14px;line-height:1.6;margin:0;'>
                                                Your review will be visible after moderation.
                                            </p>
                                        </div>
                                        <div style='padding:16px 24px;background:#f5f5f5;text-align:center;'>
                                            <p style='color:#999;font-size:11px;margin:0;'>— {$storeName}</p>
                                        </div>
                                    </div>
                                  ");
                            });
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::warning('Review thank-you email failed', ['email' => $email, 'error' => $e->getMessage()]);
                        }
                    }
                }

                // Notify store admin about new review
                $adminEmail = Setting::get('support_email', '');
                if ($adminEmail) {
                    $reviewerName = $review->user?->first_name ?? $review->guest_name ?? 'Guest';
                    $productName = $review->product->name ?? 'Unknown';
                    try {
                        \Illuminate\Support\Facades\Mail::send([], [], function ($m) use ($adminEmail, $review, $reviewerName, $productName) {
                            $storeName = Setting::get('store_name', config('app.name'));
                            $m->to($adminEmail)
                              ->subject("New Review: {$review->rating}★ on {$productName}")
                              ->html("
                                <div style='font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;max-width:500px;margin:0 auto;'>
                                    <div style='padding:24px;background:#fff;'>
                                        <h2 style='color:#111;font-size:18px;margin:0 0 12px;'>New Review Submitted</h2>
                                        <p style='color:#555;font-size:14px;line-height:1.6;margin:0 0 8px;'><strong>Product:</strong> {$productName}</p>
                                        <p style='color:#555;font-size:14px;line-height:1.6;margin:0 0 8px;'><strong>Reviewer:</strong> {$reviewerName}</p>
                                        <p style='color:#555;font-size:14px;line-height:1.6;margin:0 0 8px;'><strong>Rating:</strong> {$review->rating}/5</p>
                                        <p style='color:#555;font-size:14px;line-height:1.6;margin:0 0 8px;'><strong>Review:</strong> {$review->content}</p>
                                        <p style='color:#888;font-size:12px;margin:16px 0 0;'>This review needs moderation before it appears on the site.</p>
                                    </div>
                                </div>
                              ");
                        });
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::warning('Review admin notification failed', ['error' => $e->getMessage()]);
                    }
                }
            }
        });

        static::updated(function ($review) {
            $review->product->updateRating();
        });

        static::deleted(function ($review) {
            $review->product->updateRating();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ReviewVote::class);
    }

    public function response(): HasOne
    {
        return $this->hasOne(ReviewResponse::class);
    }

    public function generatedFromOrderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'generated_from_order_item_id');
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function approve(): void
    {
        $this->update([
            'is_approved' => true,
            'status' => 'approved',
            'moderated_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update([
            'is_approved' => false,
            'status' => 'rejected',
            'moderated_at' => now(),
        ]);
    }
}
