<?php

namespace Modules\Ecommerce\Services\Couriers;

use Modules\Ecommerce\Models\CourierProvider;

class CourierManager
{
    /**
     * Map of courier slugs to their service classes.
     *
     * @var array<string, class-string<CourierServiceInterface>>
     */
    private array $services = [
        'pathao'    => PathaoService::class,
        'steadfast' => SteadfastService::class,
        'redx'      => RedxService::class,
        'ecourier'  => EcourierService::class,
        'paperfly'  => PaperflyService::class,
    ];

    /**
     * Resolve the appropriate courier service for a given provider.
     * Returns null for unsupported providers (e.g., sundarban, sa_paribahan).
     */
    public function resolve(CourierProvider $provider): ?CourierServiceInterface
    {
        $class = $this->services[$provider->slug] ?? null;

        return $class ? new $class() : null;
    }

    /**
     * Check if a courier slug has API integration support.
     */
    public function isSupported(string $slug): bool
    {
        return isset($this->services[$slug]);
    }

    /**
     * Get a list of all supported courier slugs.
     *
     * @return array<string>
     */
    public function supportedSlugs(): array
    {
        return array_keys($this->services);
    }
}
