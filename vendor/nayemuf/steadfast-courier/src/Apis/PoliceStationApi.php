<?php

namespace Nayemuf\SteadfastCourier\Apis;

class PoliceStationApi extends BaseApi
{
    /**
     * Get police stations list
     *
     * @return array
     */
    public function getPoliceStations(): array
    {
        return $this->request('GET', '/police_stations');
    }
}

