<?php

namespace Nayemuf\SteadfastCourier\Apis;

use Nayemuf\SteadfastCourier\Exceptions\SteadfastException;

class ReturnApi extends BaseApi
{
    /**
     * Create return request
     *
     * @param array $data ['consignment_id' or 'invoice' or 'tracking_code', 'reason' (optional)]
     * @return array
     * @throws SteadfastException
     */
    public function createReturnRequest(array $data): array
    {
        if (!isset($data['consignment_id']) && !isset($data['invoice']) && !isset($data['tracking_code'])) {
            throw new SteadfastException('Either consignment_id, invoice, or tracking_code is required');
        }

        return $this->request('POST', '/create_return_request', $data);
    }

    /**
     * Get single return request by ID
     *
     * @param int $id
     * @return array
     * @throws SteadfastException
     */
    public function getReturnRequest(int $id): array
    {
        return $this->request('GET', "/get_return_request/{$id}");
    }

    /**
     * Get all return requests
     *
     * @return array
     * @throws SteadfastException
     */
    public function getReturnRequests(): array
    {
        return $this->request('GET', '/get_return_requests');
    }
}

