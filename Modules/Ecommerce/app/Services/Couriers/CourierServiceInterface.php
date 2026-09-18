<?php

namespace Modules\Ecommerce\Services\Couriers;

use Modules\Ecommerce\Models\CourierProvider;
use Modules\Ecommerce\Models\EcommerceOrder;

interface CourierServiceInterface
{
    /**
     * Create a parcel/consignment with the courier.
     *
     * @return array{success: bool, consignment_id: ?string, tracking_number: ?string, tracking_url: ?string, status: ?string, message: string}
     */
    public function createParcel(EcommerceOrder $order, CourierProvider $provider): array;

    /**
     * Track a parcel status.
     *
     * @return array{success: bool, status: ?string, details: array, message: string}
     */
    public function trackParcel(string $consignmentId, CourierProvider $provider): array;

    /**
     * Cancel a parcel.
     *
     * @return array{success: bool, message: string}
     */
    public function cancelParcel(string $consignmentId, CourierProvider $provider): array;
}
