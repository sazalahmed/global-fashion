<?php

namespace Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LoyaltyTransaction extends Model
{
    protected $fillable = [
        'customer_id', 'type', 'points', 'description',
        'source_type', 'source_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\Modules\Customer\Models\Customer::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
