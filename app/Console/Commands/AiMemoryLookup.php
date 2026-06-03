<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AiMemoryLookup extends Command
{
    protected $signature = 'ai:memory
                            {query? : What to search for in memory}
                            {--list : List all memory files}
                            {--file= : Read a specific memory file}
                            {--all : Dump all memory content}';

    protected $description = 'Look up project AI memory files for context and troubleshooting';

    private string $memoryPath;

    public function __construct()
    {
        parent::__construct();
        $this->memoryPath = base_path('.ai-memory');
    }

    public function handle(): int
    {
        if (!File::isDirectory($this->memoryPath)) {
            $this->error('.ai-memory directory not found.');
            return 1;
        }

        if ($this->option('list')) {
            return $this->listFiles();
        }

        if ($file = $this->option('file')) {
            return $this->readFile($file);
        }

        if ($this->option('all')) {
            return $this->dumpAll();
        }

        if ($query = $this->argument('query')) {
            return $this->search($query);
        }

        // Default: show index
        return $this->readFile('MEMORY.md');
    }

    private function listFiles(): int
    {
        $files = File::files($this->memoryPath);
        $this->info('AI Memory Files:');
        foreach ($files as $file) {
            $size = round($file->getSize() / 1024, 1);
            $this->line("  {$file->getFilename()} ({$size}KB)");
        }
        return 0;
    }

    private function readFile(string $filename): int
    {
        $path = $this->memoryPath . '/' . $filename;
        if (!File::exists($path)) {
            // Try adding .md extension
            $path = $this->memoryPath . '/' . $filename . '.md';
        }

        if (!File::exists($path)) {
            $this->error("File not found: {$filename}");
            return 1;
        }

        $this->info("=== {$filename} ===");
        $this->line(File::get($path));
        return 0;
    }

    private function dumpAll(): int
    {
        $files = File::files($this->memoryPath);
        foreach ($files as $file) {
            $this->newLine();
            $this->info("=== {$file->getFilename()} ===");
            $this->line(File::get($file->getPathname()));
        }
        return 0;
    }

    private function search(string $query): int
    {
        $files = File::files($this->memoryPath);
        $keywords = array_filter(explode(' ', strtolower($query)));
        $matches = [];

        foreach ($files as $file) {
            $content = strtolower(File::get($file->getPathname()));
            $score = 0;
            foreach ($keywords as $keyword) {
                $score += substr_count($content, $keyword);
            }
            if ($score > 0) {
                $matches[$file->getFilename()] = $score;
            }
        }

        if (empty($matches)) {
            $this->warn("No memory files match: {$query}");
            $this->line('Try: php artisan ai:memory --list');
            return 0;
        }

        arsort($matches);

        $this->info("Search: \"{$query}\" — Found " . count($matches) . " matching file(s):");
        $this->newLine();

        foreach ($matches as $filename => $score) {
            $this->info("=== {$filename} (relevance: {$score}) ===");
            $content = File::get($this->memoryPath . '/' . $filename);

            // Show relevant lines
            $lines = explode("\n", $content);
            $shown = false;
            foreach ($lines as $i => $line) {
                $lower = strtolower($line);
                foreach ($keywords as $keyword) {
                    if (str_contains($lower, $keyword)) {
                        // Show context: 1 line before + matching line + 2 lines after
                        $start = max(0, $i - 1);
                        $end = min(count($lines) - 1, $i + 2);
                        for ($j = $start; $j <= $end; $j++) {
                            $prefix = $j === $i ? '>>>' : '   ';
                            $this->line("  {$prefix} " . $lines[$j]);
                        }
                        $this->line('  ---');
                        $shown = true;
                        break;
                    }
                }
            }

            if (!$shown) {
                $this->line("  (matches found but in metadata/headers)");
            }
            $this->newLine();
        }

        return 0;
    }
}
