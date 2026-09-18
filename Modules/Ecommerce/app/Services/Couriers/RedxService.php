<?php

namespace Modules\Ecommerce\Services\Couriers;

use Modules\Ecommerce\Models\CourierProvider;
use Modules\Ecommerce\Models\EcommerceOrder;

class RedxService extends BaseCourierService
{
    private const DEFAULT_BASE_URL = 'https://openapi.redx.com.bd/v1.0.0-beta';

    /**
     * Create a parcel with RedX courier.
     */
    public function createParcel(EcommerceOrder $order, CourierProvider $provider): array
    {
        $baseUrl = $this->getBaseUrl($provider, self::DEFAULT_BASE_URL);
        $parsed = $this->parseAddress($order);

        $payload = [
            'customer_name'          => $parsed['name'],
            'customer_phone'         => $parsed['phone'],
            'delivery_area'          => $parsed['city'] ?: 'Dhaka',
            'delivery_area_id'       => 1,
            'customer_address'       => $parsed['address'],
            'merchant_invoice_id'    => $order->order_number,
            'cash_collection_amount' => $this->getCodAmount($order),
            'parcel_weight'          => 500, // grams
            'instruction'            => $order->notes ?? '',
            'value'                  => (float) $order->grand_total,
        ];

        $response = $this->makeRequest('POST', $baseUrl . '/parcel', $payload, $this->getHeaders($provider));

        if (!$response['success']) {
            return $this->failResponse('RedX API request failed.');
        }

        $trackingId = (string) ($response['body']['tracking_id'] ?? '');

        if (empty($trackingId)) {
            $message = $response['body']['message'] ?? 'No tracking ID returned.';
            return $this->failResponse('RedX: ' . $message);
        }

        $trackingUrl = 'https://redx.com.bd/track-parcel/?trackingId=' . $trackingId;

        return $this->successResponse(
            $trackingId,
            'Parcel created with RedX successfully.',
            $trackingId,
            $trackingUrl
        );
    }

    /**
     * Track a parcel via RedX API.
     */
    public function trackParcel(string $consignmentId, CourierProvider $provider): array
    {
        $baseUrl = $this->getBaseUrl($provider, self::DEFAULT_BASE_URL);

        $response = $this->makeRequest('GET', $baseUrl . '/parcel/track/' . $consignmentId, [], $this->getHeaders($provider));

        if (!$response['success']) {
            return $this->trackingFailResponse('RedX tracking request failed.');
        }

        $status = $response['body']['current_status'] ?? ($response['body']['status'] ?? 'unknown');
        $details = $response['body']['tracking'] ?? $response['body'];

        return $this->trackingResponse($status, is_array($details) ? $details : []);
    }

    /**
     * Cancel a parcel via RedX API.
     */
    public function cancelParcel(string $consignmentId, CourierProvider $provider): array
    {
        $baseUrl = $this->getBaseUrl($provider, self::DEFAULT_BASE_URL);

        $response = $this->makeRequest('PATCH', $baseUrl . '/parcel/cancel/' . $consignmentId, [], $this->getHeaders($provider));

        if ($response['success']) {
            return [
                'success' => true,
                'message' => 'Parcel cancelled with RedX successfully.',
            ];
        }

        return [
            'success' => false,
            'message' => 'RedX cancellation failed: ' . ($response['body']['message'] ?? 'Unknown error.'),
        ];
    }

    /**
     * Build authentication headers for RedX.
     */
    private function getHeaders(CourierProvider $provider): array
    {
        return [
            'API-ACCESS-TOKEN' => $provider->api_key,
            'Content-Type'     => 'application/json',
            'Accept'           => 'application/json',
        ];
    }
}
