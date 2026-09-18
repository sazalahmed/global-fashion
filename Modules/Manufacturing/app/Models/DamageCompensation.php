<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamageCompensation extends Model
{
    protected $table = 'damage_compensations';

    protected $fillable = [
        'damage_id', 'compensation_date', 'amount', 'method',
        'payment_account_id', 'factory_payment_id', 'replacement_lot_id',
        'reference', 'notes', 'journal_entry_id', 'created_by',
    ];

    protected $casts = [
        'compensation_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function damage(): BelongsTo { return $this->belongsTo(ProductionDamage::class, 'damage_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
}
