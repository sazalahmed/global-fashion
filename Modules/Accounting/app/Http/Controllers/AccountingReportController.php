<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\AccountingReportService;
use Modules\Accounting\Services\ChartOfAccountsService;
use Modules\Branch\Models\Branch;

class AccountingReportController extends Controller
{
    public function __construct(
        private readonly AccountingReportService $reportService,
        private readonly ChartOfAccountsService $accountService,
    ) {}

    /**
     * Display the general ledger.
     */
    public function generalLedger(Request $request)
    {
        bpAuthorize('accounting.view');
        $accounts = $this->accountService->getAccountsGroupedByType();
        $data = null;

        if ($request->filled('account_id')) {
            $data = $this->reportService->getGeneralLedger(
                (int) $request->account_id,
                $request->date_from ? Carbon::parse($request->date_from) : null,
                $request->date_to ? Carbon::parse($request->date_to) : null,
            );
        }

        return view('accounting::general-ledger', compact('accounts', 'data'));
    }

    /**
     * Display the trial balance.
     */
    public function trialBalance(Request $request)
    {
        bpAuthorize('accounting.view');
        $asOfDate = $request->as_of_date ? Carbon::parse($request->as_of_date) : now();
        $showZero = $request->boolean('show_zero', false);
        $data = $this->reportService->getTrialBalance($asOfDate, $showZero);

        return view('accounting::trial-balance', compact('data', 'asOfDate', 'showZero'));
    }

    /**
     * Display the profit & loss statement.
     */
    public function profitLoss(Request $request)
    {
        bpAuthorize('accounting.view');
        $from = $request->date_from ? Carbon::parse($request->date_from) : now()->startOfMonth();
        $to = $request->date_to ? Carbon::parse($request->date_to) : now();
        $data = $this->reportService->getProfitAndLoss($from, $to);

        return view('accounting::profit-loss', compact('data', 'from', 'to'));
    }

    /**
     * Display the balance sheet.
     */
    public function balanceSheet(Request $request)
    {
        bpAuthorize('accounting.view');
        $asOfDate = $request->as_of_date ? Carbon::parse($request->as_of_date) : now();
        $data = $this->reportService->getBalanceSheet($asOfDate);

        return view('accounting::balance-sheet', compact('data', 'asOfDate'));
    }

    /**
     * Display the cash flow statement.
     */
    public function cashFlow(Request $request)
    {
        bpAuthorize('accounting.view');
        [$from, $to] = $this->resolveDateRange($request);
        $period = $request->input('period', 'this_month');
        $data = $this->reportService->getCashFlowStatement($from, $to);
        $branches = Branch::active()->ordered()->get(['id', 'name']);

        return view('accounting::cash-flow', compact('data', 'from', 'to', 'branches', 'period'));
    }

    /**
     * Resolve date range from period or explicit date params.
     */
    private function resolveDateRange(Request $request): array
    {
        $period = $request->input('period', 'this_month');

        if ($period === 'custom' && $request->date_from) {
            return [
                Carbon::parse($request->date_from),
                $request->date_to ? Carbon::parse($request->date_to) : now(),
            ];
        }

        return match ($period) {
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_quarter' => [now()->startOfQuarter(), now()],
            'this_year' => $this->financialYearRange(),
            default => [now()->startOfMonth(), now()],
        };
    }

    /**
     * Get the current Bangladesh financial year range (Jul–Jun).
     */
    private function financialYearRange(): array
    {
        $now = now();
        $fyStart = $now->month >= 7
            ? $now->copy()->startOfYear()->addMonths(6)
            : $now->copy()->subYear()->startOfYear()->addMonths(6);

        return [$fyStart, $now];
    }
}
