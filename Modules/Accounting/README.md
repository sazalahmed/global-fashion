# Accounting Module

## Overview
Full double-entry accounting system for BizPOS Pro. Manages the chart of accounts, journal entries, bank reconciliation, credit/debit notes, payment receipts, and generates core financial reports (general ledger, trial balance, profit & loss, balance sheet, cash flow statement).

## Controllers
- **ChartOfAccountsController** - CRUD for the chart of accounts (assets, liabilities, equity, revenue, expenses)
- **JournalEntryController** - Create, view, post, and void journal entries with multi-line debits/credits
- **AccountingReportController** - Generates financial reports: general ledger, trial balance, P&L, balance sheet, cash flow
- **BankReconciliationController** - Reconcile bank statements against recorded transactions
- **PaymentReceiptController** - Record and manage payment receipts
- **CreditNoteController** - Issue credit notes against sales/invoices
- **DebitNoteController** - Issue debit notes against purchases
- **OpeningBalanceController** - Set opening balances for accounts at the start of a fiscal period
- **OtherTransactionController** - Record miscellaneous accounting transactions

## Models
- **Account** - Chart of accounts entries (account code, name, type, parent, balance)
- **JournalEntry** - Journal entry header (date, reference, status, narration)
- **JournalEntryLine** - Individual debit/credit lines within a journal entry
- **BankReconciliation** - Bank reconciliation session (bank account, statement date, closing balance)
- **BankReconciliationItem** - Individual matched/unmatched items within a reconciliation
- **CreditNote** - Credit note header (customer, date, amount, linked invoice)
- **CreditNoteItem** - Line items within a credit note
- **DebitNote** - Debit note header (supplier, date, amount, linked purchase)
- **DebitNoteItem** - Line items within a debit note
- **PaymentReceipt** - Payment receipt records
- **OtherTransaction** - Miscellaneous transaction records

## Services
- **ChartOfAccountsService** - Business logic for account hierarchy management and validation
- **JournalEntryService** - Posting, voiding, and balancing journal entries (debits must equal credits)
- **AccountingReportService** - Report generation logic for GL, trial balance, P&L, balance sheet, cash flow
- **BankReconciliationService** - Matching transactions, calculating discrepancies
- **CreditNoteService** - Credit note creation, application against invoices
- **DebitNoteService** - Debit note creation, application against purchases
- **PaymentReceiptService** - Payment receipt processing
- **AccountingIntegrationService** - Bridges other modules (sales, purchases, expenses) into accounting entries

## Routes
- **Opening Balances** - Set/update opening balances for accounts
- **Chart of Accounts** - Full CRUD with hierarchical account structure
- **Journal Entries** - CRUD plus post and void actions
- **Financial Reports** - General ledger, trial balance, profit & loss, balance sheet, cash flow statement
- **Bank Reconciliation** - Create, match items, finalize reconciliation sessions
- **Credit/Debit Notes** - CRUD for both note types
- **Payment Receipts** - CRUD for payment receipt records

## Settings / Configuration
- Fiscal year start/end dates
- Default accounts for automatic postings (sales revenue, COGS, accounts receivable/payable)
- Currency and number formatting (BDT lakh system)

## Dependencies
- **Payment** - Payment transactions feed into accounting entries
- **Sale** - Sales create receivable and revenue journal entries
- **Purchase** - Purchases create payable and expense journal entries
- **Expense** - Expense records post to appropriate expense accounts
