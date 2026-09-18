<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\Account;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'account_code' => fake()->unique()->numerify('####'),
            'account_name' => fake()->words(3, true),
            'account_type' => fake()->randomElement(['asset', 'liability', 'equity', 'revenue', 'expense']),
            'sub_type' => 'current_asset',
            'is_system' => false,
            'is_bank_account' => false,
            'status' => 'active',
        ];
    }

    public function bank(): static
    {
        return $this->state(fn () => [
            'is_bank_account' => true,
            'bank_name' => fake()->company() . ' Bank',
            'bank_account_number' => fake()->numerify('##########'),
        ]);
    }
}
