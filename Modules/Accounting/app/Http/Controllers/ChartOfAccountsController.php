<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Http\Requests\StoreAccountRequest;
use Modules\Accounting\Http\Requests\UpdateAccountRequest;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\ChartOfAccountsService;

class ChartOfAccountsController extends Controller
{
    public function __construct(
        private readonly ChartOfAccountsService $service
    ) {}

    /**
     * Display the chart of accounts.
     */
    public function index(Request $request)
    {
        bpAuthorize('accounting.view');
        $filters = $request->only(['search', 'account_type', 'status']);
        $stats = $this->service->getStats();
        $accountsByType = $this->service->getAccountsHierarchy($filters);
        $subTypes = Account::getSubTypes();

        return view('accounting::chart-of-accounts', compact('stats', 'accountsByType', 'filters', 'subTypes'));
    }

    /**
     * Show the form for creating a new account.
     */
    public function create()
    {
        bpAuthorize('accounting.create');
        $parentAccounts = $this->service->getAccountsGroupedByType();
        $subTypes = Account::getSubTypes();

        return view('accounting::chart-of-accounts-create', compact('parentAccounts', 'subTypes'));
    }

    /**
     * Store a newly created account.
     */
    public function store(StoreAccountRequest $request)
    {
        bpAuthorize('accounting.create');
        $this->service->create($request->validated());

        return redirect()->route('accounting.chart-of-accounts')
            ->with('success', __('Account created successfully.'));
    }

    /**
     * Show the form for editing an account.
     */
    public function edit(Account $account)
    {
        bpAuthorize('accounting.edit');
        $parentAccounts = $this->service->getAccountsGroupedByType();
        $subTypes = Account::getSubTypes();

        return view('accounting::chart-of-accounts-edit', compact('account', 'parentAccounts', 'subTypes'));
    }

    /**
     * Update the specified account.
     */
    public function update(UpdateAccountRequest $request, Account $account)
    {
        bpAuthorize('accounting.edit');
        $this->service->update($account, $request->validated());

        return redirect()->route('accounting.chart-of-accounts')
            ->with('success', __('Account updated successfully.'));
    }

    /**
     * Toggle the account's active status.
     */
    public function toggleStatus(Account $account): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('accounting.edit');
        $this->service->toggleStatus($account);

        return response()->json([
            'success'   => true,
            'is_active' => $account->status === 'active',
            'message'   => __('Status updated.'),
        ]);
    }

    /**
     * Delete the specified account.
     */
    public function destroy(Account $account)
    {
        bpAuthorize('accounting.delete');
        try {
            $this->service->delete($account);

            return redirect()->route('accounting.chart-of-accounts')
                ->with('success', __('Account deleted successfully.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
