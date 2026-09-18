<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LocalizeWrap extends Command
{
    protected $signature = 'localize:wrap
        {--dry-run : Preview changes without writing files}
        {--module= : Process a single module only}';

    protected $description = 'Auto-wrap hardcoded English text with __() translation helpers';

    private int $totalWrapped = 0;
    private int $totalFiles = 0;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $module = $this->option('module');

        $this->info($dryRun ? '🔍 DRY RUN — no files will be modified' : '✏️  Wrapping hardcoded text with __()...');

        // Process Blade files
        $this->processBladeFiles($dryRun, $module);

        // Process PHP controllers/services
        $this->processPhpFiles($dryRun, $module);

        $this->newLine();
        $this->info("✅ Done! {$this->totalWrapped} strings wrapped across {$this->totalFiles} files.");

        return 0;
    }

    private function processBladeFiles(bool $dryRun, ?string $module): void
    {
        $pattern = $module
            ? base_path("Modules/{$module}/resources/views/**/*.blade.php")
            : base_path('Modules/*/resources/views/**/*.blade.php');

        $files = $this->globRecursive(base_path('Modules'), '*.blade.php', $module);

        // Exclude storefront views (customer-facing, not admin)
        $files = array_filter($files, fn ($f) => !str_contains($f, 'storefront'));

        foreach ($files as $file) {
            $original = File::get($file);
            $content = $original;
            $count = 0;

            // Pattern 1: @section('title', 'Static Text') and @section('page-title', 'Static Text')
            $content = preg_replace_callback(
                "/@section\('(title|page-title)',\s*'([^']+)'\)/",
                function ($m) use (&$count) {
                    if ($this->isAlreadyWrapped($m[2])) return $m[0];
                    $count++;
                    return "@section('{$m[1]}', __(\"{$m[2]}\"))";
                },
                $content
            );

            // Pattern 2: Text between HTML tags — >English Text</tag>
            // Matches: >Text Here</ but NOT >{{ ... }}</ or >@...</ or ></ or >  </
            $content = preg_replace_callback(
                '/>([^<>{}\n]+?)<\//',
                function ($m) use (&$count) {
                    $text = trim($m[1]);
                    if (!$this->isTranslatableText($text)) return $m[0];
                    if ($this->isAlreadyWrapped($m[1])) return $m[0];
                    $count++;
                    // Preserve leading/trailing whitespace from original
                    $leading = $m[1] !== ltrim($m[1]) ? ' ' : '';
                    $trailing = $m[1] !== rtrim($m[1]) ? ' ' : '';
                    return ">{$leading}{{ __('" . $this->escapeForPhp($text) . "') }}{$trailing}</";
                },
                $content
            );

            // Pattern 3: placeholder="English text" (not containing {{ }})
            $content = preg_replace_callback(
                '/placeholder="([^"{}]+)"/',
                function ($m) use (&$count) {
                    $text = trim($m[1]);
                    if (!$this->isTranslatableText($text)) return $m[0];
                    $count++;
                    return 'placeholder="{{ __(\'' . $this->escapeForPhp($text) . '\') }}"';
                },
                $content
            );

            // Pattern 4: title="English text" on buttons/links (not HTML lang/meta tags)
            $content = preg_replace_callback(
                '/\btitle="([^"{}]+)"/',
                function ($m) use (&$count) {
                    $text = trim($m[1]);
                    if (!$this->isTranslatableText($text)) return $m[0];
                    // Skip non-user-facing titles
                    if (in_array(strtolower($text), ['dashboard', 'home'])) {
                        // These are fine to translate
                    }
                    $count++;
                    return 'title="{{ __(\'' . $this->escapeForPhp($text) . '\') }}"';
                },
                $content
            );

            if ($count > 0 && $content !== $original) {
                $rel = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file);
                $this->line("  <fg=cyan>{$rel}</> — <fg=yellow>{$count} strings</>");
                $this->totalWrapped += $count;
                $this->totalFiles++;

                if (!$dryRun) {
                    File::put($file, $content);
                }
            }
        }
    }

    private function processPhpFiles(bool $dryRun, ?string $module): void
    {
        $files = $this->globRecursive(base_path('Modules'), '*.php', $module, 'app');

        foreach ($files as $file) {
            $original = File::get($file);
            $content = $original;
            $count = 0;

            // Pattern: ->with('success'|'error'|'warning', 'English message')
            $content = preg_replace_callback(
                "/->with\('(success|error|warning)',\s*'([^']+)'\)/",
                function ($m) use (&$count) {
                    if (str_contains($m[2], '__(' ) || str_contains($m[2], '$')) return $m[0];
                    $count++;
                    return "->with('{$m[1]}', __('" . $this->escapeForPhp($m[2]) . "'))";
                },
                $content
            );

            // Pattern: ->with('success', "English message with {$var}")
            $content = preg_replace_callback(
                '/->with\(\'(success|error|warning)\',\s*"([^"]+)"\)/',
                function ($m) use (&$count) {
                    if (str_contains($m[2], '__(')) return $m[0];
                    // Skip strings with PHP variables — too complex for auto-wrap
                    if (preg_match('/\$[a-zA-Z]/', $m[2])) return $m[0];
                    $count++;
                    return "->with('{$m[1]}', __(\"" . $m[2] . '"))';
                },
                $content
            );

            if ($count > 0 && $content !== $original) {
                $rel = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file);
                $this->line("  <fg=cyan>{$rel}</> — <fg=yellow>{$count} strings</>");
                $this->totalWrapped += $count;
                $this->totalFiles++;

                if (!$dryRun) {
                    File::put($file, $content);
                }
            }
        }
    }

    private function isTranslatableText(string $text): bool
    {
        // Must be at least 2 chars
        if (strlen($text) < 2) return false;

        // Must contain at least one letter
        if (!preg_match('/[a-zA-Z]/', $text)) return false;

        // Skip if already wrapped
        if (str_contains($text, '__(') || str_contains($text, '{{ ')) return false;

        // Skip Blade variables
        if (str_contains($text, '{{') || str_contains($text, '@')) return false;

        // Skip icon classes
        if (preg_match('/^fa-/', $text) || str_contains($text, 'fa-solid')) return false;

        // Skip CSS classes
        if (preg_match('/^(bp-|btn-|col-|text-|d-|mb-|mt-|me-|ms-|p-|g-)/', $text)) return false;

        // Skip route-like strings
        if (preg_match('/\.\w+\./', $text)) return false;

        // Skip file paths
        if (str_contains($text, '/') || str_contains($text, '\\')) return false;

        // Skip numbers only
        if (preg_match('/^[\d,.\s%৳$]+$/', $text)) return false;

        // Skip single special chars
        if (preg_match('/^[*\-—–|:;#&]+$/', $text)) return false;

        // Skip Bengali text (already translated)
        if (preg_match('/[\x{0980}-\x{09FF}]/u', $text)) return false;

        return true;
    }

    private function isAlreadyWrapped(string $text): bool
    {
        return str_contains($text, '__(') || str_contains($text, '{{ ');
    }

    private function escapeForPhp(string $text): string
    {
        return str_replace("'", "\\'", trim($text));
    }

    private function globRecursive(string $base, string $pattern, ?string $module, string $subDir = 'resources'): array
    {
        $dirs = $module
            ? ["{$base}/{$module}"]
            : glob("{$base}/*", GLOB_ONLYDIR);

        $files = [];
        foreach ($dirs as $dir) {
            $searchDir = "{$dir}/{$subDir}";
            if (!is_dir($searchDir)) continue;

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($searchDir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && fnmatch($pattern, $file->getFilename())) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
