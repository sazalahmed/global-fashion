<?php

namespace Modules\AiAssistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Customer\Models\Customer;

class AiHandoffRequest extends Model
{
    protected $fillable = [
        'conversation_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'reason',
        'status',
        'handled_by',
        'handled_at',
        'admin_notes',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'handled_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
