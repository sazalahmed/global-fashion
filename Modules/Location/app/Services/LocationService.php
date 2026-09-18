<?php

namespace Modules\Location\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Location\Models\District;
use Modules\Location\Models\Thana;

class LocationService
{
    // ── Districts ──

    public function listDistricts(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return District::query()
            ->withCount('thanas')
            ->when($filters['search'] ?? null, function ($q, $term) {
                $q->where(function ($qq) use ($term) {
                    $qq->where('district_name', 'like', "%{$term}%")
                       ->orWhere('bn_name', 'like', "%{$term}%");
                });
            })
            ->orderBy('district_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createDistrict(array $data): District
    {
        return District::create([
            'district_name' => $data['district_name'],
            'bn_name'       => $data['bn_name'] ?? null,
            'is_active'     => $data['is_active'] ?? true,
        ]);
    }

    public function updateDistrict(District $district, array $data): District
    {
        $district->update([
            'district_name' => $data['district_name'],
            'bn_name'       => $data['bn_name'] ?? null,
            'is_active'     => $data['is_active'] ?? $district->is_active,
        ]);

        return $district->fresh();
    }

    public function deleteDistrict(District $district): void
    {
        $district->delete();
    }

    // ── Thanas ──

    public function listThanas(array $filters = [], int $perPage = 30): LengthAwarePaginator
    {
        return Thana::query()
            ->with('district:id,district_name')
            ->when($filters['district_id'] ?? null, fn ($q, $id) => $q->where('district_id', $id))
            ->when($filters['search'] ?? null, function ($q, $term) {
                $q->where(function ($qq) use ($term) {
                    $qq->where('thana_name', 'like', "%{$term}%")
                       ->orWhere('bn_name', 'like', "%{$term}%");
                });
            })
            ->orderBy('thana_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createThana(array $data): Thana
    {
        return Thana::create([
            'district_id' => $data['district_id'],
            'thana_name'  => $data['thana_name'],
            'bn_name'     => $data['bn_name'] ?? null,
            'is_active'   => $data['is_active'] ?? true,
        ]);
    }

    public function updateThana(Thana $thana, array $data): Thana
    {
        $thana->update([
            'district_id' => $data['district_id'],
            'thana_name'  => $data['thana_name'],
            'bn_name'     => $data['bn_name'] ?? null,
            'is_active'   => $data['is_active'] ?? $thana->is_active,
        ]);

        return $thana->fresh();
    }

    public function deleteThana(Thana $thana): void
    {
        $thana->delete();
    }
}
