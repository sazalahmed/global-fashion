# PurchaseReturn Module

## Overview
Manages purchase returns (debit notes) to suppliers. Supports multiple return types, tracks returned items with quantities and reasons, and integrates with inventory and accounting to reverse stock and financial entries.

## Controllers
- **PurchaseReturnController** — CRUD operations for purchase returns, including creating returns against existing purchases, viewing return details, and generating print/PDF output.
- **PurchaseReturnTypeController** — Manages return type categories (e.g., defective, expired, wrong item, excess quantity).

## Models
- **PurchaseReturn** — Header record for a return transaction, linked to the original purchase and supplier. Tracks return date, status, total amount, and notes.
- **PurchaseReturnItem** — Line items within a return, referencing the product, quantity returned, unit cost, and return reason.
- **PurchaseReturnType** — Lookup table for categorizing the reason/type of return.

## Services
- **PurchaseReturnService** — Business logic for creating and processing purchase returns, including inventory adjustment (stock decrease), supplier credit calculation, and accounting entry generation.

## Form Requests
- **StorePurchaseReturnRequest** - Validates purchase return creation (purchase, supplier, items, date, status)
- **UpdatePurchaseReturnRequest** - Validates purchase return update (same as store without status)
- **StorePurchaseReturnTypeRequest** - Validates return type creation (name unique, description)
- **UpdatePurchaseReturnTypeRequest** - Validates return type update (name unique excluding current)

## Routes
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/purchase-returns` | List all purchase returns |
| GET | `/purchase-returns/create` | Create return form |
| POST | `/purchase-returns` | Store new return |
| GET | `/purchase-returns/{id}` | View return details |
| GET | `/purchase-returns/{id}/edit` | Edit return form |
| PUT | `/purchase-returns/{id}` | Update return |
| DELETE | `/purchase-returns/{id}` | Delete return |
| GET/POST | `/purchase-return-types` | Return type CRUD |

## Settings / Configuration
- Default return type selection.
- Whether to auto-adjust inventory on return approval.
- Return approval workflow (if enabled).

## Dependencies
- **Purchase** — Returns reference original purchase records.
- **Supplier** — Returns are issued against suppliers.
- **Product** — Returned items reference product records.
- **Inventory** — Stock quantities are adjusted on return processing.
- **Accounting** — Debit notes and ledger entries are created for financial tracking.
