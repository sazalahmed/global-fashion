<?php

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Branch\Models\Branch;

class GoodsReceiveNote extends Model
{
    use SoftDeletes, \App\Traits\LogsActivity;

    protected string $activityLogName = 'goods_receive_notes';

    protected $fillable = [
        'purchase_id',
        'grn_number',
        'received_date',
        'branch_id',
        'received_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'received_date' => 'date',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GrnItem::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (GoodsReceiveNote $grn) {
            if (empty($grn->grn_number)) {
                $grn->grn_number = static::generateGrnNumber();
            }
        });
    }

    public static function generateGrnNumber(): string
    {
        $prefix = 'GRN-' . now()->format('Ym') . '-';
        $last = static::withTrashed()
            ->where('grn_number', 'like', $prefix . '%')
            ->orderByDesc('grn_number')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->grn_number, strlen($prefix));
            return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }

        return $prefix . '0001';
    }
}
