<?php

namespace Modules\AdSpend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\AdSpend\Services\MetaAdsService;

class MetaAdsController extends Controller
{
    public function __construct(protected MetaAdsService $service) {}

    public function settings(): View
    {
        bpAuthorize('marketing.view');
        $config = $this->service->getConfig();

        return view('adspend::meta-settings', compact('config'));
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        bpAuthorize('marketing.edit');
        $request->validate([
            'app_id'        => ['nullable', 'string', 'max:255'],
            'app_secret'    => ['nullable', 'string', 'max:255'],
            'access_token'  => ['nullable', 'string', 'max:1024'],
            'ad_account_id' => ['nullable', 'string', 'max:64'],
        ]);

        $this->service->saveConfig($request->only(['app_id', 'app_secret', 'access_token', 'ad_account_id']));

        return back()->with('success', __('Meta Ads credentials saved.'));
    }

    public function testConnection(): JsonResponse
    {
        bpAuthorize('marketing.edit');
        return response()->json($this->service->testConnection());
    }

    public function sync(): RedirectResponse
    {
        bpAuthorize('marketing.edit');
        $result = $this->service->syncAll();

        $key = $result['success'] ? 'success' : 'error';
        return back()->with($key, $result['message']);
    }
}
