<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Models\Account;
use Modules\Payment\Http\Requests\StorePaymentRequest;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAccount;
use Modules\Payment\Services\PaymentService;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $service,
    ) {}

    /**
     * Display a listing of all payments.
     */
    public function index(Request $request)
    {
        bpAuthorize('payments.view');
        $filters = $request->only(['search', 'direction', 'party_type', 'payment_method', 'date_from', 'date_to']);
        $stats = $this->service->getStats($filters);
        $payments = $this->service->list($filters);

        return view('payment::index', compact('stats', 'payments'));
    }

    /**
     * Show the form for creating a new payment. Works both as a global form
     * (no query params — user picks party in the page) and as a party-scoped
     * shortcut (party_type + party_id deep-link from a customer/supplier).
     */
    public function create(Request $request)
    {
        bpAuthorize('payments.create');
        $paymentAccounts = PaymentAccount::active()
            ->with('bank')
            ->orderBy('account_type')
            ->orderBy('name')
            ->get();

        $branches = \Modules\Branch\Models\Branch::where('is_active', true)->get();

        $partyType   = $request->query('party_type', '');
        $partyId     = $request->query('party_id', '');
        $direction   = $request->query('direction', 'receive');
        $paymentType = $request->query('payment_type', '');

        // Lock the direction / payment type only when the page was opened for a
        // specific purpose (deep link that names them in the URL). A bare
        // /payments/create stays fully flexible. Drives which controls the view
        // renders — e.g. a "Receive Due" link hides the Make Payment button.
        $directionLocked   = $request->query->has('direction');
        $paymentTypeLocked = $request->filled('payment_type');

        $prefillParty = null;
        $outstandingInvoices = collect();

        if ($partyType && $partyId) {
            $prefillParty = $this->service->findParty($partyType, (int) $partyId);
            $outstandingInvoices = $this->service->getOutstandingInvoices($partyType, (int) $partyId);
        }

        $action     = $this->paymentActionLabel($direction, $paymentType);
        $pageTitle  = $this->buildPageTitle($action, $direction, $prefillParty);
        $breadcrumb = $this->buildBreadcrumb($partyType, $prefillParty, $action);

        return view('payment::create', compact(
            'paymentAccounts', 'branches', 'direction',
            'partyType', 'partyId', 'paymentType', 'prefillParty', 'outstandingInvoices',
            'pageTitle', 'breadcrumb', 'directionLocked', 'paymentTypeLocked'
        ));
    }

    /**
     * Short action verb for the payment, e.g. "Pay Advance", "Receive Payment".
     */
    private function paymentActionLabel(string $direction, string $paymentType): string
    {
        if ($paymentType === 'advance_return') {
            return $direction === 'pay' ? 'Refund Advance' : 'Recover Advance';
        }
        if ($paymentType === 'advance_payment') {
            return $direction === 'pay' ? 'Pay Advance' : 'Receive Advance';
        }

        return $direction === 'pay' ? 'Pay' : 'Receive Payment';
    }

    /**
     * Build the H1/title for the create form. Combines the action verb
     * with the party name when party context is known.
     */
    private function buildPageTitle(string $action, string $direction, ?object $party): string
    {
        if (! $party) {
            return 'Record Payment';
        }

        $name = $party->name ?? 'Party';
        $connector = $direction === 'pay' ? 'to' : 'from';

        // "Pay {name}" reads better than "Pay to {name}"
        if ($action === 'Pay') {
            return "Pay {$name}";
        }

        return "{$action} {$connector} {$name}";
    }

    /**
     * Build breadcrumb segments for the create form. Each segment is
     * ['label' => string, 'route' => ?string, 'params' => array].
     */
    private function buildBreadcrumb(string $partyType, ?object $party, string $action): array
    {
        if (! $party) {
            return [
                ['label' => 'Payments', 'route' => 'payments.index', 'params' => []],
                ['label' => 'Record Payment', 'route' => null, 'params' => []],
            ];
        }

        $partyRoutes = [
            'customer' => ['index' => 'customers.index', 'show' => 'customers.show', 'plural' => 'Customers'],
            'supplier' => ['index' => 'supplier.index',  'show' => 'supplier.show',  'plural' => 'Suppliers'],
            'employee' => ['index' => 'employee.index',  'show' => 'employee.show',  'plural' => 'Employees'],
        ];

        $cfg = $partyRoutes[$partyType] ?? null;
        $name = $party->name ?? '#' . ($party->id ?? '');

        if (! $cfg) {
            return [
                ['label' => 'Payments', 'route' => 'payments.index', 'params' => []],
                ['label' => $action,    'route' => null,             'params' => []],
            ];
        }

        return [
            ['label' => $cfg['plural'], 'route' => $cfg['index'], 'params' => []],
            ['label' => $name,          'route' => $cfg['show'],  'params' => [$party->id]],
            ['label' => $action,        'route' => null,          'params' => []],
        ];
    }

    /**
     * Store a newly created payment.
     */
    public function store(StorePaymentRequest $request)
    {
        bpAuthorize('payments.create');
        $data = $request->validated();
        $allocations = $data['allocations'] ?? [];
        $splits = $data['splits'] ?? [];
        unset($data['allocations'], $data['splits']);

        // If splits provided, create one payment per split method
        if (!empty($splits)) {
            $payments = [];
            $totalAmount = (float) $data['amount'];

            foreach ($splits as $i => $split) {
                $splitData = $data;
                $splitData['amount'] = (float) $split['amount'];
                $splitData['payment_account_id'] = $split['payment_account_id'];

                // Proportionally distribute allocation cash amounts across
                // splits (cash follows the account it moved through).
                // Discount isn't cash and doesn't belong to any one account,
                // so it isn't split proportionally — the full discount rides
                // on the first split only, so summing every split's
                // allocations reproduces exactly the original discount with
                // no double-count or loss. $totalAmount is 0 on a pure
                // write-off (0 cash, 100% discount); guard the ratio so that
                // doesn't divide by zero.
                $splitAllocations = [];
                if (!empty($allocations)) {
                    $ratio = $totalAmount > 0 ? ($splitData['amount'] / $totalAmount) : 0;
                    foreach ($allocations as $alloc) {
                        $splitAllocations[] = [
                            'allocatable_type' => $alloc['allocatable_type'],
                            'allocatable_id'   => $alloc['allocatable_id'],
                            'amount'           => round((float) $alloc['amount'] * $ratio, 2),
                            'discount_amount'  => $i === 0 ? (float) ($alloc['discount_amount'] ?? 0) : 0,
                        ];
                    }
                }

                $payments[] = $this->service->create($splitData, $splitAllocations);
            }

            $first = $payments[0];
            $count = count($payments);

            return redirect()->route('payments.show', $first)
                ->with('success', "{$count} split payments totalling " . currency_symbol() . " " . number_format($totalAmount) . " recorded.");
        }

        $payment = $this->service->create($data, $allocations);

        return redirect()->route('payments.show', $payment)
            ->with('success', "Payment {$payment->payment_number} recorded successfully.");
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment)
    {
        bpAuthorize('payments.view');
        $payment = $this->service->find($payment->id);

        return view('payment::show', compact('payment'));
    }

    /**
     * Printable payment receipt (self-contained styled view; auto-prints).
     */
    public function printReceipt(Payment $payment)
    {
        bpAuthorize('payments.view');
        $payment = $this->service->find($payment->id);

        return view('payment::receipt', ['payment' => $payment, 'isPdf' => false]);
    }

    /**
     * Download the payment receipt as a PDF.
     */
    public function pdf(Payment $payment)
    {
        bpAuthorize('payments.view');
        $payment = $this->service->find($payment->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payment::receipt', ['payment' => $payment, 'isPdf' => true])
            ->setPaper('a4', 'portrait');

        return $pdf->download("Receipt-{$payment->payment_number}.pdf");
    }

    /**
     * Display the advance balances overview.
     */
    public function advances()
    {
        bpAuthorize('payments.view');
        $advances = $this->service->getAdvanceBalances();

        // Load party names
        $customerIds = ($advances['customer'] ?? collect())->pluck('party_id')->toArray();
        $supplierIds = ($advances['supplier'] ?? collect())->pluck('party_id')->toArray();
        $employeeIds = ($advances['employee'] ?? collect())->pluck('party_id')->toArray();

        $customerNames = [];
        $supplierNames = [];
        $employeeNames = [];

        if ($customerIds) {
            $customerNames = \Modules\Customer\Models\Customer::whereIn('id', $customerIds)
                ->pluck('name', 'id')->toArray();
        }
        if ($supplierIds) {
            $supplierNames = \Modules\Supplier\Models\Supplier::whereIn('id', $supplierIds)
                ->pluck('company_name', 'id')->toArray();
        }
        if ($employeeIds) {
            $employeeNames = \Modules\Employee\Models\Employee::whereIn('id', $employeeIds)
                ->pluck('name', 'id')->toArray();
            $employeeDepts = \Modules\Employee\Models\Employee::whereIn('id', $employeeIds)
                ->get()->mapWithKeys(fn ($e) => [$e->id => $e->department])->toArray();
        }

        $partyNames = [
            'customer' => $customerNames,
            'supplier' => $supplierNames,
            'employee' => $employeeNames,
        ];

        $employeeDepartments = $employeeDepts ?? [];

        $totals = [
            'customer' => ($advances['customer'] ?? collect())->sum(fn ($a) => $a->received - $a->returned),
            'supplier' => ($advances['supplier'] ?? collect())->sum(fn ($a) => $a->paid - $a->returned),
            'employee' => ($advances['employee'] ?? collect())->sum(fn ($a) => $a->paid - $a->returned),
        ];

        return view('payment::advance', compact('advances', 'partyNames', 'employeeDepartments', 'totals'));
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(Payment $payment)
    {
        bpAuthorize('payments.delete');
        $this->service->delete($payment);

        return redirect()->route('payments.index')
            ->with('success', __('Payment deleted and journal entry voided.'));
    }

    // ── AJAX Endpoints ──

    /**
     * Search parties (customers/suppliers) by name or phone.
     */
    public function partySearch(Request $request): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('payments.view');
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'type'         => 'required|in:customer,supplier,employee',
            'q'            => 'nullable|string|max:100',
            'payment_type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // For "against invoice" payments, only list parties that actually owe —
        // advance payments/returns can involve any party.
        $duesOnly = $request->input('payment_type') === 'against_invoice';

        $results = $this->service->searchParties(
            $request->input('type'),
            (string) $request->input('q', ''),
            $duesOnly,
        );

        return response()->json($results);
    }

    /**
     * Get outstanding invoices for a party.
     */
    public function outstandingInvoices(Request $request): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('payments.view');
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'party_type' => 'required|in:customer,supplier',
            'party_id'   => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $invoices = $this->service->getOutstandingInvoices(
            $request->input('party_type'),
            $request->input('party_id'),
        );

        return response()->json($invoices);
    }
}
