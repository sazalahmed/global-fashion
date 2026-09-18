<?php

namespace Modules\Ecommerce\Services\Couriers;

use Modules\Ecommerce\Models\CourierProvider;
use Modules\Ecommerce\Models\EcommerceOrder;

class SteadfastService extends BaseCourierService
{
    private const DEFAULT_BASE_URL = 'https://portal.steadfast.com.bd/api/v1';

    /**
     * Create a parcel with Steadfast courier.
     */
    public function createParcel(EcommerceOrder $order, CourierProvider $provider): array
    {
        $baseUrl = $this->getBaseUrl($provider, self::DEFAULT_BASE_URL);
        $parsed = $this->parseAddress($order);

        $payload = [
            'invoice'           => $order->order_number,
            'recipient_name'    => $parsed['name'],
            'recipient_phone'   => $parsed['phone'],
            'recipient_address' => $parsed['address'],
            'cod_amount'        => $this->getCodAmount($order),
            'note'              => $order->notes ?? '',
        ];

        $response = $this->makeRequest('POST', $baseUrl . '/create_order', $payload, $this->getHeaders($provider));

        if (!$response['success']) {
            return $this->failResponse('Steadfast API request failed.');
        }

        $consignment = $response['body']['consignment'] ?? [];
        $consignmentId = (string) ($consignment['consignment_id'] ?? '');
        $trackingCode = $consignment['tracking_code'] ?? $consignmentId;

        if (empty($consignmentId)) {
            $message = $response['body']['message'] ?? 'No consignment ID returned.';
            return $this->failResponse('Steadfast: ' . $message);
        }

        $trackingUrl = 'https://steadfast.com.bd/user/consignment/' . $consignmentId;

        return $this->successResponse(
            $consignmentId,
            'Parcel created with Steadfast successfully.',
            $trackingCode,
            $trackingUrl
        );
    }

    /**
     * Track a parcel via Steadfast API.
     */
    public function trackParcel(string $consignmentId, CourierProvider $provider): array
    {
        $baseUrl = $this->getBaseUrl($provider, self::DEFAULT_BASE_URL);

        $response = $this->makeRequest('GET', $baseUrl . '/status_by_cid/' . $consignmentId, [], $this->getHeaders($provider));

        if (!$response['success']) {
            return $this->trackingFailResponse('Steadfast tracking request failed.');
        }

        $status = $response['body']['delivery_status'] ?? 'unknown';

        return $this->trackingResponse($status, $response['body']);
    }

    /**
     * Cancel a parcel via Steadfast.
     * Steadfast does not support cancellation via API.
     */
    public function cancelParcel(string $consignmentId, CourierProvider $provider): array
    {
        return [
            'success' => false,
            'message' => 'Steadfast does not support parcel cancellation via API. Please cancel manually from the Steadfast portal.',
        ];
    }

    /**
     * Build authentication headers for Steadfast.
     */
    private function getHeaders(CourierProvider $provider): array
    {
        return [
            'Api-Key'      => $provider->api_key,
            'Secret-Key'   => $provider->api_secret,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }
}
