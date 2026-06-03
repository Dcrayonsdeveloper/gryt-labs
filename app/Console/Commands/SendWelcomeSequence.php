<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWelcomeSequence extends Command
{
    protected $signature = 'users:welcome-sequence';
    protected $description = 'Send welcome email sequence (day 3 bestsellers, day 7 reminder) to new users who have not ordered';

    public function handle(): int
    {
        $sent = 0;
        $sent += $this->sendDay3Bestsellers();
        $sent += $this->sendDay7Reminder();

        $this->info("Welcome sequence emails sent: {$sent}");
        return 0;
    }

    /**
     * Day 3 after registration: "Here's what our customers love" — 4 bestselling products.
     */
    private function sendDay3Bestsellers(): int
    {
        $users = User::where('role', 'customer')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->whereBetween('created_at', [now()->subDays(4), now()->subDays(3)])
            ->limit(100)
            ->get();

        $sent = 0;

        foreach ($users as $user) {
            try {
                // Skip if user already placed an order
                if (Order::where('user_id', $user->id)->exists()) {
                    continue;
                }

                $cacheKey = "welcome_seq:{$user->id}:step1";
                if (Cache::has($cacheKey)) {
                    continue;
                }

                $products = Product::active()
                    ->inStock()
                    ->orderByDesc('sales_count')
                    ->with('primaryImage')
                    ->limit(4)
                    ->get();

                if ($products->isEmpty()) {
                    continue;
                }

                $this->sendBestsellersEmail($user, $products, 'step1');

                Cache::put($cacheKey, true, now()->addDays(30));
                $sent++;

                $this->info("Day 3 welcome sent: {$user->email}");
            } catch (\Exception $e) {
                Log::warning('Welcome sequence day 3 failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Failed day 3 for user #{$user->id}: {$e->getMessage()}");
            }
        }

        return $sent;
    }

    /**
     * Day 7 after registration: "Browse our bestsellers" — 4 different products + welcome discount reminder.
     */
    private function sendDay7Reminder(): int
    {
        $users = User::where('role', 'customer')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->whereBetween('created_at', [now()->subDays(8), now()->subDays(7)])
            ->limit(100)
            ->get();

        $sent = 0;

        foreach ($users as $user) {
            try {
                // Skip if user already placed an order
                if (Order::where('user_id', $user->id)->exists()) {
                    continue;
                }

                $cacheKey = "welcome_seq:{$user->id}:step2";
                if (Cache::has($cacheKey)) {
                    continue;
                }

                // Get different products than day 3 (offset by 4)
                $products = Product::active()
                    ->inStock()
                    ->orderByDesc('sales_count')
                    ->with('primaryImage')
                    ->offset(4)
                    ->limit(4)
                    ->get();

                // Fall back to featured products if not enough
                if ($products->count() < 4) {
                    $products = Product::active()
                        ->inStock()
                        ->featured()
                        ->with('primaryImage')
                        ->limit(4)
                        ->get();
                }

                if ($products->isEmpty()) {
                    continue;
                }

                $this->sendBestsellersEmail($user, $products, 'step2');

                Cache::put($cacheKey, true, now()->addDays(30));
                $sent++;

                $this->info("Day 7 welcome sent: {$user->email}");
            } catch (\Exception $e) {
                Log::warning('Welcome sequence day 7 failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Failed day 7 for user #{$user->id}: {$e->getMessage()}");
            }
        }

        return $sent;
    }

    private function sendBestsellersEmail(User $user, $products, string $step): void
    {
        $storeName = Setting::get('store_name', config('app.name', 'Store'));
        $brandColor = Setting::get('primary_color', '') ?: '#334155';
        $baseUrl = $this->getBaseUrl();
        $logoUrl = $baseUrl . '/' . ltrim(Setting::get('store_logo', 'images/logo.png'), '/');
        $currency = Setting::get('currency', config('app.currency', 'INR'));
        $firstName = $user->first_name ?: 'there';
        $shopUrl = $baseUrl . '/products';

        $isStep2 = $step === 'step2';

        // Get welcome coupon info for step 2
        $couponHtml = '';
        if ($isStep2) {
            $coupon = Coupon::where('is_active', true)
                ->where('code', 'WELCOME10')
                ->first();

            if ($coupon) {
                $couponCode = $coupon->code;
                $discountText = $coupon->type === 'percentage'
                    ? intval($coupon->value) . '% off'
                    : $currency . ' ' . intval($coupon->value) . ' off';

                $couponHtml = <<<COUPON
                <div style="background:#f8f8f8;border:2px dashed {$brandColor};border-radius:8px;padding:16px;text-align:center;margin:0 0 24px">
                    <p style="color:#888;font-size:11px;text-transform:uppercase;letter-spacing:1px;margin:0 0 6px">Don't forget your welcome discount</p>
                    <p style="color:{$brandColor};font-size:24px;font-weight:800;margin:0 0 4px;letter-spacing:2px">{$couponCode}</p>
                    <p style="color:#555;font-size:13px;margin:0">{$discountText} your first order</p>
                </div>
                COUPON;
            }
        }

        // Build product grid (2x2)
        $productsHtml = '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
        $chunks = $products->chunk(2);

        foreach ($chunks as $row) {
            $productsHtml .= '<tr>';
            foreach ($row as $product) {
                $productUrl = $baseUrl . '/products/' . $product->slug;
                $imageUrl = url($product->primary_image_url);
                $name = e(\Illuminate\Support\Str::limit($product->name, 45));
                $price = number_format((float) $product->price, 2);
                $ratingHtml = '';
                if ($product->rating > 0 && $product->review_count > 0) {
                    $stars = str_repeat('&#9733;', (int) round($product->rating));
                    $ratingHtml = "<p style='color:#F59E0B;font-size:12px;margin:2px 0 0'>{$stars} <span style='color:#888'>({$product->review_count})</span></p>";
                }

                $productsHtml .= <<<PROD
                <td style="width:50%;padding:8px;text-align:center;vertical-align:top">
                    <a href="{$productUrl}">
                        <img src="{$imageUrl}" alt="{$name}" style="width:100%;max-width:180px;height:auto;aspect-ratio:1;object-fit:contain;border-radius:6px;border:1px solid #eee" />
                    </a>
                    <p style="margin:6px 0 2px"><a href="{$productUrl}" style="color:#111;text-decoration:none;font-size:13px;font-weight:500">{$name}</a></p>
                    <p style="color:{$brandColor};font-size:14px;font-weight:700;margin:0">{$currency} {$price}</p>
                    {$ratingHtml}
                </td>
                PROD;
            }
            // Fill empty cell if odd number
            if ($row->count() === 1) {
                $productsHtml .= '<td style="width:50%;padding:8px"></td>';
            }
            $productsHtml .= '</tr>';
        }
        $productsHtml .= '</table>';

        $heading = $isStep2
            ? "Still browsing, {$firstName}? Check these out!"
            : "Here's what our customers love, {$firstName}";
        $subtext = $isStep2
            ? 'These popular picks might be just what you need. Plus, your welcome discount is still waiting!'
            : 'Welcome to ' . e($storeName) . '! These are some of our most-loved products, handpicked for you.';
        $subject = $isStep2
            ? "Your welcome discount is waiting! Browse our bestsellers"
            : "Here's what our customers love - top picks for you";

        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif">
            <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;margin-top:20px;margin-bottom:20px">
                <div style="background:{$brandColor};padding:20px 24px;text-align:center">
                    <img src="{$logoUrl}" alt="{$storeName}" style="height:36px;object-fit:contain" />
                </div>
                <div style="padding:28px 24px">
                    <h2 style="margin:0 0 8px;color:#111;font-size:20px">{$heading}</h2>
                    <p style="color:#555;font-size:14px;line-height:1.6;margin:0 0 20px">
                        {$subtext}
                    </p>
                    {$couponHtml}
                    {$productsHtml}
                    <div style="text-align:center;margin-top:24px">
                        <a href="{$shopUrl}" style="display:inline-block;background:{$brandColor};color:#fff;text-decoration:none;padding:12px 28px;border-radius:6px;font-size:14px;font-weight:600">
                            Shop All Products
                        </a>
                    </div>
                </div>
                <div style="background:#f7f8fa;padding:14px 24px;border-top:1px solid #eee">
                    <p style="margin:0;color:#999;font-size:11px;text-align:center">
                        You received this because you signed up at {$storeName}. <a href="{$baseUrl}" style="color:#999">Unsubscribe</a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        HTML;

        Mail::send([], [], function ($message) use ($user, $storeName, $html, $subject) {
            $message->to($user->email)
                ->from(config('mail.from.address'), $storeName)
                ->subject($subject)
                ->html($html);
        });
    }

    private function getBaseUrl(): string
    {
        if (function_exists('tenant') && tenant()) {
            $domain = tenant()->domains->first()->domain ?? request()->getHost();
            return 'https://' . $domain;
        }
        return url('/');
    }
}
