<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Product\Models\Product;

class ProductsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return Product::with(['category', 'brand'])
            ->when($this->filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"))
            ->when($this->filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($this->filters['brand_id'] ?? null, fn ($q, $v) => $q->where('brand_id', $v))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->latest();
    }

    public function headings(): array
    {
        return [
            'Name',
            'SKU',
            'Barcode',
            'Category',
            'Brand',
            'Cost Price',
            'Sell Price',
            'VAT Rate',
            'Status',
            'Product Type',
        ];
    }

    public function map($product): array
    {
        return [
            $product->name,
            $product->sku,
            $product->barcode,
            $product->category->name ?? '—',
            $product->brand->name ?? '—',
            $product->cost_price,
            $product->sell_price,
            $product->vat_rate . '%',
            ucfirst($product->status),
            ucfirst($product->product_type),
        ];
    }
}
