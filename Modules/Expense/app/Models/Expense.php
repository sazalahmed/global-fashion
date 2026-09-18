<?php

namespace Modules\Expense\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Branch\Models\Branch;
use Modules\Payment\Models\PaymentAccount;
use App\Traits\LogsActivity;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;

class Expense extends Model implements SearchableInterface
{
    use SoftDeletes, LogsActivity, HasGlobalSearch;

    protected string $activityLogName = 'expenses';

    protected $fillable = [
        'expense_number', 'expense_category_id',
        'account_id', 'payment_account_id',
        'amount', 'tax_amount', 'total_amount', 'expense_date', 'due_date',
        'payment_method', 'paid_amount', 'due_amount', 'payment_status',
        'reference', 'description', 'receipt_path', 'status',
        'is_recurring', 'recurring_frequency', 'next_recurring_date',
        'approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'rejection_reason',
        'journal_entry_id', 'branch_id', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'expense_date' => 'date',
        'due_date' => 'date',
        'is_recurring' => 'boolean',
        'next_recurring_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // ── Relationships ──

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * payment_account_id is a Modules\Payment\PaymentAccount id (Cash,
     * bKash, DBBL Bank — the business's own money accounts the expense form
     * lets you pick from), not a Chart of Accounts id. account() above is
     * the real accounting.accounts FK (the expense's GL classification).
     */
    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'payment_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // ── Scopes ──

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('expense_category_id', $categoryId);
    }

    public function scopeByDateRange($query, $from = null, $to = null)
    {
        return $query
            ->when($from, fn ($q) => $q->where('expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('expense_date', '<=', $to));
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('expense_number', 'like', '%' . $term . '%')
              ->orWhere('description', 'like', '%' . $term . '%')
              ->orWhere('reference', 'like', '%' . $term . '%');
        });
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Expense'; }
    public static function getSearchIcon(): string { return 'fa-money-bill-wave'; }
    public static function getSearchRoute(): string { return 'expenses.show'; }
    public static function getSearchPermission(): ?string { return 'finance.view'; }
    public static function getSearchableColumns(): array { return ['expense_number', 'reference']; }
    public static function getSearchOrder(): int { return 29; }
    /**
     * Human-readable payment method. The column stores the payment account's
     * type ('cash', 'bank', 'mobile_banking', 'card'), so it needs formatting
     * before display — and older rows hold a capitalised 'Cash'.
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        $method = trim((string) $this->payment_method);

        return $method === '' ? '—' : ucwords(str_replace('_', ' ', strtolower($method)));
    }

    public function getSearchTitle(): string { return $this->expense_number ?? ''; }
    public function getSearchSubtitle(): string { return currency_symbol() . ' ' . number_format($this->total_amount ?? 0); }
}
