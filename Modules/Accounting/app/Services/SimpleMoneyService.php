<?php

namespace Modules\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;

/**
 * Read-only views over the GL that present journal data as a single-entry
 * cash book / income / expense / summary. All math is derived from the
 * existing posted journal entries — no separate tables, no duplication.
 */
class SimpleMoneyService
{
    /**
     * Cash flow: 2-column "Cash In vs Cash Out" categorical breakdown of all
     * movements through cash & bank accounts in the window. Categories are
     * derived from journal_entries.source_type, and for source_type='payment'
     * further split by Payment.payment_type + party_type. Opening + Net = Closing
     * is enforced so consecutive day windows reconcile exactly.
     */
    public function cashFlow(?string $from = null, ?string $to = null, ?int $accountId = null): array
    {
        $cashAccountIds = array_values(array_unique(array_merge(
            $this->cashAccountIds(),
            $this->bankAccountIds(),
            $this->mobileAccountIds(),
            $this->cardAccountIds(),
        )));

        $accountIds = $accountId ? [$accountId] : $cashAccountIds;
        $hasDateFilter = $from !== null || $to !== null;

        // Sum debit (cash in) / credit (cash out) on cash-side lines, grouped
        // by source_type. Period-bounded by the journal entry date.
        $bySource = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            // Voiding marks the original entry 'voided' AND posts a reversing
            // 'void_reversal' entry. Posted-only sums must skip the reversal
            // too, so a voided transaction nets to zero rather than minus-one.
            ->where(fn ($q) => $q->whereNull('journal_entries.source_type')
                ->orWhere('journal_entries.source_type', '!=', 'void_reversal'))
            ->whereIn('journal_entry_lines.account_id', $accountIds)
            ->when($from, fn ($q, $d) => $q->where('journal_entries.entry_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->where('journal_entries.entry_date', '<=', $d))
            ->groupBy('journal_entries.source_type')
            ->selectRaw(
                'COALESCE(journal_entries.source_type, "manual") as source_type,
                 COALESCE(SUM(journal_entry_lines.debit_amount), 0) as cash_in,
                 COALESCE(SUM(journal_entry_lines.credit_amount), 0) as cash_out'
            )
            ->get()
            ->keyBy('source_type');

        // For source_type='payment', join the payments table to split further
        // by party_type + payment_type so customer-receive, customer-advance,
        // supplier-pay etc each get their own row in the breakdown.
        $byPayment = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->join('payments as p', 'p.journal_entry_id', '=', 'je.id')
            ->where('je.status', 'posted')
            ->whereIn('jel.account_id', $accountIds)
            ->whereNull('p.deleted_at')
            ->when($from, fn ($q, $d) => $q->where('je.entry_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->where('je.entry_date', '<=', $d))
            ->groupBy('p.party_type', 'p.payment_type')
            ->selectRaw(
                'p.party_type as party_type,
                 p.payment_type as payment_type,
                 COALESCE(SUM(jel.debit_amount), 0) as cash_in,
                 COALESCE(SUM(jel.credit_amount), 0) as cash_out'
            )
            ->get();

        $pickPayment = function (string $partyType, string $paymentType) use ($byPayment): array {
            $row = $byPayment->first(fn ($r) => $r->party_type === $partyType && $r->payment_type === $paymentType);
            return [(float) ($row->cash_in ?? 0), (float) ($row->cash_out ?? 0)];
        };

        [$custReceiveIn, $custReceiveOut] = $pickPayment('customer', 'sale_payment');
        [$custInvIn, $custInvOut]         = $pickPayment('customer', 'against_invoice');
        [$custAdvIn, $custAdvOut]         = $pickPayment('customer', 'advance_payment');
        [$custAdjIn, $custAdjOut]         = $pickPayment('customer', 'advance_return');
        [$supPayIn, $supPayOut]           = $pickPayment('supplier', 'purchase_payment');
        [$supInvIn, $supInvOut]           = $pickPayment('supplier', 'against_invoice');
        [$supAdvIn, $supAdvOut]           = $pickPayment('supplier', 'advance_payment');
        [$supAdjIn, $supAdjOut]           = $pickPayment('supplier', 'advance_return');

        // sale_payment (taken against a specific sale) and against_invoice
        // (Payment module's receive-against-invoices flow) are kept apart:
        // the first is cash from selling, the second is collecting an older
        // debt, and merging them left the Product Sale row permanently empty.

        // Supplier dues are settled through two payment types.
        $supPayIn  += $supInvIn;
        $supPayOut += $supInvOut;

        $sumSource = function (string $src) use ($bySource): array {
            $row = $bySource->get($src);
            return [(float) ($row->cash_in ?? 0), (float) ($row->cash_out ?? 0)];
        };

        [$saleIn,         $saleOut]         = $sumSource('sale');
        [$saleReturnIn,   $saleReturnOut]   = $sumSource('sale_return');
        [$purchaseIn,     $purchaseOut]     = $sumSource('purchase');
        [$purchaseRetIn,  $purchaseRetOut]  = $sumSource('purchase_return');
        [$expenseIn,      $expenseOut]      = $sumSource('expense');
        [$expPayIn,       $expPayOut]       = $sumSource('expense_payment');

        // Expense payments post their journals as 'expense_payment'.
        $expenseIn  += $expPayIn;
        $expenseOut += $expPayOut;
        [$payrollIn,      $payrollOut]      = $sumSource('payroll');
        [$ecomIn,         $ecomOut]         = $sumSource('ecommerce_order');
        [$investCapIn,    $investCapOut]    = $sumSource('investor_capital');
        [$investDistIn,   $investDistOut]   = $sumSource('investor_distribution');

        // Loans — company borrowing (Loan module) and personal lending.
        [$loanRecvIn,     $loanRecvOut]     = $sumSource('loan_disbursement');          // borrowed from a lender → cash in
        [$loanRepayIn,    $loanRepayOut]    = $sumSource('loan_repayment');             // installment paid to lender → cash out
        [$plGivenIn,      $plGivenOut]      = $sumSource('personal_loan_disbursement'); // loan given to a borrower → cash out
        [$plRecovIn,      $plRecovOut]      = $sumSource('personal_loan_repayment');    // borrower repaid us → cash in
        [$plTakenIn,      $plTakenOut]      = $sumSource('personal_loan_taken');        // borrowed from a person → cash in
        [$plReturnIn,     $plReturnOut]     = $sumSource('personal_loan_return');       // paid back to the person → cash out

        // Bank fees deducted from accounts (maintenance, SMS, cash-out fees).
        [$bankChargeIn,   $bankChargeOut]   = $sumSource('bank_charge');

        // Courier COD payouts (Steadfast settlement received into our account).
        // Cash lines carry only the NET received; the charges the courier
        // deducted live on expense lines inside the same journals. Showing
        // gross as Cash In and the charges as Cash Out keeps the payout math
        // readable (gross − charges = received) without changing the net.
        [$courierWdIn,    $courierWdOut]    = $sumSource('courier_withdrawal');
        $courierCharges = (float) JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.source_type', 'courier_withdrawal')
            ->where('accounts.account_type', 'expense')
            ->when($from, fn ($q, $d) => $q->where('journal_entries.entry_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->where('journal_entries.entry_date', '<=', $d))
            // Attribute charges only to payouts whose cash line is in scope
            // (matters when the report is filtered to a single account).
            ->whereIn('journal_entries.id', function ($q) use ($accountIds) {
                $q->select('journal_entry_id')
                    ->from('journal_entry_lines')
                    ->whereIn('account_id', $accountIds);
            })
            ->sum('journal_entry_lines.debit_amount');

        // The two fees a courier deducts are separate costs — the per-parcel
        // delivery charge and the COD/recovery fee — so they are reported
        // apart rather than as one lump. The GL books both to the same expense
        // account, so the split comes from the payout rows behind the entries,
        // scoped to the same period and accounts as the total above.
        $courierSplit = DB::table('journal_entries as je')
            ->join('courier_withdrawals as cw', 'cw.id', '=', 'je.source_id')
            ->where('je.status', 'posted')
            ->where('je.source_type', 'courier_withdrawal')
            ->whereNull('cw.deleted_at')
            ->when($from, fn ($q, $d) => $q->where('je.entry_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->where('je.entry_date', '<=', $d))
            ->whereIn('je.id', function ($q) use ($accountIds) {
                $q->select('journal_entry_id')
                    ->from('journal_entry_lines')
                    ->whereIn('account_id', $accountIds);
            })
            ->selectRaw('COALESCE(SUM(cw.delivery_charge), 0) AS delivery, COALESCE(SUM(cw.cod_charge), 0) AS cod')
            ->first();

        $courierDelivery = (float) ($courierSplit->delivery ?? 0);
        $courierCod = (float) ($courierSplit->cod ?? 0);

        // Never let the split disagree with the expense actually posted: any
        // difference (a payout whose row was removed, a fee booked without
        // one) lands on delivery so the two rows still total the GL figure and
        // Cash In - Cash Out keeps reconciling.
        $courierDelivery += $courierCharges - ($courierDelivery + $courierCod);

        // Employee salary advances: given from cash → out; recovered in cash → in.
        // (Payroll-deducted recoveries never touch a cash account, so they
        // naturally net to zero here.)
        [$empAdvIn,       $empAdvOut]       = $sumSource('employee_advance');
        [$empAdvRecIn,    $empAdvRecOut]    = $sumSource('employee_advance_recovery');

        // Fixed assets. Buying one pays cash out (at acquisition, and again for
        // each instalment settling the payable); disposing of one brings the
        // proceeds back in. asset_depreciation is deliberately absent — it
        // moves value between asset and expense accounts and never touches
        // cash, so it has nothing to contribute to a cash statement.
        [$assetAcqIn,     $assetAcqOut]     = $sumSource('asset_acquisition');
        [$assetPayIn,     $assetPayOut]     = $sumSource('asset_payment');
        [$assetDispIn,    $assetDispOut]    = $sumSource('asset_disposal');

        // Due Salary (Arrears) payouts
        [$dueSalIn,       $dueSalOut]       = $pickPayment('employee', 'salary_payment');

        // Salary is netted across payments and undo-reversals. When a payment
        // made in an earlier period is undone in this one, the period's
        // payroll cash-in exceeds its cash-out — that surplus is real money
        // returning to the account and must show as Cash In (salary_refund),
        // not be clamped away by max(0, …).
        $salaryNet = ($payrollOut + $dueSalOut) - ($payrollIn + $dueSalIn);

        // Cash from selling: the sale_payment collected against a sale, plus
        // any sale or online order that credited cash directly. Sales post to
        // Accounts Receivable rather than cash, so that second part is zero
        // under current posting rules — it is kept so a flow that ever does
        // pay straight into an account is not silently dropped.
        $saleCashNet = ($saleIn + $ecomIn + $custReceiveIn) - ($saleOut + $ecomOut + $custReceiveOut);
        $dueNet = $custInvIn - $custInvOut;

        // Clamp the pair, not each row: a period whose refunds outweigh its
        // receipts would otherwise have the negative side floored at zero
        // while the positive side stood, reporting more cash in than arrived.
        $customerCash = max(0.0, $saleCashNet + $dueNet);
        $productSale = max(0.0, min($saleCashNet, $customerCash));
        $dueReceive = $customerCash - $productSale;

        // Final row values are net (positive on the side they belong to).
        // Investor capital is split explicitly across the two columns below.
        $data = [
            // ── Cash In ──
            'product_sale'         => $productSale,
            'customer_due_receive' => $dueReceive,
            'customer_advance'     => max(0.0, $custAdvIn - $custAdvOut),
            'supplier_adv_refund'  => max(0.0, $supAdjIn - $supAdjOut),
            'purchase_return'      => max(0.0, $purchaseRetIn - $purchaseRetOut),
            'investor_capital'     => max(0.0, $investCapIn - $investCapOut),
            'loan_received'        => max(0.0, $loanRecvIn - $loanRecvOut),
            'loan_recovered'       => max(0.0, $plRecovIn - $plRecovOut),
            'loan_taken'           => max(0.0, $plTakenIn - $plTakenOut),
            'courier_withdrawal'   => max(0.0, $courierWdIn - $courierWdOut) + $courierCharges, // gross payout
            'emp_adv_recovered'    => max(0.0, $empAdvRecIn - $empAdvRecOut),
            'salary_refund'        => max(0.0, -$salaryNet),
            'asset_disposal'       => max(0.0, $assetDispIn - $assetDispOut),

            // ── Cash Out ──
            'sale_return'          => max(0.0, $saleReturnOut - $saleReturnIn),
            'customer_adv_refund'  => max(0.0, $custAdvOut - $custAdvIn) + max(0.0, $custAdjOut - $custAdjIn),
            'supplier_due_pay'     => max(0.0, $supPayOut - $supPayIn) + max(0.0, $purchaseOut - $purchaseIn),
            'supplier_advance'     => max(0.0, $supAdvOut - $supAdvIn),
            'expense'              => max(0.0, $expenseOut - $expenseIn),
            'salary'               => max(0.0, $salaryNet),
            'investor_withdraw'    => max(0.0, $investCapOut - $investCapIn),
            'investor_dist'        => max(0.0, $investDistOut - $investDistIn),
            'loan_given'           => max(0.0, $plGivenOut - $plGivenIn),
            'loan_repay'           => max(0.0, $loanRepayOut - $loanRepayIn),
            'loan_returned'        => max(0.0, $plReturnOut - $plReturnIn),
            'courier_delivery_charge' => $courierDelivery, // per-parcel delivery fees the courier kept
            'courier_cod_charge'      => $courierCod,      // COD/recovery fees the courier kept
            'bank_charge'          => max(0.0, $bankChargeOut - $bankChargeIn),
            'emp_advance'          => max(0.0, $empAdvOut - $empAdvIn),
            // Acquisition and instalments are one line: both are cash leaving
            // for the same asset, and splitting them would put a row on screen
            // that is empty for every business paying up front.
            'asset_purchase'       => max(0.0, ($assetAcqOut + $assetPayOut) - ($assetAcqIn + $assetPayIn)),
        ];

        $totalReceive = $data['product_sale'] + $data['customer_due_receive'] + $data['customer_advance']
            + $data['supplier_adv_refund'] + $data['purchase_return']
            + $data['investor_capital']
            + $data['loan_received'] + $data['loan_recovered'] + $data['loan_taken']
            + $data['courier_withdrawal'] + $data['emp_adv_recovered']
            + $data['salary_refund'] + $data['asset_disposal'];

        $totalPay = $data['sale_return'] + $data['customer_adv_refund']
            + $data['supplier_due_pay'] + $data['supplier_advance']
            + $data['expense'] + $data['salary']
            + $data['investor_withdraw'] + $data['investor_dist']
            + $data['loan_given'] + $data['loan_repay'] + $data['loan_returned']
            + $data['courier_delivery_charge'] + $data['courier_cod_charge']
            + $data['bank_charge'] + $data['emp_advance'] + $data['asset_purchase'];

        // Anything posted to a cash account that no named row above claims must
        // still appear, or Cash In − Cash Out stops matching the balance the
        // same journals produce. Two known sources of this: source_types with
        // no dedicated row (e.g. asset_acquisition), and posted payment
        // journals whose payment row was soft-deleted without voiding the
        // journal — the GL still holds that cash, so the report must show it.
        // Derived as a residual against the period's actual movement, so it
        // self-corrects for any future source_type without another code change.
        $periodNet = (float) $bySource->sum('cash_in') - (float) $bySource->sum('cash_out');
        $otherNet  = $periodNet - ($totalReceive - $totalPay);

        $data['other_in']  = max(0.0, $otherNet);
        $data['other_out'] = max(0.0, -$otherNet);
        $totalReceive += $data['other_in'];
        $totalPay     += $data['other_out'];

        $accounts = Account::whereIn('id', $cashAccountIds)->orderBy('account_code')->get();
        $openingBalance = $hasDateFilter && $from
            ? $this->cashBalanceAsOf($accountIds, $from, exclusive: true)
            : 0.0;
        $currentBalance = $this->cashBalanceAsOf($accountIds, $to, exclusive: false);

        return [
            'data'           => $data,
            'totalReceive'   => $totalReceive,
            'totalPay'       => $totalPay,
            'openingBalance' => $openingBalance,
            'currentBalance' => $currentBalance,
            'hasDateFilter'  => $hasDateFilter,
            'accounts'       => $accounts,
        ];
    }

    /**
     * Cash + bank account balance as of a given date.
     * exclusive=true returns the balance strictly before $date (for opening),
     * exclusive=false returns the balance through end of $date (for closing).
     * Passing $date=null returns the all-time current balance.
     */
    private function cashBalanceAsOf(array $accountIds, ?string $date, bool $exclusive): float
    {
        if (empty($accountIds)) {
            return 0.0;
        }

        $q = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->whereIn('journal_entry_lines.account_id', $accountIds);

        if ($date) {
            $q->where('journal_entries.entry_date', $exclusive ? '<' : '<=', $date);
        }

        // Skip void reversals — their originals are already excluded by status.
        $q->where(fn ($qq) => $qq->whereNull('journal_entries.source_type')
            ->orWhere('journal_entries.source_type', '!=', 'void_reversal'));

        $totals = $q->selectRaw('COALESCE(SUM(debit_amount), 0) AS d, COALESCE(SUM(credit_amount), 0) AS c')->first();
        $opening = (float) Account::whereIn('id', $accountIds)->sum('opening_balance');

        return $opening + (float) $totals->d - (float) $totals->c;
    }

    /**
     * Expense view: every expense-account movement, netted. Debits are spend;
     * credits (e.g. an undone salary payment's reversal) appear as negative
     * rows so period totals stay truthful. Void reversals are skipped — their
     * voided originals are already excluded by status, so counting the
     * reversal would double-subtract.
     */
    public function expense(?string $from = null, ?string $to = null): array
    {
        $rows = JournalEntryLine::query()
            ->select([
                'journal_entries.entry_date',
                'journal_entries.entry_number',
                'journal_entries.description as entry_description',
                'journal_entries.source_type',
                'journal_entries.reference',
                DB::raw('(journal_entry_lines.debit_amount - journal_entry_lines.credit_amount) as amount'),
                'accounts.account_name',
                'accounts.account_code',
            ])
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.status', 'posted')
            ->where(fn ($q) => $q->whereNull('journal_entries.source_type')
                ->orWhere('journal_entries.source_type', '!=', 'void_reversal'))
            ->where('accounts.account_type', 'expense')
            ->where(fn ($q) => $q->where('journal_entry_lines.debit_amount', '>', 0)
                ->orWhere('journal_entry_lines.credit_amount', '>', 0))
            ->when($from, fn ($q, $d) => $q->where('journal_entries.entry_date', '>=', $d))
            ->when($to, fn ($q, $d) => $q->where('journal_entries.entry_date', '<=', $d))
            ->orderByDesc('journal_entries.entry_date')
            ->orderByDesc('journal_entries.id')
            ->get();

        $byAccount = $rows->groupBy('account_name')->map(fn ($g) => $g->sum('amount'));

        return [
            'rows'       => $rows,
            'total'      => $rows->sum('amount'),
            'by_account' => $byAccount,
        ];
    }

    /**
     * Physical cash only. Mobile wallets (bKash/Nagad/Rocket) live in their
     * own group — lumping them in here made "Cash in Hand" overstate actual
     * cash and left wallet money invisible on the summary.
     */
    private function cashAccountIds(): array
    {
        return Account::where('account_type', 'asset')
            ->where(function ($q) {
                $q->where('account_code', '1001')
                  ->orWhere('account_name', 'like', '%Cash%');
            })
            ->pluck('id')
            ->all();
    }

    private function mobileAccountIds(): array
    {
        return Account::where('account_type', 'asset')
            ->where(function ($q) {
                $q->whereIn('account_code', ['1002', '1003'])
                  ->orWhere('account_name', 'like', '%bKash%')
                  ->orWhere('account_name', 'like', '%Nagad%')
                  ->orWhere('account_name', 'like', '%Rocket%')
                  ->orWhere('account_name', 'like', '%Upay%')
                  ->orWhere('account_name', 'like', '%Wallet%');
            })
            ->pluck('id')
            ->all();
    }

    private function cardAccountIds(): array
    {
        return Account::where('account_type', 'asset')
            ->where('account_name', 'like', '%Card%')
            ->pluck('id')
            ->all();
    }

    private function bankAccountIds(): array
    {
        return Account::where('account_type', 'asset')
            ->where(function ($q) {
                $q->where('is_bank_account', true)
                  ->orWhere('account_name', 'like', 'Bank%');
            })
            ->pluck('id')
            ->all();
    }

    private function accountBalance(array $accountIds): float
    {
        if (empty($accountIds)) {
            return 0.0;
        }

        $totals = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->whereIn('journal_entry_lines.account_id', $accountIds)
            ->selectRaw('COALESCE(SUM(debit_amount), 0) AS d, COALESCE(SUM(credit_amount), 0) AS c')
            ->first();

        $opening = (float) Account::whereIn('id', $accountIds)->sum('opening_balance');

        return $opening + (float) $totals->d - (float) $totals->c;
    }
}
