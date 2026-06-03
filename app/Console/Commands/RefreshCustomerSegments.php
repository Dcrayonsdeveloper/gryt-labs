<?php

namespace App\Console\Commands;

use App\Models\CustomerSegment;
use Illuminate\Console\Command;

class RefreshCustomerSegments extends Command
{
    protected $signature = 'customers:refresh-segments';

    protected $description = 'Refresh customer counts for all auto segments';

    public function handle(): int
    {
        $segments = CustomerSegment::where('is_auto', true)->get();

        if ($segments->isEmpty()) {
            $this->info('No auto segments found.');
            return self::SUCCESS;
        }

        $this->info("Refreshing {$segments->count()} segment(s)...");

        foreach ($segments as $segment) {
            $count = $segment->refreshCount();
            $this->line("  {$segment->name}: {$count} customers");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
