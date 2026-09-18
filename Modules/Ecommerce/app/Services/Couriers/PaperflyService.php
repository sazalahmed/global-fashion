<?php

namespace Modules\Ecommerce\Services\Couriers;

use Modules\Ecommerce\Models\CourierProvider;
use Modules\Ecommerce\Models\EcommerceOrder;

class PaperflyService extends BaseCourierService
{
    private const DEFAULT_BASE_URL = 'https://go-app.paperfly.com.bd/merchant/api';

    /**
     * Create a parcel with Paperfly courier.
     */
    public function createParcel(EcommerceOrder $order, CourierProvider $provider): array
    {
        $baseUrl = $this->getBaseUrl($provider, self::DEFAULT_BASE_URL);
        $parsed = $this->parseAddress($order);

        $payload = [
            'merOrderRef'        => $order->order_number,
            'pickMerchantName'   => config('app.name', 'BizPOS Pro'),
            'pickMerchantAddress' => config('services.paperfly.pickup_address', ''),
            'pickMerchantThana'  => config('services.paperfly.pickup_thana', ''),
            'pickMerchantDistrict' => config('services.paperfly.pickup_district', ''),
            'custname'           => $parsed['name'],
            'custphone'          => $parsed['phone'],
            'custaddress'        => $parsed['address'],
            'custCity'           => $parsed['city'] ?: 'Dhaka',
            'custThana'          => $parsed['area'],
            'custDistrict'       => $parsed['city'] ?: 'Dhaka',
            'packageWeight'      => '0.5',
            'productSizeWeight'  => 'standard',
            'productBrief'       => 'E-commerce order #' . $order->order_number,
            'max_weight'         => '0.5',
            'quantity'           => (string) max(1, $order->items()->count()),
            'CODAmount'          => (string) $this->getCodAmount($order),
            'deliveryCharge'     => (string) ((float) $order->shipping_charge),
        ];

        $response = $this->makeRequest('POST', $baseUrl . '/react-order/order-place', $payload, $this->getHeaders($provider));

        if (!$response['success']) {
            return $this->failResponse('Paperfly API request failed.');
        }

        $body = $response['body'];
        $orderId = (string) ($body['orderID'] ?? $body['order_id'] ?? '');
        $isSuccess = ($body['success'] ?? false) || !empty($orderId);

        if (!$isSuccess || empty($orderId)) {
            $message = $body['message'] ?? 'No order ID returned.';
            return $this->failResponse('Paperfly: ' . $message);
        }

        return $this->successResponse(
            $orderId,
            'Parcel created with Paperfly successfully.',
            $orderId,
            null
        );
    }

    /**
     * Track a parcel via Paperfly API.
     */
    public function trackParcel(string $consignmentId, CourierProvider $provider): array
    {
        $baseUrl = $this->getBaseUrl($provider, self::DEFAULT_BASE_URL);

        $response = $this->makeRequest('GET', $baseUrl . '/get-order-information', [
            'merOrderRef' => $consignmentId,
        ], $this->getHeaders($provider));

        if (!$response['success']) {
            return $this->trackingFailResponse('Paperfly tracking request failed.');
        }

        $body = $response['body'];
        $status = $body['orderStatus'] ?? ($body['status'] ?? 'unknown');

        return $this->trackingResponse($status, is_array($body) ? $body : []);
    }

    /**
     * Cancel a parcel via Paperfly.
     * Paperfly does not support cancellation via API.
     */
    public function cancelParcel(string $consignmentId, CourierProvider $provider): array
    {
        return [
            'success' => false,
            'message' => 'Paperfly does not support parcel cancellation via API. Please cancel manually from the Paperfly panel.',
        ];
    }

    /**
     * Build authentication headers for Paperfly.
     */
    private function getHeaders(CourierProvider $provider): array
    {
        return [
            'paperflykey'  => $provider->api_key,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }
}
