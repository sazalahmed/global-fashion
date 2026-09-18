<?php

namespace Modules\Category\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Category\Models\Category;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $categories = [
            'Electronics', 'Clothing', 'Groceries', 'Mobile Accessories', 'Home Appliances',
            'Stationery', 'Beauty Products', 'Sports Equipment', 'Footwear', 'Jewelry',
            'Kitchen Items', 'Toys', 'Books', 'Health & Medicine', 'Furniture',
        ];

        $name = fake()->unique()->randomElement($categories);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'parent_id' => null,
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(0, 100),
            'status' => 'active',
        ];
    }

    /**
     * Mark category as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Make this a child category.
     */
    public function childOf(int $parentId): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
        ]);
    }
}
