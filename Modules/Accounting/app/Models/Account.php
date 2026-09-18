<?php

namespace Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;

class Account extends Model implements SearchableInterface
{
    use SoftDeletes, HasGlobalSearch;

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

    // ── Relationships ──

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    public function scopeBankAccounts($query)
    {
        return $query->where('is_bank_account', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('account_code', 'like', '%' . $term . '%')
              ->orWhere('account_name', 'like', '%' . $term . '%');
        });
    }

    // ── Helpers ──

    public function isDebitNormal(): bool
    {
        return in_array($this->account_type, ['asset', 'expense']);
    }

    // ── Accessors ──

    public function getBalanceAttribute(): float
    {
        $lineBalance = $this->journalEntryLines()
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted'))
            ->selectRaw('COALESCE(SUM(debit_amount), 0) as total_debit, COALESCE(SUM(credit_amount), 0) as total_credit')
            ->first();

        $totalDebit = (float) ($lineBalance->total_debit ?? 0);
        $totalCredit = (float) ($lineBalance->total_credit ?? 0);

        $netMovement = $this->isDebitNormal()
            ? ($totalDebit - $totalCredit)
            : ($totalCredit - $totalDebit);

        $openingSign = ($this->opening_balance_type === 'debit' && $this->isDebitNormal())
            || ($this->opening_balance_type === 'credit' && !$this->isDebitNormal())
            ? 1 : -1;

        return ((float) $this->opening_balance * $openingSign) + $netMovement;
    }

    public function getFormattedBalanceAttribute(): string
    {
        return currency_symbol() . ' ' . $this->formatBdt($this->balance);
    }

    // ── Static Helpers ──

    public static function getSubTypes(): array
    {
        return [
            'asset' => [
                'current_asset' => 'Current Asset',
                'fixed_asset' => 'Fixed Asset',
                'other_asset' => 'Other Asset',
            ],
            'liability' => [
                'current_liability' => 'Current Liability',
                'long_term_liability' => 'Long-term Liability',
            ],
            'equity' => [
                'owner_equity' => "Owner's Equity",
                'retained_earnings' => 'Retained Earnings',
            ],
            'revenue' => [
                'operating_revenue' => 'Operating Revenue',
                'other_revenue' => 'Other Revenue',
            ],
            'expense' => [
                'cost_of_sales' => 'Cost of Sales',
                'operating_expense' => 'Operating Expense',
                'other_expense' => 'Other Expense',
            ],
        ];
    }

    private function formatBdt(float|string|null $amount): string
    {
        if ($amount === null) {
            return '0';
        }

        $amount = (float) $amount;
        $isNegative = $amount < 0;
        $amount = abs($amount);

        $formatted = number_format($amount, 2, '.', '');
        $parts = explode('.', $formatted);
        $whole = $parts[0];
        $decimal = $parts[1] ?? '00';

        if (strlen($whole) > 3) {
            $last3 = substr($whole, -3);
            $rest = substr($whole, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $whole = $rest . ',' . $last3;
        }

        $result = $whole;
        if ($decimal !== '00') {
            $result .= '.' . $decimal;
        }

        return ($isNegative ? '-' : '') . $result;
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Account'; }
    public static function getSearchIcon(): string { return 'fa-calculator'; }
    public static function getSearchRoute(): string { return 'accounting.chart-of-accounts'; }
    public static function getSearchPermission(): ?string { return 'accounting.view'; }
    public static function getSearchableColumns(): array { return ['account_code', 'account_name']; }
    public static function getSearchOrder(): int { return 24; }
    public function getSearchTitle(): string { return $this->account_name ?? ''; }
    public function getSearchSubtitle(): string { return ($this->account_code ?? '') . ' — ' . ($this->account_type ?? ''); }
    public function getSearchUrl(): string { return route('accounting.chart-of-accounts') . '?highlight=' . $this->id; }
}
