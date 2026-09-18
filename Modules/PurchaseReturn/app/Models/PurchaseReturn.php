<?php

namespace Modules\PurchaseReturn\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Purchase\Models\Purchase;
use Modules\Supplier\Models\Supplier;
use Modules\Branch\Models\Branch;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;

class PurchaseReturn extends Model implements SearchableInterface
{
    use SoftDeletes, \App\Traits\LogsActivity, HasGlobalSearch;

    protected string $activityLogName = 'purchase_returns';

    protected $fillable = [
        'purchase_id',
        'supplier_id',
        'return_number',
        'return_date',
        'return_type_id',
        'subtotal',
        'tax_amount',
        'total',
        'reason',
        'resolution',
        'refunded_amount',
        'refund_account_id',
        'status',
        'notes',
        'created_by',
        'branch_id',
    ];

    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_COMPLETED = 'completed';

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function returnType(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturnType::class, 'return_type_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PurchaseReturn $return) {
            if (empty($return->return_number)) {
                $return->return_number = static::generateReturnNumber();
            }
        });
    }

    public static function generateReturnNumber(): string
    {
        $prefix = 'PR-' . now()->format('Ym') . '-';
        $last = static::withTrashed()
            ->where('return_number', 'like', $prefix . '%')
            ->orderByDesc('return_number')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->return_number, strlen($prefix));
            return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }

        return $prefix . '0001';
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Purchase Return'; }
    public static function getSearchIcon(): string { return 'fa-rotate-left'; }
    public static function getSearchRoute(): string { return 'purchase-returns.show'; }
    public static function getSearchPermission(): ?string { return 'purchases.view'; }
    public static function getSearchableColumns(): array { return ['return_number']; }
    public static function getSearchOrder(): int { return 23; }
    public static function getSearchWith(): array { return ['supplier']; }
    public function getSearchTitle(): string { return $this->return_number ?? ''; }
    public function getSearchSubtitle(): string
    {
        $amount = currency_symbol() . ' ' . number_format($this->total ?? 0);

        return $this->supplier?->company_name ? $this->supplier->company_name . ' — ' . $amount : $amount;
    }

    /**
     * Match return number OR the related supplier's company/phone.
     */
    public function scopeGlobalSearch(\Illuminate\Database\Eloquent\Builder $query, string $term): \Illuminate\Database\Eloquent\Builder
    {
        $like = '%' . $term . '%';

        return $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($like) {
            $q->where('return_number', 'like', $like)
                ->orWhereHas('supplier', function (\Illuminate\Database\Eloquent\Builder $s) use ($like) {
                    $s->where('company_name', 'like', $like)->orWhere('phone', 'like', $like);
                });
        });
    }
}
