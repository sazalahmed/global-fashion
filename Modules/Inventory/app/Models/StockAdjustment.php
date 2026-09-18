<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustment extends Model implements SearchableInterface
{
    use SoftDeletes, LogsActivity, HasGlobalSearch;

    protected string $activityLogName = 'stock_adjustments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'adjustment_number',
        'type',
        'reason_id',
        'reason',
        'notes',
        'reference',
        'status',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function adjustmentReason(): BelongsTo
    {
        return $this->belongsTo(AdjustmentReason::class, 'reason_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope to draft adjustments only.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to approved adjustments only.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to search by adjustment number or reason.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('adjustment_number', 'like', '%' . $term . '%')
                ->orWhere('reason', 'like', '%' . $term . '%');
        });
    }

    // -------------------------------------------------------------------------
    // Helper Methods
    // -------------------------------------------------------------------------

    /**
     * Determine if the adjustment can still be edited (draft only).
     */
    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Stock Adjustment'; }
    public static function getSearchIcon(): string { return 'fa-sliders'; }
    public static function getSearchRoute(): string { return 'inventory.adjustments.show'; }
    public static function getSearchPermission(): ?string { return 'inventory.view'; }
    public static function getSearchableColumns(): array { return ['adjustment_number', 'reference']; }
    public static function getSearchOrder(): int { return 40; }
    public function getSearchTitle(): string { return $this->adjustment_number ?? ''; }
    public function getSearchSubtitle(): string { return ($this->type ?? '') . ($this->reason ? ' — ' . $this->reason : ''); }
}
