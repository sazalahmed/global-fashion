<?php

namespace Modules\Expense\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Modules\Accounting\Models\Account;

class ExpenseCategory extends Model
{
    protected $fillable = [
        'name', 'parent_id', 'level', 'account_id', 'description', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ── Relationships ──

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Categories flattened parent → children, each row carrying a `depth`
     * (0 = root, 1 = child) so lists and dropdowns can show the hierarchy.
     */
    public static function flatTree(bool $activeOnly = true): Collection
    {
        $all = static::query()
            ->when($activeOnly, fn ($q) => $q->active())
            ->ordered()
            ->get();
        $byParent = $all->groupBy(fn ($c) => $c->parent_id ?? 0);

        $flat = collect();
        foreach ($byParent->get(0, collect()) as $root) {
            $root->depth = 0;
            $flat->push($root);
            foreach ($byParent->get($root->id, collect()) as $child) {
                $child->depth = 1;
                $flat->push($child);
            }
        }

        return $flat;
    }

    /**
     * Get the full name with parent prefix for dropdowns.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->name . ' > ' . $this->name;
        }
        return $this->name;
    }
}
