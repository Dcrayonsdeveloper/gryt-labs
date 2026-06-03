<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWishlistReminders extends Command
{
    protected $signature = 'wishlist:send-reminders {--days-old=3 : Minimum days since item was wishlisted}';
    protected $description = 'Send weekly digest emails to users with items sitting in their wishlist';

    public function handle(): int
    {
        $daysOld = (int) $this->option('days-old');

        // Get users who have wishlist items added more than N days ago
        $userIds = Wishlist::where('created_at', '<=', now()->subDays($daysOld))
            ->distinct()
            ->pluck('user_id');

        $this->info("Found {$userIds->count()} users with old wishlist items");

        $sent = 0;

        foreach ($userIds as $userId) {
            try {
                $user = User::find($userId);

                if (!$user || !$user->email || !$user->is_active) {
                    continue;
                }

                // Check if user received a wishlist reminder in the last 7 days
                $cacheKey = "wishlist_reminder_sent:{$userId}";
                if (Cache::has($cacheKey)) {
                    continue;
                }

                // Get wishlist items older than N days, with product data
                $wishlistItems = Wishlist::where('user_id', $userId)
                    ->where('created_at', '<=', now()->subDays($daysOld))
                    ->with(['product.primaryImage', 'product.category'])
                    ->get()
                    ->filter(fn($item) => $item->product && $item->product->is_active);

                if ($wishlistItems->isEmpty()) {
                    continue;
                }

                // Exclude items the user has already purchased
                $purchasedProductIds = OrderItem::whereHas('order', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->pluck('product_id')->unique();

                $wishlistItems = $wishlistItems->filter(
                    fn($item) => !$purchasedProductIds->contains($item->product_id)
                );

                if ($wishlistItems->isEmpty()) {
                    continue;
                }

                // Limit to 6 items in the email
                $emailItems = $wishlistItems->take(6);
                $totalCount = $wishlistItems->count();

                $this->sendReminderEmail($user, $emailItems, $totalCount);

                // Mark as sent for 7 days
                Cache::put($cacheKey, true, now()->addDays(7));

                $sent++;
                $this->info("Sent reminder to: {$user->email} ({$emailItems->count()} items)");

            } catch (\Exception $e) {
                Log::warning('Wishlist reminder failed', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Failed for user #{$userId}: {$e->getMessage()}");
            }
        }

        $this->info("Wishlist reminders sent: {$sent}");

        return 0;
    }

    private function sendReminderEmail(User $user, $items, int $totalCount): void
    {
        $storeName = Setting::get('store_name', config('app.name', 'Store'));
        $currency = Setting::get('currency', config('app.currency', 'INR'));
        $wishlistUrl = url('/wishlist');
        $firstName = $user->first_name ?: 'there';

        $itemsHtml = '';
        foreach ($items as $item) {
            $product = $item->product;
            $productUrl = route('products.show', $product->slug);
            $imageUrl = url($product->primary_image_url);
            $price = number_format((float) $product->price, 2);
            $name = e($product->name);
            $inStock = $product->stock_quantity > 0;
            $stockLabel = $inStock
                ? '<span style="color:#007600;font-size:12px;font-weight:600">In Stock</span>'
                : '<span style="color:#B12704;font-size:12px;font-weight:600">Out of Stock</span>';

            $itemsHtml .= <<<ITEM
            <tr>
                <td style="padding:15px;border-bottom:1px solid #e3e6e6">
                    <table cellpadding="0" cellspacing="0" border="0" width="100%"><tr>
                        <td style="width:80px;vertical-align:top">
                            <a href="{$productUrl}"><img src="{$imageUrl}" alt="{$name}" style="width:80px;height:80px;object-fit:contain;border-radius:4px;border:1px solid #e3e6e6" /></a>
                        </td>
                        <td style="padding-left:15px;vertical-align:top">
                            <a href="{$productUrl}" style="color:#0F1111;text-decoration:none;font-size:14px;font-weight:600;display:block;margin-bottom:4px">{$name}</a>
                            <div style="color:#B12704;font-size:16px;font-weight:700;margin-bottom:4px">{$currency} {$price}</div>
                            {$stockLabel}
                        </td>
                    </tr></table>
                </td>
            </tr>
            ITEM;
        }

        $moreText = $totalCount > 6
            ? "<p style='color:#565959;font-size:14px;text-align:center;margin:15px 0'>...and " . ($totalCount - 6) . " more item(s) in your wishlist</p>"
            : '';

        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif">
            <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;margin-top:20px;margin-bottom:20px">
                <div style="background:#0F1111;padding:20px 30px">
                    <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:600">{$storeName}</h1>
                </div>
                <div style="padding:30px">
                    <h2 style="margin:0 0 10px;color:#0F1111;font-size:22px">Hi {$firstName}, your wishlist is waiting!</h2>
                    <p style="color:#565959;font-size:15px;line-height:1.6;margin:0 0 20px">
                        You saved these items a while ago. Don't let them slip away — they could sell out any time.
                    </p>
                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e3e6e6;border-radius:8px;overflow:hidden">
                        {$itemsHtml}
                    </table>
                    {$moreText}
                    <div style="text-align:center;margin-top:25px">
                        <a href="{$wishlistUrl}" style="display:inline-block;background:#FFD814;color:#0F1111;text-decoration:none;padding:12px 30px;border-radius:8px;font-size:15px;font-weight:600">
                            View My Wishlist
                        </a>
                    </div>
                </div>
                <div style="background:#f7f8fa;padding:15px 30px;border-top:1px solid #e3e6e6">
                    <p style="margin:0;color:#888;font-size:12px">
                        You received this email because you have items in your wishlist at {$storeName}.
                    </p>
                </div>
            </div>
        </body>
        </html>
        HTML;

        Mail::send([], [], function ($message) use ($user, $storeName, $html, $totalCount) {
            $message->to($user->email)
                ->from(config('mail.from.address'), $storeName)
                ->subject("Your wishlist ({$totalCount} item" . ($totalCount !== 1 ? 's' : '') . ") is waiting for you!")
                ->html($html);
        });
    }
}
