<?php

namespace Modules\Purchase\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Models\Purchase;
use Modules\Purchase\Models\Requisition;

class RequisitionService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Requisition::with(['requester', 'branch', 'items'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('requisition_number', 'like', "%{$s}%"))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['branch_id'] ?? null, fn ($q, $b) => $q->where('branch_id', $b))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function statusCounts(): array
    {
        $counts = Requisition::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->toArray();
        $statuses = ['pending', 'approved', 'rejected', 'ordered', 'fulfilled', 'cancelled'];
        $result = [];
        foreach ($statuses as $s) {
            $result[$s] = $counts[$s] ?? 0;
        }
        $result['all'] = array_sum($counts);

        return $result;
    }

    public function getStats(): array
    {
        return [
            'total'    => Requisition::count(),
            'pending'  => Requisition::where('status', 'pending')->count(),
            'approved' => Requisition::where('status', 'approved')->count(),
            'ordered'  => Requisition::where('status', 'ordered')->count(),
        ];
    }

    public function create(array $data, array $items): Requisition
    {
        return DB::transaction(function () use ($data, $items) {
            $requisition = Requisition::create([
                'requisition_number' => $this->generateNumber(),
                'requested_by'       => $data['requested_by'] ?? Auth::id(),
                'department'         => $data['department'] ?? null,
                'branch_id'          => $data['branch_id'] ?? Auth::user()?->branch_id,
                'required_date'      => $data['required_date'] ?? null,
                'priority'           => $data['priority'] ?? 'normal',
                'status'             => Requisition::STATUS_PENDING,
                'note'               => $data['note'] ?? null,
                'created_by'         => Auth::id(),
            ]);

            $this->syncItems($requisition, $items);

            return $requisition->fresh('items');
        });
    }

    public function update(Requisition $requisition, array $data, array $items): Requisition
    {
        if (! $requisition->isEditable()) {
            throw new \RuntimeException('Only pending requisitions can be edited.');
        }

        return DB::transaction(function () use ($requisition, $data, $items) {
            $requisition->update([
                'requested_by'  => $data['requested_by'] ?? $requisition->requested_by,
                'department'    => $data['department'] ?? null,
                'branch_id'     => $data['branch_id'] ?? $requisition->branch_id,
                'required_date' => $data['required_date'] ?? null,
                'priority'      => $data['priority'] ?? 'normal',
                'note'          => $data['note'] ?? null,
            ]);

            $requisition->items()->delete();
            $this->syncItems($requisition, $items);

            return $requisition->fresh('items');
        });
    }

    public function approve(Requisition $requisition): Requisition
    {
        if ($requisition->status !== Requisition::STATUS_PENDING) {
            throw new \RuntimeException('Only pending requisitions can be approved.');
        }

        $requisition->update([
            'status'      => Requisition::STATUS_APPROVED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return $requisition->fresh();
    }

    public function reject(Requisition $requisition, ?string $reason): Requisition
    {
        if ($requisition->status !== Requisition::STATUS_PENDING) {
            throw new \RuntimeException('Only pending requisitions can be rejected.');
        }

        $requisition->update([
            'status'           => Requisition::STATUS_REJECTED,
            'reviewed_by'      => Auth::id(),
            'reviewed_at'      => now(),
            'rejection_reason' => $reason,
        ]);

        return $requisition->fresh();
    }

    public function cancel(Requisition $requisition): Requisition
    {
        if (! in_array($requisition->status, [Requisition::STATUS_PENDING, Requisition::STATUS_APPROVED])) {
            throw new \RuntimeException('Only pending or approved requisitions can be cancelled.');
        }

        $requisition->update(['status' => Requisition::STATUS_CANCELLED]);

        return $requisition->fresh();
    }

    /**
     * Link a newly created purchase to the requisition and mark it ordered.
     */
    public function linkPurchase(Requisition $requisition, Purchase $purchase): void
    {
        if ($requisition->status === Requisition::STATUS_APPROVED) {
            $requisition->update([
                'status'      => Requisition::STATUS_ORDERED,
                'purchase_id' => $purchase->id,
            ]);
        }
    }

    public function markFulfilled(Requisition $requisition): Requisition
    {
        if ($requisition->status !== Requisition::STATUS_ORDERED) {
            throw new \RuntimeException('Only ordered requisitions can be marked fulfilled.');
        }

        $requisition->update(['status' => Requisition::STATUS_FULFILLED]);

        return $requisition->fresh();
    }

    public function delete(Requisition $requisition): void
    {
        if (in_array($requisition->status, [Requisition::STATUS_ORDERED, Requisition::STATUS_FULFILLED])) {
            throw new \RuntimeException('A requisition that has been ordered cannot be deleted.');
        }

        $requisition->items()->delete();
        $requisition->delete();
    }

    private function syncItems(Requisition $requisition, array $items): void
    {
        foreach ($items as $item) {
            $qty = (float) ($item['quantity'] ?? $item['qty'] ?? 0);
            if (empty($item['product_id']) || $qty <= 0) {
                continue;
            }
            $requisition->items()->create([
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity'   => $qty,
                'note'       => $item['note'] ?? null,
            ]);
        }
    }

    private function generateNumber(): string
    {
        $year = now()->format('Y');
        $count = Requisition::withTrashed()->whereYear('created_at', $year)->count() + 1;

        return 'REQ-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
