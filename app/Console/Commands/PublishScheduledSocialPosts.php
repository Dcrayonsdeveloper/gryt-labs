<?php

namespace App\Console\Commands;

use App\Models\SocialMediaPost;
use App\Services\SocialMediaPublishingService;
use Illuminate\Console\Command;

class PublishScheduledSocialPosts extends Command
{
    protected $signature = 'social:publish-scheduled';
    protected $description = 'Publish social media posts that are due for publishing';

    public function handle(SocialMediaPublishingService $service): int
    {
        $posts = SocialMediaPost::dueForPublishing()->get();

        if ($posts->isEmpty()) {
            return 0;
        }

        $this->info("Found {$posts->count()} post(s) due for publishing.");

        foreach ($posts as $post) {
            $post->update(['status' => 'publishing']);

            $this->line("  Publishing: {$post->title}...");

            try {
                $results = $service->publish($post);

                $post->refresh();
                $this->line("    Status: {$post->status}");

                if ($post->status === 'failed' && $post->retry_count < 3) {
                    $post->update([
                        'status' => 'scheduled',
                        'scheduled_at' => now()->addMinutes(5),
                        'retry_count' => $post->retry_count + 1,
                    ]);
                    $this->warn("    Queued for retry #{$post->retry_count} in 5 minutes.");
                }
            } catch (\Exception $e) {
                $post->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                $this->error("    Error: {$e->getMessage()}");
            }
        }

        $this->info('Done.');

        return 0;
    }
}
