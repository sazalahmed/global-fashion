<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialSupplier extends Model
{
    use SoftDeletes;

    protected $table = 'raw_material_suppliers';

    protected $fillable = [
        'company_name',
        'contact_person',
        'phone',
        'email',
        'address',
        'bank_name',
        'account_number',
        'bank_branch',
        'payment_terms',
        'opening_balance',
        'total_purchase',
        'total_paid',
        'due_balance',
        'advance_balance',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'total_purchase' => 'decimal:2',
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
        return $query->orderBy('company_name');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
