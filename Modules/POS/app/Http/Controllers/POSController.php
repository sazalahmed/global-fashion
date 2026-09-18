<?php

namespace Modules\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Services\CustomerService;
use Modules\POS\Http\Requests\ProcessSaleRequest;
use Modules\POS\Http\Requests\QuickAddCustomerRequest;
use Modules\POS\Http\Requests\SavePosSettingsRequest;
use Modules\POS\Services\POSService;
use Modules\Sale\Models\Sale;

class POSController extends Controller
{
    public function __construct(
        protected POSService $posService,
        protected CustomerService $customerService
    ) {}

    /**
     * Display the POS terminal.
     */
    public function index()
    {
        $products = $this->posService->getPosProducts();
        $categories = $this->posService->getCategories();
        $paymentAccounts = \Modules\Payment\Models\PaymentAccount::active()
            ->with('bank')
            ->orderBy('account_type')
            ->get();

        $recentCustomers = $this->customerService->search('', 15)
            ->map(fn ($c) => [
                'id'         => $c->id,
                'name'       => $c->name,
                'phone'      => $c->phone,
                'due_amount' => $c->due_amount,
            ])->values();

        $allPosSettings = \Modules\Setting\Models\Setting::getGroup('pos');
        $posSettings = [
            'auto_print'            => (bool) \Modules\Setting\Models\Setting::get('invoice', 'auto_print', false),
            'pos_print_format'      => \Modules\Setting\Models\Setting::get('invoice', 'pos_print_format', 'pos_receipt'),
            'print_full_invoice'    => ($allPosSettings['print_full_invoice'] ?? '1') === '1',
            'print_thermal_receipt' => ($allPosSettings['print_thermal_receipt'] ?? '1') === '1',
            'sound_enabled'         => ($allPosSettings['sound_enabled'] ?? '1') === '1',
            'show_stock_qty'        => ($allPosSettings['show_stock_qty'] ?? '1') === '1',
        ];

        return view('pos::index', compact('products', 'categories', 'paymentAccounts', 'recentCustomers', 'posSettings'));
    }

    /**
     * Process a POS sale (AJAX).
     */
    public function processSale(ProcessSaleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $sale = $this->posService->processSale($validated);

            return response()->json([
                'success'     => true,
                'sale'        => [
                    'id'             => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                ],
                'receipt_url' => route('pos.receipt', $sale->id),
                'invoice_url' => route('sales.print', $sale->id),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('POS sale error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the sale.',
            ], 500);
        }
    }

    /**
     * Search products for POS (AJAX).
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $products = $this->posService->searchProducts(
            $request->input('q', '')
        );

        return response()->json($products);
    }

    /**
     * Get a product by barcode (AJAX).
     */
    public function getByBarcode(Request $request): JsonResponse
    {
        if (!$request->filled('barcode')) {
            return response()->json(['success' => false, 'message' => 'Barcode is required.'], 422);
        }

        $product = $this->posService->getProductByBarcode(
            $request->input('barcode', '')
        );

        if ($product === null) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $data = $product->toArray();
        $data['matched_variant_id'] = $product->matched_variant_id ?? null;

        return response()->json($data);
    }

    /**
     * Search customers for POS (AJAX).
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $customers = $this->customerService->search(
            (string) $request->input('q', '')
        );

        return response()->json($customers);
    }

    /**
     * Get customer advance balance (AJAX).
     */
    public function customerAdvance(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'customer_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

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

        return response()->json([
            'advance_balance' => $advanceBalance,
            'due_amount'      => $customer ? $customer->due_amount : 0,
        ]);
    }

    /**
     * Quick-add a customer from POS (AJAX).
     */
    public function quickAddCustomer(QuickAddCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $customer = $this->customerService->quickCreate($validated);

        return response()->json([
            'customer' => [
                'id'    => $customer->id,
                'name'  => $customer->name,
                'phone' => $customer->phone,
            ],
        ]);
    }

    /**
     * Display the receipt for a completed sale.
     */
    public function receipt(Sale $sale)
    {
        $sale->load(['items.variant.attributeValues.attribute', 'customer', 'branch', 'creator']);

        return view('pos::receipt', compact('sale'));
    }

    public function settings()
    {
        $posSettings = \Modules\Setting\Models\Setting::getGroup('pos');
        $customers = \Modules\Customer\Models\Customer::active()->orderBy('name')->get(['id', 'name']);

        return view('pos::settings', compact('posSettings', 'customers'));
    }

    public function saveSettings(SavePosSettingsRequest $request)
    {
        $keys = [
            'default_customer_id', 'default_payment_method', 'sound_enabled',
            'show_stock_qty', 'allow_negative_stock',
            'print_full_invoice', 'print_thermal_receipt',
            'receipt_header', 'receipt_footer', 'receipt_show_logo',
            'receipt_show_customer', 'receipt_show_barcode',
        ];

        $booleanKeys = [
            'sound_enabled', 'show_stock_qty', 'allow_negative_stock',
            'print_full_invoice', 'print_thermal_receipt',
            'receipt_show_logo', 'receipt_show_customer', 'receipt_show_barcode',
        ];

        foreach ($keys as $key) {
            $value = $request->input($key, '');
            if (in_array($key, $booleanKeys)) {
                $value = $request->boolean($key) ? '1' : '0';
            }
            \Modules\Setting\Models\Setting::set('pos', $key, $value);
        }

        return back()->with('success', __('POS settings saved successfully.'));
    }
}
