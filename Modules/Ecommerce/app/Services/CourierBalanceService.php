<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Live COD balance held by the couriers (money they collected and still owe
 * the business), read from each active courier's balance API. Cached for an
 * hour so dashboards and reports don't hit the courier APIs on every load.
 * Canonical home of the figure — the dashboard and the cashflow page both
 * read through this service.
 */
class CourierBalanceService
{
    /** Kept identical to the old DashboardService key so caches carry over. */
    public const CACHE_KEY = 'dashboard.courier_receivable';
    private const CACHE_TTL = 3600; // seconds (1 hour)

    /**
     * Cached total balance across all integrated couriers. Only successful
     * lookups are cached, so a transient courier-API outage isn't frozen in.
     */
    public function get(): float
    {
        $cached = Cache::get(self::CACHE_KEY);
        if ($cached !== null) {
            return (float) $cached;
        }

        $value = $this->fetch();
        if ($value !== null) {
            Cache::put(self::CACHE_KEY, $value, self::CACHE_TTL);
            return $value;
        }

        return 0.0; // lookup failed and nothing cached yet
    }

    /** Bust the cache — e.g. right after recording a withdrawal. */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** True when at least one courier balance API is configured. */
    public function isConfigured(): bool
    {
        return app(SteadfastApiService::class)->isConfigured();
    }

    /**
     * Sum the current account balance across all active couriers that expose
     * a balance API. Steadfast is currently the only integrated provider; add
     * more here as their APIs are wired up. Returns null if the lookup throws,
     * so the caller can avoid caching a failure.
     */
    private function fetch(): ?float
    {
        try {
            $total = 0.0;

            $steadfast = app(SteadfastApiService::class);
            if ($steadfast->isConfigured()) {
                $balance = $steadfast->client()->balance()->getCurrentBalance();
                $total += (float) ($balance['current_balance'] ?? 0);
            }

            return $total;
        } catch (\Throwable $e) {
            Log::warning('Courier balance lookup failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
