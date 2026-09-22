<?php

namespace Nayemuf\SteadfastCourier\Apis;

class PaymentApi extends BaseApi
{
    /**
     * Get payments list
     *
     * @return array
     */
    public function getPayments(): array
    {
        return $this->request('GET', '/payments');
    }

    /**
     * Get single payment with consignments
     *
     * @param int $paymentId
     * @return array
     */
    public function getPayment(int $paymentId): array
    {
        return $this->request('GET', "/payments/{$paymentId}");
    }
}

