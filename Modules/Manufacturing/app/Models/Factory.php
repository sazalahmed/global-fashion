<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Factory extends Model
{
    use SoftDeletes;

    protected $table = 'factories';

    protected $fillable = [
        'name',
        'code',
        'contact_person',
        'phone',
        'email',
        'address',
        'payment_terms',
        'total_orders',
        'total_paid',
        'due_balance',
        'advance_balance',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'total_orders' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'due_balance' => 'decimal:2',
        'advance_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Factory $factory) {
            if (empty($factory->code)) {
                $factory->code = static::generateUniqueCode();
            }
        });
    }

    protected static function generateUniqueCode(): string
    {
        $lastRecord = static::withTrashed()->where('code', 'like', 'FAC-%')->orderByDesc('code')->first();

        if ($lastRecord) {
            $lastNumber = (int) str_replace('FAC-', '', $lastRecord->code);
            return 'FAC-' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        }

        return 'FAC-001';
    }
}
