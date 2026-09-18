<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Services\InventoryService;
use Modules\Inventory\Models\WarehouseStock;
use Modules\Product\Models\Product;

class InventoryStockSeeder extends Seeder
{
    /**
     * Seed opening stock for every stock-tracked product / variant.
     *
     * Goes through InventoryService::adjustStock() so each row gets a matching
     * immutable 'opening_stock' stock_ledger entry — keeping warehouse_stock and
     * the ledger consistent, exactly like a real stock movement.
     *
     * Idempotent: products/variants that already have a stock row are skipped,
     * so re-running won't double-count.
     */
    public function run(): void
    {
        /** @var InventoryService $inventory */
        $inventory = app(InventoryService::class);

        $seeded = 0;

        $products = Product::with('variants')
            ->where('track_stock', true)
            ->get();

        foreach ($products as $product) {
            if ($product->variants->isNotEmpty()) {
                foreach ($product->variants as $variant) {
                    $seeded += $this->seedRow(
                        $inventory,
                        $product->id,
                        $variant->id,
                        (float) ($variant->cost_price ?? $product->cost_price)
                    );
                }
            } else {
                $seeded += $this->seedRow(
                    $inventory,
                    $product->id,
                    null,
                    (float) $product->cost_price
                );
            }
        }

        $this->command->info("Seeded opening stock for {$seeded} product/variant rows.");
    }

    /**
     * Seed a single stock row if it doesn't already exist. Returns 1 if a row
     * was created, 0 if it was skipped.
     */
    private function seedRow(InventoryService $inventory, int $productId, ?int $variantId, float $unitCost): int
    {
        $exists = WarehouseStock::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->exists();

        if ($exists) {
            return 0;
        }

        // Random but realistic opening quantity.
        $quantity = random_int(25, 200);

        $inventory->adjustStock(
            productId: $productId,
            variantId: $variantId,
            quantityChange: $quantity,
            sourceType: 'opening_stock',
            sourceId: 0,
            unitCost: $unitCost,
            description: 'Opening stock (seeded)'
        );

        return 1;
    }
}
