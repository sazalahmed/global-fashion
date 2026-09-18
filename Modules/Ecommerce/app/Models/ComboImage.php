<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComboImage extends Model
{
    protected $fillable = ['combo_id', 'image_path', 'product_image_id', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }
}
