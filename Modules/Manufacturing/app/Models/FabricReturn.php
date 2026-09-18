<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FabricReturn extends Model
{
    protected $table = 'fabric_returns';

    protected $fillable = [
        'production_order_id', 'return_number', 'return_date',
        'factory_id', 'total_cost', 'notes', 'received_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_cost' => 'decimal:2',
    ];

    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
    public function factory(): BelongsTo { return $this->belongsTo(Factory::class); }
    public function items(): HasMany { return $this->hasMany(FabricReturnItem::class, 'return_id'); }
    public function receiver(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'received_by'); }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->return_number)) {
                $model->return_number = static::generateUniqueCode();
            }
        });
    }

    protected static function generateUniqueCode(): string
    {
        $prefix = 'FR-';
        $last = static::query()->where('return_number', 'like', $prefix . '%')->orderByDesc('return_number')->first();
        if ($last) {
            $num = (int) str_replace($prefix, '', $last->return_number);
            return $prefix . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
        }
        return $prefix . '0001';
    }
}
