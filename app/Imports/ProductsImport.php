<?php

namespace App\Imports;

use Modules\Product\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\Importable;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError
{
    use Importable, SkipsErrors;

    private int $imported = 0;

    public function model(array $row): ?Product
    {
        // Skip if product with same SKU already exists
        if (!empty($row['sku']) && Product::where('sku', $row['sku'])->exists()) {
            return null;
        }

        $this->imported++;

        return new Product([
            'name'         => $row['name'],
            'sku'          => $row['sku'] ?? $this->generateSku(),
            'barcode'      => $row['barcode'] ?? null,
            'cost_price'   => $row['cost_price'] ?? 0,
            'sell_price'   => $row['sell_price'] ?? 0,
            'vat_rate'     => $row['vat_rate'] ?? 0,
            'description'  => $row['description'] ?? null,
            'status'       => $row['status'] ?? 'active',
            'product_type' => $row['product_type'] ?? 'standard',
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku'  => 'nullable|string|max:100',
            'cost_price' => 'nullable|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    private function generateSku(): string
    {
        $prefix = 'PRD-';
        $last = Product::where('sku', 'like', $prefix . '%')->orderByDesc('sku')->value('sku');
        $nextSeq = $last ? ((int) str_replace($prefix, '', $last)) + 1 : 1;

        return $prefix . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
    }
}
