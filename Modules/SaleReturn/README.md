# SaleReturn Module

## Overview
Manages sale returns (credit notes) from customers. Handles returned items, processes refunds or store credit, adjusts inventory, and creates the corresponding accounting entries.

## Controllers
- **SaleReturnController** — Full CRUD for sale returns, including creating returns against existing sales, viewing return details, and processing refunds.

## Models
- **SaleReturn** — Header record for a return transaction, linked to the original sale and customer. Tracks return date, status, refund amount, refund method, and notes.
- **SaleReturnItem** — Line items within a return, referencing the product, quantity returned, unit price, and return reason.

## Services
- **SaleReturnService** — Business logic for processing sale returns, including inventory restocking, refund calculation, payment reversal, and accounting credit note generation.

## Routes
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/sale-returns` | List all sale returns |
| GET | `/sale-returns/create` | Create return form |
| POST | `/sale-returns` | Store new return |
| GET | `/sale-returns/{id}` | View return details |
| GET | `/sale-returns/{id}/edit` | Edit return form |
| PUT | `/sale-returns/{id}` | Update return |
| DELETE | `/sale-returns/{id}` | Delete return |

## Settings / Configuration
- Refund methods allowed (cash, original payment method, store credit).
- Whether to auto-restock inventory on return approval.
- Return window period (days allowed for returns after sale).

## Dependencies
- **Sale** — Returns reference the original sale transaction.
- **Customer** — Returns are received from customers.
- **Product** — Returned items reference product records.
- **Inventory** — Stock quantities are restored on return processing.
- **Payment** — Refund payments are issued to customers.
- **Accounting** — Credit notes and ledger reversals are created.
