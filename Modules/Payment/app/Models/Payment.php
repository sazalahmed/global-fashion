<?php

namespace Modules\Payment\Models;

use App\Contracts\SearchableInterface;
use App\Models\User;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Accounting\Models\JournalEntry;
use App\Traits\LogsActivity;
use Modules\Branch\Models\Branch;

class Payment extends Model implements SearchableInterface
{
    use SoftDeletes, LogsActivity, HasGlobalSearch;

    protected string $activityLogName = 'payments';

    protected $fillable = [
        'payment_number', 'direction', 'party_type', 'party_id',
        'payment_type', 'amount', 'discount_amount', 'payment_method', 'payment_account_id',
        'payment_date', 'reference', 'note',
        'journal_entry_id', 'branch_id', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // ── Relationships ──

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Resolve the party (Customer, Supplier, or Employee) dynamically.
     */
    public function party()
    {
        $modelMap = [
            'customer' => \Modules\Customer\Models\Customer::class,
            'supplier' => \Modules\Supplier\Models\Supplier::class,
            'employee' => \Modules\Employee\Models\Employee::class,
        ];

        $modelClass = $modelMap[$this->party_type] ?? null;

        if ($modelClass && class_exists($modelClass)) {
            return $modelClass::find($this->party_id);
        }

        return null;
    }

    // ── Scopes ──

    public function scopeReceived($query)
    {
        return $query->where('direction', 'receive');
    }

    public function scopeMade($query)
    {
        return $query->where('direction', 'pay');
    }

    public function scopeByParty($query, string $type, ?int $id = null)
    {
        return $query->where('party_type', $type)
            ->when($id, fn ($q) => $q->where('party_id', $id));
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByDateRange($query, $from = null, $to = null)
    {
        return $query
            ->when($from, fn ($q) => $q->where('payment_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('payment_date', '<=', $to));
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('payment_number', 'like', '%' . $term . '%')
              ->orWhere('reference', 'like', '%' . $term . '%')
              ->orWhere('note', 'like', '%' . $term . '%');
        });
    }

    // ── SearchableInterface Methods ──

    public static function getSearchType(): string { return 'Payment'; }
    public static function getSearchIcon(): string { return 'fa-bangladeshi-taka-sign'; }
    public static function getSearchRoute(): string { return 'payments.show'; }
    public static function getSearchPermission(): ?string { return 'payments.view'; }
    public static function getSearchableColumns(): array { return ['payment_number', 'reference', 'note']; }
    public static function getSearchOrder(): int { return 31; }
    public function getSearchTitle(): string { return $this->payment_number ?? ''; }
    public function getSearchSubtitle(): string
    {
        $direction = $this->direction === 'receive' ? 'Received' : 'Paid';

        return $direction . ' — ' . currency_symbol() . ' ' . number_format($this->amount ?? 0);
    }
}
