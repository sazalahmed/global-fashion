<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ProductResource;
use App\Http\Resources\SaleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Services\CustomerService;
use Modules\Payment\Models\PaymentAccount;
use Modules\POS\Services\POSService;

class PosController extends BaseApiController
{
    public function __construct(
        protected POSService $posService,
        protected CustomerService $customerService
    ) {}

    /**
     * Get POS initialization data: products, categories, payment accounts.
     */
    public function init(Request $request): JsonResponse
    {
        $categoryId = $request->input('category_id') ? (int) $request->input('category_id') : null;

        $products = $this->posService->getPosProducts($categoryId);
        $categories = $this->posService->getCategories();

        $paymentAccounts = PaymentAccount::active()
            ->with('bank')
            ->orderBy('account_type')
            ->get()
            ->map(fn ($a) => [
                'id'           => $a->id,
                'name'         => $a->name,
                'account_type' => $a->account_type,
                'display_name' => $a->display_name,
                'is_default'   => $a->is_default,
            ]);

        return $this->success([
            'products'         => ProductResource::collection($products),
            'categories'       => $categories,
            'payment_accounts' => $paymentAccounts,
        ], 'POS data loaded successfully.');
    }

    /**
     * Search products for POS.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $products = $this->posService->searchProducts(
            $request->input('q', '')
        );

        return $this->success(
            ProductResource::collection($products),
            'Products retrieved successfully.'
        );
    }

    /**
     * Get product by barcode.
     */
    public function getByBarcode(Request $request): JsonResponse
    {
        $request->validate(['barcode' => 'required|string']);

        $product = $this->posService->getProductByBarcode(
            $request->input('barcode')
        );

        if ($product === null) {
            return $this->error('Product not found.', 404);
        }

        $resource = new ProductResource($product);
        $data = $resource->toArray($request);
        $data['matched_variant_id'] = $product->matched_variant_id ?? null;

        return $this->success($data, 'Product found.');
    }

    /**
     * Process a POS sale.
     */
    public function processSale(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart'                          => 'required|array|min:1',
            'cart.*.product_id'             => 'required|integer',
            'cart.*.variant_id'             => 'nullable|integer',
            'cart.*.quantity'               => 'required|integer|min:1',
            'cart.*.unit_price'             => 'required|numeric|min:0',
            'cart.*.discount_amount'        => 'nullable|numeric|min:0',
            'customer_id'                   => 'nullable|integer',
            'walkin_customer_name'          => 'nullable|string|max:255',
            'sale_date'                     => 'required|date',
            'payments'                      => 'required|array|min:1',
            'payments.*.amount'             => 'required|numeric|min:0',
            'payments.*.method'             => 'required|string|in:cash,mobile_banking,card,bank_transfer,advance',
            'payments.*.payment_account_id' => 'nullable|integer|exists:payment_accounts,id',
            'payments.*.reference'          => 'nullable|string',
            'discount_type'                 => 'nullable|in:flat,percent',
            'discount_value'                => 'nullable|numeric|min:0',
            'tax_rate'                      => 'nullable|numeric|min:0|max:100',
            'note'                          => 'nullable|string',
        ]);

        try {
            $sale = $this->posService->processSale($validated);
            $sale->load(['items.product', 'items.variant', 'customer', 'allocations.payment']);

            return $this->success(
                new SaleResource($sale),
                'Sale completed successfully.',
                201
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->error('An error occurred while processing the sale.', 500);
        }
    }

    /**
     * Search customers for POS.
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $customers = $this->customerService->search(
            (string) $request->input('q', '')
        );

        return $this->success($customers, 'Customers retrieved successfully.');
    }

    /**
     * Get customer advance balance.
     */
    public function customerAdvance(Request $request): JsonResponse
    {
        $request->validate(['customer_id' => 'required|integer|min:1']);

        $customerId = (int) $request->input('customer_id');

        $customer = \Modules\Customer\Models\Customer::select('id', 'total_purchased', 'total_paid', 'opening_balance')
            ->find($customerId);

        $advanceBalance = (float) \Modules\Payment\Models\Payment::where('party_type', 'customer')
            ->where('party_id', $customerId)
            ->whereIn('payment_type', ['advance_payment', 'advance_return'])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN payment_type = 'advance_payment' THEN amount ELSE 0 END), 0)
                - COALESCE(SUM(CASE WHEN payment_type = 'advance_return' THEN amount ELSE 0 END), 0)
                as balance
            ")
            ->value('balance');

        return $this->success([
            'advance_balance' => $advanceBalance,
            'due_amount'      => $customer ? $customer->due_amount : 0,
        ], 'Customer balance retrieved.');
    }

    /**
     * Quick-add a customer from POS.
     */
    public function quickAddCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        $customer = $this->customerService->quickCreate($validated);

        return $this->success([
            'id'    => $customer->id,
            'name'  => $customer->name,
            'phone' => $customer->phone,
        ], 'Customer created successfully.', 201);
    }

    /**
     * Get sale receipt data.
     */
    public function receipt(int $saleId): JsonResponse
    {
        $sale = \Modules\Sale\Models\Sale::with([
            'items.product', 'items.variant.attributeValues.attribute',
            'customer', 'branch', 'creator', 'allocations.payment',
        ])->findOrFail($saleId);

        return $this->success(
            new SaleResource($sale),
            'Receipt data retrieved successfully.'
        );
    }
}
