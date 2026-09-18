# Bug_16 — Remove Warehouse System‑Wide (Execution Plan)

**Status:** PLAN — awaiting approval. Nothing in this document has been executed.
**Approach (confirmed):** edit the **main migration files** (project is pre‑production; data loss acceptable). Removal will require `php artisan migrate:fresh --seed`.

This change is the master for several QA issues and **subsumes**:
- **Bug_02** — remove Warehouse field from sales
- **Bug_15** — remove Stock Transfer (create + overview)
- **Bug_06 (partial)** — "remove the branch stock" (the second warehouse / per‑warehouse stock)
- **Related: Bug_14** — adjustment variants (the adjustment form loses its warehouse selector here)

---

## 1. Goal & end state

Eliminate the "warehouse" concept entirely. Stock becomes **single‑location** (one implicit location for the whole business). After removal:

- No `warehouses` / `warehouse_locations` tables, no Warehouse module.
- `warehouse_stock` is keyed by `(product_id, variant_id)` only — one quantity row per product/variant.
- Stock ledger, adjustments, sales, purchases, GRNs, and manufacturing stock no longer carry `warehouse_id`.
- **Stock Transfer** feature is deleted outright (it only exists to move stock between warehouses).
- The central stock mutator `InventoryService::adjustStock()` drops its `$warehouseId` parameter.

---

## 2. Key decisions / assumptions (please confirm §9)

1. **Manufacturing is included.** `rm_stocks`, `rm_stock_ledger`, `fabric_issuance_items`, `fabric_return_items` all carry `warehouse_id`. "Complete system" ⇒ remove there too. *(This is the largest secondary cost — confirm in §9.)*
2. **Stock Transfer is removed entirely** (Bug_15), not just hidden.
3. **No replacement "default location" setting** is needed — there is only one implicit location, so `warehouse_id` columns are simply dropped (not replaced by a nullable settings reference).
4. **`migrate:fresh` is acceptable.** Editing the original `create_*` migrations means existing DBs must be rebuilt. The opening stock we seeded earlier (67 rows) will be wiped and must be re‑seeded.
5. Latent bug noticed: `SaleReturnService` references `$return->branch->default_warehouse_id`, a column that **does not exist** on `branches`. Removing warehouse lets us delete that dead reference.

---

## 3. Database changes (edit main migrations)

### 3a. Tables to DROP (delete the migration files)
| Table | Migration file |
|---|---|
| `warehouses` | `Modules/Warehouse/.../2026_03_13_000001_create_warehouses_table.php` |
| `warehouse_locations` | `Modules/Warehouse/.../2026_03_13_000002_create_warehouse_locations_table.php` |
| (rename helper) | `Modules/Warehouse/.../2026_04_13_023125_rename_main_warehouse_to_stock.php` |
| `stock_transfers` | `Modules/Inventory/.../2026_03_13_200004_create_stock_transfers_table.php` |
| `stock_transfer_items` | `Modules/Inventory/.../2026_03_13_200005_create_stock_transfer_items_table.php` |

### 3b. Columns to REMOVE (edit the `create_*` migration in place)
| Table | Change |
|---|---|
| `warehouse_stock` | drop `warehouse_id`; change unique key `(product_id, variant_id, warehouse_id)` → `(product_id, variant_id)` |
| `stock_adjustments` | drop `warehouse_id` (currently **NOT NULL**) |
| `stock_ledger` | drop `warehouse_id` (NOT NULL); fix index `(product_id, warehouse_id, created_at)` → `(product_id, created_at)` |
| `sales` | drop `warehouse_id` (nullable FK) |
| `purchases` | drop `warehouse_id` (nullable) |
| `goods_receive_notes` | drop `warehouse_id` (nullable) |
| `rm_stocks` | drop `warehouse_id`; unique `(raw_material_id, warehouse_id)` → `(raw_material_id)` |
| `rm_stock_ledger` | drop `warehouse_id` (NOT NULL) |
| `fabric_issuance_items` | drop `warehouse_id` |
| `fabric_return_items` | drop `warehouse_id` |

### 3c. Seeders
- `database/seeders/DatabaseSeeder.php` — remove the `WarehouseDatabaseSeeder::class` call (line ~28).
- Delete `Modules/Warehouse/database/seeders/*` and `WarehouseFactory`.

---

## 4. Code changes by area

### 4a. Central refactor (do FIRST — everything depends on it)
**`Modules/Inventory/app/Services/InventoryService.php`**
- Change `adjustStock(productId, variantId, warehouseId, qty, ...)` → `adjustStock(productId, variantId, qty, ...)`.
- `getStockLevel`, `getStockLevels`, `getStockHistory`, `getLowStockProducts` — drop all `warehouse_id` filters/eager‑loads.
- **Delete** transfer methods: `listTransfers`, `findTransfer`, `createTransfer`, `shipTransfer`, `receiveTransfer`, `cancelTransfer`, `updateTransfer`, `deleteTransfer`, `generateTransferNumber`.
- `createAdjustment`/`approveAdjustment`/`cancelAdjustment` — drop `warehouse_id`.
- `getStats()` — drop transfer/warehouse counts.

### 4b. Models (remove `warehouse_id` from `$fillable`, the `warehouse()`/`fromWarehouse()`/`toWarehouse()` relations, and `byWarehouse` scopes)
Sale, Purchase, GoodsReceiveNote, WarehouseStock, StockAdjustment, StockLedger, RmStock, RmStockLedger, FabricIssuanceItem, FabricReturnItem. **Delete** StockTransfer + StockTransferItem models.

### 4c. Services
- **SaleService** — delete `resolveWarehouseId()`; remove `warehouse_id` from create/update and the `adjustStock` calls (use new signature). Drop `warehouse` eager‑load in `find()`.
- **PurchaseService** — remove warehouse resolution in GRN receive.
- **SaleReturnService** — remove `branch->default_warehouse_id` references (lines ~219, ~302).
- **Manufacturing services** (RmReceive/FabricIssuance/FabricReturn) — remove `warehouse_id`.
- **POSService** — `getStockLevel(productId, variantId)` already aggregates; just drop any warehouse arg.

### 4d. Form Requests (remove `warehouse_id` rules + message keys)
StoreSaleRequest, UpdateSaleRequest, StorePurchaseRequest, UpdatePurchaseRequest, StoreAdjustmentRequest, StoreProductRequest, UpdateProductRequest. **Delete** StoreTransferRequest.

### 4e. Controllers (stop loading/passing `$warehouses`; remove transfer actions)
SaleController, PurchaseController, GrnController, ProductController, Manufacturing controllers, and **InventoryController** (remove `transfers*` methods + `$warehouses` in index/alerts/ledger/adjustments).

### 4f. Views
- Remove warehouse dropdown/filter/column from: sales create/edit, purchase receive, product create/edit/show, inventory index/alerts/ledger/stock‑ledger/reconciliation, adjustments create/edit, manufacturing issue/return/receive views.
- **Delete** `transfers-create/edit/transfers.blade.php`.
- **Delete** entire `Modules/Warehouse/resources/views/`.
- Exports: `app/Exports/StockExport.php`, `StockLedgerExport.php` — drop warehouse join/column.

### 4g. Routes / navigation / settings
- Delete `Modules/Warehouse/routes/web.php` + `api.php`.
- Remove `inventory.transfers.*` routes (Inventory routes file).
- `routes/api.php` — remove `inventory/warehouses` + `warehouses` settings endpoints; update `InventoryApiController`/`SettingsApiController`.
- `Modules/Core/.../sidebar.blade.php` — remove the Warehouses menu item (lines ~286‑288) **and** the Stock Transfer entry (Bug_15).
- `Modules/Setting/.../SettingController.php` — remove `'warehouses'` from sidebar config + allowlist (lines ~175, ~223).

### 4h. Module teardown
- Delete the `Modules/Warehouse/` directory.
- Remove its provider registration (`bootstrap/providers.php` / `modules_statuses.json` / composer merge — verify how nwidart registers it) so the app boots without it.

### 4i. Tests / lang
- Delete Warehouse tests; update Inventory/POS tests that create warehouses.
- Remove warehouse strings from `lang/en.json` / `lang/bn.json`.

---

## 5. Execution order (phased, each phase compiles & boots)

1. **DB layer** — edit/delete migrations (§3).
2. **Inventory core** — refactor `InventoryService` + WarehouseStock/StockLedger models (§4a/4b).
3. **Sales + Purchase + GRN** (§4b‑4f for those modules).
4. **Manufacturing** (§4b‑4f).
5. **Product** create/edit stock init (ties into Bug_06).
6. **Routes / sidebar / settings / API / exports** (§4e‑4g).
7. **Delete Warehouse module + deregister provider** (§4h).
8. **Seeders / tests / lang** (§3c, §4i).
9. `php artisan migrate:fresh --seed`, then full route sweep + form smoke tests.
10. **Re‑seed opening stock** (re‑run the per‑variant stock creation we did earlier, now warehouse‑free).

---

## 6. Risk areas
- **`adjustStock` signature** is the lynchpin — every stock movement (sales confirm/cancel, purchase receive, adjustments, manufacturing) calls it. Refactor + grep every caller.
- **NOT‑NULL `warehouse_id`** on `stock_adjustments`, `stock_ledger`, `rm_stock_ledger`, fabric items — dropping the column is clean only via `migrate:fresh` (chosen).
- **Manufacturing** is the biggest secondary surface; if out of scope, we must keep `warehouses` alive for it (contradicts "complete system"). See §9.
- **`modules_statuses.json` / provider registration** — app must not try to boot the deleted Warehouse provider.

---

## 7. Verification checklist (post‑change)
- `php artisan migrate:fresh --seed` runs clean.
- `php artisan route:list` has zero `warehouse`/`transfers` routes.
- `grep -rin "warehouse" Modules app routes` returns only incidental matches (none functional).
- Sales create/edit, Purchase receive, Adjustment create, Product create — all load 200 and submit successfully.
- POS sale deducts stock; sale confirm/cancel adjusts `warehouse_stock` (now single‑row) correctly; ledger writes.
- Inventory list + exports work; Stock Transfer routes 404.

---

## 8. Out‑of‑scope (handled by sibling bugs)
- Bug_06 product‑create initial stock UX, Bug_14 adjustment variant picker UX — separate tickets; this plan only removes the *warehouse selector* from those screens.

---

## 9. Decisions (CONFIRMED)
1. **Manufacturing — INCLUDED.** Strip `warehouse_id` from `rm_stocks`, `rm_stock_ledger`, `fabric_issuance_items`, `fabric_return_items` and their models/services/views. Truly system‑wide.
2. **Stock Transfer — DELETED** outright (feature + tables + history). No preservation.
3. **API/Mobile — DROP NOW.** Remove `warehouse_id` from API responses/endpoints immediately; React Native types updated in a separate follow‑up (tracked, not part of this change).
