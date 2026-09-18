<?php

namespace Modules\Product\Console\Commands;

use App\Helpers\Upload;
use Illuminate\Console\Command;
use Modules\Product\Models\Product;

/**
 * Repairs product `thumbnail` paths that point to a file which no longer
 * exists at the stored path.
 *
 * Two cases are corrected:
 *  1. The original was webp-converted: the stored name (e.g. "x.jpg") is gone
 *     but a ".webp" sibling ("x.jpg.webp") exists -> rewrite to the sibling.
 *  2. The thumbnail file is entirely gone (no webp either) -> null the column
 *     so single-image displays fall back to the gallery image (Product::display_image).
 *
 * Products whose thumbnail file is present are left untouched.
 */
class FixProductThumbnails extends Command
{
    protected $signature = 'product:fix-thumbnails
        {--dry-run : Show what would change without writing to the database}';

    protected $description = 'Repair product thumbnail paths that point to missing files (webp sibling rewrite or null for gallery fallback)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $rewritten = 0;
        $nulled = 0;
        $ok = 0;
        $rows = [];

        Product::query()
            ->whereNotNull('thumbnail')
            ->where('thumbnail', '!=', '')
            ->select('id', 'name', 'thumbnail')
            ->chunkById(200, function ($products) use (&$rewritten, &$nulled, &$ok, &$rows, $dry) {
                foreach ($products as $p) {
                    $thumb = $p->thumbnail;

                    // File present at the stored path — nothing to do.
                    if (Upload::exists($thumb)) {
                        $ok++;
                        continue;
                    }

                    // Webp sibling exists (original was converted + removed).
                    if (Upload::exists($thumb . '.webp')) {
                        $rows[] = [$p->id, $this->trim($p->name), $thumb, $thumb . '.webp'];
                        if (! $dry) {
                            $p->forceFill(['thumbnail' => $thumb . '.webp'])->save();
                        }
                        $rewritten++;
                        continue;
                    }

                    // Truly missing — clear it so display_image falls back to gallery.
                    $rows[] = [$p->id, $this->trim($p->name), $thumb, '(nulled → gallery)'];
                    if (! $dry) {
                        $p->forceFill(['thumbnail' => null])->save();
                    }
                    $nulled++;
                }
            });

        if ($rows) {
            $this->table(['ID', 'Product', 'Old thumbnail', 'New value'], $rows);
        }

        $verb = $dry ? 'Would fix' : 'Fixed';
        $this->info(sprintf(
            '%s: %d rewritten to .webp, %d nulled (gallery fallback). %d already valid.',
            $verb,
            $rewritten,
            $nulled,
            $ok
        ));

        if ($dry && ($rewritten + $nulled) > 0) {
            $this->comment('Dry run — no changes saved. Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }

    private function trim(?string $name, int $len = 32): string
    {
        $name = (string) $name;

        return mb_strlen($name) > $len ? mb_substr($name, 0, $len - 1) . '…' : $name;
    }
}
