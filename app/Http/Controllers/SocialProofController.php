<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SocialProofController extends Controller
{
    private const NAMES = [
        'Priya','Anjali','Sneha','Pooja','Kavya','Divya','Neha','Shreya','Ananya','Riya',
        'Meera','Sonal','Nisha','Deepa','Swati','Tanvi','Komal','Pallavi','Asha','Rekha',
        'Rahul','Amit','Vikram','Suresh','Rajesh','Ankit','Rohit','Sanjay','Manoj','Nitin',
    ];

    private const LOCATIONS = [
        'Mumbai Maharashtra India','Delhi Delhi India','Bengaluru Karnataka India',
        'Hyderabad Telangana India','Chennai Tamil Nadu India','Pune Maharashtra India',
        'Kolkata West Bengal India','Ahmedabad Gujarat India','Jaipur Rajasthan India',
        'Lucknow Uttar Pradesh India','Chandigarh Punjab India','Indore Madhya Pradesh India',
        'Bhopal Madhya Pradesh India','Nagpur Maharashtra India','Patna Bihar India',
        'Surat Gujarat India','Vadodara Gujarat India','Coimbatore Tamil Nadu India',
        'Visakhapatnam Andhra Pradesh India','Kochi Kerala India','Thiruvananthapuram Kerala India',
        'Mysuru Karnataka India','Nashik Maharashtra India','Agra Uttar Pradesh India',
    ];

    public function recent(): JsonResponse
    {
        $cacheKey = 'social_proof_recent.' . DB::connection()->getDatabaseName();

        $data = Cache::remember($cacheKey, now()->addMinutes(15), function () {
            $real = $this->fromRealOrders();
            if (count($real) >= 10) return $real;

            // Pad with catalog-based entries to reach ~15 items
            $needed = 15 - count($real);
            $generated = $this->fromCatalog($needed);

            return array_values(array_merge($real, $generated));
        });

        return response()->json($data);
    }

    private function fromRealOrders(): array
    {
        return Order::with(['items.product'])
            ->whereIn('status', ['confirmed', 'packed', 'shipped', 'delivered'])
            ->where('created_at', '>=', now()->subDays(60))
            ->latest()
            ->take(40)
            ->get()
            ->flatMap(function ($order) {
                $addr = $order->shipping_address_snapshot;
                if (is_string($addr)) $addr = json_decode($addr, true);
                $city      = $addr['city']  ?? null;
                $state     = $addr['state'] ?? null;
                $country   = $addr['country'] ?? 'India';
                $name      = $addr['name'] ?? $order->guest_name ?? null;
                $firstName = $name ? explode(' ', trim($name))[0] : null;
                if (!$firstName || !$city) return [];

                return $order->items->map(function ($item) use ($firstName, $city, $state, $country, $order) {
                    $image = $this->productImage($item);
                    $slug  = $item->product?->slug;
                    if (!$image || !$slug) return null;

                    return [
                        'name'       => $firstName,
                        'location'   => implode(' ', array_filter([$city, $state, $country])),
                        'product'    => $item->product_name,
                        'image'      => $image,
                        'url'        => url('/products/' . $slug),
                        'ago'        => $this->timeAgo($order->created_at),
                        'product_id' => $item->product_id,
                    ];
                })->filter()->values();
            })
            ->take(20)
            ->values()
            ->toArray();
    }

    private function fromCatalog(int $count): array
    {
        $products = Product::where('is_active', true)
            ->where('status', 'approved')
            ->whereHas('images')
            ->orderByDesc('sales_count')
            ->take(40)
            ->get();

        if ($products->isEmpty()) return [];

        $entries = [];
        $usedIndexes = [];

        for ($i = 0; $i < $count; $i++) {
            // Pick a random product (avoid repeating too soon)
            $available = $products->keys()->diff($usedIndexes)->values();
            if ($available->isEmpty()) {
                $usedIndexes = [];
                $available   = $products->keys();
            }
            $idx     = $available->random();
            $usedIndexes[] = $idx;
            $product = $products[$idx];

            $imgs    = $product->images;
            if (is_string($imgs)) $imgs = json_decode($imgs, true);
            $primary = collect($imgs)->firstWhere('is_primary', true) ?? collect($imgs)->first();
            $image   = $primary['url'] ?? null;
            if (!$image) continue;

            // Random time: 10 min – 48 h ago, seeded by product+day for consistency
            $seed   = crc32($product->id . now()->format('Y-m-d') . $i);
            srand($seed);
            $minsAgo = rand(10, 2880);
            srand();

            $name     = self::NAMES[array_rand(self::NAMES)];
            $location = self::LOCATIONS[array_rand(self::LOCATIONS)];
            $fakeDate = now()->subMinutes($minsAgo);

            $entries[] = [
                'name'       => $name,
                'location'   => $location,
                'product'    => $product->name,
                'image'      => $image,
                'url'        => url('/products/' . $product->slug),
                'ago'        => $this->timeAgo($fakeDate),
                'product_id' => $product->id,
            ];
        }

        return $entries;
    }

    private function productImage($item): ?string
    {
        if ($item->product) {
            $imgs = $item->product->images;
            if (is_string($imgs)) $imgs = json_decode($imgs, true);
            $primary = collect($imgs)->firstWhere('is_primary', true) ?? collect($imgs)->first();
            if ($url = $primary['url'] ?? null) return $url;
        }
        $snap = $item->product_snapshot;
        if (is_string($snap)) $snap = json_decode($snap, true);
        $imgs = $snap['images'] ?? [];
        return $imgs[0]['url'] ?? ($imgs[0] ?? null);
    }

    private function timeAgo(Carbon $date): string
    {
        $diff = now()->diffInMinutes($date);
        if ($diff < 60)  return $diff . ' minute' . ($diff === 1 ? '' : 's') . ' ago';
        $hours = (int) ($diff / 60);
        if ($hours < 24) return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
        $days = (int) ($hours / 24);
        return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
    }
}
