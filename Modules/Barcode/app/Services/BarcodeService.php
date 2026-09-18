<?php

namespace Modules\Barcode\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Modules\Product\Models\Product;

class BarcodeService
{
    /**
     * Search products by name, SKU, or barcode.
     * Returns up to 10 results for the search dropdown.
     */
    public function searchProducts(string $query): Collection
    {
        $products = Product::where('status', '!=', 'inactive')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('sku', 'like', '%' . $query . '%')
                  ->orWhere('barcode', 'like', '%' . $query . '%');
            })
            ->select(['id', 'name', 'sku', 'barcode', 'sell_price'])
            ->limit(10)
            ->get();

        return $this->attachCurrentStock($products);
    }

    /**
     * Get products by IDs for barcode generation.
     */
    public function getProductsByIds(array $ids): Collection
    {
        $products = Product::whereIn('id', $ids)
            ->select(['id', 'name', 'sku', 'barcode', 'sell_price'])
            ->get();

        return $this->attachCurrentStock($products);
    }

    /**
     * Attach `current_stock` (sum of warehouse_stock.quantity, ignoring
     * variants) to each product as a dynamic attribute.
     * Used by the barcode generator to default label QTY to on-hand stock.
     */
    protected function attachCurrentStock(Collection $products): Collection
    {
        $ids = $products->pluck('id')->all();
        if (empty($ids)) {
            return $products;
        }

        $stocks = DB::table('warehouse_stock')
            ->select('product_id', DB::raw('SUM(quantity) as total'))
            ->whereIn('product_id', $ids)
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        return $products->each(function ($p) use ($stocks) {
            $p->current_stock = (int) ($stocks[$p->id] ?? 0);
        });
    }

    /**
     * Generate EAN-13 barcode number.
     */
    public function generateEan13(): string
    {
        do {
            $code = '880' . str_pad(random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $sum += (int) $code[$i] * ($i % 2 === 0 ? 1 : 3);
            }
            $checkDigit = (10 - ($sum % 10)) % 10;
            $barcode = $code . $checkDigit;
        } while (Product::where('barcode', $barcode)->exists());

        return $barcode;
    }

    /**
     * Validate EAN-13 check digit.
     */
    public function validateEan13(string $code): bool
    {
        if (strlen($code) !== 13 || !ctype_digit($code)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $code[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        $checkDigit = (10 - ($sum % 10)) % 10;

        return (int) $code[12] === $checkDigit;
    }
}
