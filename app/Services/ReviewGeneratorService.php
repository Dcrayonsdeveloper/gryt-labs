<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;

class ReviewGeneratorService
{
    public function generateForOrderItem(OrderItem $orderItem): Review
    {
        $product = $orderItem->product;
        $order = $orderItem->order;
        $user = $order->user;
        $rating = $this->pickRating();

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'order_item_id' => $orderItem->id,
            'rating' => $rating,
            'title' => $this->generateTitle($product, $rating),
            'content' => $this->generateContent($product, $rating),
            'pros' => $this->generatePros($product, $rating),
            'cons' => $this->generateCons($product, $rating),
            'is_verified_purchase' => true,
            'is_approved' => true,
            'status' => 'approved',
            'is_generated' => true,
            'generated_from_order_item_id' => $orderItem->id,
            'helpful_count' => rand(0, 8),
            'created_at' => $this->randomTimestampToday(),
        ]);

        return $review;
    }

    public function pickRating(): int
    {
        $rand = mt_rand(1, 100);

        return match (true) {
            $rand <= 1 => 1,    // 1%
            $rand <= 5 => 2,    // 4%
            $rand <= 20 => 3,   // 15%
            $rand <= 55 => 4,   // 35%
            default => 5,       // 45%
        };
    }

    /**
     * Pick a rating targeting a specific average (3.9-4.6 range).
     * Uses weighted distribution tuned to hit the target.
     */
    public function pickRatingForTarget(float $targetAvg = 4.2): int
    {
        $targetAvg = max(3.5, min(4.8, $targetAvg));

        // Dynamically adjust distribution to hit target average
        // Base: 1star=3%, 2star=7%, 3star=18%, 4star=32%, 5star=40% → avg ~3.99
        // Shift weight between 3/4/5 stars based on target
        $shift = ($targetAvg - 4.0) * 20; // -2 to +16

        $w1 = max(1, 3 - (int) ($shift * 0.2));
        $w2 = max(2, 7 - (int) ($shift * 0.5));
        $w3 = max(8, 18 - (int) ($shift * 1.2));
        $w4 = 32 + (int) ($shift * 0.3);
        $w5 = 100 - $w1 - $w2 - $w3 - $w4;
        $w5 = max(20, $w5);

        // Normalize to 100
        $total = $w1 + $w2 + $w3 + $w4 + $w5;
        $rand = mt_rand(1, $total);

        return match (true) {
            $rand <= $w1 => 1,
            $rand <= $w1 + $w2 => 2,
            $rand <= $w1 + $w2 + $w3 => 3,
            $rand <= $w1 + $w2 + $w3 + $w4 => 4,
            default => 5,
        };
    }

    /**
     * Generate a standalone review (not tied to an order item).
     * Used by seed/drip commands.
     */
    public function generateStandaloneReview(Product $product, ?string $guestName = null, ?float $targetAvg = null, ?\Carbon\Carbon $createdAt = null): Review
    {
        $rating = $targetAvg ? $this->pickRatingForTarget($targetAvg) : $this->pickRating();

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => null,
            'guest_name' => $guestName ?? $this->randomIndianName(),
            'guest_email' => 'review' . mt_rand(10000, 99999) . '@customer.' . parse_url(config('app.url', 'localhost'), PHP_URL_HOST),
            'rating' => $rating,
            'title' => $this->generateTitle($product, $rating),
            'content' => $this->generateContent($product, $rating),
            'pros' => $this->generatePros($product, $rating),
            'cons' => $this->generateCons($product, $rating),
            'is_verified_purchase' => (bool) mt_rand(0, 1),
            'is_approved' => true,
            'status' => 'approved',
            'is_generated' => true,
            'helpful_count' => mt_rand(0, 15),
            'unhelpful_count' => mt_rand(0, 2),
            'created_at' => $createdAt ?? now()->subHours(mt_rand(1, 6)),
            'updated_at' => $createdAt ?? now()->subHours(mt_rand(1, 6)),
        ]);

        return $review;
    }

    /**
     * Return a random Indian name.
     */
    public function randomIndianName(): string
    {
        $names = [
            'Priya Sharma', 'Rahul Verma', 'Sneha Patel', 'Amit Kumar', 'Ananya Reddy',
            'Vikram Singh', 'Meera Iyer', 'Deepak Joshi', 'Kavita Nair', 'Arjun Mehta',
            'Pooja Gupta', 'Suresh Rao', 'Neha Mishra', 'Rajesh Tiwari', 'Divya Kapoor',
            'Manish Agarwal', 'Ritu Saxena', 'Sanjay Malhotra', 'Swati Bhatt', 'Arun Pillai',
            'Geeta Deshmukh', 'Kiran Jain', 'Nitin Bansal', 'Pallavi Kulkarni', 'Rohit Pandey',
            'Sunita Chaudhary', 'Vijay Thakur', 'Aarti Srivastava', 'Gaurav Dubey', 'Lakshmi Menon',
            'Harish Yadav', 'Anjali Bose', 'Prakash Naik', 'Rekha Chauhan', 'Manoj Sethi',
            'Shilpa Rathore', 'Tarun Khanna', 'Uma Devi', 'Naveen Gowda', 'Bhavna Shah',
            'Dinesh Choudhury', 'Falguni Parikh', 'Girish Shetty', 'Heena Ansari', 'Isha Tandon',
            'Jagdish Patil', 'Komal Bajaj', 'Lalit Mittal', 'Madhuri Hegde', 'Nilesh Kale',
            'Aditi Mukherjee', 'Bharat Jha', 'Chitra Nambiar', 'Devendra Rawat', 'Ekta Soni',
            'Farhan Qureshi', 'Gagan Arora', 'Hemant Bhardwaj', 'Ira Deshpande', 'Jayesh Solanki',
            'Kamini Prasad', 'Lokesh Shukla', 'Monika Randhawa', 'Nandini Bhat', 'Om Prakash',
            'Padma Subramaniam', 'Qadir Hussain', 'Rashmi Dixit', 'Sahil Chopra', 'Tara Nayak',
            'Urvashi Trivedi', 'Vaibhav Rastogi', 'Wasim Shaikh', 'Yamini Goswami', 'Zubin Contractor',
            'Ashwin Menon', 'Bindu Krishnan', 'Chetan Parmar', 'Deepa Venkatesh', 'Esha Luthra',
            'Gaurav Ahuja', 'Hema Rajan', 'Indira Chakraborty', 'Jatin Grover', 'Kriti Mathur',
            'Lavanya Hegde', 'Mohit Oberoi', 'Nisha Dalal', 'Omkar Deshpande', 'Preeti Walia',
            'Ravi Shankar', 'Shalini Batra', 'Tushar Kamat', 'Urmi Dasgupta', 'Vivek Anand',
            'Alok Tiwari', 'Bina Kothari', 'Chirag Vyas', 'Damini Kashyap', 'Eknath Shinde',
        ];

        return $names[array_rand($names)];
    }

    public function generateTitle(Product $product, int $rating): string
    {
        $productType = $this->detectProductType($product);

        $templates = $this->getTitleTemplates($rating);
        $template = $templates[array_rand($templates)];

        return $this->fillPlaceholders($template, $product, $productType);
    }

    public function generateContent(Product $product, int $rating): string
    {
        $productType = $this->detectProductType($product);
        $timeframe = $this->randomTimeframe();

        $templates = $this->getContentTemplates($rating);
        $template = $templates[array_rand($templates)];

        return $this->fillPlaceholders($template, $product, $productType, $timeframe);
    }

    public function generatePros(Product $product, int $rating): array
    {
        $productType = $this->detectProductType($product);
        $pool = $this->getProsPool($productType);
        $count = match (true) {
            $rating >= 5 => rand(3, 4),
            $rating >= 4 => rand(2, 3),
            $rating >= 3 => rand(1, 2),
            default => rand(0, 1),
        };

        shuffle($pool);
        return array_slice($pool, 0, $count);
    }

    public function generateCons(Product $product, int $rating): array
    {
        if ($rating >= 5 && rand(1, 3) > 1) {
            return [];
        }

        $pool = $this->getConsPool();
        $count = match (true) {
            $rating <= 2 => rand(2, 3),
            $rating <= 3 => rand(1, 2),
            $rating <= 4 => rand(0, 1),
            default => 0,
        };

        shuffle($pool);
        return array_slice($pool, 0, $count);
    }

    private function randomTimestampToday(): \Carbon\Carbon
    {
        return now()->setTime(rand(6, 22), rand(0, 59), rand(0, 59));
    }

    private function randomTimeframe(): string
    {
        $frames = ['a few days', 'a week', 'about two weeks', 'a couple of weeks', 'some time now', 'a month'];
        return $frames[array_rand($frames)];
    }

    /**
     * Detect product type from product name + category name for template matching.
     */
    private function detectProductType(Product $product): string
    {
        $name = strtolower($product->name);
        $cat = strtolower($product->category?->name ?? '');
        $text = $name . ' ' . $cat;

        // Health & Supplements (must be before other categories to avoid false matches)
        if (preg_match('/supplement|ayurved|herbal|capsule|tablet|vitamin|mineral|health|detox|immunity|liver|kidney|heart|stamina|energy|spirulina|ashwagandha|moringa|turmeric|shilajit|triphala|amla|giloy|neem|tulsi|coq10|omega|probiotic|collagen|biotin|multivitamin|antioxidant|anti.?aging|skin.?sculpt|skin.?radiance|booster|wellness|combo|bliss/', $text)) return 'health supplement';

        // Audio
        if (preg_match('/earphone|earbuds|earbud|tws|headphone|headset|neckband|airpod/', $text)) return 'earphones';
        if (preg_match('/speaker|soundbar|sound.?bar/', $text)) return 'speaker';

        // Charging & Power
        if (preg_match('/charger|charging.?adapter/', $text)) return 'charger';
        if (preg_match('/cable|cord|usb|lightning|type.?c/', $text)) return 'cable';
        if (preg_match('/power.?bank|portable.?charger/', $text)) return 'power bank';

        // Phone protection
        if (preg_match('/case|cover|back.?cover|bumper|flip.?cover/', $text)) return 'phone case';
        if (preg_match('/tempered|screen.?guard|screen.?protector/', $text)) return 'screen protector';

        // Wearables
        if (preg_match('/smart.?watch|fitness.?band|smart.?bracelet/', $text)) return 'smartwatch';
        if (preg_match('/watch.?strap|watch.?band/', $text)) return 'watch strap';

        // Mounts & stands
        if (preg_match('/selfie|tripod|ring.?light/', $text)) return 'selfie stick';
        if (preg_match('/car.?mount|car.?holder|phone.?holder|dash/', $text)) return 'car mount';
        if (preg_match('/mount|stand|holder|dock/', $text)) return 'stand';

        // Gaming
        if (preg_match('/gaming|controller|trigger|gamepad/', $text)) return 'gaming accessory';
        if (preg_match('/cooling.?fan|phone.?cooler/', $text)) return 'cooling fan';

        // Kitchen & Dining
        if (preg_match('/chopper|blender|grinder|mixer|processor|juicer/', $text)) return 'kitchen appliance';
        if (preg_match('/bottle|flask|tumbler|sipper/', $text)) return 'bottle';
        if (preg_match('/container|lunch|tiffin|storage.?box/', $text)) return 'container';
        if (preg_match('/kitchen|utensil|cook|pan|pot|knife|cutting|peeler/', $text)) return 'kitchen tool';
        if (preg_match('/mug|cup|glass|plate|bowl/', $text)) return 'kitchenware';

        // Household Supplies
        if (preg_match('/hook|hanger|adhesive|rack|shelf|organiz/', $text)) return 'organizer';
        if (preg_match('/clean|brush|mop|broom|duster|wipe/', $text)) return 'cleaning tool';
        if (preg_match('/soap|dispenser|holder|bathroom|shower|toilet/', $text)) return 'bathroom accessory';
        if (preg_match('/cloth.?line|laundry|dryer|iron/', $text)) return 'laundry tool';
        if (preg_match('/tape|glue|seal|repair|tool/', $text)) return 'household tool';
        if (preg_match('/bin|trash|dustbin|garbage/', $text)) return 'dustbin';
        if (preg_match('/mat|rug|carpet|doormat/', $text)) return 'mat';

        // Lighting
        if (preg_match('/lamp|light|led|bulb|lantern|torch|flashlight/', $text)) return 'light';
        if (preg_match('/solar/', $text)) return 'solar light';
        if (preg_match('/fairy|string.?light|decorat/', $text)) return 'decorative light';

        // Personal Care
        if (preg_match('/trimmer|shaver|razor|grooming/', $text)) return 'grooming tool';
        if (preg_match('/massag|body.?care|scrub|loofah/', $text)) return 'personal care';
        if (preg_match('/sunglass|eyewear|glass/', $text)) return 'sunglasses';
        if (preg_match('/hair.?dryer|straighten|curl/', $text)) return 'hair tool';
        if (preg_match('/makeup|cosmetic|beauty|mirror/', $text)) return 'beauty product';
        if (preg_match('/toothbrush|dental|oral/', $text)) return 'dental care';

        // Household Appliances
        if (preg_match('/fan|cooler|heater|humidifier|purifier|air/', $text)) return 'appliance';
        if (preg_match('/vacuum|cleaner/', $text)) return 'vacuum cleaner';
        if (preg_match('/sewing|machine/', $text)) return 'machine';

        // Bags
        if (preg_match('/backpack|rucksack/', $text)) return 'backpack';
        if (preg_match('/sling|crossbody|shoulder/', $text)) return 'sling bag';
        if (preg_match('/duffel|duffle|travel.?bag|luggage/', $text)) return 'travel bag';
        if (preg_match('/pouch|wallet|purse/', $text)) return 'pouch';
        if (preg_match('/bag/', $text)) return 'bag';

        // Toys & Kids
        if (preg_match('/toy|puzzle|game|building.?block|lego/', $text)) return 'toy';
        if (preg_match('/color|drawing|art|sketch|paint|craft/', $text)) return 'art set';
        if (preg_match('/book|magic.?book|story/', $text)) return 'book';

        // Stationery & Office
        if (preg_match('/pen|pencil|stationery|marker|highlighter|note/', $text)) return 'stationery';
        if (preg_match('/gift.?set|gift/', $text)) return 'gift set';

        // Vehicle
        if (preg_match('/car|vehicle|auto|bike|motor/', $text)) return 'car accessory';
        if (preg_match('/fastag/', $text)) return 'FASTag holder';

        // Fitness
        if (preg_match('/push.?up|abs|roller|skip|jump|yoga|fitness|exercise|gym/', $text)) return 'fitness gear';

        // Clothing accessories
        if (preg_match('/cap|beanie|muffler|scarf|glove|sock/', $text)) return 'winter wear';
        if (preg_match('/belt|buckle/', $text)) return 'belt';

        // Memory & Connectivity
        if (preg_match('/memory|sd.?card|otg|hub|adapter|hdmi|card.?reader/', $text)) return 'adapter';
        if (preg_match('/ring.?holder|pop.?socket|grip/', $text)) return 'phone grip';

        return 'product';
    }

    private function fillPlaceholders(string $template, Product $product, string $productType, string $timeframe = ''): string
    {
        $shortName = strlen($product->name) > 45 ? substr($product->name, 0, 42) . '...' : $product->name;
        $storeName = \App\Models\Setting::get('store_name', config('app.name', 'our store'));

        return str_replace(
            ['{product_name}', '{product_type}', '{timeframe}', '{brand}', '{store_name}'],
            [$shortName, $productType, $timeframe, $product->brand?->name ?? 'this brand', $storeName],
            $template
        );
    }

    // ─── Title Templates ─────────────────────────────────────

    private function getTitleTemplates(int $rating): array
    {
        return match (true) {
            $rating >= 5 => [
                'Absolutely love this {product_type}!',
                'Best {product_type} at this price',
                'Exceeded my expectations',
                'Best purchase this month!',
                'Amazing quality {product_type}',
                'Worth every rupee',
                'Highly recommend this',
                'Fantastic {product_type}!',
                'So happy with this purchase',
                'Great value for money',
                'Very pleased with this {product_type}',
                'Wonderful quality and build',
                'Outstanding {product_type}!',
                'Must buy! Really impressed',
                'Exactly what I was looking for',
                'Superb quality {product_type}',
                'Delighted with this purchase',
                'Simply the best {product_type}',
                'Brilliant quality and finish',
                '5 stars all the way!',
                'Very happy customer!',
                'Top quality {product_type}',
                'Impressed with the quality',
                'Perfect for daily use',
                'Works flawlessly!',
                'Premium feel at budget price',
                'Better than expensive alternatives',
                'Using it daily, zero complaints',
                '{store_name} never disappoints!',
                'My go-to store for such products',
            ],
            $rating >= 4 => [
                'Good quality {product_type}',
                'Happy with this purchase',
                'Nice {product_type}, minor things',
                'Pretty good for the price',
                'Solid {product_type}, would recommend',
                'Good value, decent quality',
                'Satisfied with this {product_type}',
                'Mostly impressed',
                'Nice design and build',
                'Good {product_type}, works well',
                'Decent quality, looks great',
                'Happy overall with this',
                'Good product, quick delivery',
                'A solid purchase overall',
                'Quite nice {product_type}',
                'Pleased with the quality',
                'Good for everyday use',
                'Better than expected',
                'Would buy again',
                'Good performance for the price',
                'Reliable {product_type}',
                'Does the job well',
                'Decent {product_type} from {store_name}',
                'Recommended for the price',
            ],
            $rating >= 3 => [
                'Decent but could be better',
                'OK for the price',
                'Average {product_type}',
                'It does the job',
                'Mixed feelings about this',
                'Some pros and cons',
                'Fair quality for the price',
                'Acceptable {product_type}',
                'Not bad, not great',
                'Meets basic expectations',
                'Could use improvements',
                'Average quality {product_type}',
                'Somewhat satisfied',
                'Decent product overall',
                'Works fine, nothing special',
            ],
            $rating >= 2 => [
                'Disappointed with the quality',
                'Not as expected',
                'Would not buy again',
                'Below average {product_type}',
                'Not worth the price',
                'Quality could be much better',
                'Expected better',
                'Underwhelming {product_type}',
                'Not satisfied',
                'Did not meet expectations',
            ],
            default => [
                'Very disappointing',
                'Not recommended',
                'Poor quality {product_type}',
                'Waste of money',
                'Would not recommend',
                'Extremely disappointed',
                'Bad quality overall',
            ],
        };
    }

    // ─── Content Templates ───────────────────────────────────

    private function getContentTemplates(int $rating): array
    {
        return match (true) {
            $rating >= 5 => [
                // Short reviews (1-2 lines)
                'Excellent product! Works perfectly.',
                'Love it! Best {product_type} at this price.',
                'Superb quality. Highly recommend.',
                'Perfect. Exactly what I needed.',
                'Amazing {product_type}! Worth every penny.',
                'Brilliant. No complaints at all.',
                'Outstanding quality for the price!',
                'Top notch {product_type}. Very happy.',
                'Wonderful purchase. Works like a charm!',
                'Absolutely fantastic {product_type}.',
                'Great buy! Using daily without any issues.',
                'Best value {product_type} out there.',
                'Super happy with this. Will order again!',
                'Five stars all the way. Premium feel.',
                'Really impressed. Quality exceeds the price.',

                // Medium reviews (3-4 lines)
                'Bought this {product_type} and it turned out to be a great purchase. The build quality is excellent and it feels premium. Really happy with how it works.',
                'I have been looking for a good {product_type} for {timeframe} and finally found this one. Works perfectly and the quality is top notch for the price.',
                'This {product_type} is amazing! I have been using it for {timeframe} now and it still works like new. Definitely worth the money.',
                'Excellent product! The {product_type} works perfectly and feels very well built. Will definitely order more from {store_name}.',
                'Very impressed with this {product_type}. It is exactly as shown in the pictures. Fast shipping too! No complaints at all.',
                'This is my third purchase from {store_name} and every time the quality has been consistent. This {product_type} is no exception.',
                'I was skeptical about ordering online but this {product_type} changed my mind. The quality is outstanding for this price range.',
                'Just received this {product_type} and I am thoroughly impressed. Would definitely order from {store_name} again.',
                'Fantastic quality for the price! The {product_type} is well-made and performs great. Very happy with this find.',
                'This {product_type} is honestly one of the best I have bought in this price range. Great build and works perfectly.',
                'Was looking for something reliable and this {product_type} did not disappoint. Great quality and great price.',
                'Absolutely love this {product_type}! I use it every day and it has not let me down once. The quality feels premium.',
                'Using this {product_type} for {timeframe} now. No issues whatsoever. Everything is top notch. Best value for money.',
                'Ordered this and was so impressed that I bought another one as a gift. The {product_type} performs brilliantly.',
                'The {product_type} arrived well packaged and works perfectly out of the box. Performance has been flawless for {timeframe} now.',
                'Hands down the best {product_type} I have used at this price point. Will recommend to everyone.',
                'Got this {product_type} on a friend\'s recommendation and I am not disappointed. Works exactly as described. Very happy with the purchase.',
                'Received it yesterday and already loving it. Build quality of this {product_type} is seriously impressive for the price.',
                'My wife also liked it so much she asked me to order one more. Great quality {product_type} from {store_name}.',
                'Replaced my old one with this {product_type} and the difference is night and day. So much better. Highly recommend.',

                // Long reviews (5+ lines)
                'I compared this with several other options and this {product_type} stood out for the quality. Been using it for {timeframe} and still works amazing. The build feels solid and premium. Would definitely recommend to friends and family.',
                'Really pleased with this purchase. The {product_type} is well-built and performs flawlessly. Good experience shopping at {store_name}. The delivery was quick and the packaging was nice. I have already recommended this to my colleagues.',
                'This {product_type} from {store_name} exceeded my expectations completely. The material and build quality are really good. Have been using it for {timeframe} and it is holding up great. I initially had doubts because of the low price but this is genuinely premium quality. Very impressed.',
                'Let me share my honest experience. I ordered this {product_type} after reading other reviews and I am glad I did. The quality is exactly what they describe. I have been using it daily for {timeframe} now and there are zero issues. The build feels sturdy, the performance is consistent, and the design looks great. This is the kind of product that makes you trust a brand. Will be ordering more from {store_name} for sure.',
                'I have bought many {product_type} products over the years from different brands and this one from {brand} stands out. The attention to detail is remarkable for this price point. I use it every single day and it has held up perfectly. The packaging was also neat and professional. If you are on the fence about buying this, just go for it. You will not regret it. Already gifted one to my brother.',
                'Was hesitant initially because the price seemed too good for the quality promised. But after using this {product_type} for {timeframe}, I can confirm it is the real deal. The build quality matches products that cost 2-3x more. Performance has been flawless. No issues with durability either. This has become my go-to recommendation whenever someone asks me for a good {product_type}.',
                'I bought this as a gift for my father and he absolutely loves it. The {product_type} looks premium, works great, and the packaging made it feel like a proper gift. Dad has been using it for {timeframe} now and keeps telling me how good it is. {store_name} has earned a loyal customer. Already planning to buy more products from here.',
                'My experience with this {product_type} has been nothing short of excellent. From ordering to delivery to actual usage, everything was smooth. The product looks exactly like the photos, which is rare these days with online shopping. Have been using it for {timeframe} and it works just as well as day one. The quality control is clearly good. Highly recommended for anyone looking for a reliable {product_type} without breaking the bank.',
                'I ordered two of these {product_type} - one for myself and one for my friend. We both are extremely happy with the purchase. The build quality is solid, the performance is consistent, and the design is clean and modern. For the price, you simply cannot find a better {product_type} anywhere. {store_name} has really nailed it with this product. Five stars without any hesitation.',
                'After trying multiple {product_type} products that disappointed me, I finally found this gem. The quality is top notch. Been using it for {timeframe} without a single issue. What impressed me most is the attention to detail in the build. Everything feels well thought out and premium. If {store_name} keeps making products like this, they will become my default shopping destination. Already eyeing a few more things on their site.',
            ],
            $rating >= 4 => [
                // Short reviews
                'Good product. Works well for the price.',
                'Nice quality. Minor issues but overall happy.',
                'Decent {product_type}. Would recommend.',
                'Good value. Does the job well.',
                'Solid purchase. Mostly satisfied.',
                'Pretty nice {product_type}. Happy overall.',
                'Good buy. Performs as expected.',
                'Works well. Packaging could be better.',
                'Nice {product_type}. Quick delivery too.',
                'Satisfied. Good product for the money.',

                // Medium reviews
                'Good {product_type} overall. It works well and the quality is decent for the price. Only minor thing is the build could be slightly sturdier.',
                'Bought this and it is a solid purchase. Nice build and good performance. Could be slightly better in terms of packaging.',
                'Happy with this {product_type}. Been using it for {timeframe}. The quality is good though I wish the finish was a bit more premium.',
                'Pretty good {product_type} for the price point. Works exactly as described. Delivery was prompt.',
                'Nice {product_type}! Works well for daily use. The quality is better than what I expected at this price range.',
                'Decent purchase. I use this {product_type} daily. It is well-made and performs reliably. Minor issue but overall satisfied.',
                'This {product_type} is quite nice. I use it regularly. Good quality and value for money. Shipping was fast.',
                'I like this {product_type} a lot. The build feels good and it works exactly like the pictures show.',
                'Solid {product_type} from {store_name}. Been using it for {timeframe}. Good quality but delivery took a bit longer than expected.',
                'Good buy! The {product_type} works well and looks great. Would recommend with minor reservations.',
                'Overall a good {product_type}. Quality is good for the price. Just needs slightly better packaging.',
                'The {product_type} from {brand} is quite nice. Performs well for daily use. Just a couple of minor issues keeping it from 5 stars.',
                'Good product for the price. The {product_type} has been working great for {timeframe}. Build quality is solid.',
                'Satisfied with this {product_type}. It does what it promises. Minor improvements would make it perfect.',
                'Nice {product_type} from {store_name}. Performance is good and the design looks nice. Value for money. Would buy again.',
                'Received it in good condition. The {product_type} works well and looks nice. A small QC issue but nothing major. Four stars.',
                'Used it for {timeframe} now. Good {product_type} overall. My only gripe is the colour is slightly different from photos.',
                'Gifted this to a friend and they liked it. The {product_type} quality is good. Delivery was smooth. Would have been 5 stars if packaging was better.',

                // Long reviews
                'I have been using this {product_type} for {timeframe} and it has been a good experience overall. The build quality is nice and it performs well for daily use. The only reason I am not giving 5 stars is because the packaging felt a bit basic and the finish could be slightly better. But for the price, it is a very fair deal. Would recommend to anyone looking for a budget-friendly option.',
                'Let me be honest - this {product_type} is really good but not perfect. The quality is above average for the price and it works well. I have been using it for {timeframe} and no major complaints. The design looks great. However, I noticed a few minor quality issues that kept it from being a 5-star product for me. Still, I would buy from {store_name} again.',
                'This {product_type} does what it is supposed to do and does it well. I bought it after comparing with a few other options and this had the best value for money. Been using it for {timeframe} now. Works reliably. The materials feel decent. My only complaint is that the colour is slightly different from the product photos but that is a minor thing. Overall a solid 4-star product.',
            ],
            $rating >= 3 => [
                // Short reviews
                'Okay product. Nothing special.',
                'Average quality. Does the basic job.',
                'It is alright for the price.',
                'Decent. Not great, not terrible.',
                'Average {product_type}. Expected more.',
                'Fair enough. Serves its purpose.',
                'Mediocre quality. Acceptable for the price.',
                'Just okay. Could be better.',

                // Medium reviews
                'The {product_type} is okay. Not bad but not exceptional either. It works but the build quality could be better for what I paid.',
                'Average {product_type}. It serves its purpose. The design is fine but the materials feel a bit cheap.',
                'Mixed feelings about this one. Looks nice but the quality does not feel premium. Fair for the price.',
                'It is alright. The {product_type} works but the performance is inconsistent sometimes. Okay product overall.',
                'Decent {product_type} for basic use. Had it for {timeframe} now. Acceptable for the price.',
                'Got this and it is average quality. The product photos look better than reality. Not bad but I expected more.',
                'The {product_type} is fine for everyday use. Quality is standard, nothing to write home about.',
                'OK product. I use it but it is not great. The build is acceptable and the performance is basic.',
                'Average purchase. The {product_type} looks decent but the quality is just okay. Fair value.',
                'Not great, not terrible. This {product_type} does the job. Some quality issues but still usable.',
                'Product is fine for casual use. The {product_type} works as described. Build quality is average. Nothing to complain about but nothing exciting either.',
                'Been using it for {timeframe}. The {product_type} is average. No issues so far but the quality does not impress me. Fair for the price I guess.',

                // Long reviews
                'I wanted to like this {product_type} more than I do. The design looks great in photos but in person the build quality is just average. It works fine for basic use but if you are expecting premium quality, you might be disappointed. I have been using it for {timeframe} and it does the job but I can already see some wear. For the price it is acceptable but I would not rave about it. Three stars feels fair.',
                'Mixed review here. The {product_type} has some good things going for it - the design is nice and the basic functionality works. But the materials feel a bit cheap and the overall quality is just okay. I have seen both better and worse at this price point. If your expectations are moderate, you will be fine with it. Not bad, not amazing.',
            ],
            $rating >= 2 => [
                // Short reviews
                'Disappointed. Quality is poor.',
                'Not worth the price. Expected better.',
                'Below average. Would not buy again.',
                'Poor quality {product_type}.',

                // Medium reviews
                'Honestly expected better. The {product_type} quality is below what I thought I would get. The materials feel cheap.',
                'The {product_type} looked much better in the photos. In person, the quality is disappointing.',
                'Not happy with this purchase. The build quality is poor. Would not recommend at this price.',
                'Below average {product_type}. Started having issues within a few days. Quality control seems lacking.',
                'Disappointed with the quality of this {product_type}. For the price paid, I expected much better.',
                'Not worth it. The {product_type} quality is poor and does not match the product images at all.',
                'Received the {product_type} and was disappointed from the start. The build feels flimsy and cheap. Product photos are misleading.',
                'Used it for barely {timeframe} and already seeing problems. The quality is nowhere near what was advertised.',
            ],
            default => [
                // Short
                'Terrible. Waste of money.',
                'Very poor quality. Do not buy.',

                // Medium
                'Very poor quality {product_type}. The build feels terrible and it stopped working within days. Very disappointed.',
                'Terrible purchase. The {product_type} arrived with defects and barely works. Complete waste of money.',
                'Worst {product_type} I have bought. Cheap materials, poor build, and nothing like the pictures.',
                'Extremely disappointed with this {product_type}. Stopped working after {timeframe}. Not worth even half the price. Avoid.',
            ],
        };
    }

    // ─── Pros/Cons Pools ─────────────────────────────────────

    private function getProsPool(string $productType): array
    {
        $general = [
            'Good build quality',
            'Fast delivery',
            'Nice packaging',
            'Value for money',
            'True to description',
            'Looks premium',
            'Easy to use',
            'Durable build',
            'Great for daily use',
        ];

        return match ($productType) {
            'health supplement' => array_merge($general, [
                'Noticeable energy boost', 'Improved digestion', 'Better sleep quality',
                'Natural ingredients', 'No side effects', 'Lab-tested and certified',
                'Easy to swallow capsules', 'Visible results in 2-3 weeks', 'Improved skin glow',
                'Better immunity', 'Feels more energetic daily', 'Good for overall health',
                'Ayurvedic and safe', 'Doctor recommended', 'No artificial additives',
                'Improved stamina', 'Better hair and nails', 'Organic ingredients',
            ]),
            'earphones' => array_merge($general, [
                'Clear sound quality', 'Deep bass', 'Comfortable fit', 'Good noise cancellation',
                'Long battery life', 'Fast charging', 'No connectivity drops', 'Good mic for calls',
                'Lightweight and compact', 'Low latency gaming mode',
            ]),
            'speaker' => array_merge($general, [
                'Loud and clear sound', 'Punchy bass', 'Long battery life', 'Portable and lightweight',
                'Water resistant', 'Pairs quickly via Bluetooth', 'Good range', 'TWS pairing works well',
                'Great for outdoor use', 'Compact size',
            ]),
            'charger', 'cable' => array_merge($general, [
                'Charges fast', 'Sturdy build', 'No overheating', 'Compact design',
                'Works with all devices', 'Braided cable lasts long', 'USB-C compatible',
                'Good cable length', 'BIS certified', 'Multiple port support',
            ]),
            'power bank' => array_merge($general, [
                'Fast charging output', 'Charges phone fully 2-3 times', 'Slim and portable',
                'LED indicator helpful', 'USB-C input charges quickly', 'Great for travel',
                'Charges multiple devices', 'Solid build', 'Overcharge protection', 'Good capacity',
            ]),
            'phone case', 'screen protector' => array_merge($general, [
                'Perfect fit for phone model', 'Good drop protection', 'Buttons are clickable',
                'Camera cutout is precise', 'Does not add much bulk', 'No yellowing so far',
                'Anti-fingerprint', 'Easy to install', 'Raised edges protect screen', 'Looks great',
            ]),
            'smartwatch', 'watch strap' => array_merge($general, [
                'Bright display', 'Bluetooth calling works well', 'Accurate heart rate tracking',
                'Battery lasts 5-6 days', 'Comfortable to wear all day', 'Many watch face options',
                'SpO2 monitoring is useful', 'Quick Bluetooth pairing', 'Water resistant',
                'Sleep tracking is accurate',
            ]),
            'gaming accessory', 'cooling fan' => array_merge($general, [
                'Improved my gaming', 'Responsive trigger buttons', 'Phone stays cool',
                'Low latency', 'Comfortable grip', 'Easy to attach', 'Works great for BGMI',
                'Compact and portable', 'Noticeable difference in gameplay',
            ]),
            'car mount', 'selfie stick', 'stand', 'phone mount', 'phone grip' => array_merge($general, [
                'Strong grip', '360 degree rotation', 'Easy to install', 'Sturdy on desk',
                'Adjustable angle', 'Compact when folded', 'Works with all phone sizes',
                'Good suction', 'Perfect for video calls',
            ]),
            'kitchen appliance', 'kitchen tool', 'kitchenware' => array_merge($general, [
                'Powerful motor', 'Easy to clean', 'Compact size fits kitchen', 'Sharp blades',
                'Multiple speed settings', 'Saves cooking time', 'Good capacity',
                'Sturdy handle', 'Food-grade material', 'Easy to operate',
            ]),
            'bottle', 'container' => array_merge($general, [
                'Leak-proof seal', 'Keeps water cold', 'Easy to carry', 'BPA-free material',
                'Good capacity', 'Does not retain smell', 'Dishwasher safe',
                'Sturdy lid', 'Perfect size for travel',
            ]),
            'organizer', 'cleaning tool', 'bathroom accessory', 'laundry tool', 'household tool', 'dustbin', 'mat' => array_merge($general, [
                'Strong adhesive', 'Holds weight well', 'Easy to install', 'Does not damage walls',
                'Saves space', 'Multi-purpose use', 'Good material quality',
                'Waterproof', 'Easy to clean', 'Sturdy construction',
            ]),
            'light', 'solar light', 'decorative light' => array_merge($general, [
                'Bright LED light', 'Long battery life', 'Easy to install', 'Good brightness levels',
                'Auto on/off works well', 'Motion sensor is accurate', 'Weatherproof',
                'Looks beautiful at night', 'Energy efficient', 'Remote control is convenient',
            ]),
            'grooming tool', 'personal care', 'hair tool', 'beauty product', 'dental care' => array_merge($general, [
                'Comfortable to use', 'Good battery backup', 'Skin-friendly', 'Multiple attachments',
                'Easy to clean', 'Quiet operation', 'Rechargeable via USB',
                'Compact for travel', 'Good grip', 'Works as advertised',
            ]),
            'sunglasses' => array_merge($general, [
                'UV protection', 'Lightweight frame', 'Comfortable fit', 'Stylish design',
                'Scratch resistant lens', 'Good for driving', 'Comes with case',
            ]),
            'appliance', 'vacuum cleaner', 'machine' => array_merge($general, [
                'Powerful performance', 'Quiet operation', 'Easy to clean', 'Good suction',
                'Compact size', 'Multiple speed settings', 'Energy efficient',
                'Portable design', 'Long cord length', 'Sturdy build',
            ]),
            'backpack', 'sling bag', 'travel bag', 'bag', 'pouch' => array_merge($general, [
                'Spacious compartments', 'Sturdy zippers', 'Lightweight yet strong',
                'Water-resistant material', 'Comfortable straps', 'Attractive design',
                'Multiple pockets', 'Good stitching', 'Perfect size for daily use',
            ]),
            'toy', 'art set', 'book' => array_merge($general, [
                'Kids love it', 'Safe materials', 'Keeps kids engaged', 'Educational value',
                'Bright colours', 'Age-appropriate', 'Sturdy build', 'Non-toxic',
                'Great gift option', 'Promotes creativity',
            ]),
            'stationery', 'gift set' => array_merge($general, [
                'Good variety of items', 'Smooth writing', 'Attractive packaging',
                'Great for gifting', 'Good ink quality', 'Durable',
                'Kids loved it', 'Premium feel',
            ]),
            'car accessory', 'FASTag holder' => array_merge($general, [
                'Easy to install', 'Strong adhesive', 'Does not fall off',
                'Perfect fit', 'Good build for car use', 'Looks neat',
                'Weather resistant', 'Easy to clean',
            ]),
            'fitness gear', 'winter wear', 'belt' => array_merge($general, [
                'Comfortable to use', 'Durable material', 'Good grip',
                'Perfect for workouts', 'Lightweight', 'No slipping',
                'Good build quality', 'Fits well',
            ]),
            'adapter' => array_merge($general, [
                'Works with all devices', 'Fast data transfer', 'Compact design',
                'Plug and play', 'Reliable connection', 'Good build',
            ]),
            default => $general,
        };
    }

    private function getConsPool(): array
    {
        return [
            'Packaging could be better',
            'Delivery took a bit longer',
            'Colour slightly different from photo',
            'Instructions not very clear',
            'Limited colour choices',
            'Price is a touch high',
            'Could include better accessories',
            'Build feels slightly plasticky',
            'Could be a bit sturdier',
            'Wish it came with a carry pouch',
            'Gets warm with extended use',
            'Slightly heavier than expected',
            'Would prefer better packaging',
            'Size is slightly different from expected',
        ];
    }
}
