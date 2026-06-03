<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JikraCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Mobile Cases & Covers',
                'description' => 'Protect your phone in style with our wide range of cases and covers',
                'icon' => 'device-mobile',
                'is_featured' => true,
                'children' => [
                    ['name' => 'Back Covers', 'description' => 'Slim back covers for all phone models'],
                    ['name' => 'Flip Cases', 'description' => 'Protective flip covers with card slots'],
                    ['name' => 'Transparent Cases', 'description' => 'Clear cases to show off your phone'],
                    ['name' => 'Rugged Cases', 'description' => 'Heavy duty shockproof protection'],
                    ['name' => 'Designer Covers', 'description' => 'Stylish printed and designer covers'],
                    ['name' => 'Wallet Cases', 'description' => 'Cases with built-in wallet and card holder'],
                ],
            ],
            [
                'name' => 'Chargers & Cables',
                'description' => 'Fast chargers, cables and charging accessories',
                'icon' => 'bolt',
                'is_featured' => true,
                'children' => [
                    ['name' => 'Wall Chargers', 'description' => 'Fast and standard wall chargers'],
                    ['name' => 'Car Chargers', 'description' => 'Charge your device on the go'],
                    ['name' => 'USB-C Cables', 'description' => 'Type-C to Type-C and Type-A cables'],
                    ['name' => 'Lightning Cables', 'description' => 'Apple Lightning charging cables'],
                    ['name' => 'Micro USB Cables', 'description' => 'Micro USB charging and data cables'],
                    ['name' => 'Wireless Chargers', 'description' => 'Qi wireless charging pads and stands'],
                    ['name' => 'Multi-Port Chargers', 'description' => 'Charge multiple devices simultaneously'],
                ],
            ],
            [
                'name' => 'Bluetooth Speakers',
                'description' => 'Portable and home Bluetooth speakers for every need',
                'icon' => 'musical-note',
                'is_featured' => true,
                'children' => [
                    ['name' => 'Portable Speakers', 'description' => 'Compact speakers for travel and outdoor use'],
                    ['name' => 'Waterproof Speakers', 'description' => 'IPX rated speakers for outdoor adventure'],
                    ['name' => 'Home Speakers', 'description' => 'Powerful speakers for home use'],
                    ['name' => 'Mini Speakers', 'description' => 'Ultra compact pocket-sized speakers'],
                    ['name' => 'Party Speakers', 'description' => 'High volume speakers with LED lights'],
                ],
            ],
            [
                'name' => 'Earphones & Headphones',
                'description' => 'Wired and wireless audio for every listener',
                'icon' => 'headphones',
                'is_featured' => true,
                'children' => [
                    ['name' => 'TWS Earbuds', 'description' => 'True wireless stereo earbuds'],
                    ['name' => 'Wired Earphones', 'description' => 'Type-C and 3.5mm wired earphones'],
                    ['name' => 'Neckband Earphones', 'description' => 'Wireless neckband style earphones'],
                    ['name' => 'Over-Ear Headphones', 'description' => 'Full-size over-ear headphones'],
                    ['name' => 'Gaming Headsets', 'description' => 'Headsets designed for gaming'],
                    ['name' => 'Noise Cancelling', 'description' => 'Active noise cancellation headphones'],
                ],
            ],
            [
                'name' => 'Power Banks',
                'description' => 'Portable charging for your devices anywhere anytime',
                'icon' => 'battery-charging',
                'is_featured' => true,
                'children' => [
                    ['name' => '5000-10000 mAh', 'description' => 'Compact power banks for daily use'],
                    ['name' => '10000-20000 mAh', 'description' => 'High capacity power banks'],
                    ['name' => '20000 mAh+', 'description' => 'Ultra high capacity for heavy users'],
                    ['name' => 'Fast Charge Power Banks', 'description' => 'Power banks with fast charging support'],
                    ['name' => 'Slim Power Banks', 'description' => 'Ultra thin and lightweight power banks'],
                ],
            ],
            [
                'name' => 'Screen Protectors',
                'description' => 'Keep your screen scratch-free and safe',
                'icon' => 'shield-check',
                'is_featured' => false,
                'children' => [
                    ['name' => 'Tempered Glass', 'description' => '9H hardness tempered glass protectors'],
                    ['name' => 'Matte Screen Guards', 'description' => 'Anti-glare matte finish protectors'],
                    ['name' => 'Privacy Screen Guards', 'description' => 'Anti-spy privacy screen protectors'],
                    ['name' => 'Camera Lens Protectors', 'description' => 'Tempered glass for camera lenses'],
                    ['name' => 'Full Body Protectors', 'description' => 'Front and back full body coverage'],
                ],
            ],
            [
                'name' => 'Smartwatches & Bands',
                'description' => 'Stay connected and track your fitness',
                'icon' => 'clock',
                'is_featured' => true,
                'children' => [
                    ['name' => 'Smartwatches', 'description' => 'Feature-rich smartwatches'],
                    ['name' => 'Fitness Bands', 'description' => 'Health and activity tracking bands'],
                    ['name' => 'Watch Straps', 'description' => 'Replacement straps and bands'],
                    ['name' => 'Watch Chargers', 'description' => 'Charging docks and cables for smartwatches'],
                ],
            ],
            [
                'name' => 'Mounts & Stands',
                'description' => 'Convenient holders for your devices',
                'icon' => 'device-tablet',
                'is_featured' => false,
                'children' => [
                    ['name' => 'Car Phone Mounts', 'description' => 'Dashboard and vent phone holders'],
                    ['name' => 'Desk Stands', 'description' => 'Desktop stands for phones and tablets'],
                    ['name' => 'Ring Holders', 'description' => '360° rotating ring grips'],
                    ['name' => 'Selfie Sticks', 'description' => 'Extendable selfie sticks with tripods'],
                    ['name' => 'Bike Mounts', 'description' => 'Handlebar mounts for cycling'],
                ],
            ],
            [
                'name' => 'Data & Connectivity',
                'description' => 'Adapters, hubs and data transfer accessories',
                'icon' => 'wifi',
                'is_featured' => false,
                'children' => [
                    ['name' => 'USB OTG Adapters', 'description' => 'Connect USB devices to your phone'],
                    ['name' => 'USB Hubs', 'description' => 'Multi-port USB hubs and docks'],
                    ['name' => 'Memory Cards', 'description' => 'MicroSD cards for extra storage'],
                    ['name' => 'Card Readers', 'description' => 'Memory card readers and adapters'],
                    ['name' => 'HDMI Adapters', 'description' => 'Connect phone to TV via HDMI'],
                ],
            ],
            [
                'name' => 'Gaming Accessories',
                'description' => 'Level up your mobile gaming experience',
                'icon' => 'puzzle',
                'is_featured' => false,
                'children' => [
                    ['name' => 'Mobile Controllers', 'description' => 'Gamepad controllers for mobile gaming'],
                    ['name' => 'Cooling Fans', 'description' => 'Phone cooling fans for gaming'],
                    ['name' => 'Trigger Buttons', 'description' => 'L1/R1 trigger buttons for shooters'],
                ],
            ],
        ];

        $position = 1;
        foreach ($categories as $categoryData) {
            $parent = Category::create([
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']),
                'description' => $categoryData['description'],
                'icon' => $categoryData['icon'] ?? null,
                'position' => $position++,
                'is_active' => true,
                'is_featured' => $categoryData['is_featured'] ?? false,
            ]);

            if (!empty($categoryData['children'])) {
                $childPosition = 1;
                foreach ($categoryData['children'] as $childData) {
                    Category::create([
                        'parent_id' => $parent->id,
                        'name' => $childData['name'],
                        'slug' => Str::slug($childData['name']),
                        'description' => $childData['description'],
                        'position' => $childPosition++,
                        'is_active' => true,
                        'is_featured' => false,
                    ]);
                }
            }
        }
    }
}
