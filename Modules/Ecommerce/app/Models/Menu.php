<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    /** Storefront menu locations (one menu row each). */
    public const LOCATIONS = ['header_main', 'footer', 'mobile_drawer', 'mobile_bottom'];

    protected $fillable = ['name', 'location', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    // ── Relationships ──

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    /**
     * Top-level items (no parent), ordered, with the full descendant tree
     * eager-loaded so render/admin avoid N+1.
     */
    public function rootItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->ordered()
            ->with('recursiveChildren');
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLocation($query, string $location)
    {
        return $query->where('location', $location);
    }
}
