<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RmReceive extends Model
{
    protected $table = 'rm_receives';

    protected $fillable = [
        'purchase_order_id',
        'receive_number',
        'receive_date',
        'received_by',
        'notes',
    ];

    protected $casts = [
        'receive_date' => 'date',
    ];

    // Relationships
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(RmPurchaseOrder::class, 'purchase_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RmReceiveItem::class, 'receive_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by');
    }

    // Boot
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (RmReceive $receive) {
            if (empty($receive->receive_number)) {
                $receive->receive_number = static::generateReceiveNumber();
            }
        });
    }

    public static function generateReceiveNumber(): string
    {
        $prefix = 'RMGRN-';

        $last = static::where('receive_number', 'like', $prefix . '%')
            ->orderByDesc('receive_number')
            ->value('receive_number');

        $nextSeq = 1;
        if ($last) {
            $nextSeq = (int) str_replace($prefix, '', $last) + 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
