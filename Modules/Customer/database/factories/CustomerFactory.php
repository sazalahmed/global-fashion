<?php

namespace Modules\Customer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Customer\Models\Customer;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $districts = ['Dhaka', 'Gazipur', 'Narayanganj', 'Chattogram', 'Comilla', 'Rajshahi', 'Khulna'];

        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('01#########'),
            'email' => fake()->unique()->optional(0.6)->safeEmail(),
            'district' => fake()->randomElement($districts),
            'address' => fake()->streetAddress(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function corporate(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_name' => fake()->company(),
        ]);
    }
}
