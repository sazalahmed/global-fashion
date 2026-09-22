<?php

namespace Nayemuf\SteadfastCourier;

use Nayemuf\SteadfastCourier\Apis\BalanceApi;
use Nayemuf\SteadfastCourier\Apis\OrderApi;
use Nayemuf\SteadfastCourier\Apis\PaymentApi;
use Nayemuf\SteadfastCourier\Apis\PoliceStationApi;
use Nayemuf\SteadfastCourier\Apis\ReturnApi;
use Nayemuf\SteadfastCourier\Apis\StatusApi;

class SteadfastCourier
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $secretKey;

    /**
     * @var string|null
     */
    protected $baseUrl;

    /**
     * @var OrderApi
     */
    protected $orderApi;

    /**
     * @var StatusApi
     */
    protected $statusApi;

    /**
     * @var BalanceApi
     */
    protected $balanceApi;

    /**
     * @var ReturnApi
     */
    protected $returnApi;

    /**
     * @var PaymentApi
     */
    protected $paymentApi;

    /**
     * @var PoliceStationApi
     */
    protected $policeStationApi;

    /**
     * SteadfastCourier constructor.
     *
     * @param string $apiKey
     * @param string $secretKey
     * @param string|null $baseUrl
     */
    public function __construct(
        string $apiKey,
        string $secretKey,
        ?string $baseUrl = null
    ) {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Get Order API instance
     *
     * @return OrderApi
     */
    public function order(): OrderApi
    {
        if (!$this->orderApi) {
            $this->orderApi = new OrderApi($this->apiKey, $this->secretKey, $this->baseUrl);
        }

        return $this->orderApi;
    }

    /**
     * Get Status API instance
     *
     * @return StatusApi
     */
    public function status(): StatusApi
    {
        if (!$this->statusApi) {
            $this->statusApi = new StatusApi($this->apiKey, $this->secretKey, $this->baseUrl);
        }

        return $this->statusApi;
    }

    /**
     * Get Balance API instance
     *
     * @return BalanceApi
     */
    public function balance(): BalanceApi
    {
        if (!$this->balanceApi) {
            $this->balanceApi = new BalanceApi($this->apiKey, $this->secretKey, $this->baseUrl);
        }

        return $this->balanceApi;
    }

    /**
     * Get Return API instance
     *
     * @return ReturnApi
     */
    public function return(): ReturnApi
    {
        if (!$this->returnApi) {
            $this->returnApi = new ReturnApi($this->apiKey, $this->secretKey, $this->baseUrl);
        }

        return $this->returnApi;
    }

    /**
     * Get Payment API instance
     *
     * @return PaymentApi
     */
    public function payment(): PaymentApi
    {
        if (!$this->paymentApi) {
            $this->paymentApi = new PaymentApi($this->apiKey, $this->secretKey, $this->baseUrl);
        }

        return $this->paymentApi;
    }

    /**
     * Get Police Station API instance
     *
     * @return PoliceStationApi
     */
    public function policeStation(): PoliceStationApi
    {
        if (!$this->policeStationApi) {
            $this->policeStationApi = new PoliceStationApi($this->apiKey, $this->secretKey, $this->baseUrl);
        }

        return $this->policeStationApi;
    }
}

