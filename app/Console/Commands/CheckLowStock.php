<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckLowStock extends Command
{
    protected $signature = 'stock:check-low {--threshold=5 : Stock threshold}';
    protected $description = 'Check for low stock products and send WhatsApp alert to admin';

    public function handle(): int
    {
        $threshold = (int) $this->option('threshold');

        $lowStockProducts = Product::where('is_active', true)
            ->where('stock_quantity', '<=', $threshold)
            ->where('stock_quantity', '>', 0)
            ->orderBy('stock_quantity')
            ->get(['id', 'name', 'sku', 'stock_quantity']);

        $outOfStockProducts = Product::where('is_active', true)
            ->where('stock_quantity', '<=', 0)
            ->get(['id', 'name', 'sku', 'stock_quantity']);

        if ($lowStockProducts->isEmpty() && $outOfStockProducts->isEmpty()) {
            $this->info('No low stock products found.');
            return self::SUCCESS;
        }

        $message = "LOW STOCK ALERT

";

        if ($outOfStockProducts->isNotEmpty()) {
            $message .= "OUT OF STOCK ({$outOfStockProducts->count()}):
";
            foreach ($outOfStockProducts->take(10) as $p) {
                $message .= "- {$p->name} (SKU: {$p->sku})
";
            }
            if ($outOfStockProducts->count() > 10) {
                $remaining = $outOfStockProducts->count() - 10;
                $message .= "...and {$remaining} more
";
            }
            $message .= "
";
        }

        if ($lowStockProducts->isNotEmpty()) {
            $message .= "LOW STOCK ({$lowStockProducts->count()}):
";
            foreach ($lowStockProducts->take(15) as $p) {
                $message .= "- {$p->name}: {$p->stock_quantity} left
";
            }
            if ($lowStockProducts->count() > 15) {
                $remaining = $lowStockProducts->count() - 15;
                $message .= "...and {$remaining} more
";
            }
        }

        $this->sendWhatsAppAlert($message);

        $this->info("Found {$lowStockProducts->count()} low stock and {$outOfStockProducts->count()} out of stock products. Alert sent.");

        return self::SUCCESS;
    }

    private function sendWhatsAppAlert(string $message): void
    {
        $token = \App\Models\Setting::get('whatsapp_api_token', '');
        $phoneNumberId = \App\Models\Setting::get('whatsapp_phone_number_id', '');
        $adminPhone = '919354567705';

        if (empty($token) || empty($phoneNumberId)) {
            Log::warning('CheckLowStock: WhatsApp credentials not configured');
            $this->warn('WhatsApp credentials not configured. Alert logged only.');
            Log::info('Low Stock Alert', ['message' => $message]);
            return;
        }

        try {
            $response = Http::withToken($token)
                ->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $adminPhone,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

            if ($response->failed()) {
                Log::error('CheckLowStock: WhatsApp send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                $this->error('Failed to send WhatsApp alert.');
            } else {
                $this->info('WhatsApp alert sent successfully.');
            }
        } catch (\Throwable $e) {
            Log::error('CheckLowStock: Exception', ['error' => $e->getMessage()]);
            $this->error('Exception sending WhatsApp alert: ' . $e->getMessage());
        }
    }
}
