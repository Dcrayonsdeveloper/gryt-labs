<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SyncAiMemory extends Command
{
    protected $signature = 'ai:sync-memory
                            {--from= : Source directory (defaults to Claude memory)}
                            {--to= : Target directory (defaults to .ai-memory)}';

    protected $description = 'Sync Claude memory files to project .ai-memory directory';

    public function handle(): int
    {
        $home = getenv('USERPROFILE') ?: getenv('HOME');
        $from = $this->option('from')
            ?: "{$home}/.claude/projects/d--Projects-jikraofficial/memory";
        $to = $this->option('to') ?: base_path('.ai-memory');

        if (!File::isDirectory($from)) {
            $this->error("Source not found: {$from}");
            return 1;
        }

        if (!File::isDirectory($to)) {
            File::makeDirectory($to, 0755, true);
        }

        $files = File::files($from);
        $synced = 0;

        foreach ($files as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $dest = $to . '/' . $file->getFilename();
            $sourceContent = File::get($file->getPathname());

            if (File::exists($dest) && File::get($dest) === $sourceContent) {
                continue; // Already in sync
            }

            File::put($dest, $sourceContent);
            $synced++;
            $this->line("  Synced: {$file->getFilename()}");
        }

        if ($synced === 0) {
            $this->info('All memory files already in sync.');
        } else {
            $this->info("Synced {$synced} file(s) to .ai-memory/");
        }

        return 0;
    }
}
