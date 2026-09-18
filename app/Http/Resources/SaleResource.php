<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'invoice_number'  => $this->invoice_number,
            'customer'        => $this->whenLoaded('customer', fn () => [
                'id'    => $this->customer->id,
                'name'  => $this->customer->name,
                'phone' => $this->customer->phone,
            ]),
            'branch'          => $this->whenLoaded('branch', fn () => [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'sale_date'       => $this->sale_date?->toDateString(),
            'source'          => $this->source,
            'status'          => $this->status,
            'payment_status'  => $this->payment_status,
            'subtotal'        => (float) $this->subtotal,
            'discount_type'   => $this->discount_type,
            'discount_value'  => (float) ($this->discount_value ?? 0),
            'discount_amount' => (float) ($this->discount_amount ?? 0),
            'tax_rate'        => (float) ($this->tax_rate ?? 0),
            'tax_amount'      => (float) ($this->tax_amount ?? 0),
            'shipping_charge' => (float) ($this->shipping_charge ?? 0),
            'grand_total'     => (float) $this->grand_total,
            'paid_amount'     => (float) $this->paid_amount,
            'due_amount'      => (float) $this->due_amount,
            'notes'           => $this->notes,
            'items'           => $this->whenLoaded('items', fn () =>
                $this->items->map(fn ($item) => [
                    'id'              => $item->id,
                    'product_id'      => $item->product_id,
                    'variant_id'      => $item->variant_id,
                    'product_name'    => $item->product_name,
                    'product_sku'     => $item->product_sku,
                    'quantity'        => (int) $item->quantity,
                    'unit_price'      => (float) $item->unit_price,
                    'discount_amount' => (float) ($item->discount_amount ?? 0),
                    'tax_amount'      => (float) ($item->tax_amount ?? 0),
                    'subtotal'        => (float) $item->subtotal,
                ])
            ),
            'payments'        => $this->whenLoaded('allocations', fn () =>
                $this->allocations->map(fn ($alloc) => [
                    'amount'         => (float) $alloc->amount,
                    'payment_method' => $alloc->payment?->payment_method,
                    'reference'      => $alloc->payment?->reference,
                ])
            ),
            'created_by'      => $this->whenLoaded('creator', fn () => [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }
}
