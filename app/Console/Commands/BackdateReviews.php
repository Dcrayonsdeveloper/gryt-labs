<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackdateReviews extends Command
{
    protected $signature = 'reviews:backdate
                            {--days=180 : Spread across this many past days}
                            {--forward-days=90 : Also spread some reviews into future days}
                            {--forward-percent=40 : Percentage of reviews to schedule into future}';
    protected $description = 'Spread generated reviews from today across past and future days for natural drip feed';

    public function handle(): int
    {
        $pastDays = (int) $this->option('days');
        $forwardDays = (int) $this->option('forward-days');
        $forwardPercent = (int) $this->option('forward-percent');

        $count = DB::table('reviews')
            ->where('is_generated', true)
            ->whereDate('created_at', today())
            ->count();

        if ($count === 0) {
            $this->info('No generated reviews from today to spread.');
            return 0;
        }

        $this->info("Spreading {$count} reviews: past {$pastDays} days + future {$forwardDays} days ({$forwardPercent}% forward)...");
        $bar = $this->output->createProgressBar($count);
        $pastCount = 0;
        $futureCount = 0;

        DB::table('reviews')
            ->where('is_generated', true)
            ->whereDate('created_at', today())
            ->chunkById(200, function ($reviews) use ($pastDays, $forwardDays, $forwardPercent, $bar, &$pastCount, &$futureCount) {
                foreach ($reviews as $review) {
                    if ($forwardDays > 0 && rand(1, 100) <= $forwardPercent) {
                        $date = now()->addDays(rand(1, $forwardDays))->setTime(rand(6, 22), rand(0, 59), rand(0, 59));
                        $futureCount++;
                    } else {
                        $date = now()->subDays(rand(1, $pastDays))->setTime(rand(6, 22), rand(0, 59), rand(0, 59));
                        $pastCount++;
                    }

                    DB::table('reviews')->where('id', $review->id)->update([
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Done! {$pastCount} reviews backdated, {$futureCount} reviews scheduled for future drip.");

        return 0;
    }
}
