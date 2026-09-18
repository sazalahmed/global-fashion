<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'sku'          => $this->sku,
            'barcode'      => $this->barcode,
            'sell_price'   => (float) $this->sell_price,
            'cost_price'   => (float) ($this->cost_price ?? 0),
            'vat_rate'     => (float) ($this->vat_rate ?? 0),
            'product_type' => $this->product_type,
            'image'        => $this->image,
            'category'     => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ]),
            'variants'     => $this->whenLoaded('variants', fn () =>
                $this->variants->map(fn ($v) => [
                    'id'         => $v->id,
                    'sku'        => $v->sku,
                    'barcode'    => $v->barcode,
                    'sell_price' => (float) $v->sell_price,
                    'cost_price' => (float) ($v->cost_price ?? 0),
                    'stock'      => (int) ($v->stock_quantity ?? 0),
                    'is_active'  => (bool) $v->is_active,
                    'attributes' => $v->relationLoaded('attributeValues')
                        ? $v->attributeValues->map(fn ($av) => [
                            'name'  => $av->attribute?->name,
                            'value' => $av->value,
                        ])
                        : [],
                ])
            ),
        ];
    }
}
