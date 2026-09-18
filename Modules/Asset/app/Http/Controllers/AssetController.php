<?php

namespace Modules\Asset\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Asset\Http\Requests\StoreAssetRequest;
use Modules\Asset\Models\Asset;
use Modules\Asset\Models\AssetCategory;
use Modules\Asset\Models\AssetPayment;
use Modules\Asset\Services\AssetService;
use Modules\Branch\Models\Branch;

class AssetController extends Controller
{
    public function __construct(
        private readonly AssetService $service,
    ) {}

    public function index(Request $request)
    {
        bpAuthorize('finance.view');
        $stats = $this->service->getStats();
        $assets = $this->service->list(
            $request->only(['search', 'status', 'category_id', 'branch_id']),
        );
        $categories = AssetCategory::all();
        $branches = Branch::where('is_active', true)->get();

        return view('asset::index', compact('stats', 'assets', 'categories', 'branches'));
    }

    public function create()
    {
        bpAuthorize('finance.create');
        $categories = AssetCategory::all();
        $branches = Branch::where('is_active', true)->get();
        $paymentAccounts = \Modules\Payment\Models\PaymentAccount::where('is_active', true)->orderBy('name')->get();

        return view('asset::create', compact('categories', 'branches', 'paymentAccounts'));
    }

    public function store(StoreAssetRequest $request)
    {
        bpAuthorize('finance.create');
        $asset = $this->service->create($request->validated());

        return redirect()->route('assets.show', $asset)
            ->with('success', "Asset {$asset->name} ({$asset->asset_code}) created.");
    }

    public function show(Asset $asset)
    {
        bpAuthorize('finance.view');
        $asset = $this->service->find($asset->id);

        return view('asset::show', compact('asset'));
    }

    public function edit(Asset $asset)
    {
        bpAuthorize('finance.edit');
        $asset = $this->service->find($asset->id);
        $categories = AssetCategory::all();
        $branches = Branch::where('is_active', true)->get();
        $paymentAccounts = \Modules\Payment\Models\PaymentAccount::where('is_active', true)->orderBy('name')->get();

        return view('asset::edit', compact('asset', 'categories', 'branches', 'paymentAccounts'));
    }

    public function update(StoreAssetRequest $request, Asset $asset)
    {
        bpAuthorize('finance.edit');
        $this->service->update($asset, $request->validated());

        return redirect()->route('assets.show', $asset)
            ->with('success', "Asset {$asset->name} updated.");
    }

    public function destroy(Asset $asset)
    {
        bpAuthorize('finance.delete');
        $this->service->delete($asset);

        return redirect()->route('assets.index')
            ->with('success', "Asset {$asset->name} deleted.");
    }

    public function maintenance(Request $request, Asset $asset)
    {
        bpAuthorize('finance.edit');
        $validated = $request->validate([
            'maintenance_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
            'cost' => 'nullable|numeric|min:0',
            'performed_by' => 'nullable|string|max:255',
            'next_maintenance_date' => 'nullable|date',
        ]);

        $this->service->recordMaintenance($asset, $validated);

        return back()->with('success', __('Maintenance record added.'));
    }

    public function depreciation(Request $request)
    {
        bpAuthorize('finance.edit');
        $month = $request->input('month', now()->format('Y-m'));
        $count = $this->service->runDepreciation($month);

        return back()->with('success', "Depreciation run completed. {$count} assets processed.");
    }

    /**
     * Record a payment against an asset's outstanding purchase due.
     */
    public function recordPayment(Request $request, Asset $asset)
    {
        bpAuthorize('finance.edit');
        $data = $request->validate([
            'amount'             => 'required|numeric|min:0.01',
            'payment_account_id' => 'required|exists:payment_accounts,id',
            'payment_date'       => 'nullable|date',
            'reference'          => 'nullable|string|max:100',
            'note'               => 'nullable|string|max:500',
        ]);

        try {
            $payment = $this->service->recordPayment($asset, $data);

            return back()->with('success', 'Payment ' . $payment->payment_number . ' of ' . currency_symbol() . ' ' . number_format($payment->amount) . ' recorded.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Printable asset purchase invoice.
     */
    public function invoice(Asset $asset)
    {
        bpAuthorize('finance.view');
        $asset = $this->service->find($asset->id);

        return view('asset::invoice', compact('asset'));
    }

    /**
     * Download asset purchase invoice as PDF.
     */
    public function invoicePdf(Asset $asset)
    {
        bpAuthorize('finance.view');
        $asset = $this->service->find($asset->id);

        $brand = \Modules\Setting\Services\SettingService::printBranding(true);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('asset::invoice', [
            'asset' => $asset,
            'isPdf' => true,
            'brand' => $brand,
        ])->setPaper('a4');

        return $pdf->download("Asset-{$asset->asset_code}.pdf");
    }

    /**
     * Per-asset transaction ledger.
     */
    public function ledger(Asset $asset)
    {
        bpAuthorize('finance.view');
        $asset = $this->service->find($asset->id);
        $entries = $this->service->assetLedger($asset);

        return view('asset::ledger', compact('asset', 'entries'));
    }

    /**
     * Printable receipt for a single asset payment.
     */
    public function paymentReceipt(AssetPayment $payment)
    {
        bpAuthorize('finance.view');
        $payment->load('asset', 'paymentAccount', 'creator');

        return view('asset::payment-receipt', compact('payment'));
    }
}
