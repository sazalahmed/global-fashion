<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;

class FraudCheckReport extends Model
{
    protected $fillable = [
        'phone',
        'risk_level',
        'aggregate',
        'courier_data',
        'reports',
        'source',
        'not_found',
        'last_checked_at',
    ];

    protected $casts = [
        'aggregate'       => 'array',
        'courier_data'    => 'array',
        'reports'         => 'array',
        'not_found'       => 'boolean',
        'last_checked_at' => 'datetime',
    ];
}
