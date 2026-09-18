<?php

namespace Modules\Branch\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Branch\Models\Branch;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        $districts = ['Dhaka', 'Chattogram', 'Rajshahi', 'Khulna', 'Sylhet', 'Rangpur', 'Barishal', 'Mymensingh'];
        $cities = ['Dhaka', 'Chattogram', 'Gazipur', 'Narayanganj', 'Comilla', 'Rajshahi', 'Khulna', 'Sylhet'];

        return [
            'name' => fake()->company() . ' Branch',
            'code' => 'BR-' . str_pad(fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'phone' => fake()->numerify('01#########'),
            'email' => fake()->unique()->companyEmail(),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement($cities),
            'district' => fake()->randomElement($districts),
            'zip_code' => fake()->numerify('####'),
            'manager_name' => fake()->name(),
            'manager_phone' => fake()->numerify('01#########'),
            'is_main' => false,
            'is_active' => true,
            'is_pos_enabled' => true,
            'is_ecom_enabled' => false,
        ];
    }

    /**
     * Mark as the main branch.
     */
    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_main' => true,
        ]);
    }

    /**
     * Mark as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Enable ecommerce.
     */
    public function withEcommerce(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_ecom_enabled' => true,
        ]);
    }

    /**
     * Disable POS.
     */
    public function withoutPos(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pos_enabled' => false,
        ]);
    }
}
