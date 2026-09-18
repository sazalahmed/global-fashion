<?php

namespace Modules\Ecommerce\Services\Couriers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Models\CourierProvider;
use Modules\Ecommerce\Models\EcommerceOrder;

abstract class BaseCourierService implements CourierServiceInterface
{
    /**
     * Make an HTTP request to the courier API.
     *
     * @param string $method   HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param string $url      Full URL to call
     * @param array  $data     Request body/query data
     * @param array  $headers  Additional headers
     * @param int    $timeout  Request timeout in seconds
     *
     * @return array{status: int, body: array, success: bool}
     */
    protected function makeRequest(
        string $method,
        string $url,
        array $data = [],
        array $headers = [],
        int $timeout = 30
    ): array {
        $courierName = class_basename(static::class);

        Log::info("Courier API Request [{$courierName}]", [
            'method'  => $method,
            'url'     => $url,
            'payload' => $this->redactSensitiveData($data),
        ]);

        try {
            $request = Http::timeout($timeout)->withHeaders($headers);

            $response = match (strtoupper($method)) {
                'GET'    => $request->get($url, $data),
                'POST'   => $request->post($url, $data),
                'PUT'    => $request->put($url, $data),
                'PATCH'  => $request->patch($url, $data),
                'DELETE' => $request->delete($url, $data),
                default  => $request->post($url, $data),
            };

            $body = $response->json() ?? [];
            $status = $response->status();

            Log::info("Courier API Response [{$courierName}]", [
                'status' => $status,
                'body'   => $body,
            ]);

            return [
                'status'  => $status,
                'body'    => $body,
                'success' => $response->successful(),
            ];
        } catch (\Throwable $e) {
            Log::error("Courier API Error [{$courierName}]", [
                'url'     => $url,
                'error'   => $e->getMessage(),
            ]);

            return [
                'status'  => 0,
                'body'    => [],
                'success' => false,
            ];
        }
    }

    /**
     * Parse the shipping address string into structured components.
     * Attempts to extract name, phone, address, city, and area.
     */
    protected function parseAddress(EcommerceOrder $order): array
    {
        $address = $order->shipping_address ?? '';

        return [
            'name'    => $order->customer_name ?? 'Customer',
            'phone'   => $order->customer_phone ?? '',
            'address' => $address,
            'city'    => $this->extractCity($address),
            'area'    => $this->extractArea($address),
        ];
    }

    /**
     * Determine if the order is Cash on Delivery.
     */
    protected function isCod(EcommerceOrder $order): bool
    {
        $codMethods = ['cod', 'cash_on_delivery', 'cash on delivery'];

        return in_array(strtolower($order->payment_method ?? ''), $codMethods);
    }

    /**
     * Get the COD amount based on payment method.
     */
    protected function getCodAmount(EcommerceOrder $order): float
    {
        return $this->isCod($order) ? (float) $order->grand_total : 0.0;
    }

    /**
     * Get the base URL for a courier provider, stripping trailing slashes.
     */
    protected function getBaseUrl(CourierProvider $provider, string $fallback): string
    {
        $url = $provider->base_url ?: $fallback;

        return rtrim($url, '/');
    }

    /**
     * Build a success response array.
     */
    protected function successResponse(
        string $consignmentId,
        string $message = 'Parcel created successfully.',
        ?string $trackingNumber = null,
        ?string $trackingUrl = null,
        string $status = 'created'
    ): array {
        return [
            'success'         => true,
            'consignment_id'  => $consignmentId,
            'tracking_number' => $trackingNumber ?? $consignmentId,
            'tracking_url'    => $trackingUrl,
            'status'          => $status,
            'message'         => $message,
        ];
    }

    /**
     * Build a failure response array.
     */
    protected function failResponse(string $message = 'API request failed.'): array
    {
        return [
            'success'         => false,
            'consignment_id'  => null,
            'tracking_number' => null,
            'tracking_url'    => null,
            'status'          => null,
            'message'         => $message,
        ];
    }

    /**
     * Build a tracking success response.
     */
    protected function trackingResponse(string $status, array $details = []): array
    {
        return [
            'success' => true,
            'status'  => $status,
            'details' => $details,
            'message' => 'Tracking info retrieved.',
        ];
    }

    /**
     * Build a tracking failure response.
     */
    protected function trackingFailResponse(string $message = 'Could not retrieve tracking info.'): array
    {
        return [
            'success' => false,
            'status'  => null,
            'details' => [],
            'message' => $message,
        ];
    }

    /**
     * Try to extract a city name from the address string.
     */
    private function extractCity(string $address): string
    {
        $bdCities = [
            'Dhaka', 'Chattogram', 'Chittagong', 'Rajshahi', 'Khulna',
            'Sylhet', 'Rangpur', 'Barishal', 'Barisal', 'Mymensingh',
            'Comilla', 'Gazipur', 'Narayanganj', 'Cox\'s Bazar',
        ];

        foreach ($bdCities as $city) {
            if (stripos($address, $city) !== false) {
                return $city;
            }
        }

        return 'Dhaka';
    }

    /**
     * Try to extract an area/thana from the address string.
     */
    private function extractArea(string $address): string
    {
        // Return empty string; specific courier services handle defaults
        return '';
    }

    /**
     * Redact sensitive fields from log data.
     */
    private function redactSensitiveData(array $data): array
    {
        $sensitive = ['password', 'client_secret', 'api_secret', 'secret_key'];

        foreach ($sensitive as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***REDACTED***';
            }
        }

        return $data;
    }
}
