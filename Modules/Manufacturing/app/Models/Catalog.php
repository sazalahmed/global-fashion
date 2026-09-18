<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Catalog extends Model
{
    use SoftDeletes;

    protected $table = 'catalogs';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Catalog $catalog) {
            if (empty($catalog->code)) {
                $catalog->code = static::generateUniqueCode();
            }
        });
    }

    protected static function generateUniqueCode(): string
    {
        $lastRecord = static::withTrashed()->where('code', 'like', 'CAT-%')->orderByDesc('code')->first();

        if ($lastRecord) {
            $lastNumber = (int) str_replace('CAT-', '', $lastRecord->code);
            return 'CAT-' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        }

        return 'CAT-001';
    }
}
