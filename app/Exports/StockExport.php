<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        return DB::table('warehouse_stock')
            ->join('products', 'warehouse_stock.product_id', '=', 'products.id')
            ->select('products.name', 'products.sku', 'warehouse_stock.quantity', 'warehouse_stock.reserved_quantity', 'warehouse_stock.reorder_level', 'products.cost_price')
            ->orderBy('products.name');
    }

    public function headings(): array
    {
        return ['Product', 'SKU', 'Quantity', 'Reserved', 'Reorder Level', 'Unit Cost', 'Stock Value'];
    }

    public function map($row): array
    {
        return [
            $row->name, $row->sku,
            $row->quantity, $row->reserved_quantity, $row->reorder_level,
            $row->cost_price, $row->quantity * $row->cost_price,
        ];
    }
}
