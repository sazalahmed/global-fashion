# Inventory Module

## Overview
Comprehensive inventory management covering stock levels, adjustments, transfers between warehouses/branches, stock ledger history, low-stock alerts, and reconciliation.

## Controllers
- **InventoryController** -- Stock overview, alerts, ledger, adjustments (CRUD + approval), reconciliation, and transfers (CRUD + ship/receive).

## Models
- **WarehouseStock** -- Current stock quantity per product per warehouse/branch location.
- **StockAdjustment** -- Header record for stock adjustments (increase/decrease) with reason, date, and approval status.
- **StockAdjustmentItem** -- Line items within a stock adjustment specifying product, quantity change, and cost.
- **StockTransfer** -- Header record for transferring stock between warehouses/branches with status tracking (draft/shipped/received).
- **StockTransferItem** -- Line items within a stock transfer specifying product and quantity.
- **StockLedger** -- Chronological log of all stock movements (sales, purchases, adjustments, transfers) per product per location.

## Services
- **InventoryService** -- Stock level queries, adjustment processing, transfer workflows (create/ship/receive), stock alerts, and ledger recording.
- **ReconciliationService** -- Compares physical stock counts against system records, identifies discrepancies, and generates adjustment entries.

## Routes
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/inventory` | Stock overview dashboard |
| GET | `/inventory/alerts` | Low-stock and out-of-stock alerts |
| GET | `/inventory/ledger` | Stock ledger with filters |
| Resource | `/inventory/adjustments` | Stock adjustment CRUD |
| PATCH | `/inventory/adjustments/{id}/approve` | Approve a stock adjustment |
| GET | `/inventory/reconciliation` | Stock reconciliation interface |
| POST | `/inventory/reconciliation` | Process reconciliation |
| Resource | `/inventory/transfers` | Stock transfer CRUD |
| PATCH | `/inventory/transfers/{id}/ship` | Mark transfer as shipped |
| PATCH | `/inventory/transfers/{id}/receive` | Mark transfer as received |

## Settings / Configuration
No module-specific settings. Low-stock thresholds are configured per product.

## Dependencies
- **Product** -- Stock is tracked per product/variant.
- **Warehouse** -- Stock is stored in and transferred between warehouses.
- **Branch** -- Stock levels are tracked per branch location.
