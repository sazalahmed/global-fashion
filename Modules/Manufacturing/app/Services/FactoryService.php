<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\Factory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class FactoryService
{
    /**
     * Get paginated list of factories with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Factory::ordered();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new factory.
     */
    public function create(array $data): Factory
    {
        $data['created_by'] = Auth::id();

        return Factory::create($data);
    }

    /**
     * Update an existing factory.
     */
    public function update(Factory $factory, array $data): Factory
    {
        $factory->update($data);

        return $factory;
    }

    /**
     * Soft delete a factory.
     */
    public function delete(Factory $factory): bool
    {
        // TODO: Check no active production orders exist for this factory
        if ((float) $factory->due_balance > 0) {
            throw new \RuntimeException("Cannot delete factory — outstanding due balance of " . currency_symbol() . " " . number_format($factory->due_balance) . " exists.");
        }

        return $factory->delete();
    }

    /**
     * Find a factory by ID.
     */
    public function find(int $id): Factory
    {
        return Factory::findOrFail($id);
    }

    /**
     * Get all active factories for dropdown selects.
     */
    public function getActiveFactories(): Collection
    {
        return Factory::active()->ordered()->get(['id', 'name', 'code', 'contact_person', 'phone']);
    }

    /**
     * Recalculate factory financial totals from related tables.
     * Placeholder — will be implemented when production orders are added.
     */
    public function updateFinancials(?int $factoryId): void
    {
        if (!$factoryId) {
            return;
        }

        $factory = Factory::find($factoryId);

        if (!$factory) {
            return;
        }

        // TODO: Recalculate total_orders, total_paid, due_balance
        // from production_orders and factory_payments tables once they exist.
    }
}
