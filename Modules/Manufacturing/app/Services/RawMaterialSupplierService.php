<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Models\RawMaterialSupplier;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class RawMaterialSupplierService
{
    /**
     * Get paginated list of raw material suppliers with optional filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = RawMaterialSupplier::ordered();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', '%' . $search . '%')
                  ->orWhere('contact_person', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['payment_status'])) {
            match ($filters['payment_status']) {
                'has_due' => $query->where('due_balance', '>', 0),
                'no_due' => $query->where('due_balance', 0),
                default => null,
            };
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new raw material supplier.
     */
    public function create(array $data): RawMaterialSupplier
    {
        $data['created_by'] = Auth::id();
        $data['due_balance'] = $data['opening_balance'] ?? 0;

        return RawMaterialSupplier::create($data);
    }

    /**
     * Update an existing raw material supplier.
     */
    public function update(RawMaterialSupplier $supplier, array $data): RawMaterialSupplier
    {
        $supplier->update($data);

        return $supplier;
    }

    /**
     * Soft delete a raw material supplier.
     */
    public function delete(RawMaterialSupplier $supplier): bool
    {
        if ((float) $supplier->due_balance > 0) {
            throw new \RuntimeException("Cannot delete supplier — outstanding due balance of " . currency_symbol() . " " . number_format($supplier->due_balance) . " exists.");
        }

        return $supplier->delete();
    }

    /**
     * Find a raw material supplier by ID.
     */
    public function find(int $id): RawMaterialSupplier
    {
        return RawMaterialSupplier::findOrFail($id);
    }

    /**
     * Get all active suppliers for dropdown selects.
     */
    public function getActiveSuppliers(): Collection
    {
        return RawMaterialSupplier::active()->ordered()->get(['id', 'company_name', 'contact_person', 'phone']);
    }

    /**
     * Recalculate supplier financial totals from related tables.
     * Placeholder — will be implemented when raw material purchases are added.
     */
    public function updateFinancials(?int $supplierId): void
    {
        if (!$supplierId) {
            return;
        }

        $supplier = RawMaterialSupplier::find($supplierId);

        if (!$supplier) {
            return;
        }

        // TODO: Recalculate total_purchase, total_paid, due_balance
        // from raw_material_purchases and raw_material_supplier_payments tables once they exist.
    }
}
