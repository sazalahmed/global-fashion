<?php

namespace App\Console\Commands;

use App\Services\PwaIconGenerator;
use Illuminate\Console\Command;
use Modules\Setting\Models\Setting;

class GeneratePwaIcons extends Command
{
    protected $signature = 'pwa:icons
        {--source= : Public-relative path to a source image (defaults to the business favicon)}';

    protected $description = 'Generate the PWA icon set (public/images/icons) from the brand favicon or a given image';

    public function handle(PwaIconGenerator $generator): int
    {
        $source = $this->option('source') ?: Setting::get('business', 'favicon');

        if (empty($source)) {
            $this->warn('No source image. Set a favicon in Business Profile or pass --source=uploads/...');

            return self::FAILURE;
        }

        // Resolve to an absolute file: prefer public/, fall back to the legacy
        // "public" disk path that Upload::url() understands.
        $absolute = public_path($source);
        if (! is_file($absolute)) {
            $this->error('Source image not found: ' . $absolute);

            return self::FAILURE;
        }

        if ($generator->generateFromFile($absolute)) {
            $this->info('PWA icons generated in public/images/icons from ' . $source);

            return self::SUCCESS;
        }

        $this->error('Failed to generate icons. Use a PNG/WebP/JPG source (ICO is not supported).');

        return self::FAILURE;
    }
}
