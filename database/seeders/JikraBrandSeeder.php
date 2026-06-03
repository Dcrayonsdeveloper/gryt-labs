<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JikraBrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            // Global Premium Brands
            ['name' => 'Samsung', 'description' => 'Innovating for a better world', 'is_featured' => true],
            ['name' => 'Apple', 'description' => 'Think Different', 'is_featured' => true],
            ['name' => 'Sony', 'description' => 'Be Moved', 'is_featured' => true],
            ['name' => 'JBL', 'description' => 'Dare to Listen', 'is_featured' => true],
            ['name' => 'Bose', 'description' => 'Better Sound Through Research', 'is_featured' => true],
            ['name' => 'Anker', 'description' => 'Charge Simply, Live Fully', 'is_featured' => true],

            // Popular Indian Brands
            ['name' => 'boAt', 'description' => 'Plug Into Nirvana', 'is_featured' => true],
            ['name' => 'Boult', 'description' => 'Tune Into Life', 'is_featured' => true],
            ['name' => 'Noise', 'description' => 'Stay True to Sound', 'is_featured' => true],
            ['name' => 'Zebronics', 'description' => 'Express Yourself', 'is_featured' => false],
            ['name' => 'PTron', 'description' => 'The Sound of Innovation', 'is_featured' => false],
            ['name' => 'Mivi', 'description' => 'Made in India Sound', 'is_featured' => false],
            ['name' => 'Portronics', 'description' => 'Techno-Gadgets for Everyone', 'is_featured' => false],
            ['name' => 'Fire-Boltt', 'description' => 'India\'s No.1 Smartwatch Brand', 'is_featured' => false],

            // Global Tech Brands
            ['name' => 'OnePlus', 'description' => 'Never Settle', 'is_featured' => false],
            ['name' => 'Xiaomi', 'description' => 'Just for Fans', 'is_featured' => false],
            ['name' => 'Realme', 'description' => 'Dare to Leap', 'is_featured' => false],
            ['name' => 'Baseus', 'description' => 'For Those Who Care', 'is_featured' => false],
            ['name' => 'Ugreen', 'description' => 'Technology Empowers Life', 'is_featured' => false],
            ['name' => 'Spigen', 'description' => 'Protection. Style. Durability.', 'is_featured' => false],
        ];

        $position = 1;
        foreach ($brands as $brandData) {
            Brand::create([
                'name' => $brandData['name'],
                'slug' => Str::slug($brandData['name']),
                'description' => $brandData['description'],
                'is_active' => true,
                'is_featured' => $brandData['is_featured'],
                'position' => $position++,
            ]);
        }
    }
}
