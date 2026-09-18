<?php

namespace Modules\Quotation\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Modules\Quotation\Models\Quotation;
use Modules\Quotation\Models\QuotationItem;
use Modules\Sale\Models\Sale;
use Modules\Sale\Services\SaleService;

class QuotationService
{
    public function __construct(
        private readonly SaleService $saleService,
    ) {}

    // ── List ──

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Quotation::with(['customer', 'branch', 'creator', 'items'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->byStatus($v))
            ->when($filters['customer_id'] ?? null, fn ($q, $v) => $q->byCustomer($v))
            ->when($filters['branch_id'] ?? null, fn ($q, $v) => $q->where('branch_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('quotation_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('quotation_date', '<=', $d))
            ->latest('quotation_date')
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    // ── Find ──

    public function find(int $id): Quotation
    {
        return Quotation::with([
            'customer', 'branch', 'creator',
            // images eager-loaded so display_image's gallery fallback (no thumbnail)
            // doesn't lazy-load per line item on the edit page.
            'items.product.images', 'items.variant.attributeValues.attribute', 'convertedSale',
        ])->findOrFail($id);
    }

    // ── Create ──

    public function create(array $data, array $items): Quotation
    {
        return DB::transaction(function () use ($data, $items) {
            $quotationNumber = $this->generateQuotationNumber();
            $totals = $this->calculateTotals($items, $data);

            $quotation = Quotation::create([
                'quotation_number' => $quotationNumber,
                'reference'        => $data['reference'] ?? null,
                'customer_id'      => $data['customer_id'] ?? null,
                'branch_id'        => $data['branch_id'] ?? auth()->user()->branch_id,
                'quotation_date'   => $data['quotation_date'],
                'valid_until'      => $data['valid_until'],
                'status'           => $data['status'] ?? 'pending',
                'subtotal'         => $totals['subtotal'],
                'discount_type'    => $data['discount_type'] ?? null,
                'discount_value'   => $data['discount_value'] ?? 0,
                'discount_amount'  => $totals['discount_amount'],
                'tax_rate'         => $data['tax_rate'] ?? 0,
                'tax_amount'       => $totals['tax_amount'],
                'shipping_charge'  => $data['shipping_charge'] ?? 0,
                'grand_total'      => $totals['grand_total'],
                'notes'            => $data['notes'] ?? null,
                'terms'            => $data['terms'] ?? null,
                'billing_address'  => $data['billing_address'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'created_by'       => auth()->id(),
            ]);

            $this->createItems($quotation, $items);

            return $quotation->load(['customer', 'branch', 'creator', 'items.product']);
        });
    }

    // ── Update ──

    public function update(Quotation $quotation, array $data, array $items): Quotation
    {
        return DB::transaction(function () use ($quotation, $data, $items) {
            $totals = $this->calculateTotals($items, $data);

            $quotation->update([
                'reference'       => $data['reference'] ?? null,
                'customer_id'     => $data['customer_id'] ?? null,
                'branch_id'       => $data['branch_id'] ?? $quotation->branch_id,
                'quotation_date'  => $data['quotation_date'],
                'valid_until'     => $data['valid_until'],
                'subtotal'        => $totals['subtotal'],
                'discount_type'   => $data['discount_type'] ?? null,
                'discount_value'  => $data['discount_value'] ?? 0,
                'discount_amount' => $totals['discount_amount'],
                'tax_rate'        => $data['tax_rate'] ?? 0,
                'tax_amount'      => $totals['tax_amount'],
                'shipping_charge' => $data['shipping_charge'] ?? 0,
                'grand_total'     => $totals['grand_total'],
                'notes'           => $data['notes'] ?? null,
                'terms'           => $data['terms'] ?? null,
                'billing_address'  => $data['billing_address'] ?? $quotation->billing_address,
                'shipping_address' => $data['shipping_address'] ?? $quotation->shipping_address,
            ]);

            // Delete old items and recreate
            $quotation->items()->delete();
            $this->createItems($quotation, $items);

            return $quotation->load(['customer', 'branch', 'creator', 'items.product']);
        });
    }

    // ── Update Status ──

    public function updateStatus(Quotation $quotation, string $status): void
    {
        $quotation->update(['status' => $status]);
    }

    // ── Convert to Sale ──

    public function convertToSale(Quotation $quotation): Sale
    {
        if (!$quotation->isConvertible()) {
            throw new \Exception('This quotation cannot be converted to a sale.');
        }

        return DB::transaction(function () use ($quotation) {
            $quotation->load(['items.product.variants.attributeValues.attribute', 'items.variant']);

            // Map quotation items to sale items format. A line with a per-variant
            // breakdown is expanded into one sale item per variant — the variant
            // lives in the breakdown (not variant_id), so each entry is matched to
            // the product's variant by name to carry the variant through. The line
            // discount stays on the first split so the sale total is unchanged.
            $saleItems = [];
            foreach ($quotation->items as $item) {
                $breakdown = is_array($item->variant_breakdown) ? $item->variant_breakdown : [];

                if (count($breakdown)) {
                    $variants = $item->product?->variants ?? collect();
                    $first = true;
                    foreach ($breakdown as $b) {
                        $name = $b['name'] ?? null;
                        $matched = $name
                            ? $variants->first(fn ($v) => $v->variant_name === $name)
                            : null;

                        $saleItems[] = [
                            'product_id'      => $item->product_id,
                            'variant_id'      => $matched?->id ?? $item->variant_id,
                            'variant_label'   => $name ?: $item->variant_label,
                            'quantity'        => (int) ($b['qty'] ?? 0),
                            'unit_price'      => (float) ($b['price'] ?? $item->unit_price),
                            'discount_amount' => $first ? $item->discount_amount : 0,
                            'tax_amount'      => $first ? $item->tax_amount : 0,
                        ];
                        $first = false;
                    }
                } else {
                    $saleItems[] = [
                        'product_id'      => $item->product_id,
                        'variant_id'      => $item->variant_id,
                        'variant_label'   => $item->variant_label,
                        'quantity'        => $item->quantity,
                        'unit_price'      => $item->unit_price,
                        'discount_amount' => $item->discount_amount,
                        'tax_amount'      => $item->tax_amount,
                    ];
                }
            }

            // Create sale via SaleService
            $sale = $this->saleService->createSale([
                'customer_id'     => $quotation->customer_id,
                'branch_id'       => $quotation->branch_id,
                'sale_date'       => now()->toDateString(),
                'source'          => 'store',
                'status'          => 'pending',
                'discount_type'   => $quotation->discount_type,
                'discount_value'  => $quotation->discount_value,
                'tax_rate'        => $quotation->tax_rate,
                'shipping_charge' => $quotation->shipping_charge,
                'notes'           => 'Converted from quotation ' . $quotation->quotation_number,
            ], $saleItems);

            // Mark quotation as converted
            $quotation->update([
                'status'            => 'converted',
                'converted_sale_id' => $sale->id,
            ]);

            return $sale;
        });
    }

    // ── Duplicate ──

    public function duplicate(Quotation $quotation): Quotation
    {
        return DB::transaction(function () use ($quotation) {
            $quotation->load('items');

            $newQuotation = Quotation::create([
                'quotation_number' => $this->generateQuotationNumber(),
                'reference'        => $quotation->reference,
                'customer_id'      => $quotation->customer_id,
                'branch_id'        => $quotation->branch_id,
                'quotation_date'   => now()->toDateString(),
                'valid_until'      => now()->addDays(30)->toDateString(),
                'status'           => 'pending',
                'subtotal'         => $quotation->subtotal,
                'discount_type'    => $quotation->discount_type,
                'discount_value'   => $quotation->discount_value,
                'discount_amount'  => $quotation->discount_amount,
                'tax_rate'         => $quotation->tax_rate,
                'tax_amount'       => $quotation->tax_amount,
                'shipping_charge'  => $quotation->shipping_charge,
                'grand_total'      => $quotation->grand_total,
                'notes'            => $quotation->notes,
                'terms'            => $quotation->terms,
                'created_by'       => auth()->id(),
            ]);

            foreach ($quotation->items as $item) {
                $newQuotation->items()->create([
                    'product_id'      => $item->product_id,
                    'variant_id'      => $item->variant_id,
                    'product_name'    => $item->product_name,
                    'product_sku'     => $item->product_sku,
                    'quantity'        => $item->quantity,
                    'unit_price'      => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'tax_amount'      => $item->tax_amount,
                    'subtotal'        => $item->subtotal,
                ]);
            }

            return $newQuotation->load(['customer', 'branch', 'creator', 'items.product']);
        });
    }

    // ── Stats ──

    public function getStats(array $filters = []): array
    {
        $base = Quotation::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->search($s))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->byStatus($v))
            ->when($filters['customer_id'] ?? null, fn ($q, $v) => $q->byCustomer($v))
            ->when($filters['branch_id'] ?? null, fn ($q, $v) => $q->where('branch_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->where('quotation_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->where('quotation_date', '<=', $d));

        $hasFilters = !empty(array_filter($filters));

        if ($hasFilters) {
            return [
                'total'    => (clone $base)->count(),
                'pending'  => (clone $base)->where('status', 'pending')->count(),
                'accepted' => (clone $base)->where('status', 'accepted')->count(),
                'expired'  => (clone $base)->where('status', 'expired')->count(),
            ];
        }

        return [
            'total'    => (clone $base)->count(),
            'pending'  => (clone $base)->where('status', 'pending')->count(),
            'accepted' => (clone $base)->where('status', 'accepted')->count(),
            'expired'  => (clone $base)->where('status', 'expired')->count(),
        ];
    }

    // ── Mark Expired (Schedulable) ──

    public function markExpired(): int
    {
        return Quotation::where('valid_until', '<', now()->toDateString())
            ->whereNotIn('status', ['expired', 'converted', 'rejected'])
            ->update(['status' => 'expired']);
    }

    // ── Calculate Totals ──

    public function calculateTotals(array $items, array $data): array
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $itemSubtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount_amount'] ?? 0);
            $subtotal += $itemSubtotal;
        }

        // Calculate overall discount
        $discountType = $data['discount_type'] ?? null;
        $discountValue = (float) ($data['discount_value'] ?? 0);

        if ($discountType === 'percentage') {
            $discountAmount = round($subtotal * ($discountValue / 100), 2);
        } elseif ($discountType === 'fixed') {
            $discountAmount = $discountValue;
        } else {
            $discountAmount = 0;
        }

        // Calculate tax on (subtotal - discount)
        $taxRate = (float) ($data['tax_rate'] ?? 0);
        $taxableAmount = $subtotal - $discountAmount;
        $taxAmount = round($taxableAmount * ($taxRate / 100), 2);

        // Shipping
        $shippingCharge = (float) ($data['shipping_charge'] ?? 0);

        // Grand total
        $grandTotal = $subtotal - $discountAmount + $taxAmount + $shippingCharge;

        return [
            'subtotal'        => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_amount'      => round($taxAmount, 2),
            'grand_total'     => round($grandTotal, 2),
        ];
    }

    // ── Private Helpers ──

    private function createItems(Quotation $quotation, array $items): void
    {
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            $itemSubtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount_amount'] ?? 0);

            $quotation->items()->create([
                'product_id'      => $item['product_id'],
                'variant_id'      => $item['variant_id'] ?? null,
                'product_name'    => $product ? $product->name : ($item['product_name'] ?? 'Unknown'),
                'product_sku'     => $product ? $product->sku : ($item['product_sku'] ?? ''),
                'custom_note'     => $item['custom_note'] ?? null,
                'variant_breakdown' => $this->parseBreakdown($item['variant_breakdown'] ?? null),
                'quantity'        => $item['quantity'],
                'unit_price'      => $item['unit_price'],
                'discount_amount' => $item['discount_amount'] ?? 0,
                'tax_amount'      => $item['tax_amount'] ?? 0,
                'subtotal'        => $itemSubtotal,
            ]);
        }
    }

    /**
     * Normalise a submitted variant breakdown (JSON string from the form, or an
     * array) into a clean [{name, qty, price}] array, or null when absent.
     */
    private function parseBreakdown($value): ?array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        if (! is_array($value) || empty($value)) {
            return null;
        }
        return array_values(array_filter(array_map(function ($b) {
            if (! is_array($b) || ! isset($b['name'])) return null;
            return [
                'name'  => (string) $b['name'],
                'qty'   => (int) ($b['qty'] ?? 0),
                'price' => (float) ($b['price'] ?? 0),
            ];
        }, $value)));
    }

    private function generateQuotationNumber(): string
    {
        $year = now()->format('Y');
        $count = Quotation::withTrashed()
            ->whereYear('created_at', $year)
            ->count() + 1;

        return 'QTN-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
