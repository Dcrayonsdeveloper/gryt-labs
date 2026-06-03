<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Restore a PostgreSQL database from a backup file.
 *
 * USAGE (on production server):
 *   php artisan backup:list                                    — list available backups
 *   php artisan backup:restore jikra --latest                  — restore latest jikra backup
 *   php artisan backup:restore jikra --latest --tag=pre-deploy — restore latest pre-deploy tagged backup
 *   php artisan backup:restore jikra --file=jikra_pre-deploy_2026-04-21_100000.sql.gz
 *
 * WARNING: This DROPS and RECREATES the database. All current data is LOST.
 *          Use only for disaster recovery or rollback.
 */
class RestoreDatabase extends Command
{
    protected $signature = 'backup:restore
                            {database : Database name (jikra, getsetnova, ayurvexa, natually, jikra_central)}
                            {--latest : Use the most recent backup}
                            {--tag= : Filter by tag (e.g. pre-deploy)}
                            {--file= : Exact backup filename to restore}
                            {--force : Skip confirmation prompt}';
    protected $description = 'Restore a PostgreSQL database from a backup (destructive)';

    public function handle(): int
    {
        $database = $this->argument('database');
        $backupDir = storage_path('backups');

        $file = null;
        if ($this->option('file')) {
            $file = "{$backupDir}/" . $this->option('file');
            if (!file_exists($file)) {
                $this->error("Backup file not found: {$file}");
                return 1;
            }
        } elseif ($this->option('latest')) {
            $tag = $this->option('tag');
            $pattern = $tag
                ? "{$backupDir}/{$database}_{$tag}_*.sql.gz"
                : "{$backupDir}/{$database}_*.sql.gz";
            $files = glob($pattern);
            if (empty($files)) {
                $this->error("No backups found matching: {$pattern}");
                return 1;
            }
            usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
            $file = $files[0];
        } else {
            $this->error('Must specify --latest or --file');
            $this->line('Run: php artisan backup:list');
            return 1;
        }

        $sizeMB = round(filesize($file) / 1024 / 1024, 2);
        $age = now()->diffForHumans(now()->setTimestamp(filemtime($file)));

        $this->warn("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->warn("  DESTRUCTIVE: RESTORE DATABASE");
        $this->warn("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("  Target DB:    {$database}");
        $this->line("  Backup file:  " . basename($file));
        $this->line("  Size:         {$sizeMB} MB");
        $this->line("  Age:          {$age}");

        if (!$this->option('force') && !$this->confirm('Proceed with restore? (destructive)', false)) {
            $this->info('Restore cancelled.');
            return 0;
        }

        $host = config('database.connections.pgsql.host', '127.0.0.1');
        $port = config('database.connections.pgsql.port', '5432');
        $user = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');

        // Safety: snapshot current state before overwriting it
        $safetyDate = now()->format('Y-m-d_His');
        $safetyFile = "{$backupDir}/{$database}_pre-restore_{$safetyDate}.sql.gz";
        $this->info("Taking safety backup of current state first...");
        $safetyCmd = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s %s 2>/dev/null | gzip > %s',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($database),
            escapeshellarg($safetyFile)
        );
        exec($safetyCmd, $out, $exit);
        if ($exit === 0 && file_exists($safetyFile) && filesize($safetyFile) > 100) {
            $this->line("  Safety backup saved: " . basename($safetyFile));
        } else {
            $this->warn("  Safety backup failed — continuing anyway.");
        }

        // Drop + recreate the database (must disconnect clients first)
        $this->info("Dropping and recreating {$database}...");
        $dropCmd = sprintf(
            'PGPASSWORD=%s psql -h %s -p %s -U %s -d postgres -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = %s AND pid <> pg_backend_pid(); DROP DATABASE IF EXISTS \"%s\"; CREATE DATABASE \"%s\";" 2>&1',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg("'{$database}'"),
            $database,
            $database
        );
        exec($dropCmd, $dropOut, $dropExit);
        if ($dropExit !== 0) {
            $this->error("Failed to drop/recreate database: " . implode("\n", $dropOut));
            return 1;
        }

        $this->info("Restoring from backup...");
        $restoreCmd = sprintf(
            'gunzip -c %s | PGPASSWORD=%s psql -h %s -p %s -U %s -d %s 2>&1',
            escapeshellarg($file),
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($database)
        );
        exec($restoreCmd, $restoreOut, $restoreExit);
        if ($restoreExit !== 0) {
            $this->error("Restore failed. Check log. Safety backup: " . basename($safetyFile));
            Log::critical("Database restore failed", [
                'database' => $database,
                'file' => basename($file),
                'exit_code' => $restoreExit,
            ]);
            return 1;
        }

        $this->info("Restore complete: {$database} <- " . basename($file));
        Log::warning("Database restored", [
            'database' => $database,
            'from' => basename($file),
            'safety_backup' => basename($safetyFile),
        ]);

        return 0;
    }
}
