<?php

namespace Modules\Accounting\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\CreditNote;

class CreditNoteService
{
    public function __construct(
        private readonly JournalEntryService $journalService,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return CreditNote::with(['items', 'creator', 'branch'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('issue_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('issue_date', '<=', $d))
            ->latest('issue_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): CreditNote
    {
        return CreditNote::with(['items.product', 'journalEntry.lines.account', 'creator', 'branch'])
            ->findOrFail($id);
    }

    public function create(array $data): CreditNote
    {
        return DB::transaction(function () use ($data) {
            $subtotal = 0;
            $taxTotal = 0;

            $cn = CreditNote::create([
                'credit_note_number' => $this->generateNumber(),
                'customer_id'        => $data['customer_id'] ?? null,
                'sale_id'            => $data['sale_id'] ?? null,
                'sale_return_id'     => $data['sale_return_id'] ?? null,
                'issue_date'         => $data['issue_date'],
                'reason'             => $data['reason'],
                'subtotal'           => 0,
                'tax_amount'         => 0,
                'total_amount'       => 0,
                'remaining_amount'   => 0,
                'notes'              => $data['notes'] ?? null,
                'branch_id'          => $data['branch_id'] ?? auth()->user()->branch_id ?? null,
                'created_by'         => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $lineTax   = $item['tax_amount'] ?? 0;

                $cn->items()->create([
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

            $cn->update([
                'subtotal'         => $subtotal,
                'tax_amount'       => $taxTotal,
                'total_amount'     => $subtotal + $taxTotal,
                'remaining_amount' => $subtotal + $taxTotal,
            ]);

            return $cn->load('items');
        });
    }

    public function issue(CreditNote $cn): CreditNote
    {
        if ($cn->status !== 'draft') {
            throw new \Exception('Only draft credit notes can be issued.');
        }

        return DB::transaction(function () use ($cn) {
            $salesReturnsId = Account::where('account_code', '4010')->value('id');
            $receivableId   = Account::where('account_code', '1010')->value('id');

            $lines = [
                ['account_id' => $salesReturnsId, 'debit_amount' => $cn->total_amount, 'credit_amount' => 0, 'description' => "Credit Note: {$cn->credit_note_number}"],
                ['account_id' => $receivableId, 'debit_amount' => 0, 'credit_amount' => $cn->total_amount, 'description' => "CR: {$cn->credit_note_number} — {$cn->reason}"],
            ];

            if ($cn->tax_amount > 0) {
                $vatPayableId = Account::where('account_code', '2010')->value('id');
                $lines[] = ['account_id' => $vatPayableId, 'debit_amount' => $cn->tax_amount, 'credit_amount' => 0, 'description' => "VAT adjustment: {$cn->credit_note_number}"];
                $lines[0]['debit_amount'] = $cn->subtotal;
            }

            $je = $this->journalService->createFromSource(
                'credit_note', $cn->id, $lines,
                "Credit Note: {$cn->credit_note_number} — {$cn->reason}",
                $cn->credit_note_number, $cn->issue_date,
            );

            $cn->update([
                'status'           => 'issued',
                'journal_entry_id' => $je->id,
            ]);

            return $cn->fresh();
        });
    }

    public function cancel(CreditNote $cn): CreditNote
    {
        if (!in_array($cn->status, ['draft', 'issued'])) {
            throw new \Exception('Cannot cancel this credit note.');
        }

        return DB::transaction(function () use ($cn) {
            if ($cn->journalEntry) {
                $this->journalService->void($cn->journalEntry, "Credit note {$cn->credit_note_number} cancelled");
            }
            $cn->update(['status' => 'cancelled']);
            return $cn->fresh();
        });
    }

    public function delete(CreditNote $cn): bool
    {
        if ($cn->status !== 'draft') {
            throw new \Exception('Only draft credit notes can be deleted.');
        }
        return $cn->delete();
    }

    public function getStats(): array
    {
        return [
            'total'     => CreditNote::count(),
            'draft'     => CreditNote::draft()->count(),
            'issued'    => CreditNote::issued()->count(),
            'total_amount' => CreditNote::where('status', 'issued')->sum('total_amount'),
        ];
    }

    private function generateNumber(): string
    {
        $year = now()->format('Y');
        $last = CreditNote::withTrashed()->whereYear('created_at', $year)->count() + 1;
        return 'CN-' . $year . '-' . str_pad($last, 4, '0', STR_PAD_LEFT);
    }
}
