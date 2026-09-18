<?php

namespace Modules\Brand\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Brand\Models\Brand;

class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $brands = [
            'Samsung', 'Walton', 'Symphony', 'Xiaomi', 'RFL', 'Pran', 'ACI',
            'Square', 'Bata', 'Apex', 'Aarong', 'Yellow', 'Ecstasy', 'Richman',
            'Oppo', 'Vivo', 'Realme', 'HP', 'Dell', 'Lenovo',
        ];

        $name = fake()->unique()->randomElement($brands);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'status' => 'active',
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }

    /**
     * Mark brand as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Mark brand as featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
