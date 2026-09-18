<?php

namespace Modules\Supplier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model implements SearchableInterface
{
    use SoftDeletes, LogsActivity, HasGlobalSearch;

    protected string $activityLogName = 'suppliers';

    protected $fillable = [
        'company_name',
        'contact_person',
        'phone',
        'email',
        'division',
        'district',
        'area',
        'address',
        'bank_name',
        'account_number',
        'bank_branch',
        'routing_number',
        'tin',
        'bin',
        'trade_license',
        'credit_limit',
        'payment_terms',
        'opening_balance',
        'total_purchase',
        'total_paid',
        'due_balance',
        'advance_balance',
        'status',
        'supplier_group_id',
        'notes',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'total_purchase' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'due_balance' => 'decimal:2',
        'advance_balance' => 'decimal:2',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('company_name');
    }

    public function scopeHasDue(Builder $query): Builder
    {
        return $query->where('due_balance', '>', 0);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getInitialsAttribute(): string
    {
        return initials($this->company_name);
    }

    // SearchableInterface Methods

    public static function getSearchType(): string { return 'Supplier'; }
    public static function getSearchIcon(): string { return 'fa-truck-field'; }
    public static function getSearchRoute(): string { return 'supplier.show'; }
    public static function getSearchPermission(): ?string { return 'suppliers.view'; }
    public static function getSearchableColumns(): array { return ['company_name', 'contact_person', 'phone']; }
    public static function getSearchOrder(): int { return 14; }
    public function getSearchTitle(): string { return $this->company_name ?? ''; }
    public function getSearchSubtitle(): string { return $this->contact_person ?? ''; }

    public function supplierGroup(): BelongsTo
    {
        return $this->belongsTo(SupplierGroup::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(\Modules\Payment\Models\Payment::class, 'party_id')
            ->where('party_type', 'supplier');
    }
}
