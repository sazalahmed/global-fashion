# Quotation Module

## Overview
Manages sales quotations (estimates/proforma invoices) for customers. Allows creating itemized quotes, sending them to customers, and converting accepted quotations directly into sales.

## Controllers
- **QuotationController** — Full CRUD for quotations, plus actions for converting to sale, printing, generating PDF, and sharing via email or link.

## Models
- **Quotation** — Header record containing customer info, quotation date, validity period, status (draft, sent, accepted, rejected, expired), totals, and notes.
- **QuotationItem** — Line items with product reference, quantity, unit price, discount, tax, and line total.

## Services
- **QuotationService** — Business logic for quotation creation, status management, conversion to sale, PDF generation, and expiry handling.

## Routes
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/quotations` | List all quotations |
| GET | `/quotations/create` | Create quotation form |
| POST | `/quotations` | Store new quotation |
| GET | `/quotations/{id}` | View quotation details |
| GET | `/quotations/{id}/edit` | Edit quotation form |
| PUT | `/quotations/{id}` | Update quotation |
| DELETE | `/quotations/{id}` | Delete quotation |
| POST | `/quotations/{id}/convert` | Convert quotation to sale |
| GET | `/quotations/{id}/print` | Print quotation |
| GET | `/quotations/{id}/pdf` | Download quotation as PDF |

## Settings / Configuration
- Default quotation validity period (days).
- Quotation number prefix and sequence format.
- Auto-expiry of quotations past validity date.

## Dependencies
- **Customer** — Quotations are issued to customers.
- **Product** — Line items reference products for pricing and descriptions.
- **Sale** — Accepted quotations can be converted into sale records.
