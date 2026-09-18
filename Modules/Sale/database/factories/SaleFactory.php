<?php

namespace Modules\Sale\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sale\Models\Sale;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 500, 50000);
        $tax = round($subtotal * 0.15, 2);
        $grand = $subtotal + $tax;

        return [
            'invoice_number' => 'INV-' . date('Ymd') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'sale_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'source' => 'store',
            'status' => 'completed',
            'payment_status' => 'unpaid',
            'subtotal' => $subtotal,
            'discount_type' => 'none',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_rate' => 15,
            'tax_amount' => $tax,
            'shipping_charge' => 0,
            'grand_total' => $grand,
            'paid_amount' => 0,
            'due_amount' => $grand,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $a) => [
            'payment_status' => 'paid',
            'paid_amount' => $a['grand_total'],
            'due_amount' => 0,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }

    public function pos(): static
    {
        return $this->state(fn () => ['source' => 'pos']);
    }
}
