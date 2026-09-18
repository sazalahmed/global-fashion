<?php

namespace Modules\Payment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Payment\Models\Payment;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payment_number' => 'PAY-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'direction' => 'receive',
            'party_type' => 'customer',
            'payment_type' => 'against_invoice',
            'amount' => fake()->randomFloat(2, 100, 50000),
            'payment_method' => fake()->randomElement(['cash', 'bkash', 'nagad', 'card', 'bank_transfer']),
            'payment_date' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
