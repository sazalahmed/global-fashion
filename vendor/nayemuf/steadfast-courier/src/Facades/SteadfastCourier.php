<?php

namespace Nayemuf\SteadfastCourier\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Nayemuf\SteadfastCourier\Apis\OrderApi order()
 * @method static \Nayemuf\SteadfastCourier\Apis\StatusApi status()
 * @method static \Nayemuf\SteadfastCourier\Apis\BalanceApi balance()
 * @method static \Nayemuf\SteadfastCourier\Apis\ReturnApi return()
 * @method static \Nayemuf\SteadfastCourier\Apis\PaymentApi payment()
 * @method static \Nayemuf\SteadfastCourier\Apis\PoliceStationApi policeStation()
 */
class SteadfastCourier extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'steadfast.courier';
    }
}

