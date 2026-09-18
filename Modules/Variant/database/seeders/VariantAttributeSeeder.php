<?php

namespace Modules\Variant\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Variant\Models\VariantAttribute;
use Modules\Variant\Models\VariantAttributeValue;

class VariantAttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            [
                'name' => 'Color',
                'display_type' => 'color_swatch',
                'sort_order' => 1,
                'values' => [
                    ['value' => 'Red', 'color_code' => '#E74C3C', 'sort_order' => 1],
                    ['value' => 'Blue', 'color_code' => '#2E86C1', 'sort_order' => 2],
                    ['value' => 'Black', 'color_code' => '#1C2833', 'sort_order' => 3],
                    ['value' => 'White', 'color_code' => '#F7F9F9', 'sort_order' => 4],
                    ['value' => 'Green', 'color_code' => '#1E8449', 'sort_order' => 5],
                    ['value' => 'Gold', 'color_code' => '#D4AC0D', 'sort_order' => 6],
                    ['value' => 'Silver', 'color_code' => '#BDC3C7', 'sort_order' => 7],
                ],
            ],
            [
                'name' => 'Size',
                'display_type' => 'button',
                'sort_order' => 2,
                'values' => [
                    ['value' => 'XS', 'sort_order' => 1],
                    ['value' => 'S', 'sort_order' => 2],
                    ['value' => 'M', 'sort_order' => 3],
                    ['value' => 'L', 'sort_order' => 4],
                    ['value' => 'XL', 'sort_order' => 5],
                    ['value' => 'XXL', 'sort_order' => 6],
                    ['value' => 'XXXL', 'sort_order' => 7],
                ],
            ]
        ];

        foreach ($attributes as $attrData) {
            $values = $attrData['values'];
            unset($attrData['values']);

            $attribute = VariantAttribute::firstOrCreate(
                ['name' => $attrData['name']],
                $attrData
            );

            foreach ($values as $valData) {
                VariantAttributeValue::firstOrCreate(
                    [
                        'variant_attribute_id' => $attribute->id,
                        'value' => $valData['value'],
                    ],
                    $valData
                );
            }
        }
    }
}
