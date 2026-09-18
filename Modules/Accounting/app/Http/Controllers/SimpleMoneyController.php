<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Services\SimpleMoneyService;

/**
 * Simple-mode money pages — Cash Book, Income, Expense, Summary.
 * All views are read-only over the existing GL.
 */
class SimpleMoneyController extends Controller
{
    public function __construct(
        private readonly SimpleMoneyService $money,
    ) {}

    public function cashFlow(Request $request)
    {
        bpAuthorize('finance.view');
        $hasFilter = $request->filled('date_from') || $request->filled('date_to');
        $from = $request->filled('date_from') ? $request->date_from : null;
        $to = $request->filled('date_to') ? $request->date_to : null;
        $accountId = $request->filled('account_id') ? (int) $request->account_id : null;

        $result = $this->money->cashFlow($from, $to, $accountId);

        // COD the courier has collected and not yet paid over. Not cash — it
        // sits in receivables until the payout lands — so it is shown beside
        // the flows for context and deliberately left out of the totals, which
        // must keep reconciling to the cash accounts. A point-in-time balance,
        // so it ignores the date filter. Cached hourly by the service.
        $courierBalance = app(\Modules\Ecommerce\Services\CourierBalanceService::class);

        return view('accounting::money.cashflow', [
            'courierReceivable' => $courierBalance->isConfigured() ? $courierBalance->get() : null,
            'data'           => $result['data'],
            'totalReceive'   => $result['totalReceive'],
            'totalPay'       => $result['totalPay'],
            'openingBalance' => $result['openingBalance'],
            'currentBalance' => $result['currentBalance'],
            'hasDateFilter'  => $result['hasDateFilter'],
            'accounts'       => $result['accounts'],
            'from'           => $from,
            'to'             => $to,
            'accountId'      => $accountId,
        ]);
    }

    public function expense(Request $request)
    {
        bpAuthorize('finance.view');
        $from = $request->date_from ?: now()->startOfMonth()->toDateString();
        $to = $request->date_to ?: now()->toDateString();

        $data = $this->money->expense($from, $to);

        return view('accounting::money.expense', compact('data', 'from', 'to'));
    }
}
