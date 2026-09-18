<?php

namespace Modules\Inventory\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Exceptions\InsufficientStockException;
use Modules\Accounting\Services\AccountingIntegrationService;
use Modules\Inventory\Models\AdjustmentReason;
use Modules\Inventory\Models\StockAdjustment;
use Modules\Inventory\Models\StockLedger;
use Modules\Inventory\Models\WarehouseStock;

class InventoryService
{
    public function __construct(
        private readonly AccountingIntegrationService $accountingService,
    ) {}


    // ── Core Stock Mutation ──

    /**
     * Central stock mutation method — ALL stock changes go through this.
     * Creates an immutable stock ledger entry and updates the single stock row.
     */
    public function adjustStock(
        int $productId,
        ?int $variantId,
        int $quantityChange,
        string $sourceType,
        int $sourceId,
        float $unitCost = 0,
        ?string $description = null
    ): StockLedger {
        return DB::transaction(function () use ($productId, $variantId, $quantityChange, $sourceType, $sourceId, $unitCost, $description) {
            // Get or create the stock row (with lock for concurrency)
            $stock = WarehouseStock::lockForUpdate()->firstOrCreate(
                ['product_id' => $productId, 'variant_id' => $variantId],
                ['quantity' => 0, 'reserved_quantity' => 0, 'reorder_level' => 0]
            );

            $quantityBefore = $stock->quantity;
            $quantityAfter = $quantityBefore + $quantityChange;

            // Prevent negative stock
            if ($quantityAfter < 0) {
                throw new InsufficientStockException(
                    "Insufficient stock for product #{$productId}. Available: {$quantityBefore}, Requested: " . abs($quantityChange)
                );
            }

            // Update stock
            $stock->update(['quantity' => $quantityAfter]);

            // Trigger low stock alert if below reorder level
            if ($quantityAfter > 0 && $stock->reorder_level > 0 && $quantityAfter <= $stock->reorder_level && $quantityBefore > $stock->reorder_level) {
                try {
                    $product = \Modules\Product\Models\Product::find($productId);
                    $admins = \App\Models\User::limit(10)->get();
                    foreach ($admins as $admin) {
                        $admin->notify(new \App\Notifications\LowStockAlert(
                            $product->name ?? "Product #{$productId}",
                            $quantityAfter,
                            $stock->reorder_level,
                        ));
                    }
                } catch (\Throwable $e) {
                    // Non-critical
                }
            }

            // Create immutable ledger entry
            return StockLedger::create([
                'product_id'      => $productId,
                'variant_id'      => $variantId,
                'source_type'     => $sourceType,
                'source_id'       => $sourceId,
                'quantity_before'  => $quantityBefore,
                'quantity_change'  => $quantityChange,
                'quantity_after'   => $quantityAfter,
                'unit_cost'       => $unitCost,
                'description'     => $description,
                'created_by'      => auth()->id(),
            ]);
        });
    }

    // ── Stock Queries ──

    public function getStockLevel(int $productId, ?int $variantId = null): int
    {
        $query = WarehouseStock::where('product_id', $productId);

        if ($variantId) {
            $query->where('variant_id', $variantId);
        }

        return $query->sum('quantity');
    }

    /**
     * Everything needing attention: nothing left, or down to the product's own
     * alert threshold.
     *
     * This keyed on warehouse_stock.reorder_level, which is 0 on every one of
     * the rows, and the scope guards on reorder_level > 0 — so the alerts page
     * could never report anything however low stock actually ran. The
     * threshold people do set is the product's min_stock_alert, already used
     * by the Products list and the Inventory Overview.
     *
     * Out-of-stock rows are included regardless of threshold: none left needs
     * attention whether or not anyone configured a level. The view splits the
     * two by quantity.
     */
    public function getLowStockProducts(): Collection
    {
        return $this->stockAlertQuery()
            ->where(function ($q) {
                $q->where('warehouse_stock.quantity', '<=', 0)
                    ->orWhere(fn ($w) => $w->lowByProductAlert());
            })
            ->get();
    }

    /**
     * The alerts page's two lists, paginated separately.
     *
     * They are separate queries rather than one list split in the view: the
     * two tables show different columns, and 120 rows rendered in one go is
     * what the page was doing before. Each carries its own page parameter so
     * paging one leaves the other where it was.
     */
    public function getStockAlerts(int $perPage = 25): array
    {
        $outOfStock = $this->stockAlertQuery()
            ->where('warehouse_stock.quantity', '<=', 0)
            ->orderBy('warehouse_stock.product_id')
            ->paginate($perPage, ['*'], 'oos_page')
            ->withQueryString();

        $lowStock = $this->stockAlertQuery()
            ->lowByProductAlert()
            ->orderBy('warehouse_stock.quantity')
            ->paginate($perPage, ['*'], 'low_page')
            ->withQueryString();

        return [
            'outOfStock' => $outOfStock,
            'lowStock'   => $lowStock,
            'total'      => $outOfStock->total() + $lowStock->total(),
        ];
    }

    /**
     * Shared base for the alert lists. whereHas('product') drops rows whose
     * product has been deleted — the tables read product->name straight out.
     */
    private function stockAlertQuery()
    {
        return WarehouseStock::query()
            ->whereHas('product')
            ->with(['product', 'variant']);
    }

    public function getStockHistory(int $productId, ?int $variantId = null, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return StockLedger::byProduct($productId)
            ->when($variantId, fn ($q) => $q->where('variant_id', $variantId))
            ->when($filters['source_type'] ?? null, fn ($q, $t) => $q->bySource($t))
            ->with(['creator'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Stock-ledger page data for a product: the paginated movement history plus
     * the summary cards (opening / total in / total out / closing), all scoped
     * to the same date-range + movement-type filters so the stats stay in step
     * with the table below them.
     *
     * Opening/closing are derived per variant (first quantity_before / last
     * quantity_after within the filtered window) and summed, so a multi-variant
     * product reports the correct combined balances.
     */
    public function getStockLedger(int $productId, array $filters = [], int $perPage = 20): array
    {
        // The page's "Movement Type" dropdown is a coarse grouping; map it to the
        // granular source_type values stored on each ledger row.
        $movementPatterns = [
            'sale'         => 'sale%',
            'purchase'     => 'purchase%',
            'adjustment'   => 'adjustment%',
            'transfer_in'  => 'transfer_in%',
            'transfer_out' => 'transfer_out%',
            'return'       => '%return%',
        ];

        $base = StockLedger::byProduct($productId)
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when(
                isset($filters['movement_type'], $movementPatterns[$filters['movement_type']]),
                fn ($q) => $q->where('source_type', 'like', $movementPatterns[$filters['movement_type']])
            );

        $history = (clone $base)->with(['creator', 'variant.attributeValues.attribute'])
            ->latest()->paginate($perPage)->withQueryString();

        $rows = (clone $base)->orderBy('created_at')->orderBy('id')
            ->get(['variant_id', 'quantity_before', 'quantity_change', 'quantity_after']);
        $byVariant = $rows->groupBy('variant_id');

        return [
            'history' => $history,
            'opening' => (int) $byVariant->sum(fn ($r) => (int) $r->first()->quantity_before),
            'in'      => (int) $rows->where('quantity_change', '>', 0)->sum('quantity_change'),
            'out'     => (int) abs($rows->where('quantity_change', '<', 0)->sum('quantity_change')),
            'closing' => (int) $byVariant->sum(fn ($r) => (int) $r->last()->quantity_after),
        ];
    }

    public function getStockLevels(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->stockLevelQuery($filters)
            ->with(['product', 'variant.attributeValues.attribute'])
            ->orderBy('product_id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Quantity and value across EVERY row matching the current filters, not
     * just the visible page — so the table's total row answers "how much stock
     * do we hold" rather than "how much is on screen".
     *
     * Valued at cost, preferring the variant's own cost price, which is the
     * same basis the ledger's Inventory account is built from.
     *
     * @return array{quantity: int, value: float}
     */
    public function getStockLevelTotals(array $filters = []): array
    {
        $row = $this->stockLevelQuery($filters)
            ->join('products', 'products.id', '=', 'warehouse_stock.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'warehouse_stock.variant_id')
            ->selectRaw('COALESCE(SUM(warehouse_stock.quantity), 0) AS total_quantity')
            ->selectRaw('COALESCE(SUM(warehouse_stock.quantity *
                COALESCE(NULLIF(product_variants.cost_price, 0), products.cost_price, 0)), 0) AS total_value')
            ->first();

        return [
            'quantity' => (int) ($row->total_quantity ?? 0),
            'value'    => (float) ($row->total_value ?? 0),
        ];
    }

    /**
     * The one place the stock list's filters are expressed, shared by the
     * paginated rows and the totals so the two can never disagree.
     */
    private function stockLevelQuery(array $filters)
    {
        return WarehouseStock::query()
            // Skip orphaned rows whose product was soft-deleted — they are not
            // actionable and would otherwise render as "Unknown".
            ->whereHas('product')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function ($q) use ($s) {
                // Reuse Product::scopeSearch so the stock list matches the same
                // fields as the product list — name, SKU, barcode AND model.
                $q->whereHas('product', fn ($q) => $q->search($s))
                  ->orWhereHas('variant', fn ($q) => $q->where('sku', 'like', "%{$s}%"));
            }))
            ->when(($filters['stock_status'] ?? null) === 'low_stock', fn ($q) => $q->lowByProductAlert())
            ->when(($filters['stock_status'] ?? null) === 'out_of_stock', fn ($q) => $q->where('quantity', 0))
            ->when(($filters['stock_status'] ?? null) === 'in_stock', fn ($q) => $q->where('quantity', '>', 0));
    }

    // ── Adjustments ──

    public function listAdjustments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return StockAdjustment::with([
                'items:id,stock_adjustment_id,product_id,quantity',
                'items.product:id,name',
                'creator:id,name',
                'adjustmentReason:id,name',
            ])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->when($filters['reason'] ?? null, fn ($q, $r) => $q->where('reason', $r))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('created_at', '<=', $d . ' 23:59:59'))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findAdjustment(int $id): StockAdjustment
    {
        return StockAdjustment::with([
            'items:id,stock_adjustment_id,product_id,variant_id,quantity,unit_cost,note',
            'items.product:id,name,sku',
            'items.variant:id,product_id,sku',
            'items.variant.attributeValues.attribute',
            'creator:id,name',
            'approver:id,name',
            'adjustmentReason:id,name',
        ])->findOrFail($id);
    }

    public function createAdjustment(array $data, array $items): StockAdjustment
    {
        return DB::transaction(function () use ($data, $items) {
            $reason = AdjustmentReason::findOrFail($data['reason_id']);

            $adjustment = StockAdjustment::create([
                'adjustment_number' => $this->generateAdjustmentNumber(),
                // Direction drives all stock math and reporting.
                'type'              => $data['type'],
                'reason_id'         => $reason->id,
                // Snapshot of the reason's name, so renaming or retiring a
                // reason never rewrites the history of past adjustments.
                'reason'            => $reason->name,
                'notes'             => $data['notes'] ?? null,
                'reference'         => $data['reference'] ?? null,
                'status'            => 'draft',
                'created_by'        => auth()->id(),
            ]);

            foreach ($items as $item) {
                $adjustment->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity'   => $item['quantity'],
                    'unit_cost'  => $item['unit_cost'] ?? 0,
                    'note'       => $item['note'] ?? null,
                ]);
            }

            return $adjustment->load('items.product');
        });
    }

    public function approveAdjustment(StockAdjustment $adjustment): void
    {
        if ($adjustment->status !== 'draft') {
            throw new \Exception('Only draft adjustments can be approved.');
        }

        DB::transaction(function () use ($adjustment) {
            $totalCostDelta = 0.0;

            foreach ($adjustment->items as $item) {
                $change = $adjustment->type === 'addition' ? $item->quantity : -$item->quantity;
                $totalCostDelta += $change * (float) $item->unit_cost;

                $this->adjustStock(
                    $item->product_id,
                    $item->variant_id,
                    $change,
                    'adjustment',
                    $adjustment->id,
                    $item->unit_cost,
                    "{$adjustment->type} adjustment: {$adjustment->reason}"
                );
            }

            $adjustment->update([
                'status'      => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            if (abs($totalCostDelta) > 0.01) {
                try {
                    $this->accountingService->recordInventoryAdjustment(
                        $adjustment->adjustment_number,
                        $totalCostDelta,
                        $adjustment->reason ?: $adjustment->type,
                        $adjustment->id,
                        'inventory_adjustment'
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to record inventory adjustment JE {$adjustment->adjustment_number}: {$e->getMessage()}");
                }
            }
        });
    }

    public function cancelAdjustment(StockAdjustment $adjustment): void
    {
        if ($adjustment->status === 'cancelled') {
            throw new \Exception('This adjustment is already cancelled.');
        }

        DB::transaction(function () use ($adjustment) {
            if ($adjustment->status === 'approved') {
                $adjustment->load('items');

                foreach ($adjustment->items as $item) {
                    $reverseChange = $adjustment->type === 'addition' ? -$item->quantity : $item->quantity;

                    try {
                        $this->adjustStock(
                            $item->product_id,
                            $item->variant_id,
                            $reverseChange,
                            'adjustment_cancel',
                            $adjustment->id,
                            $item->unit_cost,
                            "Cancelled {$adjustment->type} adjustment: {$adjustment->adjustment_number}"
                        );
                    } catch (\Throwable $e) {
                        \Log::warning("Failed to reverse stock on adjustment cancel: {$e->getMessage()}");
                    }
                }
            }

            try {
                $this->accountingService->voidJournalEntry('inventory_adjustment', $adjustment->id);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to void inventory adjustment JE {$adjustment->adjustment_number}: {$e->getMessage()}");
            }

            $adjustment->update(['status' => 'cancelled']);
        });
    }

    public function updateAdjustment(StockAdjustment $adjustment, array $data, array $items): StockAdjustment
    {
        if ($adjustment->status !== 'draft') {
            throw new \Exception('Only draft adjustments can be edited.');
        }

        return DB::transaction(function () use ($adjustment, $data, $items) {
            $reason = AdjustmentReason::findOrFail($data['reason_id']);

            $adjustment->update([
                'type'         => $data['type'],
                'reason_id'    => $reason->id,
                'reason'       => $reason->name,
                'notes'        => $data['notes'] ?? null,
                'reference'    => $data['reference'] ?? null,
            ]);

            $adjustment->items()->delete();

            foreach ($items as $item) {
                $adjustment->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity'   => $item['quantity'],
                    'unit_cost'  => $item['unit_cost'] ?? 0,
                    'note'       => $item['note'] ?? null,
                ]);
            }

            return $adjustment->fresh()->load('items.product');
        });
    }

    public function deleteAdjustment(StockAdjustment $adjustment): void
    {
        if ($adjustment->status !== 'draft') {
            throw new \Exception('Only draft adjustments can be deleted.');
        }

        DB::transaction(function () use ($adjustment) {
            $adjustment->items()->delete();
            $adjustment->delete();
        });
    }

    // ── Stats ──

    public function getStats(): array
    {
        return [
            'total_products'     => WarehouseStock::whereHas('product')->distinct('product_id')->count('product_id'),
            'low_stock_count'    => WarehouseStock::lowByProductAlert()->count(),
            'total_stock_value'  => WarehouseStock::join('products', 'warehouse_stock.product_id', '=', 'products.id')
                ->whereNull('products.deleted_at')
                ->selectRaw('SUM(warehouse_stock.quantity * products.cost_price) as total')
                ->value('total') ?? 0,
        ];
    }

    // ── Number Generation ──

    private function generateAdjustmentNumber(): string
    {
        $year = now()->format('Y');
        $last = StockAdjustment::whereYear('created_at', $year)->count() + 1;

        return 'ADJ-' . $year . '-' . str_pad($last, 4, '0', STR_PAD_LEFT);
    }
}
