<?php

namespace Modules\Accounting\Services;

use Carbon\Carbon;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class JournalEntryService
{
    /**
     * Paginated journal entry list with filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return JournalEntry::with(['lines.account', 'creator'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['source_type'] ?? null, fn ($q, $v) => $q->where('source_type', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('entry_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('entry_date', '<=', $d))
            ->latest('entry_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Find a single journal entry with all relations.
     */
    public function find(int $id): JournalEntry
    {
        return JournalEntry::with(['lines.account', 'creator', 'branch', 'postedByUser', 'voidedByUser'])
            ->findOrFail($id);
    }

    /**
     * Create a journal entry with lines.
     */
    public function create(array $data): JournalEntry
    {
        return DB::transaction(function () use ($data) {
            $entry = JournalEntry::create([
                'entry_number' => $data['entry_number'] ?? $this->generateEntryNumber(),
                'entry_date' => $data['entry_date'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'],
                'source_type' => $data['source_type'] ?? 'manual',
                'source_id' => $data['source_id'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'attachment_path' => $data['attachment_path'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'branch_id' => $data['branch_id'] ?? auth()->user()?->branch_id,
            ]);

            $totalDebit = 0;

            foreach ($data['lines'] as $line) {
                $debit = (float) ($line['debit_amount'] ?? 0);
                $credit = (float) ($line['credit_amount'] ?? 0);

                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'debit_amount' => $debit,
                    'credit_amount' => $credit,
                ]);

                $totalDebit += $debit;
            }

            $entry->update(['total_amount' => $totalDebit]);

            return $entry->load('lines.account');
        });
    }

    /**
     * Post a draft journal entry.
     */
    public function post(JournalEntry $entry): JournalEntry
    {
        if ($entry->status !== 'draft') {
            throw new \RuntimeException('Only draft entries can be posted.');
        }

        $entry->load('lines');

        if (!$entry->isBalanced()) {
            throw new \RuntimeException('Entry is not balanced. Total debits must equal total credits.');
        }

        $entry->update([
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => auth()->id(),
        ]);

        return $entry->fresh();
    }

    /**
     * Void a posted journal entry by creating a reversing entry.
     */
    public function void(JournalEntry $entry, string $reason): JournalEntry
    {
        if ($entry->status !== 'posted') {
            throw new \RuntimeException('Only posted entries can be voided.');
        }

        return DB::transaction(function () use ($entry, $reason) {
            $entry->load('lines');

            // Create reversing entry (swap debits and credits)
            $reversalLines = $entry->lines->map(fn ($line) => [
                'account_id' => $line->account_id,
                'description' => 'Reversal: ' . ($line->description ?? $entry->description),
                'debit_amount' => $line->credit_amount,
                'credit_amount' => $line->debit_amount,
            ])->toArray();

            $this->createFromSource(
                'void_reversal',
                $entry->id,
                $reversalLines,
                "Void reversal of {$entry->entry_number}: {$reason}",
                $entry->reference,
                $entry->entry_date
            );

            // Mark original as voided
            $entry->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => auth()->id(),
                'void_reason' => $reason,
            ]);

            return $entry->fresh();
        });
    }

    /**
     * Correct the date of a draft or posted entry in place. Void entries are
     * excluded — they're already a dead record kept only for the audit
     * trail, and their date is what the original entry looked like at the
     * moment it was voided.
     */
    public function updateDate(JournalEntry $entry, string $date): JournalEntry
    {
        if ($entry->status === 'voided') {
            throw new \RuntimeException('Voided entries cannot be edited.');
        }

        $entry->update(['entry_date' => $date]);

        return $entry->fresh();
    }

    /**
     * Delete a draft journal entry.
     */
    public function delete(JournalEntry $entry): bool
    {
        if ($entry->status !== 'draft') {
            throw new \RuntimeException('Only draft entries can be deleted.');
        }

        return $entry->delete();
    }

    /**
     * Create a posted journal entry from another module (sale, expense, payment, etc.).
     */
    public function createFromSource(
        string $sourceType,
        int $sourceId,
        array $lines,
        string $description,
        ?string $reference = null,
        $date = null
    ): JournalEntry {
        $data = [
            'entry_date' => $date ?? today(),
            'reference' => $reference,
            'description' => $description,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'lines' => $lines,
        ];

        $entry = $this->create($data);

        // Auto-post entries from source modules
        if ($entry->status === 'draft') {
            $this->post($entry);
        }

        return $entry;
    }

    /**
     * Generate the next entry number: JE-{YEAR}-{PADDED_SEQ}
     *
     * The sequence is derived from the highest existing number for the year,
     * NOT a row count. A count breaks the moment the sequence has a gap (e.g. a
     * hard-deleted row or a number assigned out of band): count+1 then lands on
     * a number that already exists and violates the unique constraint.
     */
    public function generateEntryNumber(): string
    {
        $year   = now()->format('Y');
        $prefix = "JE-{$year}-";

        $lastNumber = JournalEntry::withTrashed()
            ->where('entry_number', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(entry_number, "-", -1) AS UNSIGNED) DESC')
            ->value('entry_number');

        $seq = $lastNumber ? (int) substr($lastNumber, strlen($prefix)) : 0;

        return $prefix . str_pad($seq + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Stats for the journal entries index page.
     */
    public function getStats(): array
    {
        $now = now();

        return [
            'total' => JournalEntry::count(),
            'posted' => JournalEntry::posted()->count(),
            'draft' => JournalEntry::draft()->count(),
            'total_debits_this_month' => (float) JournalEntryLine::whereHas('journalEntry', fn ($q) =>
                $q->posted()
                  ->whereMonth('entry_date', $now->month)
                  ->whereYear('entry_date', $now->year)
            )->sum('debit_amount'),
        ];
    }
}
