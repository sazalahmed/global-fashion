<?php

namespace Modules\AiAssistant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;

class AiProductEmbedding extends Model
{
    protected $fillable = [
        'product_id',
        'provider',
        'model',
        'dimensions',
        'content_hash',
        'embedding',
    ];

    protected $casts = [
        'embedding' => 'array',
        'dimensions' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeForProvider($query, string $provider, string $model)
    {
        return $query->where('provider', $provider)->where('model', $model);
    }
}
