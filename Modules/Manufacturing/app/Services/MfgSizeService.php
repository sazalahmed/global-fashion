<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\MfgSize;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MfgSizeService
{
    /**
     * Get paginated list of sizes with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MfgSize::ordered();

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
     * Create a new size.
     */
    public function create(array $data): MfgSize
    {
        $data['code'] = $data['code'] ?? strtoupper(\Illuminate\Support\Str::slug($data['name'], '_'));

        return MfgSize::create($data);
    }

    /**
     * Update an existing size.
     */
    public function update(MfgSize $size, array $data): MfgSize
    {
        $size->update($data);

        return $size;
    }

    /**
     * Delete a size.
     */
    public function delete(MfgSize $size): bool
    {
        return $size->delete();
    }

    /**
     * Get all active sizes for dropdown selects.
     */
    public function getActiveSizes(): Collection
    {
        return MfgSize::active()->ordered()->get(['id', 'name', 'code']);
    }
}
