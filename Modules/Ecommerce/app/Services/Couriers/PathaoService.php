<?php

namespace Modules\Ecommerce\Services\Couriers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Models\CourierProvider;
use Modules\Ecommerce\Models\EcommerceOrder;

class PathaoService extends BaseCourierService
{
    private const DEFAULT_BASE_URL = 'https://api-hermes.pathao.com';
    private const TOKEN_CACHE_KEY = 'pathao_access_token';
    private const TOKEN_TTL_SECONDS = 604800; // 7 days

    /**
     * Create a parcel with Pathao courier.
     */
    public function createParcel(EcommerceOrder $order, CourierProvider $provider): array
    {
        $token = $this->getAccessToken($provider);

        if (!$token) {
            return $this->failResponse('Failed to authenticate with Pathao API.');
        }

        $baseUrl = $this->getBaseUrl($provider, self::DEFAULT_BASE_URL);
        $parsed = $this->parseAddress($order);

        $payload = [
            'store_id'            => $provider->store_id ?: '0',
            'merchant_order_id'   => $order->order_number,
            'recipient_name'      => $parsed['name'],
            'recipient_phone'     => $parsed['phone'],
            'recipient_address'   => $parsed['address'],
            'recipient_city'      => 2, // Dhaka default
            'recipient_zone'      => 1, // Default zone
            'delivery_type'       => 48, // Normal delivery
            'item_type'           => 2, // Parcel
            'item_quantity'       => max(1, $order->items()->count()),
            'item_weight'         => 0.5,
            'amount_to_collect'   => $this->getCodAmount($order),
            'special_instruction' => $order->notes ?? '',
        ];

        $response = $this->makeRequest('POST', $baseUrl . '/aladdin/api/v1/orders', $payload, [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ]);

        if (!$response['success']) {
            return $this->failResponse('Pathao API request failed.');
        }

        $data = $response['body']['data'] ?? [];
        $consignmentId = (string) ($data['consignment_id'] ?? '');

        if (empty($consignmentId)) {
            $message = $response['body']['message'] ?? 'No consignment ID returned.';
            return $this->failResponse('Pathao: ' . $message);
        }

        $trackingUrl = $baseUrl . '/tracking?consignment_id=' . $consignmentId;

        return $this->successResponse(
            $consignmentId,
            'Parcel created with Pathao successfully.',
            $consignmentId,
            $trackingUrl,
            $data['order_status'] ?? 'created'
        );
    }

    /**
     * Track a parcel via Pathao API.
     */
    public function trackParcel(string $consignmentId, CourierProvider $provider): array
    {
        $token = $this->getAccessToken($provider);

        if (!$token) {
            return $this->trackingFailResponse('Failed to authenticate with Pathao API.');
        }

        $baseUrl = $this->getBaseUrl($provider, self::DEFAULT_BASE_URL);

        $response = $this->makeRequest('GET', $baseUrl . '/aladdin/api/v1/orders/' . $consignmentId, [], [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ]);

        if (!$response['success']) {
            return $this->trackingFailResponse('Pathao tracking request failed.');
        }

        $data = $response['body']['data'] ?? [];
        $status = $data['order_status'] ?? 'unknown';

        return $this->trackingResponse($status, $data);
    }

    /**
     * Cancel a parcel via Pathao.
     * Pathao does not support direct API cancellation.
     */
    public function cancelParcel(string $consignmentId, CourierProvider $provider): array
    {
        return [
            'success' => false,
            'message' => 'Pathao does not support parcel cancellation via API. Please cancel manually from the Pathao merchant panel.',
        ];
    }

    /**
     * Get or refresh the Pathao access token.
     */
    private function getAccessToken(CourierProvider $provider): ?string
    {
        $cacheKey = self::TOKEN_CACHE_KEY . '_' . $provider->id;

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $baseUrl = $this->getBaseUrl($provider, self::DEFAULT_BASE_URL);

        $payload = [
            'client_id'     => $provider->api_key,
            'client_secret' => $provider->api_secret,
            'username'      => config('services.pathao.username', ''),
            'password'      => config('services.pathao.password', ''),
            'grant_type'    => 'password',
        ];

        $response = $this->makeRequest('POST', $baseUrl . '/aladdin/api/v1/issue-token', $payload, [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ]);

        if (!$response['success']) {
            Log::error('Pathao token retrieval failed.', ['response' => $response['body']]);
            return null;
        }

        $token = $response['body']['access_token'] ?? null;

        if ($token) {
            Cache::put($cacheKey, $token, self::TOKEN_TTL_SECONDS);
        }

        return $token;
    }
}
