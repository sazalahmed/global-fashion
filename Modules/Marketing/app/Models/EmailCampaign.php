<?php

namespace Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailCampaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'subject', 'html_body', 'from_name', 'from_email',
        'audience', 'recipient_emails', 'total_recipients', 'sent_count',
        'failed_count', 'status', 'scheduled_at', 'sent_at', 'created_by',
    ];

    protected $casts = [
        'recipient_emails' => 'array',
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
}
