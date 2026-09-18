<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FabricIssuance extends Model
{
    protected $table = 'fabric_issuances';

    protected $fillable = [
        'production_order_id', 'issuance_number', 'issuance_date',
        'factory_id', 'total_cost', 'notes', 'issued_by',
    ];

    protected $casts = [
        'issuance_date' => 'date',
        'total_cost' => 'decimal:2',
    ];

    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
    public function factory(): BelongsTo { return $this->belongsTo(Factory::class); }
    public function items(): HasMany { return $this->hasMany(FabricIssuanceItem::class, 'issuance_id'); }
    public function issuer(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'issued_by'); }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->issuance_number)) {
                $model->issuance_number = static::generateUniqueCode();
            }
        });
    }

    protected static function generateUniqueCode(): string
    {
        $prefix = 'FI-';
        $last = static::query()->where('issuance_number', 'like', $prefix . '%')->orderByDesc('issuance_number')->first();
        if ($last) {
            $num = (int) str_replace($prefix, '', $last->issuance_number);
            return $prefix . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
        }
        return $prefix . '0001';
    }
}
