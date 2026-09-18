<?php

namespace Modules\Quotation\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Quotation\Models\Quotation;

class QuotationFactory extends Factory
{
    protected $model = Quotation::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 500, 50000);
        return [
            'quotation_number' => 'QTN-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'quotation_date' => now(),
            'valid_until' => now()->addDays(30),
            'status' => 'draft',
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_charge' => 0,
            'grand_total' => $subtotal,
        ];
    }
}
