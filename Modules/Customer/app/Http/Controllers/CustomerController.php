<?php

namespace Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Customer\Http\Requests\StoreCustomerRequest;
use Modules\Customer\Http\Requests\UpdateCustomerRequest;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerGroup;
use Modules\Customer\Services\CustomerService;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService
    ) {}

    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        bpAuthorize('customers.view');
        $filters = $request->all();

        if (isset($filters['status'])) {
            $filters['is_active'] = $filters['status'] === 'active';
        }

        $stats = $this->customerService->getStats();
        $customers = $this->customerService->list($filters, 15);
        $listTotals = $this->customerService->getListTotals($filters);

        return view('customer::index', compact('stats', 'customers', 'listTotals'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        bpAuthorize('customers.create');
        $customerGroups = CustomerGroup::active()->orderBy('name')->get();
        $districts = \DB::table('districts')->where('is_active', true)->orderBy('district_name')->get(['id', 'district_name']);

        return view('customer::create', compact('customerGroups', 'districts'));
    }

    /**
     * AJAX: thanas for a given district. Used by the District → Thana
     * cascade on the customer form.
     */
    public function thanasByDistrict(int $districtId)
    {
        bpAuthorize('customers.view');
        $thanas = \DB::table('thanas')
            ->where('district_id', $districtId)
            ->where('is_active', true)
            ->orderBy('thana_name')
            ->get(['id', 'thana_name']);

        return response()->json($thanas);
    }

    /**
     * Store a newly created customer.
     */
    public function store(StoreCustomerRequest $request)
    {
        bpAuthorize('customers.create');
        $customer = $this->customerService->create($request->validated());

        return redirect()->route('customers.show', $customer)
            ->with('success', __('Customer created successfully.'));
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer)
    {
        bpAuthorize('customers.view');
        $customer = $this->customerService->find($customer->id);

        $tabData = collect(['sales', 'payments', 'ledger', 'advance'])
            ->flatMap(fn ($tab) => $this->profileTabData($customer, $tab))
            ->all();

        $profileStats = $this->customerService->getProfileStats($customer->id);

        return view('customer::show', array_merge(compact('customer', 'profileStats'), $tabData));
    }

    /**
     * One profile tab, rendered as an HTML fragment for AJAX pagination.
     */
    public function tab(Customer $customer, string $tab)
    {
        bpAuthorize('customers.view');

        abort_unless(in_array($tab, ['sales', 'payments', 'ledger', 'advance'], true), 404);

        return view('customer::partials.tab-' . $tab, $this->profileTabData($customer, $tab));
    }

    /**
     * View data for a single profile tab. Paginator URLs point at the tab
     * endpoint so pagination links always fetch the AJAX fragment.
     */
    private function profileTabData(Customer $customer, string $tab): array
    {
        $path = route('customers.tab', [$customer, $tab]);
        $perPage = min(max((int) request('per_page', 15), 1), 100);

        switch ($tab) {
            case 'sales':
                return ['sales' => $this->customerService->getSalesHistory($customer->id, $perPage)->withPath($path)];
            case 'payments':
                return ['payments' => $this->customerService->getPaymentHistory($customer->id, $perPage)->withPath($path)];
            case 'ledger':
                $ledger = $this->customerService->getLedgerPaginated($customer->id, $perPage);
                $ledger['entries']->withPath($path);

                return ['ledger' => $ledger];
            case 'advance':
                return [
                    'advanceTransactions' => $this->customerService->getAdvanceTransactions($customer->id, $perPage)->withPath($path),
                    'advanceStats'        => $this->customerService->getAdvanceStats($customer->id),
                ];
        }

        return [];
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer)
    {
        bpAuthorize('customers.edit');
        $customer = $this->customerService->find($customer->id);
        $customerGroups = CustomerGroup::active()->orderBy('name')->get();
        $districts = \DB::table('districts')->where('is_active', true)->orderBy('district_name')->get(['id', 'district_name']);

        return view('customer::edit', compact('customer', 'customerGroups', 'districts'));
    }

    /**
     * Update the specified customer.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        bpAuthorize('customers.edit');
        $this->customerService->update($customer, $request->validated());

        return redirect()->route('customers.show', $customer)
            ->with('success', __('Customer updated successfully.'));
    }

    /**
     * Toggle the customer's active status.
     */
    public function toggleStatus(Customer $customer): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('customers.edit');
        $customer = $this->customerService->toggleStatus($customer);

        return response()->json(['success' => true, 'is_active' => $customer->is_active, 'message' => __('Status updated.')]);
    }

    /**
     * Offset customer dues using advance balance.
     */
    public function offsetDue(Customer $customer)
    {
        bpAuthorize('customers.edit');
        $result = $this->customerService->offsetDueWithAdvance($customer);

        if ($result['applied'] > 0) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer)
    {
        bpAuthorize('customers.delete');
        try {
            $this->customerService->delete($customer);

            return redirect()->route('customers.index')
                ->with('success', __('Customer deleted successfully.'));
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Search customers (AJAX endpoint).
     */
    public function search(Request $request)
    {
        bpAuthorize('customers.view');
        $results = $this->customerService->search($request->input('q', ''), 15);

        return response()->json($results);
    }

    /**
     * Quick store a customer (AJAX endpoint). Accepts the same fields as the
     * full customer create form so callers can embed the form inside a modal.
     */
    public function quickStore(Request $request)
    {
        bpAuthorize('customers.create');
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'phone'             => ['required', 'string', 'max:30', 'unique:customers,phone', new \App\Rules\PhoneNumber],
            'email'             => 'nullable|email|max:255|unique:customers,email',
            'company_name'      => 'nullable|string|max:255',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'district'          => 'nullable|string|max:50',
            'upazila'           => 'nullable|string|max:100',
            'address'           => 'nullable|string|max:500',
            'photo'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'notes'             => 'nullable|string|max:2000',
        ]);

        $customer = $this->customerService->create($validated);

        return response()->json(['customer' => $customer]);
    }

    /**
     * Display customers with outstanding due balances.
     */
    public function dueReceive(Request $request)
    {
        bpAuthorize('customers.view');
        $filters = $request->only(['search', 'customer_group_id']);
        $customers = $this->customerService->getDueReceiveList($filters, 15);
        $totalDue = $this->customerService->getTotalDue();
        $dueTotals = $this->customerService->getDueReceiveTotals($filters);

        return view('customer::due-receive', compact('customers', 'totalDue', 'dueTotals'));
    }

    /**
     * Display customer advance balances.
     */
    public function advances(Request $request)
    {
        bpAuthorize('customers.view');
        $filters = $request->only(['search']);
        $advances = $this->customerService->getCustomerAdvances($filters);

        return view('customer::advances', compact('advances'));
    }

    /**
     * Display the customer ledger.
     */
    public function ledger(Request $request)
    {
        bpAuthorize('customers.view');
        $customers = \Modules\Customer\Models\Customer::orderBy('name')->get(['id', 'name', 'phone']);
        $filters = $request->only(['customer_id', 'date_from', 'date_to', 'direction']);

        $ledgerData = null;
        $advanceBalance = 0;
        if (!empty($filters['customer_id'])) {
            $customerId = (int) $filters['customer_id'];
            $ledgerData = $this->customerService->getLedgerPaginated(
                $customerId,
                (int) $request->input('per_page', 25),
                null,
                $filters
            );

            $advanceBalance = (float) \Modules\Payment\Models\Payment::where('party_type', 'customer')
                ->where('party_id', $customerId)
                ->whereIn('payment_type', ['advance_payment', 'advance_return'])
                ->selectRaw("
                    COALESCE(SUM(CASE WHEN payment_type = 'advance_payment' THEN amount ELSE 0 END), 0)
                    - COALESCE(SUM(CASE WHEN payment_type = 'advance_return' THEN amount ELSE 0 END), 0)
                    as balance
                ")
                ->value('balance');
        }

        return view('customer::ledger', [
            'customers'      => $customers,
            // Pass the paginator through untouched — wrapping it in collect()
            // strips hasPages() and the view falls back to a bare count.
            'ledgerEntries'  => $ledgerData['entries'] ?? collect(),
            'totalDebit'     => $ledgerData ? number_format($ledgerData['totalDebit']) : '0',
            'totalCredit'    => $ledgerData ? number_format($ledgerData['totalCredit']) : '0',
            'currentBalance' => $ledgerData ? number_format($ledgerData['currentBalance']) : '0',
            'advanceBalance' => number_format($advanceBalance),
            'customerName'   => $ledgerData['customerName'] ?? 'Select a customer',
        ]);
    }
}
