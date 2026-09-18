<?php

namespace Modules\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Product\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $products = [
            'Wireless Bluetooth Earbuds', 'Cotton T-Shirt', 'Stainless Steel Water Bottle',
            'LED Desk Lamp', 'Phone Case Cover', 'USB-C Charging Cable', 'Notebook A4 Size',
            'Sunglasses UV Protection', 'Hand Sanitizer 250ml', 'Kitchen Knife Set',
            'Ceramic Coffee Mug', 'Laptop Stand', 'Gym Bag', 'Wall Clock',
            'Table Fan 12 Inch', 'Power Bank 10000mAh', 'Digital Weighing Scale',
            'Rice Cooker 2.8L', 'Bamboo Cutting Board', 'Shower Gel 500ml',
        ];

        $name = fake()->unique()->randomElement($products);
        $costPrice = fake()->randomFloat(2, 50, 5000);
        $sellPrice = $costPrice * fake()->randomFloat(2, 1.15, 1.80);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sku' => fake()->unique()->bothify('SKU-####-??'),
            'barcode' => fake()->unique()->optional(0.7)->ean13(),
            'category_id' => null,
            'brand_id' => null,
            'unit_id' => null,
            'product_type' => 'simple',
            'cost_price' => round($costPrice, 2),
            'sell_price' => round($sellPrice, 2),
            'vat_rate' => 15.00,
            'vat_inclusive' => 'yes',
            'discount_type' => 'none',
            'description' => fake()->paragraph(),
            'min_stock_alert' => 10,
            'status' => 'active',
            'show_in_pos' => true,
            'track_stock' => true,
            'created_by' => null,
        ];
    }

    /**
     * Mark product as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Make a variable product.
     */
    public function variable(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'variable',
        ]);
    }

    /**
     * Make a service product.
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'service',
            'track_stock' => false,
        ]);
    }

    /**
     * Hide from POS.
     */
    public function hiddenFromPos(): static
    {
        return $this->state(fn (array $attributes) => [
            'show_in_pos' => false,
        ]);
    }

    /**
     * Product with flat discount.
     */
    public function withFlatDiscount(float $amount = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => 'flat',
            'discount_value' => $amount,
        ]);
    }

    /**
     * Product with percentage discount.
     */
    public function withPercentDiscount(float $percent = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => 'percentage',
            'discount_value' => $percent,
        ]);
    }
}
