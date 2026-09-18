<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Models\BankReconciliation;
use Modules\Accounting\Services\BankReconciliationService;

class BankReconciliationController extends Controller
{
    public function __construct(
        private readonly BankReconciliationService $service,
    ) {}

    /**
     * Display bank reconciliation list / start page.
     */
    public function index(Request $request)
    {
        bpAuthorize('accounting.view');
        $bankAccounts = $this->service->getBankAccounts();

        $selectedAccountId = $request->input('bank_account');
        $statementDate = $request->input('statement_date');
        $statementBalanceInput = $request->input('statement_balance');

        $selectedAccount = null;
        $reconciliation = null;
        $summary = null;
        $bookItems = collect();
        $statementItems = collect();

        if ($selectedAccountId && $statementDate) {
            $selectedAccount = $bankAccounts->firstWhere('id', $selectedAccountId);

            // Find an existing reconciliation for this account and date
            $reconciliation = BankReconciliation::with('items')
                ->where('account_id', $selectedAccountId)
                ->where('statement_date', $statementDate)
                ->latest()
                ->first();

            if ($reconciliation) {
                $summary = $this->service->getSummary($reconciliation);
                $bookItems = $reconciliation->items->where('type', 'book_transaction');
                $statementItems = $reconciliation->items->where('type', 'bank_statement');
            } else {
                // No reconciliation yet — provide empty summary with defaults
                $bookBalance = $selectedAccount
                    ? $selectedAccount->balance
                    : 0;
                $stmtBalance = $statementBalanceInput !== null
                    ? (float) str_replace(',', '', $statementBalanceInput)
                    : 0;

                $summary = [
                    'book_balance'                 => $bookBalance,
                    'statement_balance'            => $stmtBalance,
                    'difference'                   => $stmtBalance - $bookBalance,
                    'total_book_items'             => 0,
                    'total_statement_items'        => 0,
                    'reconciled_count'             => 0,
                    'unreconciled_count'           => 0,
                    'unreconciled_book_total'      => 0,
                    'unreconciled_statement_total' => 0,
                ];
            }
        }

        return view('accounting::bank-reconciliation', compact(
            'bankAccounts', 'selectedAccount', 'selectedAccountId',
            'statementDate', 'reconciliation', 'summary',
            'bookItems', 'statementItems',
        ));
    }

    /**
     * Start a new reconciliation session.
     */
    public function start(Request $request)
    {
        bpAuthorize('accounting.create');
        $request->validate([
            'account_id'        => 'required|exists:accounts,id',
            'statement_date'    => 'required|date',
            'statement_balance' => 'required|numeric',
        ]);

        $reconciliation = $this->service->start(
            $request->input('account_id'),
            $request->input('statement_date'),
            $request->input('statement_balance'),
        );

        return redirect()->route('accounting.bank-reconciliation.show', $reconciliation)
            ->with('success', __('Reconciliation started. Match transactions below.'));
    }

    /**
     * Show a reconciliation session with items.
     */
    public function show(BankReconciliation $reconciliation)
    {
        bpAuthorize('accounting.view');
        $reconciliation = $this->service->find($reconciliation->id);
        $summary = $this->service->getSummary($reconciliation);

        $bookItems = $reconciliation->items->where('type', 'book_transaction');
        $statementItems = $reconciliation->items->where('type', 'bank_statement');

        return view('accounting::bank-reconciliation-show', compact(
            'reconciliation', 'summary', 'bookItems', 'statementItems',
        ));
    }

    /**
     * Import bank statement CSV.
     */
    public function importStatement(Request $request, BankReconciliation $reconciliation)
    {
        bpAuthorize('accounting.create');
        $request->validate([
            'statement_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('statement_file');
        $rows = [];

        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle); // skip header row

        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) >= 3) {
                $rows[] = [
                    'date'        => $line[0],
                    'description' => $line[1],
                    'amount'      => (float) str_replace(',', '', $line[2]),
                    'reference'   => $line[3] ?? null,
                ];
            }
        }
        fclose($handle);

        $count = $this->service->importStatement($reconciliation, $rows);

        return back()->with('success', "{$count} statement entries imported.");
    }

    /**
     * Match a book transaction with a statement item.
     */
    public function match(Request $request)
    {
        bpAuthorize('accounting.edit');
        $request->validate([
            'book_item_id'      => 'required|integer|exists:bank_reconciliation_items,id',
            'statement_item_id' => 'required|integer|exists:bank_reconciliation_items,id',
        ]);

        $this->service->matchItems(
            $request->input('book_item_id'),
            $request->input('statement_item_id'),
        );

        return back()->with('success', __('Items matched.'));
    }

    /**
     * Unmatch a reconciliation item.
     */
    public function unmatch(Request $request)
    {
        bpAuthorize('accounting.edit');
        $request->validate([
            'item_id' => 'required|integer|exists:bank_reconciliation_items,id',
        ]);

        $this->service->unmatchItems($request->input('item_id'));

        return back()->with('success', __('Items unmatched.'));
    }

    /**
     * Complete the reconciliation.
     */
    public function complete(BankReconciliation $reconciliation)
    {
        bpAuthorize('accounting.edit');
        $this->service->complete($reconciliation);

        return redirect()->route('accounting.bank-reconciliation')
            ->with('success', __('Bank reconciliation completed.'));
    }
}
