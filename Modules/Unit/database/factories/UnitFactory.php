<?php

namespace Modules\Unit\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Unit\Models\Unit;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        $units = [
            ['name' => 'Piece', 'short_name' => 'pc'],
            ['name' => 'Kilogram', 'short_name' => 'kg'],
            ['name' => 'Gram', 'short_name' => 'g'],
            ['name' => 'Liter', 'short_name' => 'L'],
            ['name' => 'Milliliter', 'short_name' => 'mL'],
            ['name' => 'Meter', 'short_name' => 'm'],
            ['name' => 'Pack', 'short_name' => 'pk'],
            ['name' => 'Box', 'short_name' => 'box'],
            ['name' => 'Dozen', 'short_name' => 'dz'],
            ['name' => 'Set', 'short_name' => 'set'],
        ];

        $unit = fake()->unique()->randomElement($units);

        return [
            'name' => $unit['name'],
            'short_name' => $unit['short_name'],
            'base_unit_id' => null,
            'allow_decimal' => false,
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
     * Allow decimal quantities.
     */
    public function allowDecimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_decimal' => true,
        ]);
    }

    /**
     * Make this a derived unit of a base unit.
     */
    public function derivedFrom(int $baseUnitId, float $conversionFactor = 1.0): static
    {
        return $this->state(fn (array $attributes) => [
            'base_unit_id' => $baseUnitId,
            'conversion_factor' => $conversionFactor,
        ]);
    }
}
