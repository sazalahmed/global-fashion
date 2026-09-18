<?php

namespace Modules\AiAssistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Customer\Models\Customer;

class AiSearchLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'customer_id',
        'query',
        'vector_top_id',
        'keyword_top_id',
        'fused_top_id',
        'clicked_id',
        'converted',
        'result_ids',
        'total_results',
        'latency_ms',
        'created_at',
    ];

    protected $casts = [
        'result_ids' => 'array',
        'converted' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
