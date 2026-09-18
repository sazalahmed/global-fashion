<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Accounting\Http\Requests\StoreCapitalTransactionRequest;
use Modules\Accounting\Models\CapitalTransaction;
use Modules\Payment\Models\PaymentAccount;

class CapitalTransactionController extends Controller
{
    public function index(Request $request): View
    {
        bpAuthorize('finance.view');
        $type = $request->input('type', 'deposit');
        if (!in_array($type, ['deposit', 'withdraw'], true)) {
            $type = 'deposit';
        }

        $transactions = CapitalTransaction::with(['paymentAccount', 'creator'])
            ->where('type', $type)
            ->latest('transaction_date')
            ->paginate(15)
            ->withQueryString();

        $totals = [
            'deposit'  => (float) CapitalTransaction::deposits()->sum('amount'),
            'withdraw' => (float) CapitalTransaction::withdrawals()->sum('amount'),
        ];
        $totals['net'] = $totals['deposit'] - $totals['withdraw'];

        $accounts = PaymentAccount::where('is_active', true)->orderBy('name')->get();

        return view('accounting::capital-transactions.index', compact('transactions', 'totals', 'accounts', 'type'));
    }

    public function store(StoreCapitalTransactionRequest $request): RedirectResponse
    {
        bpAuthorize('finance.create');
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        CapitalTransaction::create($data);

        $verb = $data['type'] === 'deposit' ? 'Deposit' : 'Withdraw';
        return redirect()
            ->route('capital-transactions.index', ['type' => $data['type']])
            ->with('success', "{$verb} of " . currency_symbol() . " " . number_format($data['amount'], 2) . ' recorded.');
    }

    public function destroy(CapitalTransaction $capitalTransaction): RedirectResponse
    {
        bpAuthorize('finance.delete');
        $type = $capitalTransaction->type;
        $capitalTransaction->delete();

        return redirect()
            ->route('capital-transactions.index', ['type' => $type])
            ->with('success', __('Transaction deleted.'));
    }
}
