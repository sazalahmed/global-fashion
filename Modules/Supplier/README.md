# Supplier Module

## Overview
Manages supplier records, supplier grouping/categorization, and supplier payment tracking. Provides a complete supplier ledger showing all transactions, payments, and outstanding balances.

## Controllers
- **SupplierController** — Full CRUD for supplier records including contact details, business info, payment terms, and opening balance.
- **SupplierGroupController** — CRUD for supplier groups/categories used to organize suppliers (e.g., local, international, raw materials, packaging).
- **SupplierPaymentController** — Records and manages payments made to suppliers, tracks payment history, and generates supplier ledger reports.

## Models
- **Supplier** — Supplier master record with name, company, phone, email, address, tax ID, payment terms, credit limit, opening balance, and group assignment.
- **SupplierGroup** — Categorization for suppliers, with name and description.
- **SupplierPayment** — Individual payment records against a supplier, tracking amount, payment method, reference number, date, and linked purchase.

## Services
- **SupplierService** — Business logic for supplier management, balance calculations, ledger generation, and payment processing.

## Routes
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/suppliers` | List all suppliers |
| GET | `/suppliers/create` | Create supplier form |
| POST | `/suppliers` | Store new supplier |
| GET | `/suppliers/{id}` | View supplier details & ledger |
| GET | `/suppliers/{id}/edit` | Edit supplier form |
| PUT | `/suppliers/{id}` | Update supplier |
| DELETE | `/suppliers/{id}` | Delete supplier |
| GET | `/supplier-groups` | List supplier groups |
| POST | `/supplier-groups` | Create group |
| PUT | `/supplier-groups/{id}` | Update group |
| DELETE | `/supplier-groups/{id}` | Delete group |
| GET | `/suppliers/{id}/payments` | Supplier payment history |
| POST | `/suppliers/{id}/payments` | Record payment to supplier |
| GET | `/suppliers/{id}/ledger` | Full supplier ledger |

## Settings / Configuration
- Default payment terms for new suppliers.
- Default credit limit.
- Supplier code prefix and auto-generation format.

## Dependencies
- **Purchase** — Suppliers are linked to purchase transactions.
- **Payment** — Payment methods and processing for supplier payments.
- **Branch** — Suppliers can be associated with specific branches.
