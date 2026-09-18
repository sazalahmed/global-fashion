<?php

namespace Modules\SaleReturn\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SaleReturn\Http\Requests\StoreSaleReturnRequest;
use Modules\SaleReturn\Models\SaleReturn;
use Modules\SaleReturn\Services\SaleReturnService;
use Modules\Sale\Models\Sale;
use Modules\Customer\Models\Customer;
use Modules\Branch\Models\Branch;

class SaleReturnController extends Controller
{
    public function __construct(
        protected SaleReturnService $saleReturnService
    ) {}

    /**
     * Display a listing of sale returns.
     */
    public function index(Request $request)
    {
        bpAuthorize('sales.view');
        $stats = $this->saleReturnService->getStats();
        $returns = $this->saleReturnService->list($request->all());

        return view('salereturn::index', compact('stats', 'returns'));
    }

    /**
     * Show the form for creating a new sale return.
     */
    public function create(Request $request)
    {
        bpAuthorize('sales.create');
        $sales = Sale::with('customer')->latest()->get(['id', 'invoice_number', 'customer_id', 'customer_name_snapshot', 'grand_total']);
        $customers = Customer::all(['id', 'name', 'phone']);
        $branches = Branch::all();
        $selectedSaleId = $request->query('sale_id');

        return view('salereturn::create', compact('sales', 'customers', 'branches', 'selectedSaleId'));
    }

    /**
     * Store a newly created sale return in storage.
     */
    public function store(StoreSaleReturnRequest $request)
    {
        bpAuthorize('sales.create');
        $validated = $request->validated();

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $return = $this->saleReturnService->create($validated, $items);

        $courierMessage = $this->dispatchSteadfastReturn($return);

        $msg = __('Sale return created successfully.');
        if ($courierMessage) {
            $msg .= ' ' . $courierMessage;
        }

        return redirect()->route('sale-returns.index')->with('success', $msg);
    }

    /**
     * If the parent sale shipped via Steadfast with a known consignment id,
     * file a return-pickup request with Steadfast and persist the return id.
     * Returns a short user-facing status string, or null if no action taken.
     */
    private function dispatchSteadfastReturn(SaleReturn $return): ?string
    {
        $sale = $return->sale ?? Sale::find($return->sale_id);
        if (!$sale) {
            return null;
        }
        if (strtolower((string) $sale->courier_name) !== 'steadfast' || empty($sale->courier_consignment_id)) {
            return null;
        }

        $steadfast = app(\Modules\Ecommerce\Services\SteadfastApiService::class);
        if (!$steadfast->isConfigured()) {
            return null;
        }

        try {
            $response = $steadfast->client()->return()->createReturnRequest([
                'consignment_id' => (int) $sale->courier_consignment_id,
                'reason'         => $return->reason ?: 'Customer requested return',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Steadfast createReturnRequest failed', [
                'sale_return_id' => $return->id,
                'sale_id'        => $sale->id,
                'consignment_id' => $sale->courier_consignment_id,
                'error'          => $e->getMessage(),
            ]);
            return __('Steadfast return pickup not filed: :err', ['err' => $e->getMessage()]);
        }

        $ret = $response['return_request'] ?? $response['data'] ?? $response;
        $returnId = $ret['id'] ?? null;
        $returnStatus = $ret['status'] ?? 'pending';

        $return->update([
            'courier_return_id'     => $returnId ? (string) $returnId : null,
            'courier_return_status' => $returnStatus,
        ]);

        return __('Steadfast pickup request filed (#:id).', ['id' => $returnId ?: '?']);
    }

    /**
     * Display the specified sale return.
     */
    public function show(SaleReturn $saleReturn)
    {
        bpAuthorize('sales.view');
        $return = $this->saleReturnService->find($saleReturn->id);

        return view('salereturn::show', compact('return'));
    }

    /**
     * Show the form for editing the specified sale return.
     */
    public function edit(SaleReturn $saleReturn)
    {
        bpAuthorize('sales.edit');
        $return = $this->saleReturnService->find($saleReturn->id);
        $sales = Sale::with('customer')->latest()->get(['id', 'invoice_number', 'customer_id', 'customer_name_snapshot', 'grand_total']);

        return view('salereturn::edit', compact('return', 'sales'));
    }

    /**
     * Update the specified sale return.
     */
    public function update(StoreSaleReturnRequest $request, SaleReturn $saleReturn)
    {
        bpAuthorize('sales.edit');
        if (!$saleReturn->isEditable()) {
            return redirect()->route('sale-returns.show', $saleReturn)
                ->with('error', __('This return can no longer be edited.'));
        }

        $validated = $request->validated();
        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $this->saleReturnService->update($saleReturn, $validated, $items);

        return redirect()->route('sale-returns.show', $saleReturn)
            ->with('success', __('Sale return updated successfully.'));
    }

    /**
     * Remove the specified sale return (only if draft).
     */
    public function destroy(SaleReturn $saleReturn)
    {
        bpAuthorize('sales.delete');
        if (!$saleReturn->isEditable()) {
            return redirect()->route('sale-returns.index')
                ->with('error', __('Only draft/pending returns can be deleted.'));
        }

        $saleReturn->items()->delete();
        $saleReturn->delete();

        return redirect()->route('sale-returns.index')
            ->with('success', __('Sale return deleted successfully.'));
    }

    /**
     * Approve a draft sale return.
     */
    public function approve(SaleReturn $saleReturn)
    {
        bpAuthorize('sales.edit');
        try {
            $this->saleReturnService->approve($saleReturn);

            return redirect()->route('sale-returns.show', $saleReturn)
                ->with('success', __('Sale return approved successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Complete an approved sale return.
     */
    public function complete(SaleReturn $saleReturn)
    {
        bpAuthorize('sales.edit');
        try {
            $this->saleReturnService->complete($saleReturn);

            return redirect()->route('sale-returns.show', $saleReturn)
                ->with('success', __('Sale return completed successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel a draft or approved sale return.
     */
    public function cancel(SaleReturn $saleReturn)
    {
        bpAuthorize('sales.edit');
        try {
            $this->saleReturnService->cancel($saleReturn);

            return redirect()->route('sale-returns.show', $saleReturn)
                ->with('success', __('Sale return cancelled successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Print view for a sale return.
     */
    public function print(SaleReturn $saleReturn)
    {
        bpAuthorize('sales.view');
        $return = $this->saleReturnService->find($saleReturn->id);

        return view('salereturn::print', compact('return'));
    }

    /**
     * Download sale return as PDF.
     */
    public function pdf(SaleReturn $saleReturn)
    {
        bpAuthorize('sales.view');
        $return = $this->saleReturnService->find($saleReturn->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('salereturn::print', [
            'return' => $return,
            'isPdf' => true,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("SaleReturn-{$return->return_number}.pdf");
    }

    /**
     * Get sale items for AJAX (used when selecting a sale in the create form).
     */
    public function saleItems(int $saleId)
    {
        bpAuthorize('sales.view');
        $items = $this->saleReturnService->getSaleItems($saleId);

        $mapped = $items->map(function ($item) {
            return [
                'sale_item_id' => $item->id,
                'product_id'   => $item->product_id,
                'variant_id'   => $item->variant_id,
                'product_name' => $item->product->name ?? 'Unknown',
                'variant_name' => $item->variant ? $item->variant->variant_name : null,
                'sku'          => $item->variant->sku ?? $item->product->sku ?? $item->product_sku ?? '',
                'quantity'     => (float) $item->quantity,
                'unit_price'   => (float) $item->unit_price,
                'tax_amount'   => (float) $item->tax_amount,
            ];
        });

        return response()->json(['success' => true, 'data' => $mapped]);
    }
}
