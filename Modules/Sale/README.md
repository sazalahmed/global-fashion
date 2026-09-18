# Sale Module

## Overview
Core sales management module handling the full lifecycle of sales transactions. Supports creating sales from both the back-office and POS, managing sale items, processing payments, and generating invoices in print, PDF, email, and SMS formats.

## Controllers
- **SaleController** — Full CRUD for sales, plus print, PDF generation, email invoice, SMS notification, and share link actions.

## Models
- **Sale** — Header record for a sale transaction. Tracks customer, branch, sale date, status (draft, completed, cancelled), payment status (paid, partial, unpaid), discount, tax, total, and source (POS/web/eCommerce).
- **SaleItem** — Line items within a sale, referencing product, variant, quantity, unit price, discount, tax, and line total.

## Services
- **SaleService** — Business logic for sale creation, payment processing, inventory deduction, accounting entry generation, invoice formatting, and sale cancellation.

## Routes
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/sales` | List all sales |
| GET | `/sales/create` | Create sale form |
| POST | `/sales` | Store new sale |
| GET | `/sales/{id}` | View sale details |
| GET | `/sales/{id}/edit` | Edit sale form |
| PUT | `/sales/{id}` | Update sale |
| DELETE | `/sales/{id}` | Delete sale |
| GET | `/sales/{id}/print` | Print invoice |
| GET | `/sales/{id}/pdf` | Download invoice as PDF |
| POST | `/sales/{id}/email` | Email invoice to customer |
| POST | `/sales/{id}/sms` | Send SMS notification |
| GET | `/sales/{id}/share` | Generate shareable link |

## Settings / Configuration
- Invoice number prefix and sequence format.
- Default tax rate applied to sales.
- Default payment method.
- Invoice template selection (A4, thermal, custom).
- Whether to auto-deduct inventory on sale completion.

## Dependencies
- **Customer** — Sales are linked to customer records.
- **Product** — Sale items reference products and variants.
- **Payment** — Payment collection and method tracking.
- **Inventory** — Stock is deducted on sale completion.
- **Accounting** — Revenue entries and ledger postings are generated.
