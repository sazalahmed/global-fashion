<?php

namespace Modules\Sale\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierTrackingEvent extends Model
{
    protected $fillable = [
        'sale_id',
        'courier_provider',
        'event_type',
        'status',
        'message',
        'consignment_id',
        'payload',
        'occurred_at',
    ];

    protected $casts = [
        'payload'     => 'array',
        'occurred_at' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
