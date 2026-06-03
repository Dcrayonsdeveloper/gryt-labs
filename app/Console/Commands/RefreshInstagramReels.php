<?php

namespace App\Console\Commands;

use App\Services\InstagramReelsService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RefreshInstagramReels extends Command
{
    protected $signature = 'instagram:refresh-reels';
    protected $description = 'Refresh cached Instagram reels from the API';

    public function handle(InstagramReelsService $service): int
    {
        $service->clearCache();
        $reels = $service->getLatestReels(10);

        if (empty($reels)) {
            $this->warn('No reels fetched. Check your INSTAGRAM_USER_ID and INSTAGRAM_ACCESS_TOKEN in .env');
            return self::FAILURE;
        }

        $count = count($reels);
        $this->info("Successfully cached {$count} reels:");
        foreach ($reels as $i => $reel) {
            $this->line('  ' . ($i + 1) . '. ' . $reel['shortcode'] . ' — ' . Str::limit($reel['caption'], 50));
        }

        return self::SUCCESS;
    }
}
