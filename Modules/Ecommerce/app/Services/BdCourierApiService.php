<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client for the BD Courier hosted fraud-check API
 * (https://api.bdcourier.com/courier-check).
 *
 * Returns a unified shape compatible with the legacy scraper:
 *   [
 *     'aggregate'    => ['total_deliveries', 'success_count', 'cancelled_count', 'success_ratio'],
 *     'courier_data' => ['pathao' => [...], ...],   // per-courier rows
 *     'reports'      => [...],                       // merchant fraud reports
 *     'source'       => 'bdcourier',
 *   ]
 */
class BdCourierApiService
{
    public function isConfigured(): bool
    {
        return (bool) config('services.bd_courier.api_key');
    }

    public function check(string $phone): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $baseUrl = rtrim((string) config('services.bd_courier.base_url'), '/');
        $timeout = (int) config('services.bd_courier.timeout', 15);

        try {
            $response = Http::withToken(config('services.bd_courier.api_key'))
                ->acceptJson()
                ->timeout($timeout)
                ->post($baseUrl . '/courier-check', ['phone' => $phone]);
        } catch (\Throwable $e) {
            Log::warning('BD Courier API call failed', ['error' => $e->getMessage(), 'phone' => $phone]);
            return null;
        }

        $body = $response->json();

        // "Phone not found" is a successful answer (just no history) — return an
        // empty-but-valid record so the caller can cache it.
        if (
            is_array($body)
            && ($body['status'] ?? null) === 'error'
            && stripos((string) ($body['message'] ?? ''), 'not found') !== false
        ) {
            return [
                'aggregate'    => [
                    'total_deliveries' => 0,
                    'success_count'    => 0,
                    'cancelled_count'  => 0,
                    'success_ratio'    => 0,
                ],
                'courier_data' => [],
                'reports'      => [],
                'source'       => 'bdcourier',
                'not_found'    => true,
            ];
        }

        if (!$response->successful()) {
            Log::info('BD Courier API non-2xx', [
                'status' => $response->status(),
                'body'   => $body ?? $response->body(),
                'phone'  => $phone,
            ]);
            return null;
        }

        if (!is_array($body) || ($body['status'] ?? null) !== 'success') {
            return null;
        }

        $data = $body['data'] ?? [];
        $summary = $data['summary'] ?? [];

        $courierData = [];
        foreach ($data as $slug => $row) {
            if ($slug === 'summary' || !is_array($row)) {
                continue;
            }
            $courierData[$slug] = [
                'name'             => $row['name'] ?? ucfirst($slug),
                'logo'             => $row['logo'] ?? null,
                'total_parcel'     => (int) ($row['total_parcel'] ?? 0),
                'success_parcel'   => (int) ($row['success_parcel'] ?? 0),
                'cancelled_parcel' => (int) ($row['cancelled_parcel'] ?? 0),
                'success_ratio'    => (float) ($row['success_ratio'] ?? 0),
            ];
        }

        return [
            'aggregate' => [
                'total_deliveries' => (int) ($summary['total_parcel'] ?? 0),
                'success_count'    => (int) ($summary['success_parcel'] ?? 0),
                'cancelled_count'  => (int) ($summary['cancelled_parcel'] ?? 0),
                'success_ratio'    => (float) ($summary['success_ratio'] ?? 0),
            ],
            'courier_data' => $courierData,
            'reports'      => $body['reports'] ?? [],
            'source'       => 'bdcourier',
            'not_found'    => false,
        ];
    }
}
