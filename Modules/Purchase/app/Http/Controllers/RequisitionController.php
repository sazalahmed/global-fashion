<?php

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Branch\Models\Branch;
use Modules\Product\Models\Product;
use Modules\Purchase\Models\Requisition;
use Modules\Purchase\Services\RequisitionService;

class RequisitionController extends Controller
{
    public function __construct(
        private readonly RequisitionService $service,
    ) {}

    public function index(Request $request)
    {
        bpAuthorize('purchases.view');
        $stats = $this->service->getStats();
        $statusCounts = $this->service->statusCounts();
        $requisitions = $this->service->list($request->only(['search', 'status', 'branch_id']));
        $branches = Branch::where('is_active', true)->get();

        return view('purchase::requisitions.index', compact('stats', 'statusCounts', 'requisitions', 'branches'));
    }

    public function create()
    {
        bpAuthorize('purchases.create');

        return view('purchase::requisitions.create', $this->formData());
    }

    public function store(Request $request)
    {
        bpAuthorize('purchases.create');
        $data = $this->validateData($request);
        $requisition = $this->service->create($data, $data['items']);

        return redirect()->route('requisitions.show', $requisition)
            ->with('success', __('Requisition submitted successfully.'));
    }

    public function show(Requisition $requisition)
    {
        bpAuthorize('purchases.view');
        $requisition->load(['items.product', 'items.variant', 'requester', 'reviewer', 'branch', 'purchase']);

        return view('purchase::requisitions.show', compact('requisition'));
    }

    public function edit(Requisition $requisition)
    {
        bpAuthorize('purchases.edit');
        if (! $requisition->isEditable()) {
            return redirect()->route('requisitions.show', $requisition)->with('error', __('Only pending requisitions can be edited.'));
        }
        $requisition->load(['items.product', 'items.variant']);

        return view('purchase::requisitions.edit', array_merge(['requisition' => $requisition], $this->formData()));
    }

    public function update(Request $request, Requisition $requisition)
    {
        bpAuthorize('purchases.edit');
        $data = $this->validateData($request);
        try {
            $this->service->update($requisition, $data, $data['items']);

            return redirect()->route('requisitions.show', $requisition)->with('success', __('Requisition updated.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Requisition $requisition)
    {
        bpAuthorize('purchases.delete');
        try {
            $this->service->delete($requisition);

            return redirect()->route('requisitions.index')->with('success', __('Requisition deleted.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(Requisition $requisition)
    {
        bpAuthorize('purchases.approve');
        try {
            $this->service->approve($requisition);

            return back()->with('success', __('Requisition approved.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, Requisition $requisition)
    {
        bpAuthorize('purchases.approve');
        $request->validate(['rejection_reason' => 'nullable|string|max:500']);
        try {
            $this->service->reject($requisition, $request->input('rejection_reason'));

            return back()->with('success', __('Requisition rejected.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Requisition $requisition)
    {
        bpAuthorize('purchases.edit');
        try {
            $this->service->cancel($requisition);

            return back()->with('success', __('Requisition cancelled.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function markFulfilled(Requisition $requisition)
    {
        bpAuthorize('purchases.edit');
        try {
            $this->service->markFulfilled($requisition);

            return back()->with('success', __('Requisition marked as fulfilled.'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Convert an approved requisition into a purchase: redirect to the purchase
     * create form, pre-filled with the requisition's items.
     */
    public function convert(Requisition $requisition)
    {
        bpAuthorize('purchases.create');
        if ($requisition->status !== Requisition::STATUS_APPROVED) {
            return back()->with('error', __('Only approved requisitions can be converted to a purchase.'));
        }

        return redirect()->route('purchases.create', ['requisition_id' => $requisition->id]);
    }

    private function formData(): array
    {
        return [
            'products' => Product::active()->orderBy('name')
                ->select('id', 'name', 'sku', 'model', 'barcode', 'product_type')
                ->with(['variants' => fn ($q) => $q->with('attributeValues.attribute')])
                ->get(),
        ];
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'requested_by'      => 'nullable|exists:users,id',
            'department'        => 'nullable|string|max:100',
            'branch_id'         => 'nullable|exists:branches,id',
            'required_date'     => 'nullable|date',
            'priority'          => 'nullable|in:low,normal,high',
            'note'              => 'nullable|string|max:1000',
            'items'             => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.note'       => 'nullable|string|max:255',
        ]);
    }
}
