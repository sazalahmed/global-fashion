# Purchase Module

## Overview
Manages purchase orders to suppliers, including approval workflows, goods receiving (GRN), purchase printing/PDF, and integration with inventory and accounting.

## Controllers
- **PurchaseController** -- Purchase CRUD, approval/cancellation, goods receiving, print, and PDF generation.
- **GrnController** -- Goods Receive Note creation and management for partial or full receiving against purchases.

## Models
- **Purchase** -- Purchase order header with supplier, date, status (draft/approved/received/cancelled), total amount, discount, tax, and payment status.
- **PurchaseItem** -- Line items within a purchase specifying product, quantity ordered, quantity received, unit cost, and subtotal.
- **GoodsReceiveNote** -- GRN header linked to a purchase, recording received date and receiving status.
- **GrnItem** -- Line items within a GRN specifying product, quantity received, and condition notes.

## Services
- **PurchaseService** -- Business logic for purchase creation, approval workflow, status management, cost calculations, and PDF generation.
- **GrnService** -- Goods receiving logic including partial receiving, quality checks, stock updates on receive, and GRN document generation.

## Routes
| Method | Route | Description |
|--------|-------|-------------|
| Resource | `/purchases` | Purchase CRUD |
| PATCH | `/purchases/{id}/approve` | Approve a purchase |
| PATCH | `/purchases/{id}/cancel` | Cancel a purchase |
| POST | `/purchases/{id}/receive` | Receive goods (create GRN) |
| GET | `/purchases/{id}/print` | Print purchase order |
| GET | `/purchases/{id}/pdf` | Download purchase PDF |
| GET | `/grn` | List all GRNs |
| GET | `/grn/create` | Create GRN form |
| POST | `/grn` | Store GRN |
| GET | `/grn/{id}` | View GRN details |

## Settings / Configuration
No module-specific settings. Purchase numbering format and default terms are managed in the Setting module.

## Dependencies
- **Supplier** -- Purchases are placed with suppliers.
- **Product** -- Purchase items reference products for restocking.
- **Inventory** -- Stock levels are updated when goods are received via GRN.
- **Payment** -- Purchase payments are tracked against supplier invoices.
- **Accounting** -- Purchases post journal entries for accounts payable and inventory valuation.
