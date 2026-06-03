<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPostPurchaseEmails extends Command
{
    protected $signature = 'orders:post-purchase-sequence';
    protected $description = 'Send post-purchase email sequence (day 3 check-in, day 14 reorder) after delivery';

    /**
     * Day 7 review invitation is already handled by SendReviewInvitationAfterDelivery listener.
     * This command only handles day 3 (check-in) and day 14 (reorder/cross-sell).
     */
    public function handle(): int
    {
        if (!Setting::get('post_purchase_emails_enabled', true)) {
            $this->info('Post-purchase emails disabled for this tenant.');
            return 0;
        }

        $sent = 0;
        $sent += $this->sendDay3CheckIn();
        $sent += $this->sendDay14Reorder();

        $this->info("Post-purchase emails sent: {$sent}");
        return 0;
    }

    /**
     * Day 3 after delivery: "How's your purchase?" check-in email.
     */
    private function sendDay3CheckIn(): int
    {
        $orders = Order::where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [now()->subDays(4), now()->subDays(3)])
            ->whereNotNull('user_id')
            ->with(['user', 'items.product.primaryImage'])
            ->limit(100)
            ->get();

        $sent = 0;

        foreach ($orders as $order) {
            try {
                $user = $order->user;
                if (!$user || !$user->email || !$user->is_active) {
                    continue;
                }

                $cacheKey = "post_purchase_day3:{$order->id}";
                if (Cache::has($cacheKey)) {
                    continue;
                }

                $this->sendCheckInEmail($order, $user);

                Cache::put($cacheKey, true, now()->addDays(30));
                $sent++;

                $this->info("Day 3 check-in sent: {$user->email} (Order #{$order->order_number})");
            } catch (\Exception $e) {
                Log::warning('Post-purchase day 3 email failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Failed day 3 for order #{$order->id}: {$e->getMessage()}");
            }
        }

        return $sent;
    }

    /**
     * Day 14 after delivery: "Reorder or try something new" cross-sell email.
     */
    private function sendDay14Reorder(): int
    {
        $orders = Order::where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [now()->subDays(15), now()->subDays(14)])
            ->whereNotNull('user_id')
            ->with(['user', 'items.product.primaryImage', 'items.product.category'])
            ->limit(100)
            ->get();

        $sent = 0;

        foreach ($orders as $order) {
            try {
                $user = $order->user;
                if (!$user || !$user->email || !$user->is_active) {
                    continue;
                }

                $cacheKey = "post_purchase_day14:{$order->id}";
                if (Cache::has($cacheKey)) {
                    continue;
                }

                $this->sendReorderEmail($order, $user);

                Cache::put($cacheKey, true, now()->addDays(30));
                $sent++;

                $this->info("Day 14 reorder sent: {$user->email} (Order #{$order->order_number})");
            } catch (\Exception $e) {
                Log::warning('Post-purchase day 14 email failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Failed day 14 for order #{$order->id}: {$e->getMessage()}");
            }
        }

        return $sent;
    }

    private function sendCheckInEmail(Order $order, $user): void
    {
        $storeName = Setting::get('store_name', config('app.name', 'Store'));
        $brandColor = Setting::get('primary_color', '') ?: '#334155';
        $baseUrl = $this->getBaseUrl();
        $logoUrl = $baseUrl . '/' . ltrim(Setting::get('store_logo', 'images/logo.png'), '/');
        $contactUrl = $baseUrl . '/contact';
        $firstName = $user->first_name ?: 'there';

        // Build purchased items summary
        $itemsHtml = '';
        foreach ($order->items->take(3) as $item) {
            $product = $item->product;
            if (!$product) continue;

            $productUrl = $baseUrl . '/products/' . $product->slug;
            $imageUrl = url($product->primary_image_url);
            $name = e($item->product_name ?: $product->name);

            $itemsHtml .= <<<ITEM
            <tr>
                <td style="padding:10px;border-bottom:1px solid #eee">
                    <table cellpadding="0" cellspacing="0" border="0" width="100%"><tr>
                        <td style="width:60px;vertical-align:top">
                            <img src="{$imageUrl}" alt="{$name}" style="width:60px;height:60px;object-fit:contain;border-radius:4px;border:1px solid #eee" />
                        </td>
                        <td style="padding-left:12px;vertical-align:middle">
                            <a href="{$productUrl}" style="color:#111;text-decoration:none;font-size:14px;font-weight:500">{$name}</a>
                        </td>
                    </tr></table>
                </td>
            </tr>
            ITEM;
        }

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
                    <h2 style="margin:0 0 8px;color:#111;font-size:20px">Hi {$firstName}, how's your purchase?</h2>
                    <p style="color:#555;font-size:14px;line-height:1.6;margin:0 0 20px">
                        Your order <strong>{$order->order_number}</strong> was delivered a few days ago. We hope you're enjoying it!
                    </p>
                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #eee;border-radius:6px;overflow:hidden;margin-bottom:20px">
                        {$itemsHtml}
                    </table>
                    <p style="color:#555;font-size:14px;line-height:1.6;margin:0 0 20px">
                        If you have any questions or need help with your order, our support team is here for you.
                    </p>
                    <div style="text-align:center">
                        <a href="{$contactUrl}" style="display:inline-block;background:{$brandColor};color:#fff;text-decoration:none;padding:12px 28px;border-radius:6px;font-size:14px;font-weight:600">
                            Contact Support
                        </a>
                    </div>
                </div>
                <div style="background:#f7f8fa;padding:14px 24px;border-top:1px solid #eee">
                    <p style="margin:0;color:#999;font-size:11px;text-align:center">
                        You received this because you ordered from {$storeName}.
                    </p>
                </div>
            </div>
        </body>
        </html>
        HTML;

        Mail::send([], [], function ($message) use ($user, $storeName, $html) {
            $message->to($user->email)
                ->from(config('mail.from.address'), $storeName)
                ->subject("How's your purchase? We'd love to hear from you!")
                ->html($html);
        });
    }

    private function sendReorderEmail(Order $order, $user): void
    {
        $storeName = Setting::get('store_name', config('app.name', 'Store'));
        $brandColor = Setting::get('primary_color', '') ?: '#334155';
        $baseUrl = $this->getBaseUrl();
        $logoUrl = $baseUrl . '/' . ltrim(Setting::get('store_logo', 'images/logo.png'), '/');
        $currency = Setting::get('currency', config('app.currency', 'INR'));
        $firstName = $user->first_name ?: 'there';

        // Get the first purchased product for display
        $purchasedItem = $order->items->first();
        $purchasedProduct = $purchasedItem?->product;

        // Build purchased product HTML
        $purchasedHtml = '';
        if ($purchasedProduct) {
            $productUrl = $baseUrl . '/products/' . $purchasedProduct->slug;
            $imageUrl = url($purchasedProduct->primary_image_url);
            $name = e($purchasedProduct->name);
            $price = number_format((float) $purchasedProduct->price, 2);

            $purchasedHtml = <<<PHTML
            <div style="text-align:center;margin-bottom:24px">
                <p style="color:#888;font-size:12px;text-transform:uppercase;letter-spacing:1px;margin:0 0 10px">Your Purchase</p>
                <a href="{$productUrl}">
                    <img src="{$imageUrl}" alt="{$name}" style="width:120px;height:120px;object-fit:contain;border-radius:6px;border:1px solid #eee" />
                </a>
                <p style="margin:8px 0 2px"><a href="{$productUrl}" style="color:#111;text-decoration:none;font-size:14px;font-weight:600">{$name}</a></p>
                <p style="color:{$brandColor};font-size:15px;font-weight:700;margin:0">{$currency} {$price}</p>
                <a href="{$productUrl}" style="display:inline-block;background:{$brandColor};color:#fff;text-decoration:none;padding:8px 20px;border-radius:6px;font-size:13px;font-weight:600;margin-top:10px">
                    Reorder
                </a>
            </div>
            PHTML;
        }

        // Get 3 recommended products from the same category
        $categoryIds = $order->items
            ->map(fn($item) => $item->product?->category_id)
            ->filter()
            ->unique();

        $purchasedProductIds = $order->items->pluck('product_id')->toArray();

        $recommendations = Product::active()
            ->inStock()
            ->whereIn('category_id', $categoryIds)
            ->whereNotIn('id', $purchasedProductIds)
            ->orderByDesc('sales_count')
            ->with('primaryImage')
            ->limit(3)
            ->get();

        // If not enough from same category, fill from bestsellers
        if ($recommendations->count() < 3) {
            $excludeIds = array_merge($purchasedProductIds, $recommendations->pluck('id')->toArray());
            $more = Product::active()
                ->inStock()
                ->whereNotIn('id', $excludeIds)
                ->orderByDesc('sales_count')
                ->with('primaryImage')
                ->limit(3 - $recommendations->count())
                ->get();
            $recommendations = $recommendations->concat($more);
        }

        // Build recommendations HTML
        $recsHtml = '';
        if ($recommendations->isNotEmpty()) {
            $recsHtml = '<p style="color:#888;font-size:12px;text-transform:uppercase;letter-spacing:1px;margin:24px 0 12px;text-align:center">You Might Also Like</p>';
            $recsHtml .= '<table cellpadding="0" cellspacing="0" border="0" width="100%"><tr>';

            foreach ($recommendations as $rec) {
                $recUrl = $baseUrl . '/products/' . $rec->slug;
                $recImage = url($rec->primary_image_url);
                $recName = e(\Illuminate\Support\Str::limit($rec->name, 40));
                $recPrice = number_format((float) $rec->price, 2);
                $width = intval(100 / $recommendations->count());

                $recsHtml .= <<<REC
                <td style="width:{$width}%;padding:6px;text-align:center;vertical-align:top">
                    <a href="{$recUrl}">
                        <img src="{$recImage}" alt="{$recName}" style="width:100%;max-width:140px;height:auto;aspect-ratio:1;object-fit:contain;border-radius:6px;border:1px solid #eee" />
                    </a>
                    <p style="margin:6px 0 2px"><a href="{$recUrl}" style="color:#111;text-decoration:none;font-size:12px;font-weight:500">{$recName}</a></p>
                    <p style="color:{$brandColor};font-size:13px;font-weight:700;margin:0">{$currency} {$recPrice}</p>
                </td>
                REC;
            }

            $recsHtml .= '</tr></table>';
        }

        $shopUrl = $baseUrl . '/products';

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
                    <h2 style="margin:0 0 8px;color:#111;font-size:20px">Ready to reorder, {$firstName}?</h2>
                    <p style="color:#555;font-size:14px;line-height:1.6;margin:0 0 20px">
                        It's been a couple of weeks since your order was delivered. Need a refill, or want to try something new?
                    </p>
                    {$purchasedHtml}
                    {$recsHtml}
                    <div style="text-align:center;margin-top:24px">
                        <a href="{$shopUrl}" style="display:inline-block;background:{$brandColor};color:#fff;text-decoration:none;padding:12px 28px;border-radius:6px;font-size:14px;font-weight:600">
                            Browse All Products
                        </a>
                    </div>
                </div>
                <div style="background:#f7f8fa;padding:14px 24px;border-top:1px solid #eee">
                    <p style="margin:0;color:#999;font-size:11px;text-align:center">
                        You received this because you ordered from {$storeName}.
                    </p>
                </div>
            </div>
        </body>
        </html>
        HTML;

        Mail::send([], [], function ($message) use ($user, $storeName, $html) {
            $message->to($user->email)
                ->from(config('mail.from.address'), $storeName)
                ->subject('Reorder or try something new?')
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
