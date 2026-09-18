<?php

namespace Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Branch\Models\Branch;
use Modules\Payment\Models\Payment;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;

class PaymentReceipt extends Model implements SearchableInterface
{
    use SoftDeletes, HasGlobalSearch;

    protected $fillable = [
        'receipt_number', 'payment_id', 'receipt_type', 'party_name',
        'amount', 'payment_method', 'receipt_date', 'description',
        'branch_id', 'created_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    // ── Relationships ──

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // ── Scopes ──

    public function scopeSearch($query, string $term)
    {
        return $query->where(fn ($q) =>
            $q->where('receipt_number', 'like', "%{$term}%")
              ->orWhere('party_name', 'like', "%{$term}%")
        );
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Receipt'; }
    public static function getSearchIcon(): string { return 'fa-file-invoice-dollar'; }
    public static function getSearchRoute(): string { return 'accounting.receipts.show'; }
    public static function getSearchPermission(): ?string { return 'accounting.view'; }
    public static function getSearchableColumns(): array { return ['receipt_number', 'party_name']; }
    public static function getSearchOrder(): int { return 30; }
    public function getSearchTitle(): string { return $this->receipt_number ?? ''; }
    public function getSearchSubtitle(): string { return ($this->party_name ?? '') . ' — ' . currency_symbol() . ' ' . number_format($this->amount ?? 0); }
}
