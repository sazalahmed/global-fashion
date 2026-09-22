<?php

namespace Nayemuf\SteadfastCourier\Apis;

use Nayemuf\SteadfastCourier\Exceptions\SteadfastException;

class OrderApi extends BaseApi
{
    /**
     * Place a single order
     *
     * @param array $orderData
     * @return array
     * @throws SteadfastException
     */
    public function placeOrder(array $orderData): array
    {
        $this->validateOrderData($orderData);
        
        return $this->request('POST', '/create_order', $orderData);
    }

    /**
     * Place bulk orders
     *
     * @param array $orders Array of order data
     * @return array
     * @throws SteadfastException
     */
    public function placeBulkOrders(array $orders): array
    {
        if (empty($orders)) {
            throw new SteadfastException('Orders array cannot be empty');
        }

        if (count($orders) > 500) {
            throw new SteadfastException('Maximum 500 orders allowed per bulk request');
        }

        // Validate each order
        foreach ($orders as $order) {
            $this->validateOrderData($order);
        }

        return $this->request('POST', '/create_order/bulk-order', [
            'data' => json_encode($orders, JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * Validate order data before sending to API
     *
     * @param array $orderData
     * @return void
     * @throws SteadfastException
     */
    protected function validateOrderData(array $orderData): void
    {
        // Required fields
        $required = ['invoice', 'recipient_name', 'recipient_phone', 'recipient_address', 'cod_amount'];
        
        foreach ($required as $field) {
            if (!isset($orderData[$field])) {
                throw new SteadfastException("Required field '{$field}' is missing");
            }
        }

        // Validate invoice (alphanumeric with hyphens and underscores)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $orderData['invoice'])) {
            throw new SteadfastException('Invoice must be alphanumeric and can only contain hyphens and underscores');
        }

        // Validate recipient_name (max 100 characters)
        if (strlen($orderData['recipient_name']) > 100) {
            throw new SteadfastException('Recipient name must be within 100 characters');
        }

        // Validate recipient_phone (must be 11 digits)
        $phoneDigits = preg_replace('/[^0-9]/', '', $orderData['recipient_phone']);
        if (strlen($phoneDigits) !== 11) {
            throw new SteadfastException('Recipient phone must be exactly 11 digits');
        }

        // Validate recipient_address (max 250 characters)
        if (strlen($orderData['recipient_address']) > 250) {
            throw new SteadfastException('Recipient address must be within 250 characters');
        }

        // Validate cod_amount (must be numeric and >= 0)
        if (!is_numeric($orderData['cod_amount']) || $orderData['cod_amount'] < 0) {
            throw new SteadfastException('COD amount must be numeric and cannot be less than 0');
        }

        // Validate alternative_phone if provided (must be 11 digits)
        if (isset($orderData['alternative_phone']) && !empty($orderData['alternative_phone'])) {
            $altPhoneDigits = preg_replace('/[^0-9]/', '', $orderData['alternative_phone']);
            if (strlen($altPhoneDigits) !== 11) {
                throw new SteadfastException('Alternative phone must be exactly 11 digits');
            }
        }
    }
}

