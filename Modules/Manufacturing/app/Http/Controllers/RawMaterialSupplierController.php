<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Manufacturing\Models\RawMaterialSupplier;
use Modules\Manufacturing\Services\RawMaterialSupplierService;
use Illuminate\Http\Request;

class RawMaterialSupplierController extends Controller
{
    public function __construct(private RawMaterialSupplierService $supplierService)
    {
    }

    /**
     * Display a listing of raw material suppliers.
     */
    public function index(Request $request)
    {
        bpAuthorize('manufacturing.view');
        $suppliers = $this->supplierService->list(
            $request->only(['search', 'is_active', 'payment_status'])
        );

        $stats = [
            'total' => RawMaterialSupplier::count(),
            'active' => RawMaterialSupplier::where('is_active', true)->count(),
            'total_due' => RawMaterialSupplier::sum('due_balance'),
        ];

        return view('manufacturing::suppliers.index', compact('suppliers', 'stats'));
    }

    /**
     * Show the form for creating a new raw material supplier.
     */
    public function create()
    {
        bpAuthorize('manufacturing.create');
        return view('manufacturing::suppliers.create');
    }

    /**
     * Store a newly created raw material supplier.
     */
    public function store(Request $request)
    {
        bpAuthorize('manufacturing.create');
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'bank_branch' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $this->supplierService->create($validated);

        return redirect()->route('manufacturing.suppliers.index')->with('success', __('Supplier created successfully.'));
    }

    /**
     * Display the specified raw material supplier.
     */
    public function show(RawMaterialSupplier $supplier)
    {
        bpAuthorize('manufacturing.view');
        return view('manufacturing::suppliers.show', compact('supplier'));
    }

    /**
     * Show the form for editing the specified raw material supplier.
     */
    public function edit(RawMaterialSupplier $supplier)
    {
        bpAuthorize('manufacturing.edit');
        return view('manufacturing::suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified raw material supplier.
     */
    public function update(Request $request, RawMaterialSupplier $supplier)
    {
        bpAuthorize('manufacturing.edit');
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'bank_branch' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $this->supplierService->update($supplier, $validated);

        return redirect()->route('manufacturing.suppliers.index')->with('success', __('Supplier updated successfully.'));
    }

    /**
     * Toggle the active status of the specified raw material supplier (AJAX).
     */
    public function toggleStatus(RawMaterialSupplier $supplier): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('manufacturing.edit');
        $supplier->update(['is_active' => ! $supplier->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $supplier->is_active,
            'message' => __('Status updated.'),
        ]);
    }

    /**
     * Remove the specified raw material supplier.
     */
    public function destroy(RawMaterialSupplier $supplier)
    {
        bpAuthorize('manufacturing.delete');
        try {
            $this->supplierService->delete($supplier);

            return redirect()->route('manufacturing.suppliers.index')->with('success', __('Supplier deleted successfully.'));
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
