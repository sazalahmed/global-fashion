<?php

namespace Modules\Asset\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model implements SearchableInterface
{
    use SoftDeletes, LogsActivity, HasGlobalSearch;

    protected string $activityLogName = 'assets';

    protected $fillable = [
        'asset_code', 'name', 'vendor_name', 'vendor_invoice_no',
        'asset_category_id', 'serial_number',
        'description', 'location', 'branch_id', 'purchase_date',
        'purchase_price', 'payment_account_id', 'paid_amount', 'due_amount',
        'payment_status', 'salvage_value', 'current_value', 'disposal_book_value',
        'accumulated_depreciation', 'is_depreciable', 'depreciation_method',
        'useful_life_years', 'last_depreciation_date',
        'next_maintenance_date', 'status', 'disposed_at', 'warranty_info',
        'warranty_expiry', 'photo', 'created_by',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'disposal_book_value' => 'decimal:2',
        'disposed_at' => 'datetime',
        'accumulated_depreciation' => 'decimal:2',
        'is_depreciable' => 'boolean',
        'purchase_date' => 'date',
        'last_depreciation_date' => 'date',
        'next_maintenance_date' => 'date',
        'warranty_expiry' => 'date',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\Modules\Branch\Models\Branch::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(AssetMaintenance::class)->orderByDesc('maintenance_date');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(AssetPayment::class)->orderBy('payment_date')->orderBy('id');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(\Modules\Payment\Models\PaymentAccount::class, 'payment_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDepreciable($query)
    {
        return $query->where('is_depreciable', true)->where('status', 'active');
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Asset'; }
    public static function getSearchIcon(): string { return 'fa-building'; }
    public static function getSearchRoute(): string { return 'assets.show'; }
    public static function getSearchPermission(): ?string { return 'finance.view'; }
    public static function getSearchableColumns(): array { return ['asset_code', 'name', 'serial_number']; }
    public static function getSearchOrder(): int { return 42; }
    public function getSearchTitle(): string { return $this->name ?? ''; }
    public function getSearchSubtitle(): string { return $this->asset_code ?? ''; }
}
