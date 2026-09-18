<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Ecommerce\Models\CourierWithdrawal;
use Modules\Ecommerce\Services\SteadfastApiService;

/**
 * Records courier COD payouts. Delivered COD sits inside Accounts Receivable
 * (courier-collected amounts are stamped on sales without a payment journal),
 * so a payout moves that receivable into the bank:
 *
 *   DR Cash-Bank (net received) + DR Courier & Delivery Expense (fees)
 *   CR Accounts Receivable (gross = net + fees)
 */
class CourierWithdrawalService
{
    private const RECEIVABLE_CODE = '1010';       // Accounts Receivable
    private const COURIER_EXPENSE_CODE = '5140';  // Courier & Delivery Expense

    public function __construct(
        private readonly JournalEntryService $journalService,
        private readonly CourierBalanceService $balanceService,
    ) {}

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return CourierWithdrawal::with(['paymentAccount:id,name', 'creator:id,name'])
            ->latest('withdrawal_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Pull the courier's settlements and record any not seen before.
     *
     * Replaces typing payouts in by hand, which produced figures that did not
     * match what the courier actually paid — a settlement's gross entered as
     * the amount received, or its fees left at zero. The provider's own
     * numbers are authoritative:
     *
     *   amount    gross COD collected
     *   due_bills delivery charges it kept
     *   charges   COD/service fee it kept
     *   total     what it actually paid us  (amount - due_bills - charges)
     *
     * Idempotent — a settlement already stored against its reference is
     * skipped, so this is safe to run on every page view or on a schedule.
     *
     * @return array{recorded: int, skipped: int, conflicts: int, settlements: int}
     */
    public function syncFromSettlements(?int $paymentAccountId = null): array
    {
        $settlements = app(SteadfastApiService::class)->allPayments();
        $known = CourierWithdrawal::withTrashed()
            ->whereNotNull('settlement_id')
            ->pluck('settlement_id')
            ->all();

        // Payouts entered by hand before this sync existed carry no
        // settlement_id, so the reference check below cannot recognise them.
        // Booking a settlement one of them already covers double-counts the
        // cash, and nothing looks wrong afterwards — both rows are valid on
        // their own. Match on the payout date instead: the manual amounts are
        // usually the ones that are wrong, which is precisely why this sync
        // was written, so they cannot be matched on value.
        $manualDates = CourierWithdrawal::whereNull('settlement_id')
            ->pluck('withdrawal_date')
            ->map(fn ($d) => substr((string) $d, 0, 10))
            ->all();

        $recorded = 0;
        $skipped = 0;
        $conflicts = 0;

        foreach ($settlements as $settlement) {
            $reference = $settlement['payment_id'] ?? null;

            if (blank($reference) || in_array($reference, $known, true)) {
                $skipped++;
                continue;
            }

            $net = (float) ($settlement['total'] ?? 0);

            // A settlement the courier has not actually paid out yet moves no
            // cash, so there is nothing to post.
            if ($net <= 0) {
                $skipped++;
                continue;
            }

            $date = substr((string) ($settlement['paid_at'] ?? $settlement['created_at'] ?? ''), 0, 10);

            if ($date !== '' && in_array($date, $manualDates, true)) {
                // Skipping rather than replacing: deciding which figure is
                // right means discarding a posted journal, and that is a
                // person's call, not a page view's.
                Log::warning('Steadfast settlement skipped — a hand-entered withdrawal already covers this date', [
                    'settlement' => $reference,
                    'date'       => $date,
                    'net'        => $net,
                ]);
                $conflicts++;
                continue;
            }

            $this->recordSettlement($settlement, $paymentAccountId);
            $recorded++;
        }

        return [
            'recorded'    => $recorded,
            'skipped'     => $skipped,
            'conflicts'   => $conflicts,
            'settlements' => count($settlements),
        ];
    }

    /**
     * Store one settlement and post its journal entry. Same accounting as a
     * hand-entered payout: the money moves out of receivables, split between
     * the cash that arrived and the fees the courier kept.
     */
    private function recordSettlement(array $settlement, ?int $paymentAccountId): CourierWithdrawal
    {
        return DB::transaction(function () use ($settlement, $paymentAccountId) {
            $withdrawal = CourierWithdrawal::create([
                'withdrawal_number'  => $this->generateNumber(),
                'courier_provider'   => 'steadfast',
                'settlement_id'      => $settlement['payment_id'],
                'amount'             => (float) $settlement['total'],
                'delivery_charge'    => (float) ($settlement['due_bills'] ?? 0),
                'cod_charge'         => (float) ($settlement['charges'] ?? 0),
                'withdrawal_date'    => substr((string) ($settlement['paid_at'] ?? $settlement['created_at']), 0, 10),
                'payment_account_id' => $paymentAccountId ?? $this->defaultCashAccountId(),
                'reference'          => $settlement['payment_id'],
                'note'               => 'Imported from Steadfast settlement ' . $settlement['payment_id'],
                'created_by'         => auth()->id(),
            ]);

            $je = $this->recordJournal($withdrawal);

            if ($je) {
                $withdrawal->update(['journal_entry_id' => $je->id]);
            }

            $this->balanceService->forget();

            return $withdrawal;
        });
    }

    private function defaultCashAccountId(): ?int
    {
        return \Modules\Payment\Models\PaymentAccount::where('account_type', 'cash')
            ->orderByDesc('is_default')
            ->value('id');
    }

    public function record(array $data): CourierWithdrawal
    {
        $amount = (float) $data['amount'];
        $deliveryCharge = (float) ($data['delivery_charge'] ?? 0);
        $codCharge = (float) ($data['cod_charge'] ?? 0);

        if ($amount <= 0) {
            throw new \RuntimeException('Amount must be greater than zero.');
        }
        if ($deliveryCharge < 0 || $codCharge < 0) {
            throw new \RuntimeException('Charges cannot be negative.');
        }

        return DB::transaction(function () use ($data, $amount, $deliveryCharge, $codCharge) {
            $withdrawal = CourierWithdrawal::create([
                'withdrawal_number'  => $this->generateNumber(),
                'courier_provider'   => $data['courier_provider'] ?? 'steadfast',
                'amount'             => $amount,
                'delivery_charge'    => $deliveryCharge,
                'cod_charge'         => $codCharge,
                'withdrawal_date'    => $data['withdrawal_date'] ?? now()->toDateString(),
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'reference'          => $data['reference'] ?? null,
                'note'               => $data['note'] ?? null,
                'created_by'         => auth()->id(),
            ]);

            $je = $this->recordJournal($withdrawal);
            if ($je) {
                $withdrawal->update(['journal_entry_id' => $je->id]);
            }

            // The courier's live balance just changed — refetch on next view.
            $this->balanceService->forget();

            return $withdrawal;
        });
    }

    /**
     * Edit a recorded withdrawal: reverse the old journal entry (void keeps the
     * audit trail) and post a fresh one from the new values.
     */
    public function update(CourierWithdrawal $withdrawal, array $data): CourierWithdrawal
    {
        $amount = (float) $data['amount'];
        $deliveryCharge = (float) ($data['delivery_charge'] ?? 0);
        $codCharge = (float) ($data['cod_charge'] ?? 0);

        if ($amount <= 0) {
            throw new \RuntimeException('Amount must be greater than zero.');
        }
        if ($deliveryCharge < 0 || $codCharge < 0) {
            throw new \RuntimeException('Charges cannot be negative.');
        }

        return DB::transaction(function () use ($withdrawal, $data, $amount, $deliveryCharge, $codCharge) {
            $this->reverseJournal($withdrawal);

            $withdrawal->update([
                'amount'             => $amount,
                'delivery_charge'    => $deliveryCharge,
                'cod_charge'         => $codCharge,
                'withdrawal_date'    => $data['withdrawal_date'] ?? $withdrawal->withdrawal_date,
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'reference'          => $data['reference'] ?? null,
                'note'               => $data['note'] ?? null,
                'journal_entry_id'   => null,
            ]);

            $je = $this->recordJournal($withdrawal->fresh());
            $withdrawal->update(['journal_entry_id' => $je?->id]);

            $this->balanceService->forget();

            return $withdrawal->fresh();
        });
    }

    /**
     * Delete a recorded withdrawal and reverse its journal entry.
     */
    public function delete(CourierWithdrawal $withdrawal): void
    {
        DB::transaction(function () use ($withdrawal) {
            $this->reverseJournal($withdrawal);
            $withdrawal->delete();
            $this->balanceService->forget();
        });
    }

    /**
     * Void the withdrawal's posted journal entry (creates a reversing entry),
     * so its cash/AR/expense impact is undone while keeping the history.
     */
    private function reverseJournal(CourierWithdrawal $withdrawal): void
    {
        $entry = $withdrawal->journalEntry;
        if ($entry && $entry->status === 'posted') {
            $this->journalService->void($entry, "Reversed: courier withdrawal {$withdrawal->withdrawal_number}");
        }
    }

    private function recordJournal(CourierWithdrawal $withdrawal): ?\Modules\Accounting\Models\JournalEntry
    {
        $cashId = Account::where('account_code', $this->cashAccountCode($withdrawal->payment_account_id))->value('id');
        $arId = Account::where('account_code', self::RECEIVABLE_CODE)->value('id');
        $expenseId = Account::where('account_code', self::COURIER_EXPENSE_CODE)->value('id');

        if (! $cashId || ! $arId) {
            Log::warning('Missing accounts for courier withdrawal journal', ['withdrawal' => $withdrawal->withdrawal_number]);
            return null;
        }

        $provider = ucfirst($withdrawal->courier_provider);
        $description = "Courier payout received: {$provider} ({$withdrawal->withdrawal_number})";
        $deliveryCharge = (float) $withdrawal->delivery_charge;
        $codCharge = (float) $withdrawal->cod_charge;

        $lines = [
            ['account_id' => $cashId, 'debit_amount' => $withdrawal->amount, 'credit_amount' => 0, 'description' => $description],
        ];
        $bookedCharges = 0.0;
        if ($deliveryCharge > 0 && $expenseId) {
            $lines[] = ['account_id' => $expenseId, 'debit_amount' => $deliveryCharge, 'credit_amount' => 0, 'description' => "Delivery charge deducted: {$provider}"];
            $bookedCharges += $deliveryCharge;
        }
        if ($codCharge > 0 && $expenseId) {
            $lines[] = ['account_id' => $expenseId, 'debit_amount' => $codCharge, 'credit_amount' => 0, 'description' => "COD/recovery charge deducted: {$provider}"];
            $bookedCharges += $codCharge;
        }
        $gross = (float) $withdrawal->amount + $bookedCharges;
        $lines[] = ['account_id' => $arId, 'debit_amount' => 0, 'credit_amount' => $gross, 'description' => $description];

        try {
            return $this->journalService->createFromSource(
                'courier_withdrawal',
                $withdrawal->id,
                $lines,
                $description,
                $withdrawal->reference ?? $withdrawal->withdrawal_number,
                $withdrawal->withdrawal_date,
            );
        } catch (\Throwable $e) {
            Log::warning("Failed to record courier withdrawal journal: {$e->getMessage()}");
            return null;
        }
    }

    private function generateNumber(): string
    {
        $year = now()->format('Y');
        $count = CourierWithdrawal::withTrashed()->whereYear('created_at', $year)->count() + 1;

        return 'CW-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    private function cashAccountCode(?int $paymentAccountId): string
    {
        $typeToCode = ['cash' => '1001', 'mobile_banking' => '1002', 'bank' => '1004', 'card' => '1004'];
        $type = $paymentAccountId
            ? \Modules\Payment\Models\PaymentAccount::where('id', $paymentAccountId)->value('account_type')
            : 'cash';

        return $typeToCode[$type] ?? '1001';
    }
}
