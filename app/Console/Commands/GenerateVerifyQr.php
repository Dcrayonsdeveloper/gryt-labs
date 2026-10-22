<?php

namespace App\Console\Commands;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Console\Command;

class GenerateVerifyQr extends Command
{
    protected $signature = 'qr:verify-page
        {--url= : URL the QR points at (defaults to the tenant store URL + /v)}
        {--size=1200 : PNG edge length in pixels}
        {--dir= : Output directory (defaults to storage/app/qr)}';

    protected $description = 'Generate the printable QR code for the /v product landing page';

    public function handle(): int
    {
        $url = $this->option('url') ?: rtrim(config('app.url'), '/').'/v';
        $size = max(300, (int) $this->option('size'));
        $dir = $this->option('dir') ?: storage_path('app/qr');

        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            $this->error("Could not create output directory: {$dir}");

            return self::FAILURE;
        }

        $svgPath = $dir.DIRECTORY_SEPARATOR.'verify-qr.svg';
        $pngPath = $dir.DIRECTORY_SEPARATOR.'verify-qr.png';

        // High error correction: label QRs get scratched, smudged and curved around a
        // bottle, and this level still scans with roughly a third of the code damaged.
        // SVG is the file to hand the printer — vector, so it stays sharp at any label size.
        foreach ([$svgPath => new SvgWriter(), $pngPath => new PngWriter()] as $path => $writer) {
            (new Builder(
                writer: $writer,
                data: $url,
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: $size,
                margin: (int) round($size * 0.04),
            ))->build()->saveToFile($path);
        }

        $this->info('QR code generated.');
        $this->newLine();
        $this->line("  Points at:  <fg=cyan>{$url}</>");
        $this->line("  For print:  <fg=green>{$svgPath}</>  (vector — give this one to the printer)");
        $this->line("  Preview:    {$pngPath}  ({$size}x{$size}px)");
        $this->newLine();
        $this->comment('Print at 2cm or larger, keep the white border, and test a scan from the actual label before the full run.');

        return self::SUCCESS;
    }
}
