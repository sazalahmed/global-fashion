<?php

namespace Modules\Branch\Services;

use App\Helpers\Upload;
use Modules\Branch\Models\Branch;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BranchService
{
    /**
     * Get paginated list of branches with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Branch::ordered();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%')
                  ->orWhere('city', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['status'])) {
            $isActive = $filters['status'] === 'active';
            $query->where('is_active', $isActive);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new branch.
     */
    public function create(array $data): Branch
    {
        if (!empty($data['logo'])) {
            $data['logo'] = Upload::store($data['logo'], 'branches');
        }

        $data['is_active'] = !empty($data['is_active']);
        $data['is_main'] = !empty($data['is_main']);
        $data['is_pos_enabled'] = !empty($data['is_pos_enabled']);
        $data['is_ecom_enabled'] = !empty($data['is_ecom_enabled']);

        if ($data['is_main']) {
            Branch::where('is_main', true)->update(['is_main' => false]);
        }

        return Branch::create($data);
    }

    /**
     * Update an existing branch.
     */
    public function update(Branch $branch, array $data): Branch
    {
        if (!empty($data['logo'])) {
            if ($branch->logo) {
                Upload::delete($branch->logo);
            }
            $data['logo'] = Upload::store($data['logo'], 'branches');
        } elseif (!empty($data['remove_logo'])) {
            if ($branch->logo) {
                Upload::delete($branch->logo);
            }
            $data['logo'] = null;
        }

        unset($data['remove_logo']);

        $data['is_active'] = !empty($data['is_active']);
        $data['is_main'] = !empty($data['is_main']);
        $data['is_pos_enabled'] = !empty($data['is_pos_enabled']);
        $data['is_ecom_enabled'] = !empty($data['is_ecom_enabled']);

        if ($data['is_main'] && !$branch->is_main) {
            Branch::where('is_main', true)->where('id', '!=', $branch->id)->update(['is_main' => false]);
        }

        $branch->update($data);

        return $branch;
    }

    /**
     * Soft delete a branch.
     */
    public function delete(Branch $branch): bool
    {
        if ($branch->is_main) {
            return false;
        }

        if ($branch->logo) {
            Upload::delete($branch->logo);
        }

        return $branch->delete();
    }

    /**
     * Flip a branch's active status.
     */
    public function toggleStatus(Branch $branch): Branch
    {
        $branch->update(['is_active' => ! $branch->is_active]);

        return $branch;
    }

    /**
     * Get branch statistics for the index page.
     */
    public function getStats(): array
    {
        return [
            'total' => Branch::count(),
            'active' => Branch::where('is_active', true)->count(),
            'posEnabled' => Branch::where('is_pos_enabled', true)->count(),
            'ecomEnabled' => Branch::where('is_ecom_enabled', true)->count(),
        ];
    }

    /**
     * Get all active branches for dropdown selects.
     */
    public function getActiveBranches(): Collection
    {
        return Branch::active()->ordered()->get(['id', 'name', 'code']);
    }
}
