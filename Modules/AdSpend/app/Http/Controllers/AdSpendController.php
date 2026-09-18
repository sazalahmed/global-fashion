<?php

namespace Modules\AdSpend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AdSpend\Http\Requests\StoreAdCampaignRequest;
use Modules\AdSpend\Models\AdCampaign;
use Modules\AdSpend\Models\AdPlatform;
use Modules\AdSpend\Services\AdSpendService;
use Modules\Payment\Models\PaymentAccount;

class AdSpendController extends Controller
{
    public function __construct(private readonly AdSpendService $service) {}

    public function index(Request $request)
    {
        bpAuthorize('marketing.view');
        $filters = $request->all();
        $stats = $this->service->getStats($filters);
        $campaigns = $this->service->listCampaigns($filters);
        $platforms = AdPlatform::active()->ordered()->get();
        $spendTrend = $this->service->getSpendTrend(30);
        // All-time so the platform breakdown reconciles with the campaign table.
        $platformBreakdown = $this->service->getSpendByPlatform();

        return view('adspend::index', compact(
            'stats', 'campaigns', 'platforms', 'spendTrend', 'platformBreakdown'
        ));
    }

    public function create()
    {
        bpAuthorize('marketing.create');
        $platforms = AdPlatform::active()->ordered()->get();
        $paymentAccounts = PaymentAccount::where('is_active', true)->orderBy('name')->get();

        return view('adspend::create', compact('platforms', 'paymentAccounts'));
    }

    public function store(StoreAdCampaignRequest $request)
    {
        bpAuthorize('marketing.create');
        $campaign = $this->service->createCampaign($request->validated());

        // Auto-pay if payment account provided and no splits
        $splits = $request->input('splits', []);
        if (!empty($splits)) {
            foreach ($splits as $split) {
                $this->service->recordPayment($campaign, [
                    'amount'             => (float) $split['amount'],
                    'payment_account_id' => $split['payment_account_id'],
                    'payment_date'       => $request->input('spend_date'),
                ]);
                $campaign->refresh();
            }
        } elseif ($request->input('payment_account_id')) {
            $this->service->recordPayment($campaign, [
                'amount'             => (float) $campaign->total_amount,
                'payment_account_id' => $request->input('payment_account_id'),
                'payment_date'       => $request->input('spend_date'),
            ]);
        }

        return redirect()->route('adspend.show', $campaign)
            ->with('success', "Ad spend {$campaign->ad_number} recorded.");
    }

    public function show(AdCampaign $campaign)
    {
        bpAuthorize('marketing.view');
        $campaign = $this->service->findCampaign($campaign->id);
        $paymentAccounts = PaymentAccount::where('is_active', true)->orderBy('name')->get();

        return view('adspend::show', compact('campaign', 'paymentAccounts'));
    }

    public function edit(AdCampaign $campaign)
    {
        bpAuthorize('marketing.edit');
        $platforms = AdPlatform::active()->ordered()->get();
        $paymentAccounts = PaymentAccount::where('is_active', true)->orderBy('name')->get();

        return view('adspend::edit', compact('campaign', 'platforms', 'paymentAccounts'));
    }

    public function update(Request $request, AdCampaign $campaign)
    {
        bpAuthorize('marketing.edit');
        $validated = $request->validate([
            'ad_platform_id'       => 'required|exists:ad_platforms,id',
            'campaign_name'        => 'required|string|max:255',
            'campaign_id_external' => 'nullable|string|max:100',
            'spend_date'           => 'required|date',
            'amount'               => 'required|numeric|min:0.01',
            'tax_amount'           => 'nullable|numeric|min:0',
            'status'               => 'nullable|in:active,paused,completed',
            'impressions'          => 'nullable|integer|min:0',
            'clicks'               => 'nullable|integer|min:0',
            'conversions'          => 'nullable|integer|min:0',
            'reach'                => 'nullable|integer|min:0',
            'target_url'           => 'nullable|url|max:500',
            'notes'                => 'nullable|string',
            'receipt'              => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
        ]);

        $this->service->updateCampaign($campaign, $validated);

        return redirect()->route('adspend.show', $campaign)->with('success', __('Campaign updated.'));
    }

    public function destroy(AdCampaign $campaign)
    {
        bpAuthorize('marketing.delete');
        $this->service->deleteCampaign($campaign);

        return redirect()->route('adspend.index')->with('success', __('Campaign deleted.'));
    }

    public function payment(Request $request, AdCampaign $campaign)
    {
        bpAuthorize('marketing.create');
        $data = $request->validate([
            'amount'             => 'required|numeric|min:0.01',
            'payment_account_id' => 'required_without:splits|nullable|exists:payment_accounts,id',
            'payment_date'       => 'nullable|date',
            'reference'          => 'nullable|string|max:100',
            'splits'             => 'nullable|array|min:1',
            'splits.*.amount'    => 'required_with:splits|numeric|min:0.01',
            'splits.*.payment_account_id' => 'required_with:splits|exists:payment_accounts,id',
        ]);

        $splits = $data['splits'] ?? [];

        if (!empty($splits)) {
            foreach ($splits as $split) {
                $this->service->recordPayment($campaign, [
                    'amount'             => (float) $split['amount'],
                    'payment_account_id' => $split['payment_account_id'],
                    'payment_date'       => $data['payment_date'] ?? now()->toDateString(),
                    'reference'          => $data['reference'] ?? null,
                ]);
                $campaign->refresh();
            }

            return back()->with('success', count($splits) . ' split payments recorded.');
        }

        $this->service->recordPayment($campaign, $data);

        return back()->with('success', 'Payment of ' . currency_symbol() . ' ' . number_format($data['amount']) . ' recorded.');
    }

    public function analytics(Request $request)
    {
        bpAuthorize('marketing.view');
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $platformBreakdown = $this->service->getSpendByPlatform($from, $to);
        $topCampaigns = $this->service->getTopCampaigns(10, $from, $to);
        $spendTrend = $this->service->getSpendTrend(30);
        $periodComparison = $this->service->getPeriodComparison();
        $platforms = AdPlatform::active()->ordered()->get();

        return view('adspend::analytics', compact(
            'platformBreakdown', 'topCampaigns', 'spendTrend', 'periodComparison', 'platforms', 'from', 'to'
        ));
    }
}
