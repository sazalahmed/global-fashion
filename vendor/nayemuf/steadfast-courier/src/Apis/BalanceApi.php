<?php

namespace Nayemuf\SteadfastCourier\Apis;

class BalanceApi extends BaseApi
{
    /**
     * Get current balance
     *
     * @return array
     */
    public function getCurrentBalance(): array
    {
        return $this->request('GET', '/get_balance');
    }
}

