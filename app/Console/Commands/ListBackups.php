<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ListBackups extends Command
{
    protected $signature = 'backup:list {--database= : Filter by database name}';
    protected $description = 'List all available database backups';

    public function handle(): int
    {
        $backupDir = storage_path('backups');
        if (!is_dir($backupDir)) {
            $this->info('No backups directory yet.');
            return 0;
        }

        $files = glob("{$backupDir}/*.sql.gz");
        if ($db = $this->option('database')) {
            $files = array_filter($files, fn($f) => str_starts_with(basename($f), "{$db}_"));
        }

        if (empty($files)) {
            $this->info('No backups found.');
            return 0;
        }

        // Sort by mtime, newest first
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));

        $rows = [];
        foreach ($files as $file) {
            $name = basename($file);
            // Parse: db_tag_date.sql.gz
            preg_match('/^(.+?)_(.+?)_(\d{4}-\d{2}-\d{2}_\d{6})\.sql\.gz$/', $name, $m);
            $db = $m[1] ?? 'unknown';
            $tag = $m[2] ?? '-';
            $rows[] = [
                'Database' => $db,
                'Tag' => $tag,
                'Age' => now()->diffForHumans(now()->setTimestamp(filemtime($file)), ['short' => true]),
                'Size' => round(filesize($file) / 1024 / 1024, 1) . ' MB',
                'File' => $name,
            ];
        }

        $this->table(['Database', 'Tag', 'Age', 'Size', 'File'], $rows);
        $this->info(count($rows) . ' backup(s)');
        return 0;
    }
}
