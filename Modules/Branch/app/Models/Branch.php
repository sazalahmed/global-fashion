<?php

namespace Modules\Branch\Models;

use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model implements SearchableInterface
{
    use SoftDeletes, LogsActivity, HasGlobalSearch;

    protected string $activityLogName = 'branches';

    protected $fillable = [
        'name',
        'code',
        'phone',
        'email',
        'address',
        'city',
        'district',
        'zip_code',
        'manager_name',
        'manager_phone',
        'is_main',
        'is_active',
        'is_pos_enabled',
        'is_ecom_enabled',
        'opening_time',
        'closing_time',
        'logo',
        'notes',
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'is_active' => 'boolean',
        'is_pos_enabled' => 'boolean',
        'is_ecom_enabled' => 'boolean',
    ];

    /**
     * Scope: only active branches.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: ordered by name.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /**
     * Scope: only main branch.
     */
    public function scopeMain(Builder $query): Builder
    {
        return $query->where('is_main', true);
    }

    /**
     * Relationship: users assigned to this branch.
     */
    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class);
    }

    /**
     * Auto-generate unique code on creating.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Branch $branch) {
            if (empty($branch->code)) {
                $branch->code = static::generateUniqueCode();
            }
        });
    }

    /**
     * Generate a unique branch code (BR-001 pattern).
     */
    protected static function generateUniqueCode(): string
    {
        $lastBranch = static::withTrashed()->where('code', 'like', 'BR-%')->orderByDesc('code')->first();

        if ($lastBranch) {
            $lastNumber = (int) str_replace('BR-', '', $lastBranch->code);
            return 'BR-' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        }

        return 'BR-001';
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Branch'; }
    public static function getSearchIcon(): string { return 'fa-store'; }
    public static function getSearchRoute(): string { return 'branches.index'; }
    public static function getSearchPermission(): ?string { return 'branches.view'; }
    public static function getSearchableColumns(): array { return ['name', 'code']; }
    public static function getSearchOrder(): int { return 64; }
    public function getSearchTitle(): string { return $this->name ?? ''; }
    public function getSearchSubtitle(): string { return ($this->code ?? '') . ($this->city ? ' — ' . $this->city : ''); }
    public function getSearchUrl(): string { return route('branches.index') . '?highlight=' . $this->id; }
}
