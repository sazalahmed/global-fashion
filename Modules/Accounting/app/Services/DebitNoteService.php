<?php

namespace Modules\Accounting\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\DebitNote;

class DebitNoteService
{
    public function __construct(
        private readonly JournalEntryService $journalService,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return DebitNote::with(['supplier', 'items', 'creator', 'branch'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('issue_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('issue_date', '<=', $d))
            ->latest('issue_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): DebitNote
    {
        return DebitNote::with(['supplier', 'items.product', 'journalEntry.lines.account', 'creator', 'branch'])
            ->findOrFail($id);
    }

    public function create(array $data): DebitNote
    {
        return DB::transaction(function () use ($data) {
            $subtotal = 0;
            $taxTotal = 0;

            $dn = DebitNote::create([
                'debit_note_number' => $this->generateNumber(),
                'supplier_id'       => $data['supplier_id'] ?? null,
                'purchase_id'       => $data['purchase_id'] ?? null,
                'purchase_return_id' => $data['purchase_return_id'] ?? null,
                'issue_date'        => $data['issue_date'],
                'reason'            => $data['reason'],
                'subtotal'          => 0,
                'tax_amount'        => 0,
                'total_amount'      => 0,
                'remaining_amount'  => 0,
                'notes'             => $data['notes'] ?? null,
                'branch_id'         => $data['branch_id'] ?? auth()->user()->branch_id ?? null,
                'created_by'        => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $lineTax   = $item['tax_amount'] ?? 0;

                $dn->items()->create([
                    'product_id'  => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'tax_amount'  => $lineTax,
                    'total'       => $lineTotal + $lineTax,
                ]);

                $subtotal += $lineTotal;
                $taxTotal += $lineTax;
            }

            $dn->update([
                'subtotal'         => $subtotal,
                'tax_amount'       => $taxTotal,
                'total_amount'     => $subtotal + $taxTotal,
                'remaining_amount' => $subtotal + $taxTotal,
            ]);

            return $dn->load('items');
        });
    }

    public function issue(DebitNote $dn): DebitNote
    {
        if ($dn->status !== 'draft') {
            throw new \Exception('Only draft debit notes can be issued.');
        }

        return DB::transaction(function () use ($dn) {
            $payableId = Account::where('account_code', '2001')->value('id');
            $purchaseReturnsId = Account::where('account_code', '5001')->value('id'); // Use COGS for now

            $lines = [
                ['account_id' => $payableId, 'debit_amount' => $dn->total_amount, 'credit_amount' => 0, 'description' => "Debit Note: {$dn->debit_note_number}"],
                ['account_id' => $purchaseReturnsId, 'debit_amount' => 0, 'credit_amount' => $dn->total_amount, 'description' => "DN: {$dn->debit_note_number} — {$dn->reason}"],
            ];

            $je = $this->journalService->createFromSource(
                'debit_note', $dn->id, $lines,
                "Debit Note: {$dn->debit_note_number} — {$dn->reason}",
                $dn->debit_note_number, $dn->issue_date,
            );

            $dn->update([
                'status'           => 'issued',
                'journal_entry_id' => $je->id,
            ]);

            return $dn->fresh();
        });
    }

    public function cancel(DebitNote $dn): DebitNote
    {
        if (!in_array($dn->status, ['draft', 'issued'])) {
            throw new \Exception('Cannot cancel this debit note.');
        }

        return DB::transaction(function () use ($dn) {
            if ($dn->journalEntry) {
                $this->journalService->void($dn->journalEntry, "Debit note {$dn->debit_note_number} cancelled");
            }
            $dn->update(['status' => 'cancelled']);
            return $dn->fresh();
        });
    }

    public function delete(DebitNote $dn): bool
    {
        if ($dn->status !== 'draft') {
            throw new \Exception('Only draft debit notes can be deleted.');
        }
        return $dn->delete();
    }

    public function getStats(): array
    {
        return [
            'total'        => DebitNote::count(),
            'draft'        => DebitNote::draft()->count(),
            'issued'       => DebitNote::issued()->count(),
            'total_amount' => DebitNote::where('status', 'issued')->sum('total_amount'),
        ];
    }

    private function generateNumber(): string
    {
        $year = now()->format('Y');
        $last = DebitNote::withTrashed()->whereYear('created_at', $year)->count() + 1;
        return 'DN-' . $year . '-' . str_pad($last, 4, '0', STR_PAD_LEFT);
    }
}
