<?php

namespace Modules\Supplier\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Services\SupplierService;
use Modules\Supplier\Http\Requests\StoreSupplierRequest;
use Modules\Supplier\Http\Requests\UpdateSupplierRequest;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(private SupplierService $supplierService)
    {
    }

    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request)
    {
        bpAuthorize('suppliers.view');
        $suppliers = $this->supplierService->list($request->only(['search', 'status', 'payment_status']));
        $stats = $this->supplierService->getStats();

        return view('supplier::index', compact('suppliers', 'stats'));
    }

    /**
     * Display suppliers with outstanding payable balances.
     */
    public function payable(Request $request)
    {
        bpAuthorize('suppliers.view');
        $filters = $request->only(['search', 'supplier_group_id']);
        $suppliers = $this->supplierService->getPayableList($filters, 15);
        $totalPayable = $this->supplierService->getTotalPayable();

        return view('supplier::payable', compact('suppliers', 'totalPayable'));
    }

    /**
     * Show the form for creating a new supplier.
     */
    public function create()
    {
        bpAuthorize('suppliers.create');
        return view('supplier::create');
    }

    /**
     * Store a newly created supplier.
     */
    public function store(StoreSupplierRequest $request)
    {
        bpAuthorize('suppliers.create');
        $this->supplierService->create($request->validated());

        return redirect()->route('supplier.index')->with('success', __('Supplier created successfully.'));
    }

    /**
     * Display the specified supplier.
     */
    public function show(Supplier $supplier)
    {
        bpAuthorize('suppliers.view');
        $supplier->load('payments');

        $purchases = \Modules\Purchase\Models\Purchase::where('supplier_id', $supplier->id)
            ->orderByDesc('po_date')
            ->limit(50)
            ->get();

        // Get advance transactions for this supplier
        $advanceTransactions = \Modules\Payment\Models\Payment::where('party_type', 'supplier')
            ->where('party_id', $supplier->id)
            ->whereIn('payment_type', ['advance_payment', 'advance_return'])
            ->orderByDesc('payment_date')
            ->get();

        $advanceStats = [
            'balance' => $supplier->advance_balance,
            'total_paid' => $advanceTransactions->where('payment_type', 'advance_payment')->sum('amount'),
            'total_returned' => $advanceTransactions->where('payment_type', 'advance_return')->sum('amount'),
        ];

        $ledger = $this->supplierService->getLedger($supplier);

        return view('supplier::show', compact('supplier', 'purchases', 'advanceTransactions', 'advanceStats', 'ledger'));
    }

    /**
     * Show the form for editing the specified supplier.
     */
    public function edit(Supplier $supplier)
    {
        bpAuthorize('suppliers.edit');
        return view('supplier::edit', compact('supplier'));
    }

    /**
     * Update the specified supplier.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        bpAuthorize('suppliers.edit');
        $this->supplierService->update($supplier, $request->validated());

        return redirect()->route('supplier.show', $supplier)->with('success', __('Supplier updated successfully.'));
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier)
    {
        bpAuthorize('suppliers.delete');
        try {
            $this->supplierService->delete($supplier);

            return redirect()->route('supplier.index')->with('success', __('Supplier deleted successfully.'));
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Toggle the active/inactive status of the specified supplier.
     */
    public function toggleStatus(Supplier $supplier): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('suppliers.edit');
        $this->supplierService->toggleStatus($supplier);

        return response()->json([
            'success'   => true,
            'is_active' => $supplier->status === 'active',
            'message'   => __('Status updated.'),
        ]);
    }

    /**
     * Display the supplier ledger.
     */
    public function ledger(Request $request, Supplier $supplier)
    {
        bpAuthorize('suppliers.view');
        $ledger = $this->supplierService->getLedger($supplier, $request->only(['from', 'to']));
        $suppliers = $this->supplierService->getActiveSuppliers();

        return view('supplier::ledger', [
            'supplier' => $supplier,
            'suppliers' => $suppliers,
            'ledgerEntries' => $ledger['entries'],
            'totalDebit' => $ledger['totalDebit'],
            'totalCredit' => $ledger['totalCredit'],
            'currentBalance' => $ledger['currentBalance'],
        ]);
    }

    /**
     * Print supplier detail with purchase history.
     */
    public function print(Supplier $supplier)
    {
        bpAuthorize('suppliers.view');
        $supplier->load('payments');

        $purchases = \Modules\Purchase\Models\Purchase::where('supplier_id', $supplier->id)
            ->orderByDesc('po_date')
            ->get();

        $settings = [
            'company_name' => \Modules\Setting\Models\Setting::get('business', 'company_name', 'BizPOS Pro'),
            'address'      => \Modules\Setting\Models\Setting::get('business', 'address', ''),
            'phone'        => \Modules\Setting\Models\Setting::get('business', 'phone', ''),
        ];

        return view('supplier::print', compact('supplier', 'purchases', 'settings'));
    }

    /**
     * Export supplier purchase history as CSV.
     */
    public function export(Supplier $supplier)
    {
        bpAuthorize('suppliers.export');
        $purchases = \Modules\Purchase\Models\Purchase::where('supplier_id', $supplier->id)
            ->orderByDesc('po_date')
            ->get();

        $filename = 'supplier-' . \Illuminate\Support\Str::slug($supplier->company_name) . '-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($supplier, $purchases) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Supplier Report — ' . $supplier->company_name]);
            fputcsv($file, ['Generated', now()->format('d M Y, h:i A')]);
            fputcsv($file, []);
            fputcsv($file, ['Company', $supplier->company_name]);
            fputcsv($file, ['Contact', $supplier->contact_person ?? '']);
            fputcsv($file, ['Phone', $supplier->phone ?? '']);
            fputcsv($file, ['Email', $supplier->email ?? '']);
            fputcsv($file, ['Total Purchase', $supplier->total_purchase]);
            fputcsv($file, ['Total Paid', $supplier->total_paid]);
            fputcsv($file, ['Due Balance', $supplier->due_balance]);
            fputcsv($file, []);
            fputcsv($file, ['PO #', 'Date', 'Grand Total', 'Paid', 'Due', 'Status', 'Payment Status']);

            foreach ($purchases as $po) {
                fputcsv($file, [
                    $po->po_number,
                    $po->po_date->format('d M Y'),
                    $po->grand_total,
                    $po->paid_amount,
                    $po->due_amount,
                    $po->status,
                    $po->payment_status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
