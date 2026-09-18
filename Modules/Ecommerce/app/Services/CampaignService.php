<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\Models\Campaign;
use Modules\Product\Models\Product;

/**
 * Resolves which active campaign (if any) applies to a given product, and
 * computes the discounted price.
 *
 * Rules:
 *   - Most specific scope wins (products > categories > all).
 *   - Within the same scope, the campaign giving the LARGEST discount wins.
 *   - Priority is the tiebreaker when discounts are identical.
 *   - Active campaigns are cached for the request lifecycle to avoid N+1.
 */
class CampaignService
{
    private ?Collection $cache = null;

    /**
     * All campaigns active right now, with their pivot relations loaded.
     * Cached per request.
     */
    public function activeCampaigns(): Collection
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        return $this->cache = Campaign::active()
            ->with(['categories:id', 'products:id'])
            ->orderByDesc('priority')
            ->get();
    }

    /**
     * Wipe the cached active-campaigns collection. Called after any admin
     * mutation that could change the set of active campaigns.
     */
    public function flushCache(): void
    {
        $this->cache = null;
    }

    /**
     * The single campaign that wins for the given product, or null if none
     * applies. Caller passes the base price (usually $product->sell_price)
     * so we can compare same-scope campaigns by absolute discount value.
     */
    public function bestFor(Product $product, float $basePrice): ?Campaign
    {
        $campaigns = $this->activeCampaigns();
        if ($campaigns->isEmpty()) {
            return null;
        }

        $categoryId = $product->category_id;
        $productId  = $product->id;

        $matches = $campaigns->filter(function (Campaign $c) use ($productId, $categoryId) {
            if ($c->scope === 'all') {
                return true;
            }
            if ($c->scope === 'categories') {
                return $categoryId !== null
                    && $c->categories->pluck('id')->contains($categoryId);
            }
            if ($c->scope === 'products') {
                return $c->products->pluck('id')->contains($productId);
            }
            return false;
        });

        if ($matches->isEmpty()) {
            return null;
        }

        // Most specific scope wins. Within the winning scope, largest
        // discount amount wins; priority breaks ties.
        $bestRank = $matches->max(fn (Campaign $c) => $c->scopeRank());
        $candidates = $matches->filter(fn (Campaign $c) => $c->scopeRank() === $bestRank);

        return $candidates->sortByDesc(function (Campaign $c) use ($basePrice) {
            return [$c->discountAmountFor($basePrice), $c->priority];
        })->first();
    }

    /**
     * Decorate a collection/array of products with `campaign`,
     * `campaign_price` and `campaign_discount_percentage` attributes so
     * views can render without re-resolving.
     *
     * Accepts a Collection or any iterable; mutates each product in place.
     */
    public function decorate($products): void
    {
        if ($products === null) {
            return;
        }

        foreach ($products as $product) {
            if (!$product instanceof Product) {
                continue;
            }
            $basePrice = (float) $product->sell_price;
            $campaign = $this->bestFor($product, $basePrice);
            if (!$campaign) {
                $product->campaign = null;
                $product->campaign_price = $basePrice;
                $product->campaign_discount_percentage = 0;
                continue;
            }
            $product->campaign = $campaign;
            $product->campaign_price = $campaign->applyTo($basePrice);
            $product->campaign_discount_percentage = $campaign->percentageFor($basePrice);
        }
    }
}
