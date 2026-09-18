<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\MfgColor;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MfgColorService
{
    /**
     * Get paginated list of colors with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MfgColor::ordered();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new color.
     */
    public function create(array $data): MfgColor
    {
        $data['code'] = $data['code'] ?? strtoupper(\Illuminate\Support\Str::slug($data['name'], '_'));

        return MfgColor::create($data);
    }

    /**
     * Update an existing color.
     */
    public function update(MfgColor $color, array $data): MfgColor
    {
        $color->update($data);

        return $color;
    }

    /**
     * Delete a color.
     */
    public function delete(MfgColor $color): bool
    {
        return $color->delete();
    }

    /**
     * Get all active colors for dropdown selects.
     */
    public function getActiveColors(): Collection
    {
        return MfgColor::active()->ordered()->get(['id', 'name', 'code', 'hex_code']);
    }
}
