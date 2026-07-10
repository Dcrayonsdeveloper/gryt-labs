<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Seeds synthetic product reviews for local UI development.
 *
 * These reviews are NOT real customer feedback. They exist so the review UI
 * (star widgets, rating breakdown, sorting, pagination) can be built and
 * checked against realistic volume. Every row is written with
 * `is_generated = true` and `is_verified_purchase = false`.
 *
 * Publishing synthetic reviews as genuine customer reviews is prohibited
 * (CCPA / BIS IS 19000:2022 in India, FTC 2024 fake-review rule in the US),
 * so this seeder refuses to run outside a local/testing environment.
 */
class DemoReviewSeeder extends Seeder
{
    /** Ratings are integers (1-5). Weighted so each product averages ~4.5-4.8. */
    private const RATING_WEIGHTS = [5 => 62, 4 => 30, 3 => 8];

    private const NAMES = [
        'Aarav Mehta', 'Priya Nair', 'Rohan Deshpande', 'Sneha Iyer', 'Karan Malhotra',
        'Ananya Reddy', 'Vikram Singh', 'Meera Joshi', 'Aditya Rao', 'Ishita Bose',
        'Nikhil Kulkarni', 'Divya Menon', 'Siddharth Ghosh', 'Pooja Bhatt', 'Arjun Pillai',
        'Neha Chauhan', 'Rahul Verma', 'Kavya Krishnan', 'Manish Agarwal', 'Shreya Dutta',
        'Harsh Vardhan', 'Ritika Shah', 'Sameer Qureshi', 'Tanvi Kapoor', 'Yash Thakur',
        'Lakshmi Subramanian', 'Abhinav Chatterjee', 'Nandini Rane', 'Varun Sethi', 'Ira Banerjee',
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'DemoReviewSeeder is for local/testing only. Synthetic reviews must never '
                . 'be published as genuine customer reviews on a production storefront.'
            );
        }

        $products = Product::whereNull('deleted_at')->where('is_active', true)->get();

        if ($products->isEmpty()) {
            $this->command->warn('No active products found; nothing to seed.');
            return;
        }

        foreach ($products as $product) {
            $pool = $this->poolFor($product->name);
            $count = random_int(20, min(30, count($pool)));

            // Unique content per product: never reuse a comment.
            $chosen = collect($pool)->shuffle()->take($count);
            $names = collect(self::NAMES)->shuffle()->take($count)->values();

            $rows = [];
            foreach ($chosen->values() as $i => $entry) {
                $rows[] = [
                    'product_id' => $product->id,
                    'user_id' => null,
                    'guest_name' => $names[$i],
                    'rating' => $entry['rating'] ?? $this->weightedRating(),
                    'title' => $entry['title'],
                    'content' => $entry['body'],
                    'is_verified_purchase' => false, // nobody bought these
                    'is_approved' => true,           // visible so the UI can be designed
                    'status' => 'approved',
                    'is_generated' => true,          // clearly marked synthetic
                    'helpful_count' => random_int(0, 24),
                    'unhelpful_count' => random_int(0, 3),
                    'created_at' => now()->subDays(random_int(1, 365))->subHours(random_int(0, 23)),
                    'updated_at' => now(),
                ];
            }

            DB::table('reviews')->insert($rows);

            // Rows are inserted directly, so the denormalised products.rating and
            // products.review_count columns the storefront reads are still stale.
            $product->updateRating();

            $this->command->info(
                "  {$product->name}: {$product->review_count} reviews "
                . '(avg ' . number_format((float) $product->rating, 2) . '★)'
            );
        }
    }

    private function weightedRating(): int
    {
        $roll = random_int(1, array_sum(self::RATING_WEIGHTS));
        foreach (self::RATING_WEIGHTS as $rating => $weight) {
            if (($roll -= $weight) <= 0) {
                return $rating;
            }
        }
        return 5;
    }

    /** Pick the review pool matching the product, by keyword. */
    private function poolFor(string $name): array
    {
        $n = strtolower($name);

        return match (true) {
            str_contains($n, 'whey') || str_contains($n, 'protein') => $this->whey(),
            str_contains($n, 'creatine') => $this->creatine(),
            str_contains($n, 'pre workout') || str_contains($n, 'pre-workout') => $this->preWorkout(),
            str_contains($n, 'ashwagandha') => $this->ashwagandha(),
            str_contains($n, 'flaxseed') || str_contains($n, 'omega') => $this->omega(),
            str_contains($n, 'shaker') || str_contains($n, 'bottle') => $this->shaker(),
            default => $this->generic(),
        };
    }

    // ---------------------------------------------------------------------
    // Review pools. Written per product so nothing reads as boilerplate.
    // Deliberately avoid medical/health-outcome claims.
    // ---------------------------------------------------------------------

    private function whey(): array
    {
        return [
            ['rating' => 5, 'title' => 'Mixes clean, no clumps', 'body' => 'Been through three tubs now. It dissolves in a shaker with plain water in about ten seconds, no lumps stuck at the bottom like the last brand I used. Vanilla is mild, not the sickly sweet kind. I have it after training and it sits fine.'],
            ['rating' => 5, 'title' => 'Vanilla actually tastes like vanilla', 'body' => 'Most vanilla proteins taste like sweetened chalk. This one is closer to a light milkshake if you use milk. With water it is thinner but still drinkable. Scoop was buried in the powder, took a minute to find.'],
            ['rating' => 4, 'title' => 'Good protein, packaging could be better', 'body' => 'No complaints on the powder itself. The tub arrived with the seal intact but the outer box was dented on one corner. Courier issue probably, not the brand. Powder was fine.'],
            ['rating' => 5, 'title' => 'Value for the serving size', 'body' => 'Worked out the cost per serving and it comes to less than most imported brands doing the same grams of protein. That was the deciding factor for me. Repurchasing.'],
            ['rating' => 4, 'title' => 'Slightly sweet for my taste', 'body' => 'Quality seems solid and it mixes well. Only thing is I find it a touch sweeter than I would like when I use milk. With water it is about right. Minor preference thing, not a fault.'],
            ['rating' => 5, 'title' => 'Two weeks in, no bloating', 'body' => 'I usually react badly to concentrates and end up feeling heavy. This one has been easy on me so far. Taking one scoop post workout and one on rest days.'],
            ['rating' => 5, 'title' => 'Delivery was quick', 'body' => 'Ordered on a Sunday, had it Tuesday morning in Patna. Sealed tub, scoop inside, expiry over a year out. No complaints at all.'],
            ['rating' => 3, 'title' => 'Fine, but the scoop is awkward', 'body' => 'The powder is good and I have no issue with the taste. My gripe is the scoop, it is shallow and wide so it spills easily when you pull it out of a full tub. Small thing but it annoys me every morning.'],
            ['rating' => 5, 'title' => 'Replaced my imported tub', 'body' => 'Was buying an American brand for years. Switched to this to cut the cost and honestly I cannot tell the difference in how it mixes or how I feel after. Should have switched sooner.'],
            ['rating' => 4, 'title' => 'Does the job', 'body' => 'Nothing dramatic to report, which is what you want. Mixes, tastes fine, sealed properly. Would buy again.'],
            ['rating' => 5, 'title' => 'Blends well into oats too', 'body' => 'I stir half a scoop into my morning oats and it does not turn gluey the way some proteins do. Also works in a smoothie with banana. Versatile.'],
            ['rating' => 5, 'title' => 'Sealed and labelled properly', 'body' => 'Batch number, manufacture date and expiry all printed clearly on the tub. Inner foil seal was intact. I care about this stuff more than the flavour honestly.'],
            ['rating' => 4, 'title' => 'Good but wish there were more flavours', 'body' => 'Vanilla is well done. I would pick this up again in chocolate or coffee if it existed. Consider this a request more than a complaint.'],
            ['rating' => 5, 'title' => 'Foam settles fast', 'body' => 'Some proteins froth up and you are left drinking foam. This settles within a minute. Small detail that tells you the powder is decent quality.'],
            ['rating' => 5, 'title' => 'Third tub, still consistent', 'body' => 'Buying the same product repeatedly is the real test. Texture and taste have been identical across three tubs from different batches. Consistency matters.'],
            ['rating' => 4, 'title' => 'Tub is bulkier than expected', 'body' => 'Product is good. Just be aware the tub takes up real space on a shelf. A refill pouch option would be welcome and probably cheaper to ship.'],
            ['rating' => 5, 'title' => 'Easy on the stomach', 'body' => 'I take it first thing on training days on a fairly empty stomach and it has not bothered me once. That was not true of the previous brand I tried.'],
            ['rating' => 5, 'title' => 'Straightforward label', 'body' => 'Protein per serving is stated plainly, ingredient list is short, no long tail of things I cannot pronounce. Appreciate that.'],
            ['rating' => 3, 'title' => 'Thin with water', 'body' => 'If you drink it with water it comes out quite thin, almost watery. With milk it is much better. Depends what you are after, but worth knowing before you buy.'],
            ['rating' => 5, 'title' => 'Solid daily protein', 'body' => 'Simple, does what it says, priced sensibly. I am not looking for a miracle from a protein powder, I want it to mix and taste okay and not cost a fortune. It does all three.'],
            ['rating' => 4, 'title' => 'Arrived a day late', 'body' => 'The product deserves five, the courier gets three. Estimated Thursday, arrived Friday evening. Tub itself was in perfect condition so no real harm.'],
            ['rating' => 5, 'title' => 'Good for a beginner', 'body' => 'Started training six months ago and did not want to spend big on a first protein. This was a reasonable place to start and I have not felt the need to upgrade.'],
            ['rating' => 5, 'title' => 'No artificial aftertaste', 'body' => 'The thing that put me off other budget proteins was a chemical aftertaste that lingered. This does not have it. Clean finish.'],
            ['rating' => 4, 'title' => 'Scoop size is generous', 'body' => 'Worth measuring your first few scoops rather than eyeballing. The scoop holds a fair bit and it is easy to overshoot the serving without realising.'],
        ];
    }

    private function creatine(): array
    {
        return [
            ['rating' => 5, 'title' => 'Genuinely unflavoured', 'body' => 'A lot of "unflavoured" powders have a taste. This actually does not. I drop it in coffee, juice, my protein shake, water, and it disappears every time.'],
            ['rating' => 5, 'title' => 'Fine grain, dissolves properly', 'body' => 'The grind is fine enough that it goes into cold water without sitting at the bottom. Previous brand I had to stir with a spoon and still got grit at the end.'],
            ['rating' => 4, 'title' => 'Slight settling in cold water', 'body' => 'Mostly dissolves. If I leave the glass for a few minutes there is a little residue at the bottom. Not a big deal, I just swirl it before finishing.'],
            ['rating' => 5, 'title' => 'Simple product, priced right', 'body' => 'Creatine monohydrate is creatine monohydrate. What matters is that it is pure, it is dosed properly and it is not overpriced. This ticks all three.'],
            ['rating' => 5, 'title' => 'Scoop is well sized', 'body' => 'The included scoop is exactly the serving, so I do not have to weigh anything. Sounds trivial until you have used a product where the scoop is a guess.'],
            ['rating' => 5, 'title' => 'No fillers on the label', 'body' => 'Ingredients: creatine monohydrate. That is the whole list. Exactly what I was looking for.'],
            ['rating' => 4, 'title' => 'Pouch is hard to reseal', 'body' => 'Powder is great. The zip lock on the pouch does not close cleanly once you have opened it, and powder gets caught in the seal. I decanted it into a jar.'],
            ['rating' => 5, 'title' => 'Mixes into my pre workout', 'body' => 'I stack it with my pre workout and it does not change the flavour of that at all. Convenient, one less drink to get through.'],
            ['rating' => 5, 'title' => 'Well packed for shipping', 'body' => 'Came in a padded sleeve with the pouch sealed inside. Nothing burst, nothing leaked, no powder in the box. Good packing.'],
            ['rating' => 5, 'title' => 'Lasts a long time', 'body' => 'At one serving a day this pouch has run for the better part of two months. Works out cheap per serving compared to the tubs I was buying before.'],
            ['rating' => 3, 'title' => 'Scoop was missing', 'body' => 'Powder itself is fine and dissolves well. My pouch arrived without a scoop inside, which was mildly irritating. I weigh it now, so it sorted itself out.'],
            ['rating' => 4, 'title' => 'Does what it says', 'body' => 'No taste, no smell, mixes in, sensible price. There is not much more to say about a single ingredient powder and that is a compliment.'],
            ['rating' => 5, 'title' => 'Clean and consistent', 'body' => 'Second pouch now. Same texture, same behaviour in water. No surprises between batches, which is what I want from a staple.'],
            ['rating' => 5, 'title' => 'Easy to keep up with', 'body' => 'Because it has no flavour I can put it in whatever I am already drinking. That makes it much easier to stay consistent than a powder I have to make a separate drink for.'],
            ['rating' => 4, 'title' => 'Bag could be smaller', 'body' => 'A lot of empty space at the top of the pouch. The product is correctly weighed, just the packaging is larger than it needs to be.'],
            ['rating' => 5, 'title' => 'No grit in juice', 'body' => 'Tried it in orange juice on a whim and it went in completely. Not even a hint of texture. Better than I expected.'],
            ['rating' => 5, 'title' => 'Good value', 'body' => 'Compared prices per hundred grams across four brands before buying. This came out cheapest for the same single ingredient. Easy decision.'],
            ['rating' => 5, 'title' => 'Sealed foil inside', 'body' => 'Outer pouch plus an inner foil seal you have to break. Reassuring for something you are taking daily.'],
            ['rating' => 4, 'title' => 'Slightly clumpy in humidity', 'body' => 'Kept it in the kitchen through the monsoon and it clumped a little. Broke up easily with a spoon. Store it somewhere dry and you will be fine.'],
            ['rating' => 5, 'title' => 'Straightforward, no gimmicks', 'body' => 'Not marketed as some proprietary blend with a made up name. Plain monohydrate, clearly labelled, sensibly priced. That is all I wanted.'],
            ['rating' => 5, 'title' => 'Fast delivery to Bihar', 'body' => 'Three days door to door. Packaging intact. Ordering again without hesitation.'],
            ['rating' => 4, 'title' => 'Wish it came in a bigger size', 'body' => 'Happy with the product. I go through it steadily and would buy a double size pouch if it were offered, to save on repeat shipping.'],
        ];
    }

    private function preWorkout(): array
    {
        return [
            ['rating' => 5, 'title' => 'Not overloaded with stimulants', 'body' => 'I have had pre workouts that leave me jittery and unable to sit still. This is more measured. I take it twenty minutes before and get on with the session.'],
            ['rating' => 5, 'title' => 'Dissolves without residue', 'body' => 'Half a scoop in 300ml of water, shake for a few seconds, done. No sediment at the bottom of the shaker when I finish it.'],
            ['rating' => 4, 'title' => 'Strong flavour, dilute it', 'body' => 'At the full scoop in a small amount of water it is intense. I use more water now and it is much better. Worth mentioning to anyone trying it the first time.'],
            ['rating' => 5, 'title' => 'No crash afterwards', 'body' => 'The part I care about. I train in the evening and I am not wiped out an hour later. Sleep has been unaffected as long as I do not take it too late.'],
            ['rating' => 5, 'title' => 'Good tingle, not overwhelming', 'body' => 'You get the usual beta alanine tingle on the face and arms. It is noticeable but it fades within fifteen minutes. If you have never had it before it is normal.'],
            ['rating' => 4, 'title' => 'Scoop is easy to over pour', 'body' => 'Start with half. I did a full scoop on day one and it was more than I needed. Product is good, just respect the serving size.'],
            ['rating' => 5, 'title' => 'Reasonable price per serving', 'body' => 'Most pre workouts in this category cost noticeably more per serving. This is priced well and I do not feel like I am paying for the label.'],
            ['rating' => 5, 'title' => 'Sealed tub, clear label', 'body' => 'Every ingredient and its amount is printed, no hiding behind a proprietary blend. That alone made me pick it over two competitors.'],
            ['rating' => 3, 'title' => 'Flavour is not for me', 'body' => 'It mixes well and I have no issue with how it performs. I just do not love the taste. Entirely personal, and I will finish the tub.'],
            ['rating' => 5, 'title' => 'Focus during the session', 'body' => 'I get through my working sets without drifting off between them. Whether that is the product or the routine I cannot say for sure, but I am happy.'],
            ['rating' => 5, 'title' => 'Packed carefully', 'body' => 'Tub was bubble wrapped inside the box with the lid taped. Arrived in Patna in three days, seal untouched.'],
            ['rating' => 4, 'title' => 'Takes a moment to settle', 'body' => 'If you shake it hard it foams up quite a lot. Let it sit for a minute and it is fine. Not a defect, just how it behaves.'],
            ['rating' => 5, 'title' => 'Consistent tub to tub', 'body' => 'Second purchase. Same colour, same texture, same strength. No variation between batches that I can detect.'],
            ['rating' => 5, 'title' => 'Works on early mornings', 'body' => 'I train at six and struggle to get going. Taking this on the way to the gym has made those sessions a lot less miserable.'],
            ['rating' => 4, 'title' => 'Colours the shaker', 'body' => 'Leaves a faint stain in a clear plastic shaker if you leave it sitting. Rinses out if you do it straight away. Minor.'],
            ['rating' => 5, 'title' => 'Good for the price bracket', 'body' => 'I have used more expensive products that did not do anything more for me. Happy to stay with this.'],
            ['rating' => 5, 'title' => 'One scoop is plenty', 'body' => 'A tub lasts me a good while because I never need more than one serving. Some products push you to two scoops to hit the dose, this does not.'],
            ['rating' => 4, 'title' => 'Wish the tub had a scoop holder', 'body' => 'The scoop sinks to the bottom every time and I end up digging through the powder. Tiny gripe about an otherwise good product.'],
            ['rating' => 5, 'title' => 'Straightforward and effective for me', 'body' => 'Take it, wait, train. Nothing complicated. It does the one thing I bought it for.'],
            ['rating' => 5, 'title' => 'Delivery ahead of schedule', 'body' => 'Estimated five days, arrived in three. Everything sealed. No complaints on the service side either.'],
            ['rating' => 4, 'title' => 'Take it early enough', 'body' => 'Learned not to have it after seven in the evening or I am awake late. That is on me rather than the product, but a fair warning.'],
        ];
    }

    private function ashwagandha(): array
    {
        return [
            ['rating' => 5, 'title' => 'Capsules are easy to swallow', 'body' => 'Not oversized like some herbal capsules that get stuck. These go down with a normal glass of water without any fuss.'],
            ['rating' => 5, 'title' => 'No strong smell', 'body' => 'Opened the bottle expecting the earthy herbal smell you usually get. It is very mild. Makes taking it daily far more pleasant.'],
            ['rating' => 4, 'title' => 'Bottle is bigger than needed', 'body' => 'Capsules are fine and well made. The bottle is about twice the size it needs to be for the count. Not a real problem, just noticeable.'],
            ['rating' => 5, 'title' => 'Clear dosage on the label', 'body' => 'Strength per capsule is printed plainly along with the serving suggestion. I like knowing exactly what I am taking rather than guessing.'],
            ['rating' => 5, 'title' => 'Part of my evening routine', 'body' => 'I take it after dinner along with the rest of my supplements. Simple to fit in. Two months and I have not missed a day.'],
            ['rating' => 4, 'title' => 'Slight aftertaste if you wait', 'body' => 'If the capsule sits on your tongue for a second you get a faint bitterness. Swallow it straight away and there is nothing at all.'],
            ['rating' => 5, 'title' => 'Sealed properly', 'body' => 'Induction seal under the cap plus a shrink band around it. Expiry printed clearly. All the things I check for were in order.'],
            ['rating' => 5, 'title' => 'Good count for the price', 'body' => 'Worked out the cost per capsule against three other Indian brands. This was the better deal for the same stated strength.'],
            ['rating' => 3, 'title' => 'Cap is stiff', 'body' => 'No complaint about the capsules themselves. The child lock cap takes real effort to open, which I suppose is the point, but it is a struggle.'],
            ['rating' => 5, 'title' => 'Neat packaging', 'body' => 'Arrived in a small box with the bottle wrapped. Nothing rattling around, nothing damaged. Took four days to reach me.'],
            ['rating' => 5, 'title' => 'Consistent capsules', 'body' => 'Every capsule is filled to the same level. No half empty ones, no powder loose in the bottle. Small sign of a careful process.'],
            ['rating' => 4, 'title' => 'Would prefer a smaller bottle', 'body' => 'Product is good. I travel and would happily buy a thirty count pack to keep in my bag rather than carrying the full bottle.'],
            ['rating' => 5, 'title' => 'Second bottle', 'body' => 'Reordered without thinking about it, which is probably the strongest thing I can say. Same as the first.'],
            ['rating' => 5, 'title' => 'Fits into my routine easily', 'body' => 'One capsule, once a day, with food. There is no powder to measure and nothing to mix, so I actually keep up with it.'],
            ['rating' => 4, 'title' => 'Wish the label listed the extract ratio', 'body' => 'Strength is given, but I would like to see the extract ratio spelled out as well. Something for the brand to consider on the next print run.'],
            ['rating' => 5, 'title' => 'No capsule sticking', 'body' => 'Some herbal capsules go tacky in humid weather and clump together. These have stayed separate through a Patna summer.'],
            ['rating' => 5, 'title' => 'Honest presentation', 'body' => 'The bottle does not make wild promises on the front. It says what is inside and how much. Refreshing.'],
            ['rating' => 4, 'title' => 'Delivery took five days', 'body' => 'Slightly slower than the estimate but the bottle was sealed and undamaged. I would order again.'],
            ['rating' => 5, 'title' => 'Vegetarian capsule shell', 'body' => 'Checked before ordering and confirmed on the label. Important to me and not always clear with other brands.'],
            ['rating' => 5, 'title' => 'Good value, simple product', 'body' => 'Does not try to be a blend of fifteen things. One ingredient, stated strength, fair price. That is what I was looking for.'],
            ['rating' => 4, 'title' => 'Bottle label peeled slightly', 'body' => 'A corner of the label lifted in transit. Purely cosmetic, the seal and the capsules were untouched.'],
        ];
    }

    private function omega(): array
    {
        return [
            ['rating' => 5, 'title' => 'No fishy burps', 'body' => 'This is the whole reason I went for a flaxseed source rather than fish oil. No repeat, no aftertaste. That alone earns the five stars from me.'],
            ['rating' => 5, 'title' => 'Softgels are a sensible size', 'body' => 'Big enough to hold a proper dose but not so big you dread swallowing them. Two a day goes down easily with breakfast.'],
            ['rating' => 4, 'title' => 'Softgels stuck together once', 'body' => 'Left the bottle near the window and a few softgels fused in the heat. Separated them without breaking any. Keep it out of direct sun.'],
            ['rating' => 5, 'title' => 'Vegetarian source', 'body' => 'Been looking for a plant based omega for a while. Cold pressed flaxseed, clearly labelled, no gelatin. Exactly what I needed.'],
            ['rating' => 5, 'title' => 'Clean label', 'body' => 'Ingredients are short and readable. Manufacture date, expiry and batch all printed on the bottle rather than a sticker.'],
            ['rating' => 5, 'title' => 'Well sealed on arrival', 'body' => 'Foil seal under the cap intact, shrink wrap on the outside. Arrived in four days without a dent in the box.'],
            ['rating' => 4, 'title' => 'Faint smell on opening', 'body' => 'There is a mild oily smell when you first crack the seal, which I would expect from any oil capsule. It is not unpleasant and it does not carry over into taking them.'],
            ['rating' => 5, 'title' => 'Easy to remember', 'body' => 'I keep the bottle next to the kettle and take them with breakfast. Two months in and I have not skipped.'],
            ['rating' => 5, 'title' => 'Fair price for the count', 'body' => 'Compared against two imported brands and this came out cheaper per softgel with the same stated content. Good value.'],
            ['rating' => 3, 'title' => 'Bottle is hard to open', 'body' => 'The cap grips tightly and I struggle with it. Softgels themselves are perfectly good and I have no other complaint.'],
            ['rating' => 5, 'title' => 'Consistent softgels', 'body' => 'No leaking, no oily residue in the bottle, none stuck to each other beyond that one hot week. Uniform size and colour throughout.'],
            ['rating' => 5, 'title' => 'Simple addition to the day', 'body' => 'Two softgels with food, nothing to measure or mix. Easy to be consistent with, which is really the point.'],
            ['rating' => 4, 'title' => 'Would like a larger pack', 'body' => 'I go through it steadily and would prefer a bigger bottle to cut down on repeat orders and shipping.'],
            ['rating' => 5, 'title' => 'Good for anyone avoiding fish oil', 'body' => 'I do not eat fish, and most omega supplements are fish based. Finding a flaxseed option that is priced sensibly was the hard part.'],
            ['rating' => 5, 'title' => 'Second bottle', 'body' => 'Reordered as soon as the first ran low. Same quality, same packaging, arrived quicker the second time.'],
            ['rating' => 5, 'title' => 'Nothing rattling in the box', 'body' => 'Packed with the bottle wrapped and cushioned. Sometimes you get a bottle loose in an oversized box. Not here.'],
            ['rating' => 4, 'title' => 'Softgels are a touch oily to handle', 'body' => 'They can be slightly slippery coming out of the bottle. Tip them into your hand rather than fishing them out one at a time.'],
            ['rating' => 5, 'title' => 'Cold pressed as stated', 'body' => 'The extraction method is on the label, which not every brand bothers with. Part of why I chose this one.'],
            ['rating' => 5, 'title' => 'No complaints at all', 'body' => 'Sealed, well priced, easy to take, no aftertaste, arrived on time. There is nothing I would change.'],
            ['rating' => 4, 'title' => 'Arrived a day past the estimate', 'body' => 'Product itself is excellent. Courier was slightly slow. Bottle was in perfect condition so it hardly matters.'],
        ];
    }

    private function shaker(): array
    {
        return [
            ['rating' => 5, 'title' => 'Genuinely leak proof', 'body' => 'Threw it in my gym bag on its side with a full shake inside and nothing escaped. That was the only thing I cared about and it passed.'],
            ['rating' => 5, 'title' => 'No smell retention', 'body' => 'Rinse it straight after use and it stays neutral. My old plastic shaker held on to the smell of every protein I ever put in it.'],
            ['rating' => 4, 'title' => 'Lid needs a firm push', 'body' => 'The seal is tight, which is why it does not leak, but it takes a proper press to close. Worth checking before you shake.'],
            ['rating' => 5, 'title' => 'Mixes without a whisk ball', 'body' => 'The shape of the base seems to do the work. I get a smooth shake in about ten seconds without any ball rattling around.'],
            ['rating' => 5, 'title' => 'Sturdier than it looks', 'body' => 'Dropped it on a tiled gym floor twice. No cracks, no chips, lid still seals. Solid build.'],
            ['rating' => 4, 'title' => 'Markings wear with washing', 'body' => 'The volume markings on the side have faded a little after a couple of months in the sink. The bottle itself is unaffected.'],
            ['rating' => 5, 'title' => 'Fits my bottle cage', 'body' => 'Checked the diameter before ordering and it slots into a standard cycle bottle cage. Useful if you ride to the gym.'],
            ['rating' => 5, 'title' => 'Easy to clean', 'body' => 'Wide mouth so a hand and sponge go straight in. No awkward corners where powder hides.'],
            ['rating' => 5, 'title' => 'Good size for one serving', 'body' => 'Holds a scoop and enough water without filling to the brim. Anything bigger would be dead weight in the bag.'],
            ['rating' => 3, 'title' => 'Cap is a little stiff at first', 'body' => 'Took a week of use before the lid stopped fighting me. It has loosened up now and seals fine.'],
            ['rating' => 5, 'title' => 'Well packed', 'body' => 'Arrived boxed with the lid taped shut so it could not pop open in transit. Clean out of the box, no plastic smell.'],
            ['rating' => 5, 'title' => 'Keeps drinks cold', 'body' => 'The double wall does what it claims. A cold shake was still cold at the end of an hour long session.'],
            ['rating' => 4, 'title' => 'Slightly heavy when full', 'body' => 'That is the trade off for the steel build. If you want something light this is not it, but I prefer the durability.'],
            ['rating' => 5, 'title' => 'No plastic taste', 'body' => 'Water tastes like water. That is not true of every bottle I have owned in this price range.'],
            ['rating' => 5, 'title' => 'Two months daily use', 'body' => 'Still sealing, still not stained, no wobble in the lid. Holding up better than the branded one it replaced.'],
            ['rating' => 4, 'title' => 'Wish it had a carry loop', 'body' => 'Great bottle. A small loop on the lid would make it easier to clip to a bag. Consider it a suggestion.'],
            ['rating' => 5, 'title' => 'Good value', 'body' => 'Cheaper than the big brand equivalents and I cannot find anything it does worse. Bought a second one for work.'],
            ['rating' => 5, 'title' => 'Ergonomic to hold', 'body' => 'The body is slightly contoured so it does not slip out of a sweaty hand mid set.'],
            ['rating' => 4, 'title' => 'Fast delivery, small dent in box', 'body' => 'Box had a corner knocked in but the bottle inside was untouched. Arrived in three days.'],
            ['rating' => 5, 'title' => 'Does exactly one job, well', 'body' => 'It holds a drink, it mixes it, it does not leak, it does not smell. There is nothing else to ask of a shaker.'],
        ];
    }

    private function generic(): array
    {
        return [
            ['rating' => 5, 'title' => 'Happy with the purchase', 'body' => 'Arrived sealed and on time, exactly as described on the listing. No complaints so far.'],
            ['rating' => 4, 'title' => 'Good, packaging could improve', 'body' => 'The product is fine. The outer box arrived a little battered, though nothing inside was damaged.'],
            ['rating' => 5, 'title' => 'Good value', 'body' => 'Priced sensibly against the alternatives I looked at. Would order again.'],
            ['rating' => 5, 'title' => 'Quick delivery', 'body' => 'Ordered midweek and had it by the weekend. Well packed, everything intact.'],
            ['rating' => 4, 'title' => 'Does the job', 'body' => 'Nothing remarkable to report, which is what you want. It does what it says it will.'],
        ];
    }
}
