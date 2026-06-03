<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Bulk image optimizer for storage/app/public/.
 *
 * Shells out to ImageMagick (`convert`) — must be installed on host (it is on prod).
 * Idempotent: skips files that already have a `.original-*` sibling.
 *
 * Examples:
 *   php artisan images:optimize                                    # dry-run, scans default path
 *   php artisan images:optimize --path=storage/app/public/products # dry-run, narrower scope
 *   php artisan images:optimize --apply                            # actually modify files
 *   php artisan images:optimize --apply --threshold=300            # process anything >300 KB
 */
class OptimizeStorageImages extends Command
{
    protected $signature = 'images:optimize
                            {--path=storage/app/public : Subpath under base_path() to scan recursively}
                            {--threshold=500 : Skip files smaller than this many KB}
                            {--max-width=1200 : Resize images wider than this (px). Aspect ratio preserved.}
                            {--jpg-quality=85 : JPEG re-encode quality (1-100)}
                            {--png-colors=256 : Convert PNG to PNG8 palette of this many colors. Set 0 to keep RGBA.}
                            {--apply : Actually modify files. Default is dry-run.}
                            {--no-backup : Skip writing .original-<date> backup. Not recommended.}';

    protected $description = 'Bulk-resize and recompress oversized images in storage/. Dry-run by default.';

    private int $scanned = 0;
    private int $skippedSmall = 0;
    private int $skippedAlreadyDone = 0;
    private int $skippedUnsupported = 0;
    private int $modified = 0;
    private int $errors = 0;
    private int $bytesBefore = 0;
    private int $bytesAfter = 0;

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $thresholdKb = (int) $this->option('threshold');
        $thresholdBytes = $thresholdKb * 1024;
        $maxWidth = (int) $this->option('max-width');
        $jpgQuality = (int) $this->option('jpg-quality');
        $pngColors = (int) $this->option('png-colors');
        $writeBackup = ! $this->option('no-backup');

        $path = base_path($this->option('path'));
        if (! is_dir($path)) {
            $this->error("Path does not exist: {$path}");
            return self::FAILURE;
        }

        if (! $this->commandAvailable('convert')) {
            $this->error('ImageMagick `convert` not found in PATH. Install with: sudo apt-get install imagemagick');
            return self::FAILURE;
        }

        $mode = $apply ? 'APPLY' : 'DRY-RUN';
        $this->info("[{$mode}] Scanning {$path}");
        $this->info("  threshold: {$thresholdKb} KB | max-width: {$maxWidth}px | jpg-quality: {$jpgQuality} | png-colors: {$pngColors}");
        $this->newLine();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        $backupDate = now()->format('Y-m-d');

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $this->processFile($file->getPathname(), [
                'apply' => $apply,
                'threshold_bytes' => $thresholdBytes,
                'max_width' => $maxWidth,
                'jpg_quality' => $jpgQuality,
                'png_colors' => $pngColors,
                'write_backup' => $writeBackup,
                'backup_date' => $backupDate,
            ]);
        }

        $this->newLine();
        $this->printSummary($apply);

        return $this->errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function processFile(string $path, array $opts): void
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            return;
        }

        $this->scanned++;

        if (str_contains($path, '.original-')) {
            return;
        }

        if (glob($path . '.original-*')) {
            $this->skippedAlreadyDone++;
            return;
        }

        $sizeBefore = filesize($path);
        if ($sizeBefore < $opts['threshold_bytes']) {
            $this->skippedSmall++;
            return;
        }

        [$width, $height] = @getimagesize($path) ?: [0, 0];
        if ($width === 0) {
            $this->skippedUnsupported++;
            return;
        }

        $tmpOutput = $path . '.tmp-' . bin2hex(random_bytes(4));
        $resizeArg = $width > $opts['max_width'] ? "{$opts['max_width']}x>" : '';
        $args = ['convert', $path, '-strip', '-auto-orient'];
        if ($resizeArg !== '') {
            $args[] = '-resize';
            $args[] = $resizeArg;
        }
        if ($ext === 'png') {
            $args[] = '-define';
            $args[] = 'png:compression-level=9';
            if ($opts['png_colors'] > 0) {
                $args[] = '-colors';
                $args[] = (string) $opts['png_colors'];
                $tmpOutput = 'PNG8:' . $tmpOutput;
            }
        } else {
            $args[] = '-quality';
            $args[] = (string) $opts['jpg_quality'];
            $args[] = '-interlace';
            $args[] = 'Plane';
            $args[] = '-sampling-factor';
            $args[] = '4:2:0';
        }
        $args[] = $tmpOutput;

        $tmpRealPath = str_starts_with($tmpOutput, 'PNG8:') ? substr($tmpOutput, 5) : $tmpOutput;

        $process = new Process($args);
        $process->setTimeout(60);
        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            $this->errors++;
            $this->warn("  ✗ convert failed: {$path}");
            @unlink($tmpRealPath);
            return;
        }

        if (! file_exists($tmpRealPath)) {
            $this->errors++;
            @unlink($tmpRealPath);
            return;
        }

        $sizeAfter = filesize($tmpRealPath);
        if ($sizeAfter >= $sizeBefore) {
            unlink($tmpRealPath);
            $this->skippedAlreadyDone++;
            return;
        }

        $this->bytesBefore += $sizeBefore;
        $this->bytesAfter += $sizeAfter;
        $this->modified++;

        $relPath = str_replace(base_path() . '/', '', $path);
        $pctSaved = round((1 - $sizeAfter / $sizeBefore) * 100, 1);
        $this->line(sprintf(
            '  %s  %s  %dx%d  %s → %s  (-%s%%)',
            $opts['apply'] ? '✔' : '·',
            $relPath,
            $width,
            $height,
            $this->formatBytes($sizeBefore),
            $this->formatBytes($sizeAfter),
            $pctSaved
        ));

        if (! $opts['apply']) {
            unlink($tmpRealPath);
            return;
        }

        if ($opts['write_backup']) {
            $backupPath = $path . '.original-' . $opts['backup_date'];
            if (! file_exists($backupPath) && ! @copy($path, $backupPath)) {
                $this->errors++;
                $this->warn("  ✗ failed to write backup: {$backupPath}");
                unlink($tmpRealPath);
                return;
            }
        }

        if (! @rename($tmpRealPath, $path)) {
            $this->errors++;
            $this->warn("  ✗ failed to replace: {$path}");
            unlink($tmpRealPath);
            return;
        }

        @chown($path, 'www-data');
        @chgrp($path, 'www-data');
        @chmod($path, 0664);
    }

    private function printSummary(bool $apply): void
    {
        $saved = $this->bytesBefore - $this->bytesAfter;
        $verb = $apply ? 'modified' : 'would modify';

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  SUMMARY');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("  Scanned:                {$this->scanned}");
        $this->line("  Skipped (under threshold): {$this->skippedSmall}");
        $this->line("  Skipped (already done):    {$this->skippedAlreadyDone}");
        $this->line("  Skipped (unsupported):     {$this->skippedUnsupported}");
        $this->line("  Errors:                    {$this->errors}");
        $this->line("  Files {$verb}:           {$this->modified}");
        $this->line("  Total before:              " . $this->formatBytes($this->bytesBefore));
        $this->line("  Total after:               " . $this->formatBytes($this->bytesAfter));
        $this->line("  Bytes saved:               " . $this->formatBytes($saved));
        $this->newLine();

        if (! $apply && $this->modified > 0) {
            $this->warn('Dry-run only. Re-run with --apply to actually modify files.');
        }
    }

    private function commandAvailable(string $cmd): bool
    {
        $proc = new Process(['which', $cmd]);
        $proc->run();
        return $proc->isSuccessful();
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $val = (float) $bytes;
        while ($val >= 1024 && $i < count($units) - 1) {
            $val /= 1024;
            $i++;
        }
        return round($val, 1) . ' ' . $units[$i];
    }
}
