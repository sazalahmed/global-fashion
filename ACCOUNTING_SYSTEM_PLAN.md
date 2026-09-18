# Accounting System — Complete Implementation Plan

## Context
BizPOS Pro needs a full double-entry accounting system with all financial documents. Existing modules (Accounting, Expense, Payment) have comprehensive UI views with hardcoded demo data but zero backend. The Purchase module already has models/services/migrations — it's the only financial module with backend implementation.

**Core principle:** All financial balances derive from journal entries. Every debit has an equal credit. Account balances are computed by summing journal entry lines.

**Scope:** Chart of Accounts, Journal Entries, General Ledger, Cash Flow, Expenses, Payments, Bank Management, Financial Reports, Sales Invoices, Purchase Invoices, Credit Notes, Debit Notes, Payment Receipts.

---

## Phase 1: Chart of Accounts + Journal Entries

### 1.1 Migration: `Modules/Accounting/database/migrations/2026_03_13_100001_create_accounts_table.php`

```php
Schema::create('accounts', function (Blueprint $table) {
    $table->id();
    $table->string('account_code', 20)->unique();
    $table->string('account_name', 255);
    $table->enum('account_type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
    $table->string('sub_type', 50);
    $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
    $table->text('description')->nullable();
    $table->decimal('opening_balance', 15, 2)->default(0);
    $table->enum('opening_balance_type', ['debit', 'credit'])->default('debit');
    $table->date('opening_balance_date')->nullable();
    $table->boolean('is_system')->default(false);
    $table->boolean('is_bank_account')->default(false);
    $table->string('bank_name', 255)->nullable();
    $table->string('bank_account_number', 100)->nullable();
    $table->string('bank_branch', 255)->nullable();
    $table->string('status', 20)->default('active');
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index('account_type');
    $table->index('sub_type');
    $table->index('parent_id');
    $table->index('status');
    $table->index('is_bank_account');
});
```

### 1.2 Migration: `2026_03_13_100002_create_journal_entries_table.php`

```php
Schema::create('journal_entries', function (Blueprint $table) {
    $table->id();
    $table->string('entry_number', 50)->unique();
    $table->date('entry_date');
    $table->string('reference', 100)->nullable();
    $table->text('description');
    $table->string('source_type', 50)->nullable();
    $table->unsignedBigInteger('source_id')->nullable();
    $table->decimal('total_amount', 15, 2)->default(0);
    $table->string('status', 20)->default('draft');
    $table->string('attachment_path', 500)->nullable();
    $table->text('notes')->nullable();
    $table->timestamp('posted_at')->nullable();
    $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('voided_at')->nullable();
    $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
    $table->text('void_reason')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index('entry_date');
    $table->index('status');
    $table->index(['source_type', 'source_id']);
    $table->index('branch_id');
});
```

### 1.3 Migration: `2026_03_13_100003_create_journal_entry_lines_table.php`

```php
Schema::create('journal_entry_lines', function (Blueprint $table) {
    $table->id();
    $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
    $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
    $table->string('description', 500)->nullable();
    $table->decimal('debit_amount', 15, 2)->default(0);
    $table->decimal('credit_amount', 15, 2)->default(0);
    $table->timestamps();

    $table->index('journal_entry_id');
    $table->index('account_id');
});
```

### 1.4 Model: `Modules/Accounting/app/Models/Account.php`

```php
class Account extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_code', 'account_name', 'account_type', 'sub_type',
        'parent_id', 'description', 'opening_balance', 'opening_balance_type',
        'opening_balance_date', 'is_system', 'is_bank_account',
        'bank_name', 'bank_account_number', 'bank_branch',
        'status', 'created_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_system' => 'boolean',
        'is_bank_account' => 'boolean',
        'opening_balance_date' => 'date',
    ];

    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id'); }
    public function journalEntryLines(): HasMany { return $this->hasMany(JournalEntryLine::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeActive($q) { return $q->where('status', 'active'); }
    public function scopeByType($q, string $type) { return $q->where('account_type', $type); }
    public function scopeBankAccounts($q) { return $q->where('is_bank_account', true); }
    public function scopeSearch($q, string $term) {
        return $q->where(fn($q) => $q->where('account_code', 'like', "%{$term}%")
            ->orWhere('account_name', 'like', "%{$term}%"));
    }

    public function isDebitNormal(): bool {
        return in_array($this->account_type, ['asset', 'expense']);
    }

    public function getBalanceAttribute(): float {
        $lineBalance = $this->journalEntryLines()
            ->whereHas('journalEntry', fn($q) => $q->where('status', 'posted'))
            ->selectRaw('COALESCE(SUM(debit_amount),0) as total_debit, COALESCE(SUM(credit_amount),0) as total_credit')
            ->first();
        $netMovement = $this->isDebitNormal()
            ? ($lineBalance->total_debit - $lineBalance->total_credit)
            : ($lineBalance->total_credit - $lineBalance->total_debit);
        $openingSign = ($this->opening_balance_type === 'debit' && $this->isDebitNormal())
            || ($this->opening_balance_type === 'credit' && !$this->isDebitNormal()) ? 1 : -1;
        return ($this->opening_balance * $openingSign) + $netMovement;
    }
}
```

### 1.5 Model: `Modules/Accounting/app/Models/JournalEntry.php`

```php
class JournalEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'entry_number', 'entry_date', 'reference', 'description',
        'source_type', 'source_id', 'total_amount', 'status',
        'attachment_path', 'notes', 'posted_at', 'posted_by',
        'voided_at', 'voided_by', 'void_reason', 'created_by', 'branch_id',
    ];

    protected $casts = [
        'entry_date' => 'date', 'total_amount' => 'decimal:2',
        'posted_at' => 'datetime', 'voided_at' => 'datetime',
    ];

    public function lines(): HasMany { return $this->hasMany(JournalEntryLine::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function postedByUser(): BelongsTo { return $this->belongsTo(User::class, 'posted_by'); }

    public function scopePosted($q) { return $q->where('status', 'posted'); }
    public function scopeDraft($q) { return $q->where('status', 'draft'); }
    public function scopeByDateRange($q, $from, $to) {
        return $q->when($from, fn($q) => $q->where('entry_date', '>=', $from))
            ->when($to, fn($q) => $q->where('entry_date', '<=', $to));
    }
    public function scopeSearch($q, $term) {
        return $q->where(fn($q) => $q->where('entry_number', 'like', "%{$term}%")
            ->orWhere('reference', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%"));
    }

    public function isBalanced(): bool { return bccomp($this->totalDebit(), $this->totalCredit(), 2) === 0; }
    public function isEditable(): bool { return $this->status === 'draft'; }
    public function totalDebit(): string { return $this->lines->sum('debit_amount'); }
    public function totalCredit(): string { return $this->lines->sum('credit_amount'); }
}
```

### 1.6 Model: `JournalEntryLine.php`

```php
class JournalEntryLine extends Model
{
    protected $fillable = ['journal_entry_id', 'account_id', 'description', 'debit_amount', 'credit_amount'];
    protected $casts = ['debit_amount' => 'decimal:2', 'credit_amount' => 'decimal:2'];

    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }
}
```

### 1.7 Service: `ChartOfAccountsService.php`

Key methods:
- `list(array $filters, int $perPage = 50): LengthAwarePaginator`
- `getAccountsGroupedByType(): Collection` — for `<optgroup>` dropdowns
- `getAccountsHierarchy(): array` — nested: type → sub_type → accounts
- `find(int $id): Account`
- `create(array $data): Account`
- `update(Account, array $data): Account`
- `delete(Account)` — blocks if is_system or has journal lines
- `getStats(): array` — total_accounts, total_assets, total_liabilities, total_equity, total_revenue, total_expenses
- `getSubTypes(): array` — maps type → sub_types

### 1.8 Service: `JournalEntryService.php`

Key methods:
- `list(array $filters, int $perPage = 15): LengthAwarePaginator`
- `find(int $id): JournalEntry`
- `create(array $data): JournalEntry` — DB::transaction, validates balanced
- `post(JournalEntry): JournalEntry` — draft→posted
- `void(JournalEntry, string $reason): JournalEntry` — creates reversing entry
- `delete(JournalEntry)` — drafts only
- `createFromSource(string $sourceType, int $sourceId, array $lines, string $desc, ?string $ref, ?Carbon $date): JournalEntry` — auto-creates posted entry (used by other modules)
- `generateEntryNumber(): string` — `JE-2026-0235`
- `getStats(): array` — total, posted, draft, total_debits_this_month

### 1.9 Form Requests

**StoreAccountRequest** — account_code unique, account_type enum, sub_type required, parent_id exists, opening_balance numeric
**UpdateAccountRequest** — same, unique ignores self
**StoreJournalEntryRequest** — date, description required; lines array min:2; custom `withValidator()`: total debits == credits, each line has debit XOR credit
**UpdateJournalEntryRequest** — same + validates entry is draft

### 1.10 Controller Split (replace monolithic AccountingController)

**ChartOfAccountsController:**
- `index(Request)` → passes `$stats`, `$accountsByType`, `$filters`
- `create()` → passes `$parentAccounts`, `$subTypes`
- `store(StoreAccountRequest)` → redirect with success
- `edit(Account)` → passes `$account`, `$parentAccounts`, `$subTypes`
- `update(UpdateAccountRequest, Account)` → redirect
- `destroy(Account)` → try/catch for system/has-entries

**JournalEntryController:**
- `index(Request)` → passes `$stats`, `$entries` (paginated)
- `create()` → passes `$accounts` (grouped), `$nextEntryNumber`
- `store(StoreJournalEntryRequest)` → handles attachment upload, checks `action=post`
- `show(JournalEntry)` → passes `$entry` with lines.account
- `post(JournalEntry)` → POST route
- `void(Request, JournalEntry)` → validates void_reason
- `destroy(JournalEntry)` → drafts only

### 1.11 Routes

```php
Route::middleware('auth')->prefix('accounting')->name('accounting.')->group(function () {
    // Chart of Accounts
    Route::get('/chart-of-accounts', [ChartOfAccountsController::class, 'index'])->name('chart-of-accounts');
    Route::get('/chart-of-accounts/create', [ChartOfAccountsController::class, 'create'])->name('chart-of-accounts.create');
    Route::post('/chart-of-accounts', [ChartOfAccountsController::class, 'store'])->name('chart-of-accounts.store');
    Route::get('/chart-of-accounts/{account}/edit', [ChartOfAccountsController::class, 'edit'])->name('chart-of-accounts.edit');
    Route::put('/chart-of-accounts/{account}', [ChartOfAccountsController::class, 'update'])->name('chart-of-accounts.update');
    Route::delete('/chart-of-accounts/{account}', [ChartOfAccountsController::class, 'destroy'])->name('chart-of-accounts.destroy');

    // Journal Entries
    Route::get('/journal-entries', [JournalEntryController::class, 'index'])->name('journal-entries');
    Route::get('/journal-entries/create', [JournalEntryController::class, 'create'])->name('journal-entries.create');
    Route::post('/journal-entries', [JournalEntryController::class, 'store'])->name('journal-entries.store');
    Route::get('/journal-entries/{entry}', [JournalEntryController::class, 'show'])->name('journal-entries.show');
    Route::post('/journal-entries/{entry}/post', [JournalEntryController::class, 'post'])->name('journal-entries.post');
    Route::post('/journal-entries/{entry}/void', [JournalEntryController::class, 'void'])->name('journal-entries.void');
    Route::delete('/journal-entries/{entry}', [JournalEntryController::class, 'destroy'])->name('journal-entries.destroy');

    // Reports
    Route::get('/general-ledger', [AccountingReportController::class, 'generalLedger'])->name('general-ledger');
    Route::get('/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('trial-balance');
    Route::get('/profit-loss', [AccountingReportController::class, 'profitLoss'])->name('profit-loss');
    Route::get('/balance-sheet', [AccountingReportController::class, 'balanceSheet'])->name('balance-sheet');
    Route::get('/cash-flow', [AccountingReportController::class, 'cashFlow'])->name('cash-flow');

    // Bank Reconciliation
    Route::get('/bank-reconciliation', [BankReconciliationController::class, 'index'])->name('bank-reconciliation');
    Route::post('/bank-reconciliation/reconcile', [BankReconciliationController::class, 'reconcile'])->name('bank-reconciliation.reconcile');
    Route::post('/bank-reconciliation/import', [BankReconciliationController::class, 'importStatement'])->name('bank-reconciliation.import');

    // Credit Notes
    Route::resource('credit-notes', CreditNoteController::class)->except(['edit']);
    Route::post('/credit-notes/{creditNote}/issue', [CreditNoteController::class, 'issue'])->name('credit-notes.issue');
    Route::post('/credit-notes/{creditNote}/apply', [CreditNoteController::class, 'apply'])->name('credit-notes.apply');
    Route::get('/credit-notes/{creditNote}/print', [CreditNoteController::class, 'print'])->name('credit-notes.print');

    // Debit Notes
    Route::resource('debit-notes', DebitNoteController::class)->except(['edit']);
    Route::post('/debit-notes/{debitNote}/issue', [DebitNoteController::class, 'issue'])->name('debit-notes.issue');
    Route::post('/debit-notes/{debitNote}/apply', [DebitNoteController::class, 'apply'])->name('debit-notes.apply');
    Route::get('/debit-notes/{debitNote}/print', [DebitNoteController::class, 'print'])->name('debit-notes.print');

    // Receipts
    Route::get('/receipts', [PaymentReceiptController::class, 'index'])->name('receipts.index');
    Route::get('/receipts/{receipt}', [PaymentReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/receipts/{receipt}/print', [PaymentReceiptController::class, 'print'])->name('receipts.print');
});
```

### 1.12 Seeder: `ChartOfAccountsSeeder.php`

Seeds 47 default accounts as `is_system=true`:
- Assets 1001-1510: Cash in Hand, bKash, Nagad, Bank DBBL (is_bank), Bank BRAC (is_bank), AR, Inventory, Advance Tax, Shop Equipment, Furniture
- Liabilities 2001-2500: AP, VAT Payable 15%, Salary Payable, Bank Loan City Bank
- Equity 3001-3020: Owner's Capital, Retained Earnings, Owner's Drawing
- Revenue 4001-4030: Sales Revenue, Sales Returns, Service Income, Discount Received
- Expenses 5001-5190: COGS, Rent, Salary, Utilities, Marketing, Courier, Depreciation, Office Supplies, Bank Charges, Discount Allowed, Interest

### 1.13 View Wiring — Blade Variable Mapping

**`chart-of-accounts.blade.php`** (737 lines):
- Stats → `{{ $stats['total_accounts'] }}`, `{{ number_format($stats['total_assets'], 2) }}`
- Rows → `@foreach($accountsByType as $type => $subTypes)` → `@foreach($subTypes as $subType => $accounts)` → `@foreach($accounts as $account)`
- Each: `{{ $account->account_code }}`, `{{ $account->account_name }}`, `{{ number_format($account->balance, 2) }}`

**`chart-of-accounts-create.blade.php`** (248 lines):
- Parent dropdown → `@foreach($parentAccounts as $type => $accts)` `<optgroup>`
- Sub-type options → pass `$subTypes` as JSON to JS

**`journal-entries.blade.php`** (357 lines):
- Stats → `{{ $stats['total'] }}`, `{{ $stats['posted'] }}`, `{{ $stats['draft'] }}`
- Table → `@foreach($entries as $entry)` → `{{ $entry->entry_number }}`, `{{ $entry->entry_date->format('d M Y') }}`
- Pagination → `{{ $entries->withQueryString()->links() }}`

**`journal-entries-create.blade.php`** (370 lines):
- Entry # → `{{ $nextEntryNumber }}`
- Account selects → `@foreach($accounts as $type => $accts)` `<optgroup>`
- Pass `$accountsJson` for dynamic row JS

**`journal-entries-show.blade.php`** (241 lines):
- `{{ $entry->entry_number }}`, `{{ $entry->entry_date->format('d M Y') }}`
- Lines → `@foreach($entry->lines as $line)` → `{{ $line->account->account_code }}`, `{{ number_format($line->debit_amount, 2) }}`

---

## Phase 2: Expense Module

### 2.1 Migration: `create_expense_categories_table.php`

```php
expense_categories: id, name, account_id nullable FK(accounts), description nullable,
  is_active boolean default true, sort_order int default 0, timestamps
```

### 2.2 Migration: `create_expenses_table.php`

```php
expenses: id, expense_number (unique), expense_category_id FK, account_id FK(accounts),
  payment_account_id nullable FK(accounts), amount decimal(15,2),
  tax_amount decimal(15,2) default 0, total_amount decimal(15,2),
  expense_date date, payment_method VARCHAR(50), reference nullable,
  description text, receipt_path nullable,
  status VARCHAR(20) default 'pending' (pending/approved/paid/rejected),
  is_recurring boolean default false, recurring_frequency nullable, next_recurring_date nullable,
  approved_by nullable FK(users), approved_at nullable,
  rejected_by nullable FK(users), rejected_at nullable, rejection_reason nullable,
  journal_entry_id nullable FK(journal_entries),
  branch_id nullable FK(branches), created_by FK(users),
  timestamps, softDeletes
Index: (expense_category_id, expense_date), status, branch_id
```

### 2.3 Service: `ExpenseService.php`

Key methods:
- `list(filters, perPage)` — search, category, status, date range
- `create(data)` — auto-generates EXP-2026-0001
- `update(expense, data)` — pending only
- `approve(expense)` → status=approved
- `reject(expense, reason)` → status=rejected
- `markPaid(expense)` → DB::transaction: creates journal entry (DR Expense Account / CR Payment Account), sets status=paid
- `getStats()` → today, this_week, this_month, pending_count
- `getLedger(filters)` → paginated with category grouping

### 2.4 View Wiring

**`index.blade.php`** — Stats: `$stats['today']`, `$stats['this_week']`, `$stats['this_month']`, `$stats['pending_count']`. Table: `@foreach($expenses as $expense)`. Action buttons based on `$expense->status`.
**`create.blade.php`** — Categories: `@foreach($categories as $cat)`. Payment accounts: `@foreach($paymentAccounts as $acct)`.
**`show.blade.php`** — All `$expense->` properties. Approval timeline.
**`ledger.blade.php`** — Already has optional dynamic vars. Wire: `$totalExpenses`, `$branches`, `$expenseEntries`.

---

## Phase 3: Payment Module

### 3.1 Migration: `create_payments_table.php`

```php
payments: id, payment_number (unique), direction ENUM(receive, pay),
  party_type VARCHAR(30), party_id BIGINT,
  payment_type VARCHAR(30) (against_invoice/advance_payment/advance_return),
  amount decimal(15,2), payment_method VARCHAR(50),
  payment_account_id FK(accounts), payment_date date,
  reference nullable, note nullable,
  journal_entry_id nullable FK(journal_entries),
  branch_id nullable FK(branches), created_by FK(users),
  timestamps, softDeletes
Index: (direction, party_type, party_id), payment_date, payment_method
```

### 3.2 Migration: `create_payment_allocations_table.php`

```php
payment_allocations: id, payment_id FK CASCADE,
  allocatable_type VARCHAR(100), allocatable_id BIGINT,
  amount decimal(15,2), timestamps
Index: (allocatable_type, allocatable_id)
```

### 3.3 Service: `PaymentService.php`

Key methods:
- `create(data, allocations[])` — DB::transaction: creates payment, journal entry (receive: DR Cash/Bank CR AR; pay: DR AP CR Cash/Bank), allocations
- `mapMethodToAccount(method)` — cash→1001, bkash→1002, nagad→1003, card→1004, bank_transfer→1004
- `getStats()` — total_received, total_paid, customer_advances, supplier_advances
- `getAdvanceBalances()` — grouped by party
- `getOutstandingInvoices(partyType, partyId)` — unpaid/partial Sales or Purchases

### 3.4 View Wiring

**`index.blade.php`** — Stats from `$stats`. Table: `@foreach($payments as $payment)`.
**`create.blade.php`** — AJAX party search + outstanding invoices endpoints.
**`show.blade.php`** — `$payment` with journal entry link.
**`advance.blade.php`** — `$customerAdvances`, `$supplierAdvances`, `$employeeAdvances`.

---

## Phase 4: Bank Management

### 4.1 Migrations

**`create_bank_reconciliations_table.php`:**
```php
bank_reconciliations: id, account_id FK(accounts), statement_date date,
  statement_balance decimal(15,2), book_balance decimal(15,2),
  adjusted_balance nullable, status default 'in_progress',
  reconciled_by nullable, reconciled_at nullable, notes nullable, timestamps
```

**`create_bank_reconciliation_items_table.php`:**
```php
bank_reconciliation_items: id, reconciliation_id FK CASCADE,
  journal_entry_line_id nullable FK, type VARCHAR(30),
  transaction_date date, description, reference nullable,
  amount decimal(15,2), is_reconciled boolean default false,
  matched_item_id nullable, timestamps
```

### 4.2 Service: `BankReconciliationService.php`

- `getBookTransactions(accountId, asOfDate)` — journal entry lines for bank account
- `createReconciliation(data)` — starts reconciliation session
- `reconcileItems(recon, matchedIds)` — marks items as reconciled
- `importBankStatement(recon, csvFile)` — parses CSV, creates bank_statement items
- `getReconciliationSummary(accountId, date, statementBalance)` — book vs statement diff

### 4.3 View Wiring

**`bank-reconciliation.blade.php`** — Bank dropdown: `@foreach($bankAccounts as $acct)`. Book transactions: `@foreach($bookTransactions as $txn)`. Summary: `$bookBalance`, `$statementBalance`, `$difference`.

---

## Phase 5: Financial Reports

### 5.1 Service: `AccountingReportService.php`

```php
public function getGeneralLedger(int $accountId, ?Carbon $from, ?Carbon $to): array
// Returns: account, opening_balance, transactions[] (date, entry_number, desc, ref, debit, credit, running_balance), closing_balance

public function getTrialBalance(?Carbon $asOfDate): array
// Returns: rows[] (account, debit, credit), total_debit, total_credit, is_balanced

public function getProfitAndLoss(Carbon $from, Carbon $to): array
// Returns: revenue[] by sub_type, expenses[] by sub_type, total_revenue, total_cogs, gross_profit, total_operating_expenses, operating_profit, net_profit, net_margin

public function getBalanceSheet(Carbon $asOfDate): array
// Returns: assets{} by sub_type, liabilities{} by sub_type, equity{} by sub_type, total_assets, total_liabilities, total_equity, is_balanced

public function getCashFlowStatement(Carbon $from, Carbon $to): array
// Returns: opening_cash, operating{cash_in, cash_out, net, items[]}, investing{}, financing{}, closing_cash, net_change
```

### 5.2 Controller: `AccountingReportController.php`

- `generalLedger(Request)` → passes `$accounts` (for dropdown), `$data` (ledger if account selected)
- `trialBalance(Request)` → passes `$data`, `$asOfDate`
- `profitLoss(Request)` → passes `$data`, `$from`, `$to`
- `balanceSheet(Request)` → passes `$data`, `$asOfDate`
- `cashFlow(Request)` → passes `$data`, `$from`, `$to`

### 5.3 View Wiring

**`general-ledger.blade.php`** — Account dropdown from `$accounts`. `@if($data)` show ledger table: opening row, `@foreach($data['transactions'])` rows, closing row.
**`trial-balance.blade.php`** — `@foreach($data['rows'] as $row)` → code, name, debit, credit. Totals. Balance badge.
**`profit-loss.blade.php`** — Revenue/Expense sections from `$data`. Summary: gross_profit, operating_profit, net_profit, net_margin.
**`balance-sheet.blade.php`** — Assets/Liabilities/Equity from `$data`. Totals. Balance check.
**`cash-flow.blade.php`** — Operating/Investing/Financing sections. Opening/closing cash.

---

## Phase 6: Financial Documents

### 6.1 Credit Notes

**Migration `create_credit_notes_table.php`:**
```php
credit_notes: id, credit_note_number (unique), customer_id nullable FK,
  sale_id nullable FK, sale_return_id nullable FK,
  issue_date date, reason VARCHAR(500),
  subtotal decimal(15,2), tax_amount decimal(15,2) default 0,
  total_amount decimal(15,2),
  status default 'draft' (draft/issued/applied/cancelled),
  applied_amount decimal(15,2) default 0, remaining_amount decimal(15,2) default 0,
  notes nullable, journal_entry_id nullable FK,
  branch_id nullable FK, created_by FK(users),
  timestamps, softDeletes
```

**Migration `create_credit_note_items_table.php`:**
```php
credit_note_items: id, credit_note_id FK CASCADE, product_id nullable FK,
  description VARCHAR(500), quantity int default 1,
  unit_price decimal(15,2), tax_amount decimal(15,2) default 0,
  total decimal(15,2), timestamps
```

**Service: CreditNoteService** — create, issue (DR Sales Returns 4010 / CR AR 1010, Mushak 6.5), applyToInvoice, cancel
**Controller: CreditNoteController** — CRUD + issue + apply + print

**New Views:**
- `credit-notes/index.blade.php` — list with stats, filters
- `credit-notes/create.blade.php` — select sale/customer, line items
- `credit-notes/show.blade.php` — detail with journal entry
- `credit-notes/print.blade.php` — Mushak 6.5 format

### 6.2 Debit Notes

**Migration `create_debit_notes_table.php`:**
```php
debit_notes: id, debit_note_number (unique), supplier_id nullable FK,
  purchase_id nullable FK, purchase_return_id nullable FK,
  issue_date date, reason VARCHAR(500),
  subtotal, tax_amount, total_amount,
  status default 'draft' (draft/issued/applied/cancelled),
  applied_amount, remaining_amount,
  notes, journal_entry_id nullable FK,
  branch_id nullable FK, created_by FK(users),
  timestamps, softDeletes
```

**Migration `create_debit_note_items_table.php`:** (same pattern as credit note items)

**Service: DebitNoteService** — create, issue (DR AP 2001 / CR Purchase Returns), applyToPurchase, cancel
**Controller: DebitNoteController** — CRUD + issue + apply + print

**New Views:** debit-notes/index, create, show, print

### 6.3 Payment Receipts

**Migration `create_payment_receipts_table.php`:**
```php
payment_receipts: id, receipt_number (unique), payment_id FK,
  receipt_type VARCHAR(20) (payment_received/payment_made),
  party_name VARCHAR(255), amount decimal(15,2),
  payment_method VARCHAR(50), receipt_date date,
  description nullable, branch_id nullable FK, created_by FK(users),
  timestamps, softDeletes
```

**Service: PaymentReceiptService** — generateReceipt (auto from payment), printReceipt
**Controller: PaymentReceiptController** — index, show, print

**New Views:** receipts/index, show, print (thermal receipt format)

---

## Phase 7: Cross-Module Integration

### 7.1 AccountingIntegrationService

```php
class AccountingIntegrationService
{
    public function recordSale(Sale $sale): JournalEntry
    // DR AR(1010) $grand_total / CR Sales Revenue(4001) $net / CR VAT Payable(2010) $tax

    public function recordPurchase(Purchase $purchase): JournalEntry
    // DR Inventory(1020) $net / DR VAT(input) / CR AP(2001) $grand_total

    public function recordSaleReturn(SaleReturn $return): JournalEntry
    // DR Sales Returns(4010) / CR AR(1010)

    public function recordPurchaseReturn(PurchaseReturn $return): JournalEntry
    // DR AP(2001) / CR Purchase Returns
}
```

### 7.2 Sidebar Additions

Add under Finance section: Credit Notes, Debit Notes, Receipts

### 7.3 New Permissions

```php
'credit_notes' => ['credit-notes.view', 'credit-notes.create', 'credit-notes.delete', 'credit-notes.issue'],
'debit_notes' => ['debit-notes.view', 'debit-notes.create', 'debit-notes.delete', 'debit-notes.issue'],
'receipts' => ['receipts.view', 'receipts.print'],
```

---

## File Count Summary

| Category | Count |
|---|---|
| Migrations | 12 |
| Models | ~15 (incl. item models) |
| Services | 9 |
| Form Requests | 8 |
| Controllers | 9 |
| Seeders | 2 |
| Views (wire existing) | ~15 |
| Views (create new) | ~11 |
| **Total files** | **~81** |

## Implementation Order

1. **Phase 1** — Accounts + Journal Entries (foundation)
2. **Phase 2** — Expenses (depends on accounts)
3. **Phase 3** — Payments (depends on accounts)
4. **Phase 4** — Bank Reconciliation (depends on accounts + journal lines)
5. **Phase 5** — Financial Reports (depends on all above)
6. **Phase 6** — Credit Notes, Debit Notes, Receipts (depends on journal entries)
7. **Phase 7** — Cross-module integration + sidebar + permissions

## Verification Plan

1. Migrate + seed chart of accounts (47 accounts)
2. Create/edit/delete accounts, verify system account protection
3. Create journal entry → post → verify balance updates → void → verify reversal
4. Create expense → approve → mark paid → verify journal entry auto-created
5. Record payment → verify journal entry + allocation against invoice
6. Bank reconciliation → import CSV → match items → complete
7. Run all 5 financial reports → verify data from journal entries
8. Issue credit note → apply to invoice → print Mushak 6.5
9. Issue debit note → apply to purchase
10. Generate receipt from payment → print
