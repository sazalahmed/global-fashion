<?php

namespace Modules\Purchase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Purchase\Models\Purchase;

class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 1000, 100000);
        $tax = round($subtotal * 0.15, 2);
        $grand = $subtotal + $tax;

        return [
            'po_number' => 'PO-' . date('Ym') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'po_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'payment_terms' => 'Net 30',
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'tax_amount' => $tax,
            'shipping_cost' => 0,
            'grand_total' => $grand,
            'paid_amount' => 0,
            'due_amount' => $grand,
            'status' => 'draft',
            'payment_status' => 'unpaid',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }
}
