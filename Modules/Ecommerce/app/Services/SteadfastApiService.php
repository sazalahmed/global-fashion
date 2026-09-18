<?php

namespace Modules\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Models\CourierProvider;
use Nayemuf\SteadfastCourier\SteadfastCourier;

/**
 * Thin wrapper around nayemuf/steadfast-courier that pulls api_key +
 * api_secret from the configured Steadfast CourierProvider row.
 *
 * Throws RuntimeException if Steadfast isn't active + has no credentials.
 */
class SteadfastApiService
{
    private const DEFAULT_BASE_URL = 'https://portal.packzy.com/api/v1';
    private const PAYMENTS_CACHE_KEY = 'steadfast_all_payments';
    private const PAYMENTS_CACHE_TTL = 3600;
    private const PAYMENTS_PER_PAGE = 10;
    private const MAX_PAYMENT_PAGES = 100;

    private ?SteadfastCourier $client = null;
    private ?CourierProvider $provider = null;

    public function isConfigured(): bool
    {
        $provider = $this->loadProvider();
        if ($provider === null || !$provider->is_active) {
            return false;
        }

        // api_key / api_secret use the "encrypted" cast, so reading them
        // decrypts with the current APP_KEY. If the key changed after the
        // credentials were saved, decryption throws "The payload is invalid."
        // Treat that as "not configured" (and log a clear hint) instead of
        // bubbling a 500 up to the sale page or spamming dashboard warnings.
        try {
            return !empty($provider->api_key) && !empty($provider->api_secret);
        } catch (DecryptException $e) {
            Log::warning('Steadfast credentials could not be decrypted — re-save the API key & secret in Settings → Courier Providers (this usually means APP_KEY changed after they were saved).', [
                'provider' => $provider->slug,
            ]);
            return false;
        }
    }

    public function client(): SteadfastCourier
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $provider = $this->loadProvider();
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Steadfast courier is not active or missing API credentials. Configure it in Settings → Courier Providers.');
        }

        $this->client = new SteadfastCourier(
            apiKey:    (string) $provider->api_key,
            secretKey: (string) $provider->api_secret,
            baseUrl:   !empty($provider->base_url) ? (string) $provider->base_url : null,
        );

        return $this->client;
    }

    /**
     * Every settlement Steadfast holds, newest first.
     *
     * The vendor client's getPayments() requests /payments with no query
     * string, and Steadfast serves a fixed 10 rows per page oldest-first — so
     * that call returns the *oldest* ten and hides everything since. Its
     * request() helper is protected and sends data as a JSON body, so the page
     * number cannot be passed through it; the pages are walked directly here.
     *
     * Rows dated before the configured business start date are dropped: the
     * courier account can predate the business's own records.
     */
    public function allPayments(): array
    {
        return Cache::remember(self::PAYMENTS_CACHE_KEY, self::PAYMENTS_CACHE_TTL, function () {
            $provider = $this->provider();
            $base = rtrim($provider->base_url ?: self::DEFAULT_BASE_URL, '/');
            $headers = [
                'Api-Key'    => (string) $provider->api_key,
                'Secret-Key' => (string) $provider->api_secret,
            ];

            $rows = [];

            for ($page = 1; $page <= self::MAX_PAYMENT_PAGES; $page++) {
                $response = Http::withHeaders($headers)->timeout(20)
                    ->get($base . '/payments', ['page' => $page]);

                if (! $response->successful()) {
                    break;
                }

                $batch = $response->json('payments');

                if (! is_array($batch) || $batch === []) {
                    break;
                }

                $rows = array_merge($rows, $batch);

                // A short page is the last one — Steadfast exposes no total.
                if (count($batch) < self::PAYMENTS_PER_PAGE) {
                    break;
                }
            }

            $startDate = business_start_date();

            if ($startDate) {
                $rows = array_values(array_filter($rows, static function ($row) use ($startDate) {
                    $date = $row['created_at'] ?? $row['paid_at'] ?? null;

                    // Keep undated rows rather than silently discarding money.
                    return blank($date) || Carbon::parse($date)->gte($startDate);
                }));
            }

            usort($rows, static fn ($a, $b) => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

            return $rows;
        });
    }

    /**
     * One settlement with its consignments, each carrying the delivery charge
     * the courier billed us.
     *
     * Steadfast returns no per-consignment delivery charge — only a single
     * charges figure for the whole settlement — but the amount is recorded
     * against the sale when the consignment is created, so it is joined back
     * on by consignment id.
     */
    public function paymentWithLocalCharges(int $paymentId): array
    {
        $response = $this->client()->payment()->getPayment($paymentId);

        // Unwrap Steadfast's envelope so callers get the settlement itself.
        $payment = $response['payment'] ?? $response;
        $consignments = $payment['consignments'] ?? null;

        if (! is_array($consignments) || $consignments === []) {
            return $payment;
        }

        $charges = DB::table('sales')
            ->whereIn('courier_consignment_id', array_filter(array_column($consignments, 'consignment_id')))
            ->pluck('courier_delivery_charge', 'courier_consignment_id');

        $payment['consignments'] = array_map(static function (array $consignment) use ($charges) {
            $id = $consignment['consignment_id'] ?? null;
            $charge = $id !== null ? $charges->get($id) : null;

            // null, not 0 — a consignment we hold no record for is unknown
            // rather than free, and the view says so.
            $consignment['delivery_charge'] = $charge !== null ? (float) $charge : null;

            return $consignment;
        }, $consignments);

        return $payment;
    }

    /**
     * Drop the cached settlement list, so a newly recorded withdrawal or a
     * changed business start date is reflected without waiting out the TTL.
     */
    public function forgetPaymentsCache(): void
    {
        Cache::forget(self::PAYMENTS_CACHE_KEY);
    }

    public function provider(): CourierProvider
    {
        $provider = $this->loadProvider();
        if (!$provider) {
            throw new \RuntimeException('Steadfast courier provider row not found. Run the courier_providers seeder.');
        }
        return $provider;
    }

    private function loadProvider(): ?CourierProvider
    {
        if ($this->provider === null) {
            $this->provider = CourierProvider::query()
                ->where('slug', 'steadfast')
                ->first();
        }
        return $this->provider;
    }
}
