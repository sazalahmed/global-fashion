<?php

namespace Modules\Expense\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Expense\Models\Expense;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 100, 25000);
        return [
            'expense_number' => 'EXP-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'amount' => $amount,
            'tax_amount' => 0,
            'total_amount' => $amount,
            'expense_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'payment_method' => 'Cash',
            'description' => fake()->sentence(),
            'status' => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => 'paid']);
    }
}
