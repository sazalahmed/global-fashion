<?php

namespace App\Console\Commands;

use App\Helpers\Upload;
use Illuminate\Console\Command;

class GenerateWebpImages extends Command
{
    protected $signature = 'images:webp
        {--path= : Public-relative directory to scan (default: the uploads directory)}
        {--force : Regenerate even if a .webp already exists}';

    protected $description = 'Generate .webp variants for JPG/PNG images under a public directory (uploads by default)';

    public function handle(): int
    {
        $relPath = trim((string) ($this->option('path') ?: Upload::BASE), '/\\');
        $base = public_path($relPath);
        if (! is_dir($base)) {
            $this->warn('No directory found at ' . $base);
            return self::SUCCESS;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );

        $made = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                continue;
            }
            $abs = $file->getPathname();
            if (! $this->option('force') && is_file($abs . '.webp')) {
                $skipped++;
                continue;
            }
            if (Upload::makeWebp($abs)) {
                $made++;
            } else {
                $failed++;
            }
        }

        $this->info("WebP backfill complete for /{$relPath}: {$made} created, {$skipped} skipped, {$failed} failed.");

        return self::SUCCESS;
    }
}
