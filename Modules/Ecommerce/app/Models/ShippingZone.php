<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class ShippingZone extends Model
{
    protected $fillable = [
        'name', 'bn_name', 'flat_rate', 'free_shipping_threshold',
        'estimated_days', 'is_active',
    ];

    protected $casts = [
        'flat_rate'               => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'is_active'               => 'boolean',
    ];

    /**
     * Districts this zone covers. A district can only be assigned to one
     * zone (unique constraint on the pivot), so reverse lookup is unique.
     */
    public function districts(): BelongsToMany
    {
        return $this->belongsToMany(
            \Modules\Location\Models\District::class,
            'shipping_zone_districts',
            'shipping_zone_id',
            'district_id',
        )->using(\Illuminate\Database\Eloquent\Relations\Pivot::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Resolve the zone for a given district ID, or null if not covered.
     * Single query — used by checkout to apply the correct shipping rate.
     */
    public static function forDistrict(int $districtId): ?self
    {
        $zoneId = DB::table('shipping_zone_districts')
            ->where('district_id', $districtId)
            ->value('shipping_zone_id');

        return $zoneId ? static::active()->find($zoneId) : null;
    }
}
