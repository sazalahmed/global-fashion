<?php

namespace Modules\Ecommerce\Services\Couriers;

use Modules\Ecommerce\Models\CourierProvider;
use Modules\Ecommerce\Models\EcommerceOrder;

class EcourierService extends BaseCourierService
{
    private const DEFAULT_BASE_URL = 'https://backoffice.ecourier.com.bd/api';

    /**
     * Create a parcel with eCourier.
     */
    public function createParcel(EcommerceOrder $order, CourierProvider $provider): array
    {
        $baseUrl = $this->getBaseUrl($provider, self::DEFAULT_BASE_URL);
        $parsed = $this->parseAddress($order);

        $payload = [
            'recipient_name'    => $parsed['name'],
            'recipient_mobile'  => $parsed['phone'],
            'recipient_city'    => $parsed['city'] ?: 'Dhaka',
            'recipient_thana'   => $parsed['area'],
            'recipient_area'    => $parsed['area'],
            'recipient_address' => $parsed['address'],
            'package_code'      => '#' . $order->order_number,
            'product_price'     => (float) $order->grand_total,
            'payment_method'    => $this->isCod($order) ? 'COD' : 'PREPAID',
            'number_of_item'    => max(1, $order->items()->count()),
            'comments'          => $order->notes ?? '',
        ];

        $response = $this->makeRequest('POST', $baseUrl . '/order-place', $payload, $this->getHeaders($provider));

        if (!$response['success']) {
            return $this->failResponse('eCourier API request failed.');
        }

        $body = $response['body'];
        $ecrId = (string) ($body['ID'] ?? $body['id'] ?? '');
        $isSuccess = ($body['success'] ?? false) || !empty($ecrId);

        if (!$isSuccess || empty($ecrId)) {
            $message = $body['message'] ?? 'No ECR tracking ID returned.';
            return $this->failResponse('eCourier: ' . $message);
        }

        $trackingUrl = 'https://ecourier.com.bd/track/?ecr=' . $ecrId;

        return $this->successResponse(
            $ecrId,
            'Parcel created with eCourier successfully.',
            $ecrId,
            $trackingUrl
        );
    }

    /**
     * Track a parcel via eCourier API.
     */
    public function trackParcel(string $consignmentId, CourierProvider $provider): array
    {
        $baseUrl = $this->getBaseUrl($provider, self::DEFAULT_BASE_URL);

        $response = $this->makeRequest('POST', $baseUrl . '/order-tracking', [
            'ecr' => $consignmentId,
        ], $this->getHeaders($provider));

        if (!$response['success']) {
            return $this->trackingFailResponse('eCourier tracking request failed.');
        }

        $body = $response['body'];
        $status = $body['status'] ?? ($body['order_status'] ?? 'unknown');
        $details = $body['tracking_history'] ?? $body;

        return $this->trackingResponse($status, is_array($details) ? $details : []);
    }

    /**
     * Cancel a parcel via eCourier.
     * eCourier does not support cancellation via API.
     */
    public function cancelParcel(string $consignmentId, CourierProvider $provider): array
    {
        return [
            'success' => false,
            'message' => 'eCourier does not support parcel cancellation via API. Please cancel manually from the eCourier panel.',
        ];
    }

    /**
     * Build authentication headers for eCourier.
     */
    private function getHeaders(CourierProvider $provider): array
    {
        return [
            'API-KEY'      => $provider->api_key,
            'API-SECRET'   => $provider->api_secret,
            'USER-ID'      => $provider->store_id ?? '',
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }
}
