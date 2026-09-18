<?php

namespace Modules\Brand\Services;

use App\Helpers\Upload;
use Modules\Brand\Models\Brand;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class BrandService
{
    /**
     * Get paginated list of brands with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Brand::ordered();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new brand.
     */
    public function create(array $data): Brand
    {
        if (!empty($data['logo'])) {
            $data['logo'] = Upload::store($data['logo'], 'brands');
        }

        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        $data['is_featured'] = !empty($data['is_featured']);

        return Brand::create($data);
    }

    /**
     * Update an existing brand.
     */
    public function update(Brand $brand, array $data): Brand
    {
        if (!empty($data['logo'])) {
            if ($brand->logo) {
                Upload::delete($brand->logo);
            }
            $data['logo'] = Upload::store($data['logo'], 'brands');
        } elseif (!empty($data['remove_logo'])) {
            if ($brand->logo) {
                Upload::delete($brand->logo);
            }
            $data['logo'] = null;
        }

        unset($data['remove_logo']);

        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $brand->id);
        }

        $data['is_featured'] = !empty($data['is_featured']);

        $brand->update($data);

        return $brand;
    }

    /**
     * Toggle a brand's active/inactive status.
     */
    public function toggleStatus(Brand $brand): Brand
    {
        $brand->update(['status' => $brand->status === 'active' ? 'inactive' : 'active']);

        return $brand;
    }

    /**
     * Soft delete a brand.
     */
    public function delete(Brand $brand): bool
    {
        if ($brand->logo) {
            Upload::delete($brand->logo);
        }

        return $brand->delete();
    }

    /**
     * Get brand statistics for the index page.
     */
    public function getStats(): array
    {
        return [
            'total' => Brand::count(),
            'active' => Brand::where('status', 'active')->count(),
            'inactive' => Brand::where('status', 'inactive')->count(),
            'featured' => Brand::where('is_featured', true)->count(),
        ];
    }

    /**
     * Generate a unique slug for a brand.
     */
    protected function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;

        $query = Brand::withTrashed()->where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $original . '-' . $count++;
            $query = Brand::withTrashed()->where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }
}
