<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CatalogPosition extends Model
{
    protected $fillable = [
        'category_id', 'positionable_type', 'positionable_id', 'position',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'positionable_id' => 'integer',
        'position' => 'integer',
    ];

    public function positionable(): MorphTo
    {
        return $this->morphTo();
    }
}
