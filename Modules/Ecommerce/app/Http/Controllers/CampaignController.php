<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Category\Models\Category;
use Modules\Ecommerce\Http\Requests\StoreCampaignRequest;
use Modules\Ecommerce\Models\Campaign;
use Modules\Ecommerce\Services\CampaignService;
use Modules\Product\Models\Product;

class CampaignController extends Controller
{
    public function __construct(
        protected CampaignService $campaigns
    ) {}

    public function index(Request $request): View
    {
        bpAuthorize('ecommerce.view');
        $status = $request->input('status');
        $now = now();

        $campaigns = Campaign::with(['categories:id,name', 'products:id,name'])
            ->when($status === 'active', fn ($q) => $q->where('is_active', true)
                ->where('starts_at', '<=', $now)->where('ends_at', '>=', $now))
            ->when($status === 'scheduled', fn ($q) => $q->where('is_active', true)
                ->where('starts_at', '>', $now))
            ->when($status === 'expired', fn ($q) => $q->where('ends_at', '<', $now))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('starts_at')
            ->paginate(15)
            ->withQueryString();

        return view('ecommerce::campaigns.index', compact('campaigns', 'status'));
    }

    public function create(): View
    {
        bpAuthorize('ecommerce.create');
        $categories = $this->categoriesWithProductCount();
        $products   = Product::with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'sell_price', 'category_id']);

        return view('ecommerce::campaigns.create', compact('categories', 'products'));
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        bpAuthorize('ecommerce.create');
        DB::transaction(function () use ($request) {
            $campaign = Campaign::create($request->toCampaignAttributes());

            if ($request->categoryIds()) {
                $campaign->categories()->sync($request->categoryIds());
            }
            if ($request->productIds()) {
                $campaign->products()->sync($request->productIds());
            }
        });

        $this->campaigns->flushCache();

        return redirect()->route('ecommerce.campaigns.index')
            ->with('success', __('Campaign created.'));
    }

    public function edit(Campaign $campaign): View
    {
        bpAuthorize('ecommerce.edit');
        $campaign->load(['categories:id', 'products:id']);
        $categories = $this->categoriesWithProductCount();
        $products   = Product::with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'sell_price', 'category_id']);

        $selectedCategoryIds = $campaign->categories->pluck('id')->all();
        $selectedProductIds  = $campaign->products->pluck('id')->all();

        return view('ecommerce::campaigns.edit', compact(
            'campaign', 'categories', 'products', 'selectedCategoryIds', 'selectedProductIds'
        ));
    }

    public function update(StoreCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        bpAuthorize('ecommerce.edit');
        DB::transaction(function () use ($request, $campaign) {
            $campaign->update($request->toCampaignAttributes());

            // Sync pivots — scope decides which one (the other clears).
            $campaign->categories()->sync($request->categoryIds());
            $campaign->products()->sync($request->productIds());
        });

        $this->campaigns->flushCache();

        return redirect()->route('ecommerce.campaigns.index')
            ->with('success', __('Campaign updated.'));
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        bpAuthorize('ecommerce.delete');
        $campaign->delete();
        $this->campaigns->flushCache();

        return redirect()->route('ecommerce.campaigns.index')
            ->with('success', __('Campaign deleted.'));
    }

    /**
     * Categories list decorated with a per-category product count, so the
     * picker table can show how many products each scope would cover.
     * The Category model has no products() relation, so we resolve the count
     * with a single grouped query instead of N+1.
     */
    private function categoriesWithProductCount()
    {
        $counts = Product::selectRaw('category_id, COUNT(*) as c')
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->pluck('c', 'category_id');

        return Category::orderBy('name')->get(['id', 'name'])->map(function ($cat) use ($counts) {
            $cat->products_count = (int) ($counts[$cat->id] ?? 0);
            return $cat;
        });
    }
}
