<?php

use Illuminate\Support\Facades\DB;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;

/**
 * One-off data fix: find every payment whose posted journal entry no longer
 * matches its current amount/discount (left stale by SaleService::syncPayments()
 * editing a payment without rebuilding its journal — fixed in code, but that
 * fix only prevents new drift, it doesn't repair rows already broken by it).
 */
$paymentService = app(PaymentService::class);

$payments = DB::table('payments')->whereNotNull('journal_entry_id')->whereNull('deleted_at')->get();

$mismatches = [];
foreach ($payments as $p) {
    $je = DB::table('journal_entries')->find($p->journal_entry_id);
    if (! $je || $je->status !== 'posted') {
        continue;
    }
    $sumDr = DB::table('journal_entry_lines')->where('journal_entry_id', $je->id)->sum('debit_amount');
    $expected = (float) $p->amount + (float) $p->discount_amount;
    if (abs($sumDr - $expected) > 0.01) {
        $mismatches[] = $p->id;
    }
}

echo 'Mismatched payments found: ' . count($mismatches) . PHP_EOL;

foreach ($mismatches as $id) {
    $payment = Payment::find($id);
    if (! $payment) {
        continue;
    }
    echo "Resyncing payment #{$id} ({$payment->payment_number})...";
    $paymentService->resyncJournal($payment);
    echo ' done.' . PHP_EOL;
}

echo 'Done.' . PHP_EOL;
