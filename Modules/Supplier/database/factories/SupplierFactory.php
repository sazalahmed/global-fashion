<?php

namespace Modules\Supplier\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Supplier;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        $companies = [
            'Dhaka Trading Co.', 'Bengal Imports Ltd.', 'Star Electronics',
            'Meghna Group Industries', 'ACI Trading', 'Square Consumer Products',
            'Pran-RFL Group', 'Bashundhara Industries', 'Akij Group',
            'City Group of Industries', 'Partex Group', 'PHP Group',
        ];

        $districts = ['Dhaka', 'Chattogram', 'Gazipur', 'Narayanganj', 'Rajshahi', 'Khulna'];
        $paymentTerms = ['Net 15', 'Net 30', 'Net 45', 'Net 60', 'Cash on Delivery'];

        return [
            'company_name' => fake()->unique()->randomElement($companies),
            'contact_person' => fake()->name(),
            'phone' => fake()->numerify('01#########'),
            'email' => fake()->unique()->companyEmail(),
            'address' => fake()->streetAddress() . ', ' . fake()->randomElement($districts),
            'credit_limit' => fake()->randomElement([25000, 50000, 100000, 200000, 500000]),
            'payment_terms' => fake()->randomElement($paymentTerms),
            'opening_balance' => 0,
            'status' => 'active',
        ];
    }

    /**
     * Mark as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Supplier with opening balance.
     */
    public function withOpeningBalance(float $amount = 10000): static
    {
        return $this->state(fn (array $attributes) => [
            'opening_balance' => $amount,
        ]);
    }

    /**
     * Supplier with high credit limit.
     */
    public function highCredit(): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_limit' => 1000000,
        ]);
    }
}
