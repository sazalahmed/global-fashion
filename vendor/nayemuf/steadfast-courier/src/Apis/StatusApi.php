<?php

namespace Nayemuf\SteadfastCourier\Apis;

use Nayemuf\SteadfastCourier\Exceptions\SteadfastException;

class StatusApi extends BaseApi
{
    /**
     * Check delivery status by consignment ID
     *
     * @param int $consignmentId
     * @return array
     * @throws SteadfastException
     */
    public function getStatusByConsignmentId(int $consignmentId): array
    {
        return $this->request('GET', "/status_by_cid/{$consignmentId}");
    }

    /**
     * Check delivery status by invoice ID
     *
     * @param string $invoice
     * @return array
     * @throws SteadfastException
     */
    public function getStatusByInvoice(string $invoice): array
    {
        return $this->request('GET', "/status_by_invoice/{$invoice}");
    }

    /**
     * Check delivery status by tracking code
     *
     * @param string $trackingCode
     * @return array
     * @throws SteadfastException
     */
    public function getStatusByTrackingCode(string $trackingCode): array
    {
        return $this->request('GET', "/status_by_trackingcode/{$trackingCode}");
    }
}

