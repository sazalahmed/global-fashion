# Remaining Modules — Complete Implementation Plan

## Context
BizPOS Pro has 35 modules total. 20 are fully implemented with backend (Product, Category, Brand, Unit, Warehouse, Variant, Sale, Customer, Supplier, Purchase, PurchaseReturn, Branch, User, Role, Accounting, Expense, Payment, POS, Barcode, Core). **15 modules remain as stubs** — they have controllers returning views with hardcoded demo data but no models, services, migrations, or real data.

This plan covers all 15 remaining modules with exact schemas, services, and view wiring.

**Architecture pattern (established):** Migration → Model → Service → FormRequest → Controller (thin) → View wiring. All money as `decimal(15,2)`. SoftDeletes on transactional data. Services injected via constructor. `DB::transaction()` for multi-table writes.

**Key integration points:**
- `JournalEntryService::createFromSource()` — auto-creates posted journal entries (used by Payroll, Asset)
- `PaymentService::create()` — records payments with journal entries (used by Installment)
- `InventoryService` — central gateway for all stock mutations (new, used by SaleReturn)
- `AccountingIntegrationService` — bridges Sale/Purchase to accounting journal

---

## Implementation Order

| Priority | Module | Dependencies | Est. Files |
|---|---|---|---|
| 1 | **Inventory** | Warehouse, Product, Branch | ~15 |
| 2 | **SaleReturn** | Sale, Inventory, Accounting | ~8 |
| 3 | **Setting** | None | ~5 |
| 4 | **Quotation** | Customer, Product, Branch, Sale | ~10 |
| 5 | **Delivery** | Sale, Customer, Branch | ~10 |
| 6 | **Dashboard** | All modules (read-only) | ~2 |
| 7 | **Report** | All modules (read-only) | ~8 |
| 8 | **Employee** | Branch | ~8 |
| 9 | **Attendance** | Employee, Branch | ~10 |
| 10 | **Payroll** | Employee, Attendance, Accounting | ~12 |
| 11 | **Installment** | Sale, Customer, Payment | ~8 |
| 12 | **Asset** | Branch, Accounting | ~10 |
| 13 | **Ecommerce** | Product, Customer, Sale, Delivery | ~15 |
| 14 | **Marketing** | Customer | ~10 |
| 15 | **Activity** | None (spatie/laravel-activitylog) | ~3 |

**Total: ~15 modules, ~40+ migrations, ~40+ models, ~15 services, ~130+ files**

---

## Module 1: INVENTORY (Critical)

### Why
Stock tracking is missing. Product quantities are static. No stock adjustments, transfers, or ledger.

### 1.1 Migration: `create_warehouse_stock_table`

File: `Modules/Inventory/database/migrations/2026_03_13_200001_create_warehouse_stock_table.php`

```php
Schema::create('warehouse_stock', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
    $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
    $table->integer('quantity')->default(0);
    $table->integer('reserved_quantity')->default(0);
    $table->integer('reorder_level')->default(0);
    $table->timestamps();
    $table->unique(['product_id', 'variant_id', 'warehouse_id'], 'ws_unique');
});
```

### 1.2 Migration: `create_stock_adjustments_table`

File: `Modules/Inventory/database/migrations/2026_03_13_200002_create_stock_adjustments_table.php`

```php
Schema::create('stock_adjustments', function (Blueprint $table) {
    $table->id();
    $table->string('adjustment_number', 50)->unique();
    $table->foreignId('warehouse_id')->constrained();
    $table->enum('type', ['addition', 'subtraction']);
    $table->string('reason', 255);
    $table->text('notes')->nullable();
    $table->string('reference', 100)->nullable();
    $table->string('status', 20)->default('draft'); // draft, approved
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();
});
```

### 1.3 Migration: `create_stock_adjustment_items_table`

File: `Modules/Inventory/database/migrations/2026_03_13_200003_create_stock_adjustment_items_table.php`

```php
Schema::create('stock_adjustment_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('stock_adjustment_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
    $table->integer('quantity');
    $table->decimal('unit_cost', 15, 2)->default(0);
    $table->text('note')->nullable();
    $table->timestamps();
});
```

### 1.4 Migration: `create_stock_transfers_table`

File: `Modules/Inventory/database/migrations/2026_03_13_200004_create_stock_transfers_table.php`

```php
Schema::create('stock_transfers', function (Blueprint $table) {
    $table->id();
    $table->string('transfer_number', 50)->unique();
    $table->foreignId('from_warehouse_id')->constrained('warehouses');
    $table->foreignId('to_warehouse_id')->constrained('warehouses');
    $table->string('status', 20)->default('draft'); // draft, in_transit, received
    $table->text('notes')->nullable();
    $table->string('reference', 100)->nullable();
    $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('shipped_at')->nullable();
    $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('received_at')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();
});
```

### 1.5 Migration: `create_stock_transfer_items_table`

File: `Modules/Inventory/database/migrations/2026_03_13_200005_create_stock_transfer_items_table.php`

```php
Schema::create('stock_transfer_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
    $table->integer('quantity');
    $table->integer('received_quantity')->default(0);
    $table->timestamps();
});
```

### 1.6 Migration: `create_stock_ledger_table` (append-only/immutable)

File: `Modules/Inventory/database/migrations/2026_03_13_200006_create_stock_ledger_table.php`

```php
Schema::create('stock_ledger', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
    $table->foreignId('warehouse_id')->constrained();
    $table->string('source_type', 50); // sale, purchase, adjustment, transfer_out, transfer_in, sale_return, purchase_return
    $table->unsignedBigInteger('source_id');
    $table->integer('quantity_before');
    $table->integer('quantity_change'); // positive = in, negative = out
    $table->integer('quantity_after');
    $table->decimal('unit_cost', 15, 2)->default(0);
    $table->text('description')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->index(['product_id', 'warehouse_id', 'created_at']);
    $table->index(['source_type', 'source_id']);
});
```

### 1.7 Models (6)

**WarehouseStock** — `Modules/Inventory/app/Models/WarehouseStock.php`
```php
namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseStock extends Model
{
    protected $table = 'warehouse_stock';

    protected $fillable = [
        'product_id', 'variant_id', 'warehouse_id',
        'quantity', 'reserved_quantity', 'reorder_level',
    ];

    // Relationships
    public function product(): BelongsTo { return $this->belongsTo(\Modules\Product\Models\Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(\Modules\Product\Models\ProductVariant::class, 'variant_id'); }
    public function warehouse(): BelongsTo { return $this->belongsTo(\Modules\Warehouse\Models\Warehouse::class); }

    // Scopes
    public function scopeLowStock($q) { return $q->whereColumn('quantity', '<=', 'reorder_level')->where('reorder_level', '>', 0); }
    public function scopeByWarehouse($q, int $id) { return $q->where('warehouse_id', $id); }
    public function scopeByProduct($q, int $id) { return $q->where('product_id', $id); }

    // Accessors
    public function getAvailableQuantityAttribute(): int { return $this->quantity - $this->reserved_quantity; }
}
```

**StockAdjustment** — `Modules/Inventory/app/Models/StockAdjustment.php`
```php
namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class StockAdjustment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'adjustment_number', 'warehouse_id', 'type', 'reason',
        'notes', 'reference', 'status', 'approved_by', 'approved_at', 'created_by',
    ];

    protected $casts = ['approved_at' => 'datetime'];

    // Relationships
    public function warehouse(): BelongsTo { return $this->belongsTo(\Modules\Warehouse\Models\Warehouse::class); }
    public function items(): HasMany { return $this->hasMany(StockAdjustmentItem::class); }
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }

    // Scopes
    public function scopeDraft($q) { return $q->where('status', 'draft'); }
    public function scopeApproved($q) { return $q->where('status', 'approved'); }
    public function scopeSearch($q, string $term) {
        return $q->where(fn($q) => $q->where('adjustment_number', 'like', "%{$term}%")->orWhere('reason', 'like', "%{$term}%"));
    }

    // Helpers
    public function isEditable(): bool { return $this->status === 'draft'; }
}
```

**StockAdjustmentItem** — `Modules/Inventory/app/Models/StockAdjustmentItem.php`
```php
namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    protected $fillable = ['stock_adjustment_id', 'product_id', 'variant_id', 'quantity', 'unit_cost', 'note'];
    protected $casts = ['unit_cost' => 'decimal:2'];

    public function adjustment(): BelongsTo { return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id'); }
    public function product(): BelongsTo { return $this->belongsTo(\Modules\Product\Models\Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(\Modules\Product\Models\ProductVariant::class, 'variant_id'); }
}
```

**StockTransfer** — `Modules/Inventory/app/Models/StockTransfer.php`
```php
namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class StockTransfer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transfer_number', 'from_warehouse_id', 'to_warehouse_id', 'status',
        'notes', 'reference', 'shipped_by', 'shipped_at', 'received_by', 'received_at', 'created_by',
    ];

    protected $casts = ['shipped_at' => 'datetime', 'received_at' => 'datetime'];

    // Relationships
    public function fromWarehouse(): BelongsTo { return $this->belongsTo(\Modules\Warehouse\Models\Warehouse::class, 'from_warehouse_id'); }
    public function toWarehouse(): BelongsTo { return $this->belongsTo(\Modules\Warehouse\Models\Warehouse::class, 'to_warehouse_id'); }
    public function items(): HasMany { return $this->hasMany(StockTransferItem::class); }
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
    public function shippedByUser(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'shipped_by'); }
    public function receivedByUser(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'received_by'); }

    // Scopes
    public function scopeByStatus($q, string $status) { return $q->where('status', $status); }
    public function scopeSearch($q, string $term) {
        return $q->where(fn($q) => $q->where('transfer_number', 'like', "%{$term}%")->orWhere('reference', 'like', "%{$term}%"));
    }

    // Helpers
    public function isEditable(): bool { return $this->status === 'draft'; }
    public function isShippable(): bool { return $this->status === 'draft'; }
    public function isReceivable(): bool { return $this->status === 'in_transit'; }
}
```

**StockTransferItem** — `Modules/Inventory/app/Models/StockTransferItem.php`
```php
namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    protected $fillable = ['stock_transfer_id', 'product_id', 'variant_id', 'quantity', 'received_quantity'];

    public function transfer(): BelongsTo { return $this->belongsTo(StockTransfer::class, 'stock_transfer_id'); }
    public function product(): BelongsTo { return $this->belongsTo(\Modules\Product\Models\Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(\Modules\Product\Models\ProductVariant::class, 'variant_id'); }
}
```

**StockLedger** — `Modules/Inventory/app/Models/StockLedger.php` (immutable — no update/delete)
```php
namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedger extends Model
{
    protected $table = 'stock_ledger';

    protected $fillable = [
        'product_id', 'variant_id', 'warehouse_id', 'source_type', 'source_id',
        'quantity_before', 'quantity_change', 'quantity_after',
        'unit_cost', 'description', 'created_by',
    ];

    protected $casts = ['unit_cost' => 'decimal:2'];

    public function product(): BelongsTo { return $this->belongsTo(\Modules\Product\Models\Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(\Modules\Product\Models\ProductVariant::class, 'variant_id'); }
    public function warehouse(): BelongsTo { return $this->belongsTo(\Modules\Warehouse\Models\Warehouse::class); }
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by'); }

    // Scopes
    public function scopeByProduct($q, int $id) { return $q->where('product_id', $id); }
    public function scopeByWarehouse($q, int $id) { return $q->where('warehouse_id', $id); }
    public function scopeBySource($q, string $type, ?int $id = null) {
        return $q->where('source_type', $type)->when($id, fn($q) => $q->where('source_id', $id));
    }
}
```

### 1.8 Service: `InventoryService` (Central Gateway)

File: `Modules/Inventory/app/Services/InventoryService.php`

```php
namespace Modules\Inventory\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\{WarehouseStock, StockAdjustment, StockTransfer, StockLedger};
use Modules\Inventory\Exceptions\InsufficientStockException;

class InventoryService
{
    /**
     * Central stock mutation method — ALL stock changes go through this.
     * Creates stock ledger entry (immutable) and updates warehouse_stock.
     */
    public function adjustStock(
        int $productId,
        ?int $variantId,
        int $warehouseId,
        int $quantityChange,
        string $sourceType,
        int $sourceId,
        float $unitCost = 0,
        ?string $description = null
    ): StockLedger {
        return DB::transaction(function () use ($productId, $variantId, $warehouseId, $quantityChange, $sourceType, $sourceId, $unitCost, $description) {
            // Get or create warehouse_stock row (with lock for concurrency)
            $stock = WarehouseStock::lockForUpdate()->firstOrCreate(
                ['product_id' => $productId, 'variant_id' => $variantId, 'warehouse_id' => $warehouseId],
                ['quantity' => 0, 'reserved_quantity' => 0, 'reorder_level' => 0]
            );

            $quantityBefore = $stock->quantity;
            $quantityAfter = $quantityBefore + $quantityChange;

            // Prevent negative stock
            if ($quantityAfter < 0) {
                throw new InsufficientStockException(
                    "Insufficient stock for product #{$productId} in warehouse #{$warehouseId}. Available: {$quantityBefore}, Requested: " . abs($quantityChange)
                );
            }

            // Update warehouse_stock
            $stock->update(['quantity' => $quantityAfter]);

            // Create immutable ledger entry
            return StockLedger::create([
                'product_id' => $productId,
                'variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'quantity_before' => $quantityBefore,
                'quantity_change' => $quantityChange,
                'quantity_after' => $quantityAfter,
                'unit_cost' => $unitCost,
                'description' => $description,
                'created_by' => auth()->id(),
            ]);
        });
    }

    // --- Stock Queries ---

    public function getStockLevel(int $productId, ?int $variantId = null, ?int $warehouseId = null): int
    {
        $query = WarehouseStock::where('product_id', $productId);
        if ($variantId) $query->where('variant_id', $variantId);
        if ($warehouseId) $query->where('warehouse_id', $warehouseId);
        return $query->sum('quantity');
    }

    public function getLowStockProducts(?int $warehouseId = null): Collection
    {
        return WarehouseStock::lowStock()
            ->when($warehouseId, fn($q) => $q->byWarehouse($warehouseId))
            ->with(['product', 'warehouse'])
            ->get();
    }

    public function getStockHistory(int $productId, ?int $variantId = null, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return StockLedger::byProduct($productId)
            ->when($variantId, fn($q) => $q->where('variant_id', $variantId))
            ->when($filters['warehouse_id'] ?? null, fn($q, $w) => $q->byWarehouse($w))
            ->when($filters['source_type'] ?? null, fn($q, $t) => $q->bySource($t))
            ->with(['warehouse', 'creator'])
            ->latest()
            ->paginate($perPage);
    }

    public function getStockLevels(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return WarehouseStock::with(['product', 'variant', 'warehouse'])
            ->when($filters['search'] ?? null, fn($q, $s) => $q->whereHas('product', fn($q) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")))
            ->when($filters['warehouse_id'] ?? null, fn($q, $w) => $q->byWarehouse($w))
            ->when(($filters['low_stock'] ?? null) === 'true', fn($q) => $q->lowStock())
            ->paginate($perPage);
    }

    // --- Adjustments ---

    public function listAdjustments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return StockAdjustment::with(['warehouse', 'items.product', 'creator'])
            ->when($filters['search'] ?? null, fn($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['warehouse_id'] ?? null, fn($q, $w) => $q->where('warehouse_id', $w))
            ->latest()
            ->paginate($perPage);
    }

    public function findAdjustment(int $id): StockAdjustment
    {
        return StockAdjustment::with(['warehouse', 'items.product', 'items.variant', 'creator', 'approver'])->findOrFail($id);
    }

    public function createAdjustment(array $data, array $items): StockAdjustment
    {
        return DB::transaction(function () use ($data, $items) {
            $adjustment = StockAdjustment::create([
                'adjustment_number' => $this->generateAdjustmentNumber(),
                'warehouse_id' => $data['warehouse_id'],
                'type' => $data['type'],
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'reference' => $data['reference'] ?? null,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $adjustment->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'note' => $item['note'] ?? null,
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
            foreach ($adjustment->items as $item) {
                $change = $adjustment->type === 'addition' ? $item->quantity : -$item->quantity;
                $this->adjustStock(
                    $item->product_id, $item->variant_id, $adjustment->warehouse_id,
                    $change, 'adjustment', $adjustment->id, $item->unit_cost,
                    "{$adjustment->type} adjustment: {$adjustment->reason}"
                );
            }

            $adjustment->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });
    }

    // --- Transfers ---

    public function listTransfers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return StockTransfer::with(['fromWarehouse', 'toWarehouse', 'items.product', 'creator'])
            ->when($filters['search'] ?? null, fn($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->byStatus($s))
            ->latest()
            ->paginate($perPage);
    }

    public function findTransfer(int $id): StockTransfer
    {
        return StockTransfer::with(['fromWarehouse', 'toWarehouse', 'items.product', 'items.variant', 'creator', 'shippedByUser', 'receivedByUser'])->findOrFail($id);
    }

    public function createTransfer(array $data, array $items): StockTransfer
    {
        if ($data['from_warehouse_id'] === $data['to_warehouse_id']) {
            throw new \Modules\Inventory\Exceptions\InvalidTransferException('Source and destination warehouses must be different.');
        }

        return DB::transaction(function () use ($data, $items) {
            $transfer = StockTransfer::create([
                'transfer_number' => $this->generateTransferNumber(),
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'notes' => $data['notes'] ?? null,
                'reference' => $data['reference'] ?? null,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                ]);
            }

            return $transfer->load('items.product');
        });
    }

    public function shipTransfer(StockTransfer $transfer): void
    {
        if (!$transfer->isShippable()) {
            throw new \Exception('Only draft transfers can be shipped.');
        }

        DB::transaction(function () use ($transfer) {
            // Deduct from source warehouse
            foreach ($transfer->items as $item) {
                $this->adjustStock(
                    $item->product_id, $item->variant_id, $transfer->from_warehouse_id,
                    -$item->quantity, 'transfer_out', $transfer->id, 0,
                    "Transfer to {$transfer->toWarehouse->name}: {$transfer->transfer_number}"
                );
            }

            $transfer->update([
                'status' => 'in_transit',
                'shipped_by' => auth()->id(),
                'shipped_at' => now(),
            ]);
        });
    }

    public function receiveTransfer(StockTransfer $transfer, array $receivedQuantities): void
    {
        if (!$transfer->isReceivable()) {
            throw new \Exception('Only in-transit transfers can be received.');
        }

        DB::transaction(function () use ($transfer, $receivedQuantities) {
            foreach ($transfer->items as $item) {
                $received = $receivedQuantities[$item->id] ?? $item->quantity;
                $item->update(['received_quantity' => $received]);

                // Add to destination warehouse
                $this->adjustStock(
                    $item->product_id, $item->variant_id, $transfer->to_warehouse_id,
                    $received, 'transfer_in', $transfer->id, 0,
                    "Transfer from {$transfer->fromWarehouse->name}: {$transfer->transfer_number}"
                );
            }

            $transfer->update([
                'status' => 'received',
                'received_by' => auth()->id(),
                'received_at' => now(),
            ]);
        });
    }

    // --- Stats ---

    public function getStats(): array
    {
        return [
            'total_products' => WarehouseStock::distinct('product_id')->count('product_id'),
            'low_stock_count' => WarehouseStock::lowStock()->count(),
            'total_stock_value' => WarehouseStock::join('products', 'warehouse_stock.product_id', '=', 'products.id')
                ->selectRaw('SUM(warehouse_stock.quantity * products.purchase_price) as total')
                ->value('total') ?? 0,
            'pending_transfers' => StockTransfer::byStatus('in_transit')->count(),
        ];
    }

    // --- Number Generation ---

    private function generateAdjustmentNumber(): string
    {
        $year = now()->format('Y');
        $last = StockAdjustment::whereYear('created_at', $year)->count() + 1;
        return 'ADJ-' . $year . '-' . str_pad($last, 4, '0', STR_PAD_LEFT);
    }

    private function generateTransferNumber(): string
    {
        $year = now()->format('Y');
        $last = StockTransfer::whereYear('created_at', $year)->count() + 1;
        return 'TRF-' . $year . '-' . str_pad($last, 4, '0', STR_PAD_LEFT);
    }
}
```

### 1.9 Form Requests

**`Modules/Inventory/app/Http/Requests/StoreAdjustmentRequest.php`**
```php
public function rules(): array
{
    return [
        'warehouse_id' => 'required|exists:warehouses,id',
        'type' => 'required|in:addition,subtraction',
        'reason' => 'required|string|max:255',
        'notes' => 'nullable|string|max:2000',
        'reference' => 'nullable|string|max:100',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.variant_id' => 'nullable|exists:product_variants,id',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.unit_cost' => 'nullable|numeric|min:0',
        'items.*.note' => 'nullable|string|max:500',
    ];
}
```

**`Modules/Inventory/app/Http/Requests/StoreTransferRequest.php`**
```php
public function rules(): array
{
    return [
        'from_warehouse_id' => 'required|exists:warehouses,id',
        'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
        'notes' => 'nullable|string|max:2000',
        'reference' => 'nullable|string|max:100',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.variant_id' => 'nullable|exists:product_variants,id',
        'items.*.quantity' => 'required|integer|min:1',
    ];
}
```

### 1.10 Exception Classes

**`Modules/Inventory/app/Exceptions/InsufficientStockException.php`**
```php
namespace Modules\Inventory\Exceptions;

class InsufficientStockException extends \RuntimeException {}
```

**`Modules/Inventory/app/Exceptions/InvalidTransferException.php`**
```php
namespace Modules\Inventory\Exceptions;

class InvalidTransferException extends \RuntimeException {}
```

### 1.11 Controller: Rewrite `InventoryController`

File: `Modules/Inventory/app/Http/Controllers/InventoryController.php`

```php
namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Services\InventoryService;
use Modules\Inventory\Models\{StockAdjustment, StockTransfer};
use Modules\Inventory\Http\Requests\{StoreAdjustmentRequest, StoreTransferRequest};
use Modules\Product\Models\Product;
use Modules\Warehouse\Models\Warehouse;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $service) {}

    // GET /inventory — Stock levels overview
    public function index(Request $request)
    {
        $stats = $this->service->getStats();
        $stockLevels = $this->service->getStockLevels($request->only(['search', 'warehouse_id', 'low_stock']));
        $warehouses = Warehouse::where('is_active', true)->get();
        return view('inventory::index', compact('stats', 'stockLevels', 'warehouses'));
    }

    // GET /inventory/stock-history/{product}
    public function stockHistory(Product $product, Request $request)
    {
        $history = $this->service->getStockHistory($product->id, null, $request->only(['warehouse_id', 'source_type']));
        $warehouses = Warehouse::where('is_active', true)->get();
        return view('inventory::stock-history', compact('product', 'history', 'warehouses'));
    }

    // GET /inventory/adjustments
    public function adjustments(Request $request)
    {
        $adjustments = $this->service->listAdjustments($request->only(['search', 'status', 'warehouse_id']));
        $warehouses = Warehouse::where('is_active', true)->get();
        return view('inventory::adjustments', compact('adjustments', 'warehouses'));
    }

    // GET /inventory/adjustments/create
    public function createAdjustment()
    {
        $warehouses = Warehouse::where('is_active', true)->get();
        $products = Product::orderBy('name')->get(['id', 'name', 'sku', 'purchase_price']);
        return view('inventory::adjustment-create', compact('warehouses', 'products'));
    }

    // POST /inventory/adjustments
    public function storeAdjustment(StoreAdjustmentRequest $request)
    {
        $adj = $this->service->createAdjustment($request->validated(), $request->items);
        return redirect()->route('inventory.adjustments.show', $adj)
            ->with('success', "Adjustment {$adj->adjustment_number} created.");
    }

    // GET /inventory/adjustments/{adjustment}
    public function showAdjustment(StockAdjustment $adjustment)
    {
        $adjustment = $this->service->findAdjustment($adjustment->id);
        return view('inventory::adjustment-show', compact('adjustment'));
    }

    // POST /inventory/adjustments/{adjustment}/approve
    public function approveAdjustment(StockAdjustment $adjustment)
    {
        try {
            $this->service->approveAdjustment($adjustment);
            return back()->with('success', "Adjustment {$adjustment->adjustment_number} approved. Stock updated.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // GET /inventory/transfers
    public function transfers(Request $request)
    {
        $transfers = $this->service->listTransfers($request->only(['search', 'status']));
        return view('inventory::transfers', compact('transfers'));
    }

    // GET /inventory/transfers/create
    public function createTransfer()
    {
        $warehouses = Warehouse::where('is_active', true)->get();
        $products = Product::orderBy('name')->get(['id', 'name', 'sku']);
        return view('inventory::transfer-create', compact('warehouses', 'products'));
    }

    // POST /inventory/transfers
    public function storeTransfer(StoreTransferRequest $request)
    {
        $transfer = $this->service->createTransfer($request->validated(), $request->items);
        return redirect()->route('inventory.transfers.show', $transfer)
            ->with('success', "Transfer {$transfer->transfer_number} created.");
    }

    // GET /inventory/transfers/{transfer}
    public function showTransfer(StockTransfer $transfer)
    {
        $transfer = $this->service->findTransfer($transfer->id);
        return view('inventory::transfer-show', compact('transfer'));
    }

    // POST /inventory/transfers/{transfer}/ship
    public function shipTransfer(StockTransfer $transfer)
    {
        try {
            $this->service->shipTransfer($transfer);
            return back()->with('success', "Transfer {$transfer->transfer_number} shipped.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // POST /inventory/transfers/{transfer}/receive
    public function receiveTransfer(Request $request, StockTransfer $transfer)
    {
        try {
            $this->service->receiveTransfer($transfer, $request->input('received_quantities', []));
            return back()->with('success', "Transfer {$transfer->transfer_number} received.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
```

### 1.12 Routes

File: `Modules/Inventory/routes/web.php`

```php
Route::middleware('auth')->prefix('inventory')->name('inventory.')->group(function () {
    // Stock Overview
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::get('/stock-history/{product}', [InventoryController::class, 'stockHistory'])->name('stock-history');

    // Stock Adjustments
    Route::get('/adjustments', [InventoryController::class, 'adjustments'])->name('adjustments');
    Route::get('/adjustments/create', [InventoryController::class, 'createAdjustment'])->name('adjustments.create');
    Route::post('/adjustments', [InventoryController::class, 'storeAdjustment'])->name('adjustments.store');
    Route::get('/adjustments/{adjustment}', [InventoryController::class, 'showAdjustment'])->name('adjustments.show');
    Route::post('/adjustments/{adjustment}/approve', [InventoryController::class, 'approveAdjustment'])->name('adjustments.approve');

    // Stock Transfers
    Route::get('/transfers', [InventoryController::class, 'transfers'])->name('transfers');
    Route::get('/transfers/create', [InventoryController::class, 'createTransfer'])->name('transfers.create');
    Route::post('/transfers', [InventoryController::class, 'storeTransfer'])->name('transfers.store');
    Route::get('/transfers/{transfer}', [InventoryController::class, 'showTransfer'])->name('transfers.show');
    Route::post('/transfers/{transfer}/ship', [InventoryController::class, 'shipTransfer'])->name('transfers.ship');
    Route::post('/transfers/{transfer}/receive', [InventoryController::class, 'receiveTransfer'])->name('transfers.receive');
});
```

### 1.13 View Wiring (8 existing views)

| View | Key Variables |
|---|---|
| `index.blade.php` | `$stats`, `$stockLevels` (paginator with product/warehouse), `$warehouses` |
| `stock-history.blade.php` | `$product`, `$history` (paginator of stock_ledger), `$warehouses` |
| `adjustments.blade.php` | `$adjustments` (paginator), `$warehouses` |
| `adjustment-create.blade.php` | `$warehouses`, `$products` |
| `adjustment-show.blade.php` | `$adjustment` with items, warehouse, creator, approver |
| `transfers.blade.php` | `$transfers` (paginator) |
| `transfer-create.blade.php` | `$warehouses`, `$products` |
| `transfer-show.blade.php` | `$transfer` with items, fromWarehouse, toWarehouse, creator |

---

## Module 2: SALE RETURN (Critical)

### 2.1 Migration: `create_sale_returns_table`

File: `Modules/SaleReturn/database/migrations/2026_03_13_210001_create_sale_returns_table.php`

```php
Schema::create('sale_returns', function (Blueprint $table) {
    $table->id();
    $table->string('return_number', 50)->unique();
    $table->foreignId('sale_id')->constrained('sales');
    $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
    $table->foreignId('branch_id')->constrained('branches');
    $table->date('return_date');
    $table->string('reason', 500);
    $table->decimal('subtotal', 15, 2)->default(0);
    $table->decimal('tax_amount', 15, 2)->default(0);
    $table->decimal('total_amount', 15, 2)->default(0);
    $table->enum('status', ['draft', 'approved', 'completed', 'cancelled'])->default('draft');
    $table->enum('refund_method', ['cash', 'credit_note', 'exchange'])->default('cash');
    $table->foreignId('credit_note_id')->nullable()->constrained('credit_notes')->nullOnDelete();
    $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();
    $table->index(['sale_id', 'status']);
});
```

### 2.2 Migration: `create_sale_return_items_table`

File: `Modules/SaleReturn/database/migrations/2026_03_13_210002_create_sale_return_items_table.php`

```php
Schema::create('sale_return_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
    $table->foreignId('sale_item_id')->nullable()->constrained('sale_items')->nullOnDelete();
    $table->foreignId('product_id')->constrained('products');
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
    $table->integer('quantity');
    $table->decimal('unit_price', 15, 2);
    $table->decimal('tax_amount', 15, 2)->default(0);
    $table->decimal('subtotal', 15, 2);
    $table->string('condition', 50)->default('good'); // good, damaged
    $table->text('note')->nullable();
    $table->timestamps();
});
```

### 2.3 Models (2)

**SaleReturn** — `Modules/SaleReturn/app/Models/SaleReturn.php`
```php
// SoftDeletes
// Fillable: return_number, sale_id, customer_id, branch_id, return_date, reason, subtotal, tax_amount, total_amount, status, refund_method, credit_note_id, journal_entry_id, notes, created_by
// Casts: return_date => date, subtotal/tax_amount/total_amount => decimal:2
// Relationships: sale(), customer(), branch(), items(), creditNote(), journalEntry(), creator()
// Scopes: scopeByStatus, scopeByCustomer, scopeByDateRange, scopeSearch
// Helpers: isEditable(), isCompletable()
```

**SaleReturnItem** — `Modules/SaleReturn/app/Models/SaleReturnItem.php`
```php
// Fillable: sale_return_id, sale_item_id, product_id, variant_id, quantity, unit_price, tax_amount, subtotal, condition, note
// Casts: unit_price/tax_amount/subtotal => decimal:2
// Relationships: saleReturn(), saleItem(), product(), variant()
```

### 2.4 Service: `SaleReturnService`

File: `Modules/SaleReturn/app/Services/SaleReturnService.php`

```php
namespace Modules\SaleReturn\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\SaleReturn\Models\{SaleReturn, SaleReturnItem};
use Modules\Inventory\Services\InventoryService;
use Modules\Accounting\Services\AccountingIntegrationService;
use Modules\Accounting\Services\CreditNoteService;

class SaleReturnService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly AccountingIntegrationService $accountingService,
        private readonly CreditNoteService $creditNoteService,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return SaleReturn::with(['sale', 'customer', 'branch', 'creator'])
            ->when($filters['search'] ?? null, fn($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->byStatus($s))
            ->when($filters['date_from'] ?? null, fn($q, $d) => $q->where('return_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn($q, $d) => $q->where('return_date', '<=', $d))
            ->latest('return_date')
            ->paginate($perPage);
    }

    public function find(int $id): SaleReturn
    {
        return SaleReturn::with(['sale.customer', 'customer', 'branch', 'items.product', 'items.variant', 'creditNote', 'journalEntry.lines.account', 'creator'])->findOrFail($id);
    }

    public function create(array $data, array $items): SaleReturn
    {
        return DB::transaction(function () use ($data, $items) {
            $return = SaleReturn::create([
                'return_number' => $this->generateReturnNumber(),
                'sale_id' => $data['sale_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'branch_id' => $data['branch_id'],
                'return_date' => $data['return_date'],
                'reason' => $data['reason'],
                'refund_method' => $data['refund_method'] ?? 'cash',
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            $subtotal = 0;
            $taxTotal = 0;
            foreach ($items as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $itemTax = $item['tax_amount'] ?? 0;
                $return->items()->create([
                    'sale_item_id' => $item['sale_item_id'] ?? null,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_amount' => $itemTax,
                    'subtotal' => $itemSubtotal,
                    'condition' => $item['condition'] ?? 'good',
                    'note' => $item['note'] ?? null,
                ]);
                $subtotal += $itemSubtotal;
                $taxTotal += $itemTax;
            }

            $return->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total_amount' => $subtotal + $taxTotal,
            ]);

            return $return->load('items.product');
        });
    }

    public function approve(SaleReturn $return): SaleReturn
    {
        if ($return->status !== 'draft') {
            throw new \Exception('Only draft returns can be approved.');
        }

        // Validate quantities don't exceed original sale quantities
        // (implementation validates each item against sale_items)

        $return->update(['status' => 'approved']);
        return $return->fresh();
    }

    public function complete(SaleReturn $return): SaleReturn
    {
        if ($return->status !== 'approved') {
            throw new \Exception('Only approved returns can be completed.');
        }

        return DB::transaction(function () use ($return) {
            // 1. Restock items in good condition via InventoryService
            foreach ($return->items as $item) {
                if ($item->condition === 'good') {
                    $warehouseId = $return->branch->default_warehouse_id ?? 1;
                    $this->inventoryService->adjustStock(
                        $item->product_id, $item->variant_id, $warehouseId,
                        $item->quantity, 'sale_return', $return->id, $item->unit_price,
                        "Return: {$return->return_number}"
                    );
                }
            }

            // 2. Create journal entry via AccountingIntegrationService
            $je = $this->accountingService->recordSaleReturn($return);

            // 3. If refund_method is credit_note, create credit note
            $creditNoteId = null;
            if ($return->refund_method === 'credit_note') {
                $cn = $this->creditNoteService->create([
                    'customer_id' => $return->customer_id,
                    'sale_id' => $return->sale_id,
                    'issue_date' => $return->return_date,
                    'reason' => "Sale return: {$return->return_number}",
                    'items' => $return->items->map(fn($item) => [
                        'product_id' => $item->product_id,
                        'description' => $item->product->name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'tax_amount' => $item->tax_amount,
                    ])->toArray(),
                ]);
                $creditNoteId = $cn->id;
            }

            // 4. Update sale due_amount (reduce by return total)
            $return->sale->decrement('due_amount', $return->total_amount);

            // 5. Mark as completed
            $return->update([
                'status' => 'completed',
                'journal_entry_id' => $je->id,
                'credit_note_id' => $creditNoteId,
            ]);

            return $return->fresh();
        });
    }

    public function cancel(SaleReturn $return): SaleReturn
    {
        if (!in_array($return->status, ['draft', 'approved'])) {
            throw new \Exception('Only draft or approved returns can be cancelled.');
        }
        $return->update(['status' => 'cancelled']);
        return $return->fresh();
    }

    public function getStats(): array
    {
        return [
            'total' => SaleReturn::count(),
            'pending' => SaleReturn::byStatus('draft')->count() + SaleReturn::byStatus('approved')->count(),
            'completed' => SaleReturn::byStatus('completed')->count(),
            'total_value' => SaleReturn::byStatus('completed')->sum('total_amount'),
        ];
    }

    public function getSaleItems(int $saleId): Collection
    {
        return \Modules\Sale\Models\SaleItem::where('sale_id', $saleId)
            ->with('product')
            ->get();
    }

    private function generateReturnNumber(): string
    {
        $year = now()->format('Y');
        $last = SaleReturn::whereYear('created_at', $year)->count() + 1;
        return 'SR-' . $year . '-' . str_pad($last, 4, '0', STR_PAD_LEFT);
    }
}
```

### 2.5 Form Request

**`Modules/SaleReturn/app/Http/Requests/StoreSaleReturnRequest.php`**
```php
public function rules(): array
{
    return [
        'sale_id' => 'required|exists:sales,id',
        'customer_id' => 'nullable|exists:customers,id',
        'branch_id' => 'required|exists:branches,id',
        'return_date' => 'required|date',
        'reason' => 'required|string|max:500',
        'refund_method' => 'required|in:cash,credit_note,exchange',
        'notes' => 'nullable|string|max:2000',
        'items' => 'required|array|min:1',
        'items.*.sale_item_id' => 'nullable|exists:sale_items,id',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.variant_id' => 'nullable|exists:product_variants,id',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.unit_price' => 'required|numeric|min:0',
        'items.*.tax_amount' => 'nullable|numeric|min:0',
        'items.*.condition' => 'nullable|in:good,damaged',
    ];
}
```

### 2.6 Controller: Rewrite `SaleReturnController`

Standard CRUD pattern + `approve()`, `complete()`, `cancel()`, `print()`, AJAX `saleItems(Sale $sale)`

### 2.7 View Wiring (5 existing views)

| View | Key Variables |
|---|---|
| `index.blade.php` | `$stats`, `$returns` (paginator) |
| `create.blade.php` | `$sales` (with items), `$customers`, `$branches` |
| `show.blade.php` | `$return` with items, sale, creditNote, journalEntry |
| `edit.blade.php` | `$return`, `$sales` |
| `print.blade.php` | `$return` (standalone print layout) |

---

## Module 3: SETTING

### 3.1 Migration: `create_settings_table`

File: `Modules/Setting/database/migrations/2026_03_13_220001_create_settings_table.php`

```php
Schema::create('settings', function (Blueprint $table) {
    $table->id();
    $table->string('group', 50); // business, tax, invoice, notification, localization, sms, courier
    $table->string('key', 100);
    $table->text('value')->nullable();
    $table->string('type', 20)->default('string'); // string, boolean, integer, json
    $table->timestamps();
    $table->unique(['group', 'key']);
});
```

### 3.2 Model: `Setting`

File: `Modules/Setting/app/Models/Setting.php`

```php
namespace Modules\Setting\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type'];

    /**
     * Get a single setting value.
     */
    public static function get(string $group, string $key, $default = null)
    {
        $setting = static::where('group', $group)->where('key', $key)->first();
        if (!$setting) return $default;
        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set a single setting value.
     */
    public static function set(string $group, string $key, $value, string $type = 'string'): void
    {
        $storeValue = $type === 'json' ? json_encode($value) : (string) $value;
        static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $storeValue, 'type' => $type]
        );
    }

    /**
     * Get all settings for a group as key-value array.
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)->pluck('value', 'key')->toArray();
    }
}
```

### 3.3 Service: `SettingService`

File: `Modules/Setting/app/Services/SettingService.php`

```php
namespace Modules\Setting\Services;

use Modules\Setting\Models\Setting;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    public function getAll(): array
    {
        $settings = Setting::all();
        $grouped = [];
        foreach ($settings as $s) {
            $grouped[$s->group][$s->key] = match ($s->type) {
                'boolean' => filter_var($s->value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $s->value,
                'json' => json_decode($s->value, true),
                default => $s->value,
            };
        }
        return $grouped;
    }

    public function getGroup(string $group): array
    {
        return Setting::getGroup($group);
    }

    public function updateGroup(string $group, array $data): void
    {
        foreach ($data as $key => $value) {
            Setting::set($group, $key, $value);
        }
    }

    public function updateBusinessProfile(array $data): void
    {
        // Handle logo upload
        if (isset($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {
            $path = $data['logo']->store('settings', 'public');
            Setting::set('business', 'logo', $path, 'string');
            unset($data['logo']);
        }

        foreach ($data as $key => $value) {
            if ($key === '_token' || $key === '_method') continue;
            Setting::set('business', $key, $value);
        }
    }

    public function updateTaxSettings(array $data): void
    {
        $this->updateGroup('tax', $data);
    }

    public function updateInvoiceSettings(array $data): void
    {
        $this->updateGroup('invoice', $data);
    }

    public function updateCourierSettings(array $data): void
    {
        $this->updateGroup('courier', $data);
    }

    public function updateNotificationSettings(array $data): void
    {
        $this->updateGroup('notification', $data);
    }

    public function updateLocalization(array $data): void
    {
        $this->updateGroup('localization', $data);
    }

    public function updateSmsGateway(array $data): void
    {
        $this->updateGroup('sms', $data);
    }
}
```

### 3.4 Seeder: `SettingsSeeder`

File: `Modules/Setting/database/seeders/SettingsSeeder.php`

```php
// Seeds defaults:
// business: company_name, address, phone, email, website, logo, bin_number, tin_number
// tax: vat_enabled (boolean), vat_rate (15), tax_inclusive (boolean)
// invoice: prefix (INV-), footer_text, terms, show_logo (boolean)
// notification: low_stock_alert (boolean), payment_reminder (boolean)
// localization: currency (BDT), date_format (d M Y), timezone (Asia/Dhaka), language (en)
// sms: gateway (ssl_wireless), api_key, sender_id
// courier: default_courier (pathao), pathao_api_key, steadfast_api_key
```

### 3.5 Controller: Rewrite `SettingController`

```php
public function index()
{
    $settings = $this->service->getAll();
    $branches = \Modules\Branch\Models\Branch::all();
    return view('setting::index', compact('settings', 'branches'));
}

public function update(Request $request, string $group)
{
    match ($group) {
        'business' => $this->service->updateBusinessProfile($request->all()),
        'tax' => $this->service->updateTaxSettings($request->all()),
        'invoice' => $this->service->updateInvoiceSettings($request->all()),
        'courier' => $this->service->updateCourierSettings($request->all()),
        'notification' => $this->service->updateNotificationSettings($request->all()),
        'localization' => $this->service->updateLocalization($request->all()),
        'sms' => $this->service->updateSmsGateway($request->all()),
        default => throw new \Exception("Unknown settings group: {$group}"),
    };

    return back()->with('success', ucfirst($group) . ' settings updated.');
}
```

### 3.6 View Wiring

`index.blade.php` → Replace all hardcoded form values with `$settings['business']['company_name']`, `$settings['tax']['vat_rate']`, etc. Each tab form posts to `route('settings.update', $group)`.

---

## Module 4: QUOTATION (High Priority)

### 4.1 Migration: `create_quotations_table`

File: `Modules/Quotation/database/migrations/2026_03_13_350001_create_quotations_table.php`

```php
Schema::create('quotations', function (Blueprint $table) {
    $table->id();
    $table->string('quotation_number', 50)->unique();
    $table->string('reference', 50)->nullable();
    $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
    $table->foreignId('branch_id')->constrained('branches');
    $table->date('quotation_date');
    $table->date('valid_until');
    $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired', 'converted'])->default('draft');
    $table->decimal('subtotal', 15, 2)->default(0);
    $table->enum('discount_type', ['fixed', 'percentage'])->nullable();
    $table->decimal('discount_value', 15, 2)->default(0);
    $table->decimal('discount_amount', 15, 2)->default(0);
    $table->decimal('tax_rate', 5, 2)->default(0);
    $table->decimal('tax_amount', 15, 2)->default(0);
    $table->decimal('shipping_charge', 15, 2)->default(0);
    $table->decimal('grand_total', 15, 2)->default(0);
    $table->text('notes')->nullable();
    $table->text('terms')->nullable();
    $table->foreignId('converted_sale_id')->nullable()->constrained('sales')->nullOnDelete();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index('customer_id');
    $table->index('branch_id');
    $table->index('quotation_date');
    $table->index('status');
    $table->index('valid_until');
});
```

### 4.2 Migration: `create_quotation_items_table`

File: `Modules/Quotation/database/migrations/2026_03_13_350002_create_quotation_items_table.php`

```php
Schema::create('quotation_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
    $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
    $table->string('product_name');
    $table->string('product_sku', 100)->nullable();
    $table->integer('quantity')->default(1);
    $table->decimal('unit_price', 15, 2)->default(0);
    $table->decimal('discount_amount', 15, 2)->default(0);
    $table->decimal('tax_amount', 15, 2)->default(0);
    $table->decimal('subtotal', 15, 2)->default(0);
    $table->timestamps();
});
```

### 4.3 Models (2)

**Quotation** — SoftDeletes. Relationships: customer(), branch(), creator(), items(), convertedSale(). Scopes: scopeByStatus, scopeByCustomer, scopeByDateRange, scopeSearch, scopeExpired, scopePending. Helpers: isExpired(), isConvertible().

**QuotationItem** — Relationships: quotation(), product(), variant().

### 4.4 Service: `QuotationService`

```php
// Constructor injects: SaleService (for convertToSale)

public function list(array $filters, int $perPage = 15): LengthAwarePaginator
public function find(int $id): Quotation
public function create(array $data, array $items): Quotation // DB::transaction, calculate totals
public function update(Quotation $quotation, array $data, array $items): Quotation // DB::transaction, delete old items, recalculate
public function updateStatus(Quotation $quotation, string $status): void
public function convertToSale(Quotation $quotation): Sale
    // Map quotation items → sale items, create Sale via SaleService, mark quotation as 'converted'
public function duplicate(Quotation $quotation): Quotation // copy with new number/dates
public function getStats(): array // total, pending, accepted, expired
public function markExpired(): int // bulk update past valid_until (schedulable)
private function generateQuotationNumber(): string // QTN-YYYY-XXXX
```

### 4.5 Form Request: `StoreQuotationRequest`

```php
public function rules(): array
{
    return [
        'customer_id' => 'nullable|exists:customers,id',
        'branch_id' => 'required|exists:branches,id',
        'quotation_date' => 'required|date',
        'valid_until' => 'required|date|after_or_equal:quotation_date',
        'reference' => 'nullable|string|max:50',
        'discount_type' => 'nullable|in:fixed,percentage',
        'discount_value' => 'nullable|numeric|min:0',
        'tax_rate' => 'nullable|numeric|min:0|max:100',
        'shipping_charge' => 'nullable|numeric|min:0',
        'notes' => 'nullable|string|max:2000',
        'terms' => 'nullable|string|max:5000',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.unit_price' => 'required|numeric|min:0',
        'items.*.discount_amount' => 'nullable|numeric|min:0',
    ];
}
```

### 4.6 Controller

Standard CRUD + `convertToSale()`, `send()`, `print()`, `duplicate()`

### 4.7 View Wiring (4 views)

| View | Key Variables |
|---|---|
| `index.blade.php` | `$stats`, `$quotations` (paginator) |
| `create.blade.php` | `$customers`, `$branches`, `$products` |
| `edit.blade.php` | `$quotation` with items, `$customers`, `$branches`, `$products` |
| `show.blade.php` | `$quotation` with items, customer, branch |

---

## Module 5: DELIVERY (High Priority)

### 5.1 Migration: `create_delivery_challans_table`

File: `Modules/Delivery/database/migrations/2026_03_13_360001_create_delivery_challans_table.php`

```php
Schema::create('delivery_challans', function (Blueprint $table) {
    $table->id();
    $table->string('challan_number', 50)->unique();
    $table->foreignId('sale_id')->constrained('sales');
    $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
    $table->foreignId('branch_id')->constrained('branches');
    $table->date('delivery_date');
    $table->string('recipient_name')->nullable();
    $table->string('recipient_phone', 20)->nullable();
    $table->text('delivery_address');
    $table->string('division', 100)->nullable();
    $table->string('district', 100)->nullable();
    $table->string('area', 200)->nullable();
    $table->string('post_code', 10)->nullable();
    $table->enum('courier', ['pathao', 'steadfast', 'ecourier', 'redx', 'paperfly', 'sundarban', 'sa_paribahan', 'own_delivery'])->default('own_delivery');
    $table->string('tracking_number', 100)->nullable();
    $table->string('courier_order_id', 100)->nullable();
    $table->enum('status', ['pending', 'picked_up', 'in_transit', 'delivered', 'returned', 'cancelled'])->default('pending');
    $table->decimal('shipping_charge', 15, 2)->default(0);
    $table->decimal('cod_amount', 15, 2)->default(0);
    $table->decimal('courier_charge', 15, 2)->default(0);
    $table->decimal('weight', 8, 2)->nullable();
    $table->text('special_instructions')->nullable();
    $table->text('notes')->nullable();
    $table->timestamp('picked_up_at')->nullable();
    $table->timestamp('delivered_at')->nullable();
    $table->timestamp('returned_at')->nullable();
    $table->string('return_reason')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index('sale_id');
    $table->index('customer_id');
    $table->index('courier');
    $table->index('status');
    $table->index('delivery_date');
    $table->index('tracking_number');
});
```

### 5.2 Migration: `create_delivery_challan_items_table`

```php
Schema::create('delivery_challan_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('delivery_challan_id')->constrained('delivery_challans')->cascadeOnDelete();
    $table->foreignId('sale_item_id')->nullable()->constrained('sale_items')->nullOnDelete();
    $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
    $table->string('product_name');
    $table->integer('quantity')->default(1);
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

### 5.3 Models, Service, Controller

Same pattern as other modules. Key service methods:
- `list()`, `find()`, `create()`, `update()`, `updateStatus()`, `getStats()`, `generateChallanNumber()` — DC-YYYY-XXXX
- **Future:** `CourierInterface` with Strategy pattern for Pathao, Steadfast, eCourier, Redx providers (Open/Closed principle)

### 5.4 View Wiring (3 views): index, create, show

---

## Module 6: DASHBOARD

### 6.1 Service: `DashboardService`

File: `Modules/Dashboard/app/Services/DashboardService.php`

```php
namespace Modules\Dashboard\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getKpiCards(): array
    {
        return [
            'today_sales' => \Modules\Sale\Models\Sale::whereDate('sale_date', today())->sum('grand_total'),
            'today_expenses' => \Modules\Expense\Models\Expense::whereDate('expense_date', today())->where('status', 'paid')->sum('total_amount'),
            'today_profit' => 0, // today_sales - today_expenses (simplified)
            'total_receivable' => \Modules\Sale\Models\Sale::where('due_amount', '>', 0)->sum('due_amount'),
            'total_payable' => \Modules\Purchase\Models\Purchase::where('due_amount', '>', 0)->sum('due_amount'),
            'cash_in_hand' => \Modules\Accounting\Models\Account::where('account_code', '1001')->first()?->balance ?? 0,
        ];
    }

    public function getSalesTrend(int $days = 30): array
    {
        // Returns daily sales totals for Chart.js line chart
        // ['labels' => ['01 Mar', '02 Mar', ...], 'data' => [5000, 8000, ...]]
    }

    public function getPaymentMethodBreakdown(): array
    {
        // Group payments by method for Chart.js doughnut
        // ['labels' => ['Cash', 'bKash', ...], 'data' => [50000, 30000, ...]]
    }

    public function getTopSellingProducts(int $limit = 10): Collection
    {
        // Sum sale_items quantity grouped by product, ordered by total_qty desc
    }

    public function getRecentSales(int $limit = 10): Collection
    {
        return \Modules\Sale\Models\Sale::with('customer')->latest('sale_date')->limit($limit)->get();
    }

    public function getRecentExpenses(int $limit = 10): Collection
    {
        return \Modules\Expense\Models\Expense::with('category')->latest('expense_date')->limit($limit)->get();
    }

    public function getLowStockAlerts(int $limit = 10): Collection
    {
        return \Modules\Inventory\Models\WarehouseStock::lowStock()
            ->with(['product', 'warehouse'])
            ->limit($limit)->get();
    }

    public function getUpcomingInstallments(int $limit = 10): Collection
    {
        return \Modules\Installment\Models\InstallmentSchedule::where('status', 'upcoming')
            ->where('due_date', '<=', now()->addDays(7))
            ->with('installmentPlan.customer')
            ->orderBy('due_date')
            ->limit($limit)->get();
    }

    public function getOverdueReceivables(int $limit = 10): Collection
    {
        return \Modules\Sale\Models\Sale::where('due_amount', '>', 0)
            ->where('sale_date', '<', now()->subDays(30))
            ->with('customer')
            ->orderByDesc('due_amount')
            ->limit($limit)->get();
    }
}
```

### 6.2 Controller Update

```php
public function index()
{
    $kpi = $this->service->getKpiCards();
    $salesTrend = $this->service->getSalesTrend();
    $paymentBreakdown = $this->service->getPaymentMethodBreakdown();
    $topProducts = $this->service->getTopSellingProducts();
    $recentSales = $this->service->getRecentSales();
    $recentExpenses = $this->service->getRecentExpenses();
    $lowStock = $this->service->getLowStockAlerts();

    return view('dashboard::index', compact(
        'kpi', 'salesTrend', 'paymentBreakdown',
        'topProducts', 'recentSales', 'recentExpenses', 'lowStock'
    ));
}
```

### 6.3 View Wiring

`index.blade.php` → Replace hardcoded KPIs with `$kpi['today_sales']`, etc. Pass chart data as JSON via `@push('scripts')`:
```javascript
const salesChart = new Chart(ctx, { type: 'line', data: @json($salesTrend) });
```

---

## Module 7: REPORT

### 7.1 Services (7 report services)

All in `Modules/Report/app/Services/`:

**SalesReportService**: `dailySales($from, $to)`, `monthlySales($year)`, `salesByProduct($from, $to)`, `salesByCustomer($from, $to)`, `salesByBranch($from, $to)`

**PurchaseReportService**: `dailyPurchases($from, $to)`, `purchaseBySupplier($from, $to)`, `purchaseByProduct($from, $to)`

**InventoryReportService**: `stockSummary(?$warehouseId)`, `stockMovement($productId, $from, $to)`, `lowStockReport()`

**FinancialReportService**: `incomeExpenseSummary($from, $to)`, `profitTrend($year)`, `accountBalances($asOfDate)`

**TaxReportService**: `vatSummary($from, $to)`, `mushak63Report($month)`, `monthlyTaxReturn($month)`

**StaffReportService**: `attendanceSummary($month)`, `payrollSummary($month)`

**CustomerReportService**: `topCustomers($from, $to, $limit)`, `customerLedger($customerId, $from, $to)`, `agingReport($asOfDate)`

### 7.2 Controller

One method per report view, each delegates to appropriate service.

### 7.3 View Wiring (8 views)

| View | Service | Key Variables |
|---|---|---|
| `sales.blade.php` | SalesReportService | `$reportData`, `$from`, `$to` |
| `purchases.blade.php` | PurchaseReportService | `$reportData`, `$from`, `$to` |
| `inventory.blade.php` | InventoryReportService | `$reportData`, `$warehouses` |
| `financial.blade.php` | FinancialReportService | `$reportData`, `$from`, `$to` |
| `tax.blade.php` | TaxReportService | `$reportData`, `$month` |
| `staff.blade.php` | StaffReportService | `$reportData`, `$month` |
| `customer.blade.php` | CustomerReportService | `$reportData`, `$customers` |
| `custom.blade.php` | Multiple services | Query builder interface |

---

## Module 8: EMPLOYEE (Medium)

### 8.1 Migration: `create_employees_table`

File: `Modules/Employee/database/migrations/2026_03_13_400001_create_employees_table.php`

```php
Schema::create('employees', function (Blueprint $table) {
    $table->id();
    $table->string('employee_id', 20)->unique();
    $table->string('name');
    $table->string('phone', 20);
    $table->string('email')->nullable();
    $table->string('nid', 30)->nullable();
    $table->string('department', 100)->nullable();
    $table->string('designation', 100)->nullable();
    $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    $table->decimal('salary', 15, 2)->default(0);
    $table->decimal('advance_balance', 15, 2)->default(0);
    $table->date('joining_date');
    $table->date('leaving_date')->nullable();
    $table->string('emergency_contact_name')->nullable();
    $table->string('emergency_contact_phone', 20)->nullable();
    $table->text('address')->nullable();
    $table->string('photo')->nullable();
    $table->enum('status', ['active', 'inactive', 'on_leave', 'terminated'])->default('active');
    $table->text('notes')->nullable();
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index('department');
    $table->index('designation');
    $table->index('branch_id');
    $table->index('status');
    $table->index('joining_date');
});
```

### 8.2 Model, Service, Controller

Standard pattern. Service methods: `list()`, `find()`, `create()` (auto-generate EMP-XXX), `update()`, `delete()`, `getStats()`, `adjustAdvanceBalance()`.

### 8.3 View Wiring (4 views): index, create, edit, show

---

## Module 9: ATTENDANCE (Medium)

### 9.1 Migration: `create_attendances_table`

File: `Modules/Attendance/database/migrations/2026_03_13_410001_create_attendances_table.php`

```php
Schema::create('attendances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    $table->date('attendance_date');
    $table->time('check_in')->nullable();
    $table->time('check_out')->nullable();
    $table->decimal('hours_worked', 5, 2)->nullable();
    $table->enum('status', ['present', 'absent', 'late', 'half_day', 'on_leave'])->default('present');
    $table->integer('late_minutes')->default(0);
    $table->text('note')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();

    $table->unique(['employee_id', 'attendance_date']);
    $table->index('attendance_date');
    $table->index('status');
});
```

### 9.2 Migration: `create_leaves_table`

File: `Modules/Attendance/database/migrations/2026_03_13_410002_create_leaves_table.php`

```php
Schema::create('leaves', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
    $table->enum('leave_type', ['casual', 'sick', 'annual', 'maternity', 'paternity', 'unpaid'])->default('casual');
    $table->date('start_date');
    $table->date('end_date');
    $table->integer('total_days');
    $table->text('reason')->nullable();
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['employee_id', 'start_date']);
    $table->index('status');
});
```

### 9.3 Models, Service, Controller

**AttendanceService** methods: `list()`, `markAttendance()`, `bulkMarkAttendance()`, `getStats()`, `getReport()` (monthly summary), `leaveList()`, `applyLeave()`, `approveLeave()`, `rejectLeave()`

### 9.4 View Wiring (5 views): index, create (bulk), report, leave, leave-create

---

## Module 10: PAYROLL (Medium)

### 10.1 Migrations

**`create_salary_structures_table`**
```php
Schema::create('salary_structures', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('code', 20)->unique();
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();
});
```

**`create_salary_structure_components_table`**
```php
Schema::create('salary_structure_components', function (Blueprint $table) {
    $table->id();
    $table->foreignId('salary_structure_id')->constrained('salary_structures')->cascadeOnDelete();
    $table->string('name', 100);
    $table->enum('type', ['earning', 'deduction']);
    $table->enum('calculation_type', ['fixed', 'percentage'])->default('fixed');
    $table->decimal('amount', 15, 2)->default(0);
    $table->decimal('percentage', 5, 2)->default(0);
    $table->string('percentage_of', 50)->nullable(); // 'basic' or 'gross'
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**`create_payrolls_table`**
```php
Schema::create('payrolls', function (Blueprint $table) {
    $table->id();
    $table->string('payroll_number', 50)->unique();
    $table->string('month', 7); // YYYY-MM
    $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    $table->integer('total_employees')->default(0);
    $table->decimal('total_gross', 15, 2)->default(0);
    $table->decimal('total_deductions', 15, 2)->default(0);
    $table->decimal('total_net', 15, 2)->default(0);
    $table->enum('status', ['draft', 'approved', 'paid', 'cancelled'])->default('draft');
    $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['month', 'branch_id']);
    $table->index('status');
});
```

**`create_payroll_items_table`**
```php
Schema::create('payroll_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
    $table->foreignId('employee_id')->constrained('employees');
    $table->foreignId('salary_structure_id')->nullable()->constrained('salary_structures')->nullOnDelete();
    $table->decimal('basic_salary', 15, 2)->default(0);
    $table->decimal('gross_salary', 15, 2)->default(0);
    $table->decimal('total_earnings', 15, 2)->default(0);
    $table->decimal('total_deductions', 15, 2)->default(0);
    $table->decimal('net_salary', 15, 2)->default(0);
    $table->decimal('advance_deduction', 15, 2)->default(0);
    $table->json('earnings_breakdown')->nullable(); // [{name, amount}]
    $table->json('deductions_breakdown')->nullable(); // [{name, amount}]
    $table->integer('working_days')->default(0);
    $table->integer('present_days')->default(0);
    $table->integer('absent_days')->default(0);
    $table->enum('payment_status', ['pending', 'paid'])->default('pending');
    $table->string('payment_method', 50)->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->unique(['payroll_id', 'employee_id']);
});
```

### 10.2 Models (4): SalaryStructure, SalaryStructureComponent, Payroll (SoftDeletes), PayrollItem

### 10.3 Service: `PayrollService`

```php
// Constructor injects: JournalEntryService

public function generatePayroll(string $month, ?int $branchId): Payroll
    // Get active employees for branch, calculate salary per structure
    // Factor attendance (present_days from Attendance module)
    // Deduct advance_balance if any
    // Create payroll + payroll_items in DB::transaction

public function approvePayroll(Payroll $payroll): void
    // Create journal: DR Salary Expense (5110) / CR Salary Payable (2015)

public function markAsPaid(Payroll $payroll, string $paymentMethod): void
    // Create journal: DR Salary Payable (2015) / CR Cash/Bank
    // Deduct advance_balance from employees where advance_deduction > 0

public function listPayrolls(), findPayroll(), getStats()
public function listSalaryStructures(), createSalaryStructure()
public function calculateEmployeeSalary(Employee, SalaryStructure, workingDays, presentDays): array
private function generatePayrollNumber(string $month): string // PR-YYYY-MM-XXX
```

### 10.4 View Wiring (5 views): index, show, generate, salary-structure, salary-structure-create

---

## Module 11: INSTALLMENT (Medium)

### 11.1 Migrations

**`create_installment_plans_table`**
```php
Schema::create('installment_plans', function (Blueprint $table) {
    $table->id();
    $table->string('plan_number', 50)->unique();
    $table->foreignId('sale_id')->constrained('sales');
    $table->foreignId('customer_id')->constrained('customers');
    $table->foreignId('branch_id')->constrained('branches');
    $table->decimal('total_amount', 15, 2);
    $table->decimal('down_payment', 15, 2)->default(0);
    $table->decimal('financed_amount', 15, 2);
    $table->integer('total_installments');
    $table->decimal('installment_amount', 15, 2);
    $table->decimal('total_paid', 15, 2)->default(0);
    $table->decimal('total_remaining', 15, 2)->default(0);
    $table->enum('frequency', ['weekly', 'bi_weekly', 'monthly'])->default('monthly');
    $table->date('start_date');
    $table->date('next_due_date')->nullable();
    $table->enum('status', ['active', 'completed', 'overdue', 'defaulted', 'cancelled'])->default('active');
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index('customer_id');
    $table->index('sale_id');
    $table->index('status');
    $table->index('next_due_date');
});
```

**`create_installment_schedules_table`**
```php
Schema::create('installment_schedules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('installment_plan_id')->constrained('installment_plans')->cascadeOnDelete();
    $table->integer('installment_number');
    $table->date('due_date');
    $table->decimal('amount', 15, 2);
    $table->decimal('paid_amount', 15, 2)->default(0);
    $table->enum('status', ['upcoming', 'paid', 'partial', 'overdue'])->default('upcoming');
    $table->date('paid_date')->nullable();
    $table->string('payment_method', 50)->nullable();
    $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->unique(['installment_plan_id', 'installment_number']);
    $table->index('due_date');
    $table->index('status');
});
```

### 11.2 Models, Service, Controller

**InstallmentService** (injects PaymentService):
- `create()` — generate schedule dates based on frequency, record down_payment via PaymentService
- `recordPayment()` — find next unpaid schedule, record via PaymentService, update plan totals
- `markOverdue()` — schedulable bulk update
- `getStats()` — total_sales, active_plans, total_receivable, total_overdue

### 11.3 View Wiring (3 views): index, create, show

---

## Module 12: ASSET (Medium)

### 12.1 Migrations

**`create_asset_categories_table`**
```php
Schema::create('asset_categories', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->integer('useful_life_years')->default(5);
    $table->decimal('depreciation_rate', 5, 2)->default(20);
    $table->enum('depreciation_method', ['straight_line', 'declining_balance'])->default('straight_line');
    $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
    $table->foreignId('depreciation_account_id')->nullable()->constrained('accounts')->nullOnDelete();
    $table->foreignId('accumulated_depreciation_account_id')->nullable()->constrained('accounts')->nullOnDelete();
    $table->timestamps();
});
```

**`create_assets_table`**
```php
Schema::create('assets', function (Blueprint $table) {
    $table->id();
    $table->string('asset_code', 30)->unique();
    $table->string('name');
    $table->foreignId('asset_category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
    $table->string('serial_number', 100)->nullable();
    $table->text('description')->nullable();
    $table->string('location', 200)->nullable();
    $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    $table->date('purchase_date');
    $table->decimal('purchase_price', 15, 2);
    $table->decimal('salvage_value', 15, 2)->default(0);
    $table->decimal('current_value', 15, 2)->default(0);
    $table->decimal('accumulated_depreciation', 15, 2)->default(0);
    $table->boolean('is_depreciable')->default(true);
    $table->enum('depreciation_method', ['straight_line', 'declining_balance'])->default('straight_line');
    $table->integer('useful_life_years')->default(5);
    $table->date('last_depreciation_date')->nullable();
    $table->date('next_maintenance_date')->nullable();
    $table->enum('status', ['active', 'disposed', 'under_maintenance', 'written_off'])->default('active');
    $table->string('warranty_info')->nullable();
    $table->date('warranty_expiry')->nullable();
    $table->string('photo')->nullable();
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index('asset_category_id');
    $table->index('branch_id');
    $table->index('status');
    $table->index('purchase_date');
    $table->index('next_maintenance_date');
});
```

**`create_asset_maintenances_table`**
```php
Schema::create('asset_maintenances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
    $table->date('maintenance_date');
    $table->string('description');
    $table->decimal('cost', 15, 2)->default(0);
    $table->string('performed_by')->nullable();
    $table->date('next_maintenance_date')->nullable();
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
});
```

### 12.2 Models (3): AssetCategory, Asset (SoftDeletes), AssetMaintenance

### 12.3 Service: `AssetService` (injects JournalEntryService)

- `create()` — set current_value = purchase_price, create journal: DR Fixed Asset / CR Cash
- `dispose()` — journal for disposal gain/loss
- `runDepreciation(string $month)` — schedulable: DR Depreciation Expense (5150) / CR Accumulated Depreciation
- `recordMaintenance()` — create maintenance record, update next_maintenance_date

### 12.4 View Wiring (4 views): index, create, edit, show

---

## Module 13: ECOMMERCE (Low)

### 13.1 Migrations

**`create_ecommerce_orders_table`** — order_number, customer_id FK, customer info, shipping/billing address, status, payment_status, amounts, coupon_code, sale_id FK nullable, delivery_challan_id FK nullable

**`create_ecommerce_order_items_table`** — order_id FK cascade, product_id FK, product_name, quantity, unit_price, subtotal

**`create_coupons_table`** — code unique, name, type (fixed/percentage), value, min_order_amount, max_discount_amount, usage_limit, used_count, per_customer_limit, date range, is_active

**`create_shipping_zones_table`** — name, divisions JSON, flat_rate, free_shipping_threshold, estimated_days, is_active

**`create_ecommerce_settings_table`** — key-value store

### 13.2 Models (5): EcommerceOrder, EcommerceOrderItem, Coupon, ShippingZone, EcommerceSetting

### 13.3 Service: `EcommerceService`

- `listOrders()`, `findOrder()`, `confirmOrder()` (converts to Sale + DeliveryChallan), `updateOrderStatus()`
- `listCoupons()`, `createCoupon()`, `validateCoupon()`
- `listShippingZones()`, `createShippingZone()`
- `getSettings()`, `updateSettings()`

### 13.4 View Wiring (8 views): orders, order-show, products, coupons, coupon-create, shipping, shipping-create, settings

---

## Module 14: MARKETING (Low)

### 14.1 Migrations

**`create_sms_campaigns_table`** — name, gateway (ssl_wireless/bulksmsbd), message, audience, recipient_numbers JSON, counts, cost, status, scheduled_at

**`create_email_campaigns_table`** — similar + subject, html_body, from_name, from_email

**`create_loyalty_settings_table`** — key-value store

**`create_loyalty_transactions_table`** — customer_id FK, type (earn/redeem/adjust/expire), points, description, morphs(source)

### 14.2 Models (4): SmsCampaign, EmailCampaign, LoyaltyTransaction + settings

### 14.3 Service: `MarketingService`

- SMS/Email campaign CRUD + send
- **SMS Gateway Interface** (Strategy pattern): `SmsGatewayInterface` → `SslWirelessGateway`, `BulkSmsBdGateway`
- Loyalty: `earnPoints()`, `redeemPoints()`, settings CRUD

### 14.4 View Wiring (5 views): sms-campaigns, sms-create, email, email-create, loyalty

---

## Module 15: ACTIVITY (Low)

### 15.1 Implementation: Use `spatie/laravel-activitylog`

1. `composer require spatie/laravel-activitylog`
2. `php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"`
3. Add `LogsActivity` trait to key models:

```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

// In Sale, Product, Customer, Employee, Expense, Payment models:
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['*'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}
```

### 15.2 Service: `ActivityService`

```php
public function list(array $filters, int $perPage = 30): LengthAwarePaginator
    // Query Spatie's Activity model with filters: search, causer_id, subject_type, date range

public function find(int $id): Activity

public function clear(?string $before = null): int
    // Delete old logs, protected by permission
```

### 15.3 View Wiring (2 views): index, show

---

## Verification Plan

### Per-Module Testing
After each module:
1. `php artisan migrate` — verify tables created
2. `php artisan db:seed` — seed test data where applicable
3. Browser test: navigate to module index → verify data loads → create new record → verify in DB → edit → delete
4. For modules with accounting integration: verify journal entries created correctly
5. For Inventory: verify stock ledger entries are immutable and quantities balance

### Integration Tests (after all modules)
- **Sale → Return → Restock:** Sale → Sale Return → verify stock restored → credit note created → journal entries correct
- **Purchase → Inventory → Transfer:** Purchase → inventory increase → stock transfer → verify quantities at both warehouses
- **HR Flow:** Employee → attendance → payroll generate → approve → pay → verify journal entries (DR Salary Expense / CR Cash)
- **Quotation → Sale → Delivery:** Quotation → convert to sale → create delivery challan → track status
- **Installment Flow:** Create installment plan → record payments → verify schedule updates → completion
- **Asset Depreciation:** Create asset → run depreciation → verify accounting entries (DR Depreciation / CR Accumulated)
- **Dashboard KPIs:** Verify all KPI cards show real data from actual transactions
- **Reports:** Run each report and verify totals match actual data

---

## Critical Reference Files

| File | Purpose |
|---|---|
| `Modules/Sale/app/Services/SaleService.php` | Reference service pattern (list, find, create with DB::transaction, calculateTotals, getStats, generateNumber) |
| `Modules/Sale/app/Models/Sale.php` | Reference model pattern (SoftDeletes, fillable, casts, relationships, scopes, helpers) |
| `Modules/Accounting/app/Services/JournalEntryService.php` | `createFromSource()` for auto journal entries |
| `Modules/Payment/app/Services/PaymentService.php` | Payment recording with allocations pattern |
| `Modules/Accounting/app/Services/AccountingIntegrationService.php` | Sale/Purchase to journal bridge |
| `Modules/Sale/app/Http/Requests/StoreSaleRequest.php` | Reference FormRequest with nested items and custom rules |
| `Modules/Accounting/app/Services/CreditNoteService.php` | Credit note creation for sale returns |
