<?php

namespace Modules\Marketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Marketing\Http\Requests\StoreEmailCampaignRequest;
use Modules\Marketing\Http\Requests\StoreSmsCampaignRequest;
use Modules\Marketing\Models\SmsCampaign;
use Modules\Marketing\Services\MarketingService;

class MarketingController extends Controller
{
    public function __construct(
        protected MarketingService $marketingService
    ) {}

    /**
     * Marketing overview dashboard.
     */
    public function index(): View
    {
        bpAuthorize('marketing.view');
        $stats = $this->marketingService->getStats();
        $loyaltyStats = $this->marketingService->getLoyaltyStats();

        return view('marketing::index', compact('stats', 'loyaltyStats'));
    }

    /**
     * Display a listing of SMS campaigns.
     */
    public function smsCampaigns(Request $request): View
    {
        bpAuthorize('marketing.view');
        $campaigns = $this->marketingService->listSmsCampaigns($request->only('status', 'search'));

        return view('marketing::sms-campaigns', compact('campaigns'));
    }

    /**
     * Show the form for creating a new SMS campaign.
     */
    public function smsCampaignCreate(): View
    {
        bpAuthorize('marketing.create');
        return view('marketing::sms-campaign-create');
    }

    /**
     * Store a newly created SMS campaign.
     */
    public function smsCampaignStore(StoreSmsCampaignRequest $request): RedirectResponse
    {
        bpAuthorize('marketing.create');
        $data = $request->validated();

        if ($request->input('action') === 'draft') {
            $data['status'] = 'draft';
        } elseif (!empty($data['scheduled_at'])) {
            $data['status'] = 'scheduled';
        }

        $this->marketingService->createSmsCampaign($data);

        return redirect()->route('marketing.sms-campaigns')
            ->with('success', __('SMS campaign created successfully.'));
    }

    /**
     * Display a single SMS campaign report.
     */
    public function smsCampaignShow(SmsCampaign $campaign): View
    {
        bpAuthorize('marketing.view');
        $campaign->load('creator');

        return view('marketing::sms-campaign-show', compact('campaign'));
    }

    /**
     * Show the form for editing a draft SMS campaign.
     */
    public function smsCampaignEdit(SmsCampaign $campaign): View|RedirectResponse
    {
        bpAuthorize('marketing.edit');
        if ($campaign->status !== 'draft') {
            return redirect()->route('marketing.sms-campaigns')
                ->with('error', __('Only draft campaigns can be edited.'));
        }

        return view('marketing::sms-campaign-edit', compact('campaign'));
    }

    /**
     * Update a draft SMS campaign.
     */
    public function smsCampaignUpdate(StoreSmsCampaignRequest $request, SmsCampaign $campaign): RedirectResponse
    {
        bpAuthorize('marketing.edit');
        if ($campaign->status !== 'draft') {
            return redirect()->route('marketing.sms-campaigns')
                ->with('error', __('Only draft campaigns can be edited.'));
        }

        $this->marketingService->updateSmsCampaign($campaign, $request->validated());

        // "Send" action moves the draft out the door immediately.
        if ($request->input('action') === 'send') {
            $this->marketingService->sendSmsCampaignNow($campaign);

            return redirect()->route('marketing.sms-campaigns')
                ->with('success', __('SMS campaign updated and queued for sending.'));
        }

        return redirect()->route('marketing.sms-campaigns')
            ->with('success', __('SMS campaign updated successfully.'));
    }

    /**
     * Send a campaign immediately.
     */
    public function smsCampaignSend(SmsCampaign $campaign): RedirectResponse
    {
        bpAuthorize('marketing.edit');
        if (in_array($campaign->status, ['sending', 'completed'], true)) {
            return redirect()->route('marketing.sms-campaigns')
                ->with('error', __('This campaign has already been sent.'));
        }

        $this->marketingService->sendSmsCampaignNow($campaign);

        return redirect()->route('marketing.sms-campaigns')
            ->with('success', __('SMS campaign queued for sending.'));
    }

    /**
     * Duplicate a campaign as a new draft.
     */
    public function smsCampaignDuplicate(SmsCampaign $campaign): RedirectResponse
    {
        bpAuthorize('marketing.create');
        $copy = $this->marketingService->duplicateSmsCampaign($campaign);

        return redirect()->route('marketing.sms-campaigns.edit', $copy)
            ->with('success', __('Campaign duplicated as a draft. Review and send when ready.'));
    }

    /**
     * Delete a campaign.
     */
    public function smsCampaignDestroy(SmsCampaign $campaign): RedirectResponse
    {
        bpAuthorize('marketing.delete');
        $this->marketingService->deleteSmsCampaign($campaign);

        return redirect()->route('marketing.sms-campaigns')
            ->with('success', __('SMS campaign deleted.'));
    }

    /**
     * Display a listing of email campaigns.
     */
    public function email(Request $request): View
    {
        bpAuthorize('marketing.view');
        $campaigns = $this->marketingService->listEmailCampaigns($request->only('status', 'search'));

        return view('marketing::email', compact('campaigns'));
    }

    /**
     * Show the form for creating a new email campaign.
     */
    public function emailCreate(): View
    {
        bpAuthorize('marketing.create');
        return view('marketing::email-create');
    }

    /**
     * Store a newly created email campaign.
     */
    public function emailStore(StoreEmailCampaignRequest $request): RedirectResponse
    {
        bpAuthorize('marketing.create');
        $data = $request->validated();

        if ($request->input('action') === 'draft') {
            $data['status'] = 'draft';
        } elseif (!empty($data['scheduled_at'])) {
            $data['status'] = 'scheduled';
        }

        $this->marketingService->createEmailCampaign($data);

        return redirect()->route('marketing.email')
            ->with('success', __('Email campaign created successfully.'));
    }

    /**
     * Display the loyalty program overview.
     */
    public function loyalty(Request $request): View
    {
        bpAuthorize('marketing.view');
        $loyaltyStats = $this->marketingService->getLoyaltyStats();
        $transactions = $this->marketingService->listLoyaltyTransactions($request->only('customer_id', 'type'));

        return view('marketing::loyalty', compact('loyaltyStats', 'transactions'));
    }
}
