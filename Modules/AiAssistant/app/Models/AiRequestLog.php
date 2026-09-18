<?php

namespace Modules\AiAssistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Customer\Models\Customer;

class AiRequestLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'customer_id',
        'operation',
        'provider',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost_bdt',
        'latency_ms',
        'success',
        'error',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'success' => 'boolean',
        'cost_bdt' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeForOperation($query, string $operation)
    {
        return $query->where('operation', $operation);
    }

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
}
