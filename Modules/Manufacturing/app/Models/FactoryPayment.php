<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactoryPayment extends Model
{
    use SoftDeletes;

    protected $table = 'factory_payments';

    protected $fillable = [
        'payment_number', 'factory_id', 'production_order_id', 'payment_id',
        'payment_date', 'amount', 'payment_method', 'payment_account_id',
        'payment_type', 'deduction_amount', 'net_amount',
        'reference', 'notes', 'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function factory(): BelongsTo { return $this->belongsTo(Factory::class); }
    public function productionOrder(): BelongsTo { return $this->belongsTo(ProductionOrder::class); }
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by'); }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->payment_number)) {
                $model->payment_number = static::generateUniqueCode();
            }
        });
    }

    protected static function generateUniqueCode(): string
    {
        $prefix = 'FP-';
        $last = static::withTrashed()->where('payment_number', 'like', $prefix . '%')->orderByDesc('payment_number')->first();
        if ($last) {
            $num = (int) str_replace($prefix, '', $last->payment_number);
            return $prefix . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
        }
        return $prefix . '0001';
    }
}
