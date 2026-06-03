<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Automated daily database backup for all tenant databases.
 * Keeps last 7 days of backups locally.
 *
 * Cron: 0 2 * * * cd /var/www/jikra && php artisan backup:database
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database
                            {--tenant= : Backup specific tenant only}
                            {--tag= : Tag the backup (e.g. pre-deploy, manual) — goes in filename}
                            {--retention=14 : Days to keep backups (default 14)}';
    protected $description = 'Backup PostgreSQL databases (all tenants + central)';

    public function handle(): int
    {
        $backupDir = storage_path('backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $tag = $this->option('tag') ?: 'scheduled';
        $date = now()->format('Y-m-d_His');
        $centralDb = config('database.connections.pgsql.database', 'jikra_central');
        $tenantModelClass = config('tenancy.tenant_model', \App\Models\Tenant::class);
        $tenantDbs = $tenantModelClass::all()->pluck('tenancy_db_name')->filter()->toArray();
        $databases = array_merge([$centralDb], $tenantDbs);

        if ($tenant = $this->option('tenant')) {
            $databases = [$tenant];
        }

        $host = config('database.connections.pgsql.host', '127.0.0.1');
        $port = config('database.connections.pgsql.port', '5432');
        $user = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');

        $success = 0;
        $failed = 0;

        foreach ($databases as $db) {
            $file = "{$backupDir}/{$db}_{$tag}_{$date}.sql.gz";
            $this->info("Backing up {$db} (tag: {$tag})...");

            $cmd = sprintf(
                'PGPASSWORD=%s pg_dump -h %s -p %s -U %s %s 2>/dev/null | gzip > %s',
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($user),
                escapeshellarg($db),
                escapeshellarg($file)
            );

            exec($cmd, $output, $exitCode);

            if ($exitCode === 0 && file_exists($file) && filesize($file) > 100) {
                $sizeMB = round(filesize($file) / 1024 / 1024, 2);
                $this->line("  Done: {$file} ({$sizeMB} MB)");
                $success++;
            } else {
                $this->error("  FAILED: {$db}");
                Log::error("Database backup failed", ['database' => $db, 'exit_code' => $exitCode]);
                $failed++;
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }

        // Clean up backups older than retention (default 14 days)
        // EXCEPT pre-deploy backups — those are kept 30 days for rollback safety
        $retentionDays = (int) $this->option('retention') ?: 14;
        $this->info("Cleaning backups older than {$retentionDays} days (pre-deploy kept 30d)...");
        $cutoff = now()->subDays($retentionDays)->timestamp;
        $preDeployCutoff = now()->subDays(30)->timestamp;
        $cleaned = 0;
        foreach (glob("{$backupDir}/*.sql.gz") as $oldFile) {
            $isPredeploy = str_contains(basename($oldFile), '_pre-deploy_');
            $threshold = $isPredeploy ? $preDeployCutoff : $cutoff;
            if (filemtime($oldFile) < $threshold) {
                unlink($oldFile);
                $cleaned++;
            }
        }
        if ($cleaned > 0) {
            $this->line("  Removed {$cleaned} old backup(s)");
        }

        $this->info("Backup complete. Success: {$success}, Failed: {$failed}");

        if ($failed > 0) {
            Log::critical("Database backup had {$failed} failure(s)");
            return 1;
        }

        return 0;
    }
}
