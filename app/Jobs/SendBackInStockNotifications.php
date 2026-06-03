<?php

namespace App\Jobs;

use App\Models\BackInStockRequest;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBackInStockNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $productId
    ) {}

    public function handle(): void
    {
        $product = Product::find($this->productId);

        if (!$product || $product->stock_quantity <= 0) {
            return;
        }

        $requests = BackInStockRequest::where('product_id', $this->productId)
            ->pending()
            ->get();

        if ($requests->isEmpty()) {
            return;
        }

        $storeName = Setting::get('store_name', config('app.name', 'Store'));
        $productUrl = route('products.show', $product->slug);
        $productImage = url($product->primary_image_url);
        $productName = $product->name;
        $productPrice = number_format((float) $product->price, 2);
        $currency = Setting::get('currency', config('app.currency', 'INR'));

        foreach ($requests as $request) {
            try {
                Mail::send([], [], function ($message) use ($request, $storeName, $productUrl, $productImage, $productName, $productPrice, $currency) {
                    $message->to($request->email)
                        ->from(config('mail.from.address'), $storeName)
                        ->subject("Good news! {$productName} is back in stock")
                        ->html($this->buildEmailHtml($storeName, $productUrl, $productImage, $productName, $productPrice, $currency));
                });

                $request->update(['notified_at' => now()]);

            } catch (\Exception $e) {
                Log::warning('Back-in-stock notification failed', [
                    'email' => $request->email,
                    'product_id' => $this->productId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Back-in-stock notifications sent", [
            'product_id' => $this->productId,
            'count' => $requests->count(),
        ]);
    }

    private function buildEmailHtml(string $storeName, string $productUrl, string $productImage, string $productName, string $productPrice, string $currency): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif">
            <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;margin-top:20px;margin-bottom:20px">
                <div style="background:#0F1111;padding:20px 30px">
                    <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:600">{$storeName}</h1>
                </div>
                <div style="padding:30px">
                    <h2 style="margin:0 0 10px;color:#0F1111;font-size:22px">It's Back in Stock!</h2>
                    <p style="color:#565959;font-size:15px;line-height:1.6;margin:0 0 25px">
                        Great news! The item you were waiting for is now available. Grab it before it sells out again.
                    </p>
                    <div style="border:1px solid #e3e6e6;border-radius:8px;padding:20px;display:flex;align-items:center;gap:20px;margin-bottom:25px">
                        <img src="{$productImage}" alt="{$productName}" style="width:120px;height:120px;object-fit:contain;border-radius:4px" />
                        <div>
                            <h3 style="margin:0 0 8px;color:#0F1111;font-size:16px;font-weight:600">{$productName}</h3>
                            <p style="margin:0;color:#B12704;font-size:18px;font-weight:700">{$currency} {$productPrice}</p>
                            <p style="margin:8px 0 0;color:#007600;font-size:14px;font-weight:600">In Stock</p>
                        </div>
                    </div>
                    <a href="{$productUrl}" style="display:inline-block;background:#FFD814;color:#0F1111;text-decoration:none;padding:12px 30px;border-radius:8px;font-size:15px;font-weight:600">
                        Shop Now
                    </a>
                </div>
                <div style="background:#f7f8fa;padding:15px 30px;border-top:1px solid #e3e6e6">
                    <p style="margin:0;color:#888;font-size:12px">
                        You received this email because you requested to be notified when this item was back in stock.
                    </p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
