<?php

namespace Modules\POS\Services;

use Illuminate\Support\Collection;
use Modules\Category\Models\Category;
use Modules\Inventory\Services\InventoryService;
use Modules\Product\Models\Product;
use Modules\Sale\Models\Sale;
use Modules\Sale\Services\SaleService;
use Modules\Variant\Models\ProductVariant;

class POSService
{
    public function __construct(
        protected SaleService $saleService,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Process a POS sale by transforming cart data and delegating to SaleService.
     * Validates stock availability for all items before processing.
     *
     * @throws \RuntimeException When insufficient stock is detected.
     */
    public function processSale(array $cartData): Sale
    {
        // Validate stock availability for all cart items
        $outOfStock = [];
        foreach ($cartData['cart'] as $cartItem) {
            $productId = $cartItem['product_id'];
            $variantId = $cartItem['variant_id'] ?? null;
            $quantity = $cartItem['quantity'];

            if (!$this->checkAvailability($productId, $variantId, $quantity)) {
                $product = Product::find($productId);
                $available = $this->inventoryService->getStockLevel($productId, $variantId);
                $outOfStock[] = ($product ? $product->name : "Product #{$productId}") . " (available: {$available}, requested: {$quantity})";
            }
        }

        if (!empty($outOfStock)) {
            throw new \RuntimeException('Insufficient stock for: ' . implode(', ', $outOfStock));
        }

        // Map POS discount type names to Sale enum values
        $discountTypeMap = ['flat' => 'fixed', 'percent' => 'percentage'];
        $posDiscountType = $cartData['discount_type'] ?? null;

        $notes = $cartData['note'] ?? null;
        $walkinName = $cartData['walkin_customer_name'] ?? null;
        if ($walkinName && empty($cartData['customer_id'])) {
            $notes = 'Customer: ' . $walkinName . ($notes ? ' | ' . $notes : '');
        }

        $data = [
            'customer_id'    => $cartData['customer_id'] ?? null,
            'sale_date'      => $cartData['sale_date'] ?? now()->toDateString(),
            'source'         => 'pos',
            'status'         => 'confirmed',
            'discount_type'  => $discountTypeMap[$posDiscountType] ?? $posDiscountType,
            'discount_value' => $cartData['discount_value'] ?? 0,
            'tax_rate'       => $cartData['tax_rate'] ?? 0,
            'notes'          => $notes,
        ];

        $items = array_map(function (array $cartItem) {
            // Re-validate price from database — never trust frontend prices
            $product = Product::find($cartItem['product_id']);
            $dbPrice = $product ? (float) $product->sell_price : 0;

            if ($cartItem['variant_id'] ?? null) {
                $variant = \Modules\Variant\Models\ProductVariant::find($cartItem['variant_id']);
                if ($variant && $variant->sell_price) {
                    $dbPrice = (float) $variant->sell_price;
                }
            }

            return [
                'product_id'      => $cartItem['product_id'],
                'variant_id'      => $cartItem['variant_id'] ?? null,
                'quantity'        => $cartItem['quantity'],
                'unit_price'      => $dbPrice,
                'discount_amount' => $cartItem['discount_amount'] ?? 0,
            ];
        }, $cartData['cart']);

        $payments = array_map(function (array $payment) {
            return [
                'amount'             => $payment['amount'],
                'method'             => $payment['method'] ?? null,
                'payment_account_id' => $payment['payment_account_id'] ?? null,
                'reference'          => $payment['reference'] ?? null,
            ];
        }, $cartData['payments']);

        return $this->saleService->createSale($data, $items, $payments);
    }

    /**
     * Search products available for POS by term (includes variant barcode/SKU matches).
     */
    public function searchProducts(string $term, int $limit = 20): Collection
    {
        $products = Product::forPos()
            ->search($term)
            ->with(['category', 'variants' => fn ($q) => $q->active()->with('attributeValues.attribute')])
            ->select('id', 'name', 'sku', 'barcode', 'sell_price', 'vat_rate', 'category_id', 'product_type', 'thumbnail')
            ->limit($limit)
            ->get();

        // Also search by variant barcode/SKU
        if ($products->isEmpty()) {
            $variantMatches = ProductVariant::active()
                ->where(fn ($q) => $q->where('sku', 'like', '%' . $term . '%')->orWhere('barcode', 'like', '%' . $term . '%'))
                ->with(['product' => fn ($q) => $q->forPos()->select('id', 'name', 'sku', 'barcode', 'sell_price', 'vat_rate', 'category_id', 'product_type'), 'product.category', 'attributeValues.attribute'])
                ->limit($limit)
                ->get();

            $parentProducts = $variantMatches->pluck('product')->filter()->unique('id');
            foreach ($parentProducts as $product) {
                $product->load(['variants' => fn ($q) => $q->active()->with('attributeValues.attribute')]);
            }
            $products = $products->merge($parentProducts)->unique('id');
        }

        return $products;
    }

    /**
     * Find a product by its barcode (checks variant barcodes too).
     */
    public function getProductByBarcode(string $barcode): ?Product
    {
        $product = Product::forPos()
            ->where('barcode', $barcode)
            ->with(['variants' => fn ($q) => $q->active()->with('attributeValues.attribute')])
            ->first();

        if ($product) {
            return $product;
        }

        // Check variant barcodes
        $variant = ProductVariant::active()
            ->where('barcode', $barcode)
            ->with(['product' => fn ($q) => $q->forPos(), 'attributeValues.attribute'])
            ->first();

        if ($variant && $variant->product) {
            $variant->product->load(['variants' => fn ($q) => $q->active()->with('attributeValues.attribute')]);
            // Attach matched variant info for the frontend
            $variant->product->matched_variant_id = $variant->id;
            return $variant->product;
        }

        return null;
    }

    /**
     * Get all active categories ordered by name.
     */
    public function getCategories(): Collection
    {
        return Category::active()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    /**
     * Get products available for POS, optionally filtered by category.
     * Eager-loads active variants with their attribute values.
     */
    public function getPosProducts(?int $categoryId = null): Collection
    {
        $query = Product::forPos()
            ->select('id', 'name', 'sku', 'barcode', 'sell_price', 'vat_rate', 'category_id', 'product_type', 'thumbnail')
            ->with(['category', 'images', 'variants' => fn ($q) => $q->active()->with('attributeValues.attribute')]);

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        return $query->get();
    }

    /**
     * Check product/variant stock availability.
     */
    public function checkAvailability(int $productId, ?int $variantId, int $qty): bool
    {
        $available = $this->inventoryService->getStockLevel($productId, $variantId);

        return $available >= $qty;
    }
}
