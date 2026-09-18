<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterial extends Model
{
    use SoftDeletes;

    protected $table = 'raw_materials';

    const CATEGORY_FABRIC = 'fabric';
    const CATEGORY_THREAD = 'thread';
    const CATEGORY_BUTTON = 'button';
    const CATEGORY_ZIPPER = 'zipper';
    const CATEGORY_OTHER = 'other';

    const UNIT_YARD = 'yard';
    const UNIT_METER = 'meter';
    const UNIT_KG = 'kg';
    const UNIT_PIECE = 'piece';
    const UNIT_ROLL = 'roll';

    protected $fillable = [
        'name',
        'code',
        'category',
        'unit',
        'cost_price',
        'last_purchase_price',
        'reorder_level',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'last_purchase_price' => 'decimal:2',
        'reorder_level' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_FABRIC => 'Fabric',
            self::CATEGORY_THREAD => 'Thread',
            self::CATEGORY_BUTTON => 'Button',
            self::CATEGORY_ZIPPER => 'Zipper',
            self::CATEGORY_OTHER => 'Other',
        ];
    }

    public static function getUnits(): array
    {
        return [
            self::UNIT_YARD => 'Yard',
            self::UNIT_METER => 'Meter',
            self::UNIT_KG => 'KG',
            self::UNIT_PIECE => 'Piece',
            self::UNIT_ROLL => 'Roll',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (RawMaterial $material) {
            if (empty($material->code)) {
                $material->code = static::generateUniqueCode();
            }
        });
    }

    protected static function generateUniqueCode(): string
    {
        $lastRecord = static::withTrashed()->where('code', 'like', 'RM-%')->orderByDesc('code')->first();

        if ($lastRecord) {
            $lastNumber = (int) str_replace('RM-', '', $lastRecord->code);
            return 'RM-' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        }

        return 'RM-001';
    }
}
