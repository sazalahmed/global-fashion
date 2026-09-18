<?php

namespace Modules\AdSpend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AdSpend\Models\AdPlatform;
use Modules\AdSpend\Services\AdSpendService;

class AdPlatformController extends Controller
{
    public function __construct(private readonly AdSpendService $service) {}

    public function index()
    {
        bpAuthorize('marketing.view');
        $platforms = $this->service->listPlatforms();

        return view('adspend::platforms', compact('platforms'));
    }

    public function store(Request $request)
    {
        bpAuthorize('marketing.create');
        $validated = $request->validate([
            'name'       => 'required|string|max:100|unique:ad_platforms,name',
            'icon'       => 'nullable|string|max:50',
            'color'      => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = true;
        $this->service->createPlatform($validated);

        return back()->with('success', __('Platform added.'));
    }

    public function update(Request $request, AdPlatform $platform)
    {
        bpAuthorize('marketing.edit');
        $validated = $request->validate([
            'name'       => 'required|string|max:100|unique:ad_platforms,name,' . $platform->id,
            'icon'       => 'nullable|string|max:50',
            'color'      => 'nullable|string|max:7',
            'is_active'  => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $this->service->updatePlatform($platform, $validated);

        return back()->with('success', __('Platform updated.'));
    }

    public function toggleStatus(AdPlatform $platform): \Illuminate\Http\JsonResponse
    {
        bpAuthorize('marketing.edit');
        $platform->update(['is_active' => ! $platform->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $platform->is_active,
            'message'   => __('Status updated.'),
        ]);
    }

    public function destroy(AdPlatform $platform)
    {
        bpAuthorize('marketing.delete');
        try {
            $this->service->deletePlatform($platform);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Platform deleted.'));
    }
}
