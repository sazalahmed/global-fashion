<?php

namespace Modules\Asset\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenance extends Model
{
    protected $fillable = [
        'asset_id', 'maintenance_date', 'description',
        'cost', 'performed_by', 'next_maintenance_date', 'created_by',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
