<?php

namespace Modules\Variant\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Models\VariantAttributeValue;
use Modules\Variant\Services\VariantService;

/**
 * Gives every "simple" product (one with no variants yet) a Color × Size
 * variant matrix and converts it to a "variable" product so the variants
 * surface in the POS / Sale / Purchase / Quotation flows.
 *
 * A subset of values is used (3 colours × 3 sizes = 9 variants each) to keep
 * the matrix realistic. Re-running is safe: only simple products are touched,
 * and VariantService::generateVariants() is idempotent per combination.
 */
class SimpleProductVariantSeeder extends Seeder
{
    /** Colour values to use (matched by name against the Color attribute). */
    private array $colors = ['Red', 'Blue', 'Black'];

    /** Size values to use (matched by name against the Size attribute). */
    private array $sizes = ['S', 'M', 'L'];

    public function run(): void
    {
        $colorAttr = VariantAttribute::where('name', 'Color')->first();
        $sizeAttr = VariantAttribute::where('name', 'Size')->first();

        if (! $colorAttr || ! $sizeAttr) {
            $this->command?->warn('Color/Size attributes not found — run VariantAttributeSeeder first. Skipping.');
            return;
        }

        $colorIds = VariantAttributeValue::where('variant_attribute_id', $colorAttr->id)
            ->whereIn('value', $this->colors)->pluck('id')->all();
        $sizeIds = VariantAttributeValue::where('variant_attribute_id', $sizeAttr->id)
            ->whereIn('value', $this->sizes)->pluck('id')->all();

        if (empty($colorIds) || empty($sizeIds)) {
            $this->command?->warn('No matching Color/Size values found. Skipping.');
            return;
        }

        $attributeValueIds = [
            $colorAttr->id => $colorIds,
            $sizeAttr->id  => $sizeIds,
        ];

        $variantService = app(VariantService::class);

        // Target only simple products that currently have no variants.
        $products = Product::where('product_type', 'simple')
            ->whereDoesntHave('variants')
            ->get();

        if ($products->isEmpty()) {
            $this->command?->info('No simple products without variants to process.');
            return;
        }

        DB::transaction(function () use ($products, $variantService, $attributeValueIds) {
            foreach ($products as $product) {
                $product->update(['product_type' => 'variable']);
                $created = $variantService->generateVariants($product->fresh(), $attributeValueIds);
                $this->command?->info("✓ {$product->name}: {$created->count()} variants");
            }
        });

        $this->command?->info("Done — {$products->count()} product(s) converted to variable.");
    }
}
