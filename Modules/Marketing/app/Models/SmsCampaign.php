<?php

namespace Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsCampaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'gateway', 'message', 'audience', 'recipient_numbers',
        'total_recipients', 'sent_count', 'failed_count', 'cost_per_sms',
        'total_cost', 'status', 'scheduled_at', 'sent_at', 'created_by',
    ];

    protected $casts = [
        'recipient_numbers' => 'array',
        'cost_per_sms' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
