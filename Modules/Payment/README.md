# Payment Module

## Overview
Centralized payment processing for sales, purchases, and expenses. Manages payment accounts, balance transfers between accounts, bank records, advance payments, and account ledgers.

## Controllers
- **PaymentController** -- Payment CRUD, advance payments, party search, and outstanding invoice lookups.
- **PaymentAccountController** -- Payment account CRUD, balance transfers, bank management, and account ledger views.

## Models
- **Payment** -- Individual payment records with amount, method (Cash, bKash, Nagad, Rocket, Card, Bank Transfer), date, reference, and linked transaction (sale/purchase/expense).
- **PaymentAccount** -- Accounts where money is held (cash register, bKash account, bank account) with current balance.
- **PaymentAllocation** -- Maps payments to specific invoices when a single payment covers multiple outstanding amounts.
- **Bank** -- Bank records (DBBL, BRAC Bank, Islami Bank, City Bank, etc.) with branch and account details.
- **BalanceTransfer** -- Records of money transferred between payment accounts with source, destination, amount, and date.

## Services
- **PaymentService** -- Payment processing, advance handling, multi-invoice allocation, outstanding balance calculations, party search, and ledger generation.

## Routes
| Method | Route | Description |
|--------|-------|-------------|
| Resource | `/payments` | Payment CRUD |
| POST | `/payments/advance` | Record advance payment |
| GET | `/payments/party-search` | Search customers/suppliers |
| GET | `/payments/outstanding` | List outstanding invoices for a party |
| Resource | `/payment-accounts` | Payment account CRUD |
| POST | `/payment-accounts/transfer` | Transfer between accounts |
| Resource | `/payment-accounts/banks` | Bank CRUD |
| GET | `/payment-accounts/{id}/ledger` | Account ledger view |

## Settings / Configuration
- Enabled payment methods: Cash, bKash, Nagad, Rocket, Card (Visa/Master), Bank Transfer.
- Default payment account per method.
- Payment terms and grace periods.

## Dependencies
- **Sale** -- Receives payments against sales invoices.
- **Purchase** -- Records payments to suppliers for purchases.
- **Customer** -- Tracks customer advances and outstanding dues.
- **Supplier** -- Tracks supplier advances and outstanding dues.
- **Accounting** -- Payments post journal entries for double-entry bookkeeping.
