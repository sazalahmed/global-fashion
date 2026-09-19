<?php
namespace Modules\Sale\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Branch\Models\Branch;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerGroup;
use Modules\Customer\Services\CustomerService;
use Modules\Location\Models\District;
use Modules\Location\Models\Thana;
use Modules\Payment\Models\PaymentAccount;
use Modules\Product\Models\Product;
use Modules\Sale\Http\Requests\StoreSaleRequest;
use Modules\Sale\Http\Requests\UpdateSaleRequest;
use Modules\Sale\Models\Sale;
use Modules\Sale\Services\SaleService;

class SaleController extends Controller
{
    public function __construct(
        protected SaleService $saleService,
        protected CustomerService $customerService
    ) {}

    /**
     * Display a listing of sales.
     */
    public function index(Request $request)
    {
        bpAuthorize('sales.view');
        $filters = $request->all();
        // An explicit search is a lookup across ALL sales — a leftover status
        // tab or date range would silently hide matches (e.g. searching a
        // customer phone while on the Partial Cancelled tab), so search wins
        // over the status/date filters.
        if (filled($filters['search'] ?? null)) {
            unset($filters['status'], $filters['date_from'], $filters['date_to']);
        } else {
            // Sales list defaults to the Pending tab; the "All" tab (status=all)
            // clears the status filter to show every sale.
            $status = $request->input('status', 'pending');
            if ($status === 'all') {
                unset($filters['status']);
            } else {
                $filters['status'] = $status;
            }
        }
        $stats        = $this->saleService->getStats();
        $sales        = $this->saleService->list($filters);
        $listTotals   = $this->saleService->getListTotals($filters);
        $statusCounts = $this->saleService->getStatusCounts();
        $couriers     = \Modules\Ecommerce\Models\CourierProvider::query()
            ->where('is_active', true)
            ->whereNotNull('api_key')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        // Map the human-readable name stored in Settings → Courier → "Default
        // Courier Partner" to the courier_providers slug. Two of the option
        // labels in the settings form don't match courier_providers.name
        // exactly ("Steadfast Courier" vs "Steadfast", "Redx" vs "RedX"), so
        // a small static map is more reliable than a name match.
        $courierNameToSlug = [
            'Pathao Courier'    => 'pathao',
            'Steadfast Courier' => 'steadfast',
            'eCourier'          => 'ecourier',
            'Redx'              => 'redx',
            'Paperfly'          => 'paperfly',
            'Sundarban Courier' => 'sundarban',
            'SA Paribahan'      => 'sa_paribahan',
        ];
        $defaultCourierName = \Modules\Setting\Models\Setting::get('courier', 'default_courier');
        $defaultCourierSlug = $courierNameToSlug[$defaultCourierName] ?? null;
        $defaultCourier     = $defaultCourierSlug ? $couriers->firstWhere('slug', $defaultCourierSlug) : null;

        // Active web-guard users (admins/staff) assignable as sale owners.
        $assignableStaff = \App\Models\User::active()->orderBy('name')->get(['id', 'name']);

        return view('sale::index', compact('stats', 'sales', 'listTotals', 'statusCounts', 'couriers', 'defaultCourier', 'assignableStaff'));
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create()
    {
        bpAuthorize('sales.create');
        $customers = Customer::active()->get(['id', 'name', 'phone', 'address', 'shipping_address']);
        $branches  = Branch::all();
        $products  = Product::active()
            ->orderBy('name')
            ->with(['variants' => fn($q) => $q->active()->with('attributeValues.attribute')->withSum('warehouseStock as stock_qty', 'quantity')])
            ->get();

        // Decorate with the same storefront pricing (campaign + flash deal +
        // own discount, lowest price wins) so the order form can pre-fill each
        // line's discount to match the shop. Guarded: a decoration failure must
        // never break the create page — it just falls back to full price.
        try {
            app(\Modules\Ecommerce\Services\CampaignService::class)->decorate($products);
            app(\Modules\Ecommerce\Services\StorefrontService::class)->decorateFlashDeals($products);
        } catch (\Throwable $e) {
            \Log::warning('Sale create: product price decoration failed: ' . $e->getMessage());
        }

        $paymentAccounts = PaymentAccount::where('is_active', true)->get();
        $saleStatuses    = Sale::selectableStatuses();
        $districts       = District::where('is_active', true)->orderBy('district_name')->get(['id', 'district_name']);
        $customerGroups  = CustomerGroup::active()->orderBy('name')->get();

        // Rebuild the order line items after a validation failure (they're added
        // client-side, so withInput()/old() alone can't restore the rows).
        $prefillItems = collect(old('items', []))
            ->filter(fn($it) => ! empty($it['product_id']))
            ->map(function ($it) use ($products) {
                $product = $products->firstWhere('id', (int) $it['product_id']);

                return [
                    'id'            => (int) $it['product_id'],
                    'name'          => $product->name ?? ('Product #' . $it['product_id']),
                    'image'         => $product && $product->display_image ? upload_url($product->display_image) : null,
                    'model'         => $product->model ?? '',
                    'sku'           => $product->sku ?? '',
                    'price'         => $it['price'] ?? ($product->sell_price ?? 0),
                    'quantity'      => $it['quantity'] ?? 1,
                    'discount'      => $it['discount'] ?? 0,
                    'variant_id'    => $it['variant_id'] ?? null,
                    'variant_label' => $it['variant_label'] ?? null,
                ];
            })
            ->values()
            ->all();

        $comboCatalog = $this->buildComboCatalog();

        // Customer prior balances, shown in the order summary when a customer is
        // selected (display-only — see CustomerService::getActiveDueMap).
        $previousDueMap = $this->customerService->getActiveDueMap();

        return view('sale::create', compact(
            'customers', 'branches', 'products',
            'paymentAccounts', 'saleStatuses', 'districts', 'customerGroups', 'prefillItems', 'comboCatalog', 'previousDueMap'
        ));
    }

    /**
     * Combos the order form can add, each with its components and the combo's
     * effective price ALLOCATED across them (per single combo) — so picking a
     * combo expands into per-component sale lines whose prices sum to the combo
     * price. Allocation stays server-side (ComboService) as the single source
     * of truth; the JS just renders the rows.
     */
    private function buildComboCatalog(): array
    {
        $service = app(\Modules\Ecommerce\Services\ComboService::class);

        return \Modules\Ecommerce\Models\Combo::active()
            ->with(['items.product', 'items.variant.attributeValues.attribute'])
            ->orderBy('name')
            ->get()
            ->map(function ($combo) use ($service) {
                $alloc = $service->allocatePrices($combo, 1);

                return [
                    'id'    => $combo->id,
                    'name'  => $combo->name,
                    'price' => (float) $service->effectivePrice($combo),
                    'image' => $combo->thumbnail ? upload_url($combo->thumbnail) : null,
                    'items' => $combo->items->map(function ($ci) use ($alloc) {
                        $variantLabel = $ci->variant
                            ? $ci->variant->attributeValues->pluck('value')->filter()->implode(' / ')
                            : null;

                        return [
                            'product_id'    => $ci->product_id,
                            'product_name'  => $ci->product->name ?? ('Product #' . $ci->product_id),
                            'variant_id'    => $ci->variant_id,
                            'variant_label' => $variantLabel ?: null,
                            'quantity'      => (int) $ci->quantity,
                            'unit_price'    => round((float) ($alloc[$ci->id] ?? 0), 2),
                            'image'         => optional($ci->product)->display_image
                                ? upload_url($ci->product->display_image) : null,
                        ];
                    })->values(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Store a newly created sale in storage.
     */
    public function store(StoreSaleRequest $request)
    {
        bpAuthorize('sales.create');
        $validated = $request->validated();

        $rawItems = $validated['items'] ?? [];
        $payments = $validated['payments'] ?? [];
        unset($validated['items'], $validated['payments']);

        // Map form field names to service field names
        $items = array_map(fn($item) => [
            'product_id'      => $item['product_id'],
            'variant_id'      => $item['variant_id'] ?? null,
            'variant_label'   => $item['variant_label'] ?? null,
            'quantity'        => $item['quantity'],
            'batch_no'        => $item['batch_no'] ?? null,
            'expired_date'    => $item['expired_date'] ?? null,
            'unit_price'      => $item['price'],
            'discount_amount' => $item['discount'] ?? 0,
            'tax_amount'      => $item['tax'] ?? 0,
            // Combo provenance carried from the form (null for plain products).
            'combo_id'        => $item['combo_id'] ?? null,
            'combo_group'     => $item['combo_group'] ?? null,
            'combo_name'      => $item['combo_name'] ?? null,
            'combo_price'     => $item['combo_price'] ?? null,
        ], $rawItems);

        $validated['sale_date'] = $validated['invoice_date'];
        unset($validated['invoice_date']);

        if (empty($validated['source'])) {
            $validated['source'] = 'store';
        }

        $action = $validated['action'] ?? 'create';
        unset($validated['action']);

        // "Save Draft" always parks the sale as a draft, overriding the status
        // dropdown (which is required and defaults to "pending"). Otherwise honor
        // the chosen sale_status, falling back to confirmed when none was given.
        if ($action === 'draft') {
            $validated['status'] = 'draft';
            unset($validated['sale_status']);
        } elseif (empty($validated['sale_status'])) {
            $validated['status'] = 'confirmed';
        }

        $sale = $this->saleService->createSale($validated, $items, $payments);

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', __('Invoice created successfully.'));
    }

    /**
     * Display the specified sale.
     */
    public function show(Sale $sale)
    {
        bpAuthorize('sales.view');
        $sale = $this->saleService->find($sale->id);

        $couriers = \Modules\Ecommerce\Models\CourierProvider::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('sale::show', compact('sale', 'couriers'));
    }

    /**
     * Render a compact sale summary for the Quick View modal on the sales
     * index page. Returns a partial HTML view (not a full page) that is
     * injected into the modal body via AJAX.
     */
    public function quickView(Sale $sale)
    {
        bpAuthorize('sales.view');
        $sale = $this->saleService->find($sale->id);
        $sale->loadMissing(['district', 'thana']);

        // Bulk-aggregate the returned quantity per sale item so we can show a
        // "Returned" column in the line-items table without N+1 queries.
        if ($sale->items->isNotEmpty()) {
            $returned = \Illuminate\Support\Facades\DB::table('sale_return_items')
                ->whereIn('sale_item_id', $sale->items->pluck('id'))
                ->groupBy('sale_item_id')
                ->select('sale_item_id', \Illuminate\Support\Facades\DB::raw('SUM(quantity) as total'))
                ->pluck('total', 'sale_item_id');

            $sale->items->each(function ($item) use ($returned) {
                $item->returned_quantity = (int) ($returned[$item->id] ?? 0);
            });
        }

        return view('sale::partials.quick-view', compact('sale'));
    }

    /**
     * Show the form for editing the specified sale.
     */
    public function edit(Sale $sale)
    {
        bpAuthorize('sales.edit');
        $sale->load(['items.product.variants.attributeValues.attribute', 'items.variant', 'allocations.payment']);
        $customers = Customer::active()->get(['id', 'name', 'phone', 'address', 'shipping_address']);
        $branches  = Branch::all();
        $products  = Product::active()
            ->orderBy('name')
            ->with(['variants' => fn($q) => $q->active()->with('attributeValues.attribute')->withSum('warehouseStock as stock_qty', 'quantity')])
            ->get();

        $payments = \Modules\Payment\Models\Payment::whereHas(
            'allocations',
            fn($q) => $q->where('allocatable_type', Sale::class)->where('allocatable_id', $sale->id)
        )->with('paymentAccount')->orderBy('payment_date')->get();

        $paymentAccounts = \Modules\Payment\Models\PaymentAccount::active()
            ->with('bank')
            ->orderBy('account_type')
            ->orderBy('name')
            ->get();

        // Accounts this sale's existing payments were received on that are no
        // longer selectable (soft-deleted or deactivated). Rendered only into
        // the existing advance rows — without them the dropdown shows
        // "Select Account" and saving would strip the payment's account.
        $historicalAccounts = \Modules\Payment\Models\PaymentAccount::withTrashed()
            ->whereIn('id', $payments->pluck('payment_account_id')->filter()->unique()
                    ->diff($paymentAccounts->pluck('id')))
            ->get();

        $saleStatuses = Sale::selectableStatuses($sale->status);
        $districts    = District::where('is_active', true)->orderBy('district_name')->get(['id', 'district_name']);
        $thanas       = $sale->district_id
            ? Thana::where('district_id', $sale->district_id)->where('is_active', true)->orderBy('thana_name')->get(['id', 'thana_name'])
            : collect();
        $customerGroups = CustomerGroup::active()->orderBy('name')->get();

        // Order line items are rendered client-side (same widget as the create
        // page), so provide them as prefill data: the submitted values after a
        // validation error, otherwise the sale's existing items.
        $prefillItems = collect(old('items'))->isNotEmpty()
            ? collect(old('items'))
            ->filter(fn($it) => ! empty($it['product_id']))
            ->map(function ($it) use ($products) {
                $product = $products->firstWhere('id', (int) $it['product_id']);

                return [
                    'id'            => (int) $it['product_id'],
                    'name'          => $product->name ?? ('Product #' . $it['product_id']),
                    'image'         => $product && $product->display_image ? upload_url($product->display_image) : null,
                    'model'         => $product->model ?? '',
                    'sku'           => $product->sku ?? '',
                    'price'         => $it['price'] ?? ($product->sell_price ?? 0),
                    'quantity'      => $it['quantity'] ?? 1,
                    'discount'      => $it['discount'] ?? 0,
                    'variant_id'    => $it['variant_id'] ?? null,
                    'variant_label' => $it['variant_label'] ?? null,
                    'combo_id'      => $it['combo_id'] ?? null,
                    'combo_group'   => $it['combo_group'] ?? null,
                    'combo_name'    => $it['combo_name'] ?? null,
                    'combo_price'   => $it['combo_price'] ?? null,
                ];
            })
            ->values()
            ->all()
            : $sale->items
            ->map(function ($item) {
                $product = $item->product;

                return [
                    'id'            => $item->product_id,
                    'name'          => $item->product_name ?: ($product->name ?? ('Product #' . $item->product_id)),
                    'image'         => $product && $product->display_image ? upload_url($product->display_image) : null,
                    'model'         => $product->model ?? '',
                    'sku'           => $product->sku ?? '',
                    'price'         => (float) $item->unit_price,
                    'quantity'      => (int) $item->quantity,
                    'discount'      => (float) $item->discount_amount,
                    'variant_id'    => $item->variant_id,
                    'variant_label' => $item->variant_label,
                    'combo_id'      => $item->combo_id,
                    'combo_group'   => $item->combo_group,
                    'combo_name'    => $item->combo_name,
                    'combo_price'   => $item->combo_price !== null ? (float) $item->combo_price : null,
                ];
            })
            ->values()
            ->all();

        $comboCatalog = $this->buildComboCatalog();

        // Customer prior balances, excluding THIS sale so its own due is not
        // reported as "previous" (display-only — see getActiveDueMap).
        $previousDueMap = $this->customerService->getActiveDueMap($sale->id);

        return view('sale::edit', compact(
            'sale', 'customers', 'branches', 'products',
            'payments', 'paymentAccounts', 'historicalAccounts', 'saleStatuses', 'districts', 'thanas', 'customerGroups', 'prefillItems', 'comboCatalog', 'previousDueMap'
        ));
    }

    /**
     * Update the specified sale in storage.
     */
    public function update(UpdateSaleRequest $request, Sale $sale)
    {
        bpAuthorize('sales.edit');
        $validated = $request->validated();
        $items     = $validated['items'] ?? [];
        $payments  = $validated['payments'] ?? [];
        unset($validated['items'], $validated['payments'], $validated['action']);

        // Delivered is final — ignore any submitted status so the edit form
        // (whose status select is disabled) cannot move a delivered sale.
        if ($sale->status === 'delivered') {
            unset($validated['sale_status'], $validated['status']);
        }

        $this->saleService->updateSale($sale, $validated, $items, $payments);

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', __('Invoice updated successfully.'));
    }

    /**
     * Soft-delete the specified sale (reverses stock and financial effects).
     */
    public function destroy(Sale $sale)
    {
        bpAuthorize('sales.delete');

        // Delivered is final — deleting would silently reverse stock and
        // collected money. Reversals go through the Sale Return workflow.
        if ($sale->status === 'delivered') {
            return back()->with('error', __('Delivered sales cannot be deleted. Use Sale Return instead.'));
        }

        try {
            $this->saleService->deleteSale($sale);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sales.index')
            ->with('success', __('Sale deleted successfully.'));
    }

    /**
     * Display printable invoice view.
     */
    public function print(Sale $sale)
    {
        bpAuthorize('sales.view');
        $sale = $this->saleService->find($sale->id);

        return view('sale::invoice-print', compact('sale'));
    }

    /**
     * Download invoice as PDF.
     */
    public function pdf(Sale $sale)
    {
        bpAuthorize('sales.view');
        $sale = $this->saleService->find($sale->id);

        return $this->letterheadPdf($sale)->download("Invoice-{$sale->invoice_number}.pdf");
    }

    /**
     * The sale rendered as a letterhead PDF (same design as the print page).
     */
    private function letterheadPdf(Sale $sale): \Barryvdh\DomPDF\PDF
    {
        $brand = \Modules\Setting\Services\SettingService::printBranding(true);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('sale::invoice-print', [
            'sale'  => $sale,
            'isPdf' => true,
            'brand' => $brand,
        ])->setPaper('a4', 'portrait');
    }

    /**
     * Email invoice to customer.
     */
    public function email(Request $request, Sale $sale)
    {
        bpAuthorize('sales.edit');
        $sale = $this->saleService->find($sale->id);

        $email = $request->input('email') ?? $sale->customer?->email;

        if (! $email) {
            return back()->with('error', __('No email address found for this customer.'));
        }

        $pdf = $this->letterheadPdf($sale);

        \Illuminate\Support\Facades\Mail::raw(
            "Dear {$sale->customer->name},\n\nPlease find your invoice {$sale->invoice_number} attached.\n\nThank you for your business!",
            function ($message) use ($email, $sale, $pdf) {
                $bizName = \Modules\Setting\Models\Setting::get('business', 'company_name', 'BizPOS Pro');
                $message->to($email)
                    ->subject("Invoice {$sale->invoice_number} — {$bizName}")
                    ->attachData($pdf->output(), "Invoice-{$sale->invoice_number}.pdf", ['mime' => 'application/pdf']);
            }
        );

        return back()->with('success', __('Invoice emailed to customer successfully.'));
    }

    /**
     * Send invoice via SMS.
     */
    public function sms(Request $request, Sale $sale)
    {
        bpAuthorize('sales.edit');
        $phone = $request->input('phone') ?? $sale->customer?->phone;

        if (! $phone) {
            return back()->with('error', __('No phone number found for this customer.'));
        }

        $shareUrl = route('sales.share', $sale);
        $message  = "Invoice {$sale->invoice_number} — Total: " . currency_symbol() . " " . number_format($sale->grand_total) . ". View: {$shareUrl}";

        try {
            $gateway = app(\Modules\Marketing\Contracts\SmsGatewayInterface::class);
            $gateway->send($phone, $message);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send invoice SMS', [
                'sale_id' => $sale->id,
                'error'   => $e->getMessage(),
            ]);
            return back()->with('error', __('Failed to send SMS. Please check SMS gateway configuration.'));
        }

        return back()->with('success', __('Invoice SMS sent to customer.'));
    }

    /**
     * Generate shareable invoice link.
     */
    public function share(Sale $sale)
    {
        bpAuthorize('sales.view');
        return view('sale::invoice-share', compact('sale'));
    }

    /**
     * Bulk update sale statuses.
     */
    public function bulkStatus(Request $request)
    {
        bpAuthorize('sales.edit');
        $statuses = array_keys(Sale::STATUSES);

        $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:sales,id',
            'status' => ['required', 'string', 'in:' . implode(',', $statuses)],
        ]);

        // Per-sale so stock is moved (deduct/restore) idempotently via the
        // service, rather than a bare mass column update. Delivered sales are
        // final — their status only moves via the Sale Return workflow.
        $status  = $request->input('status');
        $sales   = Sale::whereIn('id', $request->input('ids'))->get();
        $skipped = $sales->where('status', 'delivered')->count();
        $sales->where('status', '!=', 'delivered')
            ->each(fn(Sale $sale) => $this->saleService->changeStatus($sale, $status));

        $message = __('Status updated successfully.');
        if ($skipped > 0) {
            $message .= ' ' . trans_choice(':count delivered sale(s) skipped — delivered sales cannot be changed.', $skipped, ['count' => $skipped]);
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    /**
     * Assign (or unassign) a staff owner to many sales at once. A null staff_id
     * clears the assignment. Staff are web-guard users (App\Models\User).
     */
    public function bulkAssign(Request $request)
    {
        bpAuthorize('sales.edit');
        $request->validate([
            'ids'      => 'required|array|min:1',
            'ids.*'    => 'integer|exists:sales,id',
            'staff_id' => 'nullable|integer|exists:users,id',
        ]);

        Sale::whereIn('id', $request->input('ids'))
            ->update(['assigned_to' => $request->input('staff_id')]);

        return response()->json(['success' => true, 'message' => __('Staff assignment updated successfully.')]);
    }

    /**
     * Assign (or unassign) a staff owner to a single sale. A null staff_id
     * clears the assignment.
     */
    public function assignStaff(Request $request, Sale $sale)
    {
        bpAuthorize('sales.edit');
        $request->validate([
            'staff_id' => 'nullable|integer|exists:users,id',
        ]);

        $sale->update(['assigned_to' => $request->input('staff_id')]);

        return response()->json(['success' => true, 'message' => __('Staff assignment updated successfully.')]);
    }

    /**
     * Assign a sale to a configured courier provider.
     *
     * If exactly one active + configured courier exists, the request body's
     * `courier_provider_id` is optional and that single courier is used.
     * Otherwise the caller must pick one.
     */
    public function sendToCourier(Request $request, Sale $sale)
    {
        bpAuthorize('sales.edit');
        $provider = $this->resolveCourierProvider($request);
        if ($provider instanceof \Illuminate\Http\RedirectResponse) {
            return $provider;
        }

        $result = $this->dispatchSaleToCourier($sale, $provider);
        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Inline status change for a single sale (per-row dropdown).
     */
    public function updateStatus(Request $request, Sale $sale)
    {
        bpAuthorize('sales.edit');
        $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_keys(Sale::STATUSES))],
        ]);

        // Delivered is final for admins — goods and money have both moved.
        // Any reversal must go through the Sale Return workflow instead.
        if ($sale->status === 'delivered') {
            return response()->json([
                'success' => false,
                'message' => __('This sale is already delivered — its status cannot be changed. Use Sale Return instead.'),
            ], 422);
        }

        // Delegate to the service so the status change also moves stock
        // (deduct on fulfilment, restore on cancel) idempotently.
        $sale = $this->saleService->changeStatus($sale, $request->input('status'));

        return response()->json([
            'success' => true,
            'message' => __('Status updated.'),
            'status'  => $sale->status,
            'label'   => Sale::STATUSES[$sale->status] ?? $sale->status,
        ]);
    }

    /**
     * Run a fraud check on the sale's customer phone.
     * Cache-first; pass ?refresh=1 to force a fresh BD Courier API call.
     */
    public function fraudCheck(Request $request, Sale $sale)
    {
        bpAuthorize('sales.view');
        $phone = $sale->customer?->phone ?? $sale->customer_phone_snapshot;
        if (! $phone) {
            return response()->json([
                'success' => false,
                'message' => __('No phone number available for this sale.'),
            ], 422);
        }

        $service = app(\Modules\Ecommerce\Services\FraudCheckService::class);
        $report  = $service->check($phone, forceRefresh: $request->boolean('refresh'));

        if (! $report) {
            return response()->json([
                'success' => false,
                'message' => __('Fraud check is unavailable. Set BD_COURIER_API_KEY or check the phone number.'),
            ], 503);
        }

        return response()->json([
            'success'    => true,
            'phone'      => $phone,
            'risk_level' => $service->getRiskLevel($report),
            'report'     => $report,
        ]);
    }

    /**
     * Pull the latest courier status from Steadfast for one sale, persist it,
     * and record a CourierTrackingEvent so the timeline reflects the update.
     */
    public function refreshCourierStatus(Sale $sale)
    {
        bpAuthorize('sales.edit');
        $courier = strtolower((string) $sale->courier_name);
        if ($courier !== 'steadfast') {
            return response()->json([
                'success' => false,
                'message' => __('Live status refresh is only available for Steadfast right now.'),
            ], 422);
        }

        $steadfast = app(\Modules\Ecommerce\Services\SteadfastApiService::class);
        if (! $steadfast->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => __('Steadfast is not configured.'),
            ], 422);
        }

        try {
            if ($sale->courier_consignment_id) {
                $response = $steadfast->client()->status()->getStatusByConsignmentId((int) $sale->courier_consignment_id);
            } elseif ($sale->invoice_number) {
                $response = $steadfast->client()->status()->getStatusByInvoice($sale->invoice_number);
            } else {
                return response()->json(['success' => false, 'message' => __('Sale has no consignment id or invoice.')], 422);
            }
            \Illuminate\Support\Facades\Log::info('Steadfast Live Status Response', (array) $response);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('Steadfast API error: :err', ['err' => $e->getMessage()]),
            ], 502);
        }

        $status   = $response['delivery_status'] ?? $response['status'] ?? null;
        $cid      = $response['consignment_id'] ?? $sale->courier_consignment_id;
        $tracking = $response['tracking_code'] ?? $sale->courier_tracking_code;

        $updates = ['courier_status_updated_at' => now()];
        if ($status !== null) {
            $updates['courier_status'] = $status;
        }
        if ($cid && empty($sale->courier_consignment_id)) {
            $updates['courier_consignment_id'] = (string) $cid;
        }
        if ($tracking && empty($sale->courier_tracking_code)) {
            $updates['courier_tracking_code'] = (string) $tracking;
        }
        if ($cid) {
            $updates['courier_tracking_url'] = 'https://steadfast.com.bd/user/consignment/' . $cid;
        }
        $sale->update($updates);

        // Append to timeline
        \Modules\Sale\Models\CourierTrackingEvent::create([
            'sale_id'          => $sale->id,
            'courier_provider' => 'steadfast',
            'event_type'       => 'manual_refresh',
            'status'           => $status,
            'message'          => __('Pulled live status'),
            'consignment_id'   => $cid ? (string) $cid : null,
            'payload'          => $response,
            'occurred_at'      => now(),
        ]);

        return response()->json([
            'success' => true,
            'status'  => $status,
            'message' => __('Status refreshed.'),
        ]);
    }

    /**
     * Manually link a Steadfast consignment ID to a sale, then immediately
     * pull its current status from the Steadfast API and persist all data.
     *
     * POST /sales/{sale}/link-consignment
     * Body: { consignment_id: "12345678", courier_name: "Steadfast Courier" }
     */
    public function linkConsignment(Request $request, Sale $sale)
    {
        bpAuthorize('sales.edit');

        $request->validate([
            'consignment_id' => ['required', 'string', 'max:100'],
            'courier_name'   => ['nullable', 'string', 'max:50'],
        ]);

        $cid         = trim($request->input('consignment_id'));
        $courierName = trim($request->input('courier_name', '')) ?: ($sale->courier_name ?: 'Steadfast Courier');

        $steadfast = app(\Modules\Ecommerce\Services\SteadfastApiService::class);
        if (! $steadfast->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => __('Steadfast is not configured. Set the API key & secret in Settings → Courier Providers.'),
            ], 422);
        }

        // Fetch live status from Steadfast by consignment ID.
        try {
            $response = $steadfast->client()->status()->getStatusByConsignmentId((int) $cid);
            \Illuminate\Support\Facades\Log::info('Steadfast Manual Link Response', (array) $response);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('Steadfast API error: :err', ['err' => $e->getMessage()]),
            ], 502);
        }

        // Steadfast wraps results in different keys depending on endpoint version.
        $data      = $response['consignment'] ?? $response['data'] ?? $response;
        $status    = $response['delivery_status'] ?? $data['delivery_status'] ?? $data['status'] ?? null;
        $tracking  = $data['tracking_code'] ?? null;
        $codAmount = isset($response['cod_amount']) && is_numeric($response['cod_amount'])
            ? (float) $response['cod_amount']
            : (isset($data['cod_amount']) && is_numeric($data['cod_amount']) ? (float) $data['cod_amount'] : null);
        $deliveryCharge = isset($response['delivery_charge']) && is_numeric($response['delivery_charge'])
            ? (float) $response['delivery_charge']
            : (isset($data['delivery_charge']) && is_numeric($data['delivery_charge']) ? (float) $data['delivery_charge'] : null);

        $updates = [
            'courier_name'              => $courierName,
            'courier_consignment_id'    => $cid,
            'courier_tracking_url'      => 'https://steadfast.com.bd/user/consignment/' . $cid,
            'courier_status_updated_at' => now(),
        ];

        if ($status !== null) {
            $updates['courier_status'] = $status;
        }
        if ($tracking) {
            $updates['courier_tracking_code'] = $tracking;
        }
        if ($deliveryCharge !== null) {
            $updates['courier_delivery_charge'] = $deliveryCharge;
        }

        // Only record collected COD amount for terminal delivery states.
        $deliveredStatuses = ['delivered', 'partial_delivered'];
        if ($codAmount !== null && in_array(strtolower((string) $status), $deliveredStatuses, true)) {
            $updates['courier_collected_amount'] = $codAmount;
        }

        $sale->update($updates);

        // Move sale workflow status to 'courier' if it's still pre-courier and we have a consignment.
        if (in_array($sale->status, ['pending', 'packing', 'draft', 'on_hold', 'incompleted'], true)) {
            $this->saleService->changeStatus($sale, 'courier');
        }

        // Recompute paid/due after the data import.
        $this->saleService->updatePaymentStatus($sale);

        // Audit trail entry.
        \Modules\Sale\Models\CourierTrackingEvent::create([
            'sale_id'          => $sale->id,
            'courier_provider' => 'steadfast',
            'event_type'       => 'manual_link',
            'status'           => $status,
            'message'          => __('Consignment manually linked and data fetched from Steadfast.'),
            'consignment_id'   => $cid,
            'payload'          => $response,
            'occurred_at'      => now(),
        ]);

        return response()->json([
            'success'        => true,
            'message'        => __('Consignment linked successfully. Status: :status', ['status' => $status ?? 'unknown']),
            'courier_status' => $status,
        ]);
    }

    /**
     * Bulk-assign multiple sales to a configured courier provider.
     */
    public function bulkSendToCourier(Request $request)
    {
        bpAuthorize('sales.edit');
        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:sales,id'],
        ]);

        $provider = $this->resolveCourierProvider($request);
        if ($provider instanceof \Illuminate\Http\RedirectResponse) {
            return response()->json([
                'success' => false,
                'message' => $provider->getSession()->get('error') ?: __('No active courier providers are configured.'),
            ], 422);
        }

        $sales    = Sale::whereIn('id', $request->input('ids'))->get();
        $count    = 0;
        $failures = [];

        foreach ($sales as $sale) {
            $result = $this->dispatchSaleToCourier($sale, $provider);
            if ($result['success']) {
                $count++;
            } else {
                $failures[] = ($sale->invoice_number ?: ('Sale #' . $sale->id)) . ': ' . $result['message'];
            }
        }

        $message = trans_choice(':count sale(s) sent to :courier.', $count, ['count' => $count, 'courier' => $provider->name]);
        if (! empty($failures)) {
            $message .= ' Failures: ' . implode(' | ', array_slice($failures, 0, 5));
            if (count($failures) > 5) {
                $message .= ' (+ ' . (count($failures) - 5) . ' more)';
            }
        }

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Send one sale to the chosen provider. For Steadfast (active+configured)
     * this actually places the parcel via API and stores consignment id +
     * tracking code. For other providers it just records the assignment.
     */
    private function dispatchSaleToCourier(Sale $sale, \Modules\Ecommerce\Models\CourierProvider $provider): array
    {
        // A finished sale must never be re-dispatched — it would place a new
        // parcel and flip the workflow status back to 'courier'. Mirrors the
        // $courierSendable check that hides the buttons on the sales list.
        if (in_array($sale->status, ['delivered', 'partial_cancelled', 'cancelled', 'returned', 'return_received'], true)) {
            return [
                'success' => false,
                'message' => __('Sale :inv is already :status — it cannot be sent to a courier.', [
                    'inv'    => $sale->invoice_number,
                    'status' => Sale::STATUSES[$sale->status] ?? $sale->status,
                ]),
            ];
        }

        // Courier metadata only — the 'courier' workflow status is applied via
        // changeStatus() below so stock is deducted idempotently.
        $updates = [
            'courier_name' => $provider->name,
        ];

        if ($provider->slug === 'steadfast') {
            $steadfast = app(\Modules\Ecommerce\Services\SteadfastApiService::class);
            if (! $steadfast->isConfigured()) {
                return ['success' => false, 'message' => __('Steadfast is missing API key or secret. Configure it in Settings → Courier Providers.')];
            }

            // Note and item description are admin-managed on the sale form and
            // sent to Steadfast straight from the database (see buildSteadfastOrderPayload).
            $payload = $this->buildSteadfastOrderPayload($sale);
            if (isset($payload['__error'])) {
                return ['success' => false, 'message' => $payload['__error']];
            }

            try {
                $response = $steadfast->client()->order()->placeOrder($payload);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Steadfast placeOrder failed', [
                    'sale_id' => $sale->id,
                    'invoice' => $sale->invoice_number,
                    'error'   => $e->getMessage(),
                ]);
                return ['success' => false, 'message' => __('Steadfast API error: :err', ['err' => $e->getMessage()])];
            }

            // Steadfast wraps the parcel in `consignment` (sometimes `data`).
            $consignment = $response['consignment'] ?? $response['data'] ?? $response;
            $cid         = $consignment['consignment_id'] ?? null;
            $tracking    = $consignment['tracking_code'] ?? null;

            if ($cid) {
                $updates['courier_consignment_id'] = (string) $cid;
            }
            if ($tracking) {
                $updates['courier_tracking_code'] = (string) $tracking;
            }
            if ($cid) {
                $updates['courier_tracking_url'] = 'https://steadfast.com.bd/user/consignment/' . $cid;
            }
            $updates['courier_status']            = $consignment['status'] ?? 'in_review';
            $updates['courier_status_updated_at'] = now();
        }

        $sale->update($updates);

        // Move to 'courier' through the service so stock is deducted once.
        $this->saleService->changeStatus($sale, 'courier');

        return [
            'success' => true,
            'message' => __('Sale sent to :courier.', ['courier' => $provider->name]),
        ];
    }

    /**
     * Build the Steadfast create_order payload from a Sale row.
     * Returns ['__error' => ...] on validation failure.
     */
    private function buildSteadfastOrderPayload(Sale $sale): array
    {
        $name  = $sale->customer?->name ?? $sale->customer_name_snapshot ?? 'Walk-in Customer';
        $phone = preg_replace('/[^0-9]/', '', (string) ($sale->customer?->phone ?? $sale->customer_phone_snapshot ?? ''));
        if (strlen($phone) === 13 && str_starts_with($phone, '880')) {
            $phone = '0' . substr($phone, 3);
        }
        $address = trim((string) $sale->customer_address);

        if (strlen($phone) !== 11) {
            return ['__error' => __('Customer phone must be 11 digits (Bangladeshi format).')];
        }
        if ($address === '') {
            return ['__error' => __('Customer address is required to ship via Steadfast.')];
        }

        // Steadfast accepts alphanumeric + hyphen/underscore — our invoice numbers match.
        $invoice = (string) $sale->invoice_number;

        // COD is what the courier collects from the customer (outstanding balance).
        $cod = max(0, (float) $sale->due_amount);

        // Sale note is the buyer-facing instruction; fall back to the internal
        // staff note only when no sale note was entered.
        $note = $sale->notes ?: $sale->staff_note;

        // Parcel contents description. Prefer the admin-entered item description
        // (managed on the sale form); fall back to an auto list of the items,
        // e.g. "2x GLOBAL Drop Shoulder (XL), 1x Polo Tee".
        $itemDescription = trim((string) $sale->item_description);
        if ($itemDescription === '') {
            $itemDescription = $sale->items->map(function ($item) {
                $label = $item->product_name ?? $item->product?->name ?? 'Item';
                if ($item->variant_label) {
                    $label .= ' (' . $item->variant_label . ')';
                }
                return $item->quantity . 'x ' . $label;
            })->implode(', ');
        }

        $payload = [
            'invoice'           => $invoice,
            'recipient_name'    => mb_substr($name, 0, 100),
            'recipient_phone'   => $phone,
            'recipient_address' => mb_substr($address, 0, 250),
            'cod_amount'        => $cod,
            'note'              => $note ? mb_substr($note, 0, 250) : null,
            'delivery_type'     => 0,
            'item_description'  => mb_substr($itemDescription, 0, 500),
        ];

        // Steadfast also accepts the destination district + police station
        // (thana). Send them so the parcel is routed correctly.
        if ($district = $sale->district?->district_name) {
            $payload['district'] = $district;
        }
        if ($policeStation = $sale->thana?->thana_name) {
            $payload['policestation'] = $policeStation;
        }

        return $payload;
    }

    /**
     * Resolve which CourierProvider this request targets. Returns either the
     * provider or a redirect with an error.
     */
    private function resolveCourierProvider(Request $request)
    {
        $activeCouriers = \Modules\Ecommerce\Models\CourierProvider::query()
            ->where('is_active', true)
            ->whereNotNull('api_key')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($activeCouriers->isEmpty()) {
            return back()->with('error', __('No active courier providers are configured. Configure one in eCommerce → Courier Providers.'));
        }

        if ($activeCouriers->count() === 1) {
            return $activeCouriers->first();
        }

        $request->validate([
            'courier_provider_id' => ['required', 'integer', 'exists:courier_providers,id'],
        ]);

        $provider = $activeCouriers->firstWhere('id', (int) $request->input('courier_provider_id'));
        if (! $provider) {
            return back()->with('error', __('Selected courier is not active or not configured.'));
        }

        return $provider;
    }

    /**
     * Bulk print invoices.
     */
    public function bulkPrint(Request $request)
    {
        bpAuthorize('sales.view');
        $ids   = explode(',', $request->input('ids', ''));
        $sales = Sale::with(['customer', 'items.product', 'items.variant.attributeValues.attribute'])
            ->whereIn('id', $ids)
            ->get();

        return view('sale::invoice-print', ['sales' => $sales, 'sale' => $sales->first()]);
    }

    /**
     * Courier shipping label for a single sale. Auto-opens the print dialog.
     */
    public function label(Sale $sale)
    {
        bpAuthorize('sales.view');
        $sale = $this->saleService->find($sale->id);

        return view('sale::label-print', ['sales' => collect([$sale]), 'autoPrint' => true]);
    }

    /**
     * Courier shipping labels for many selected sales — one 4x6in label each.
     */
    public function bulkLabel(Request $request)
    {
        bpAuthorize('sales.view');
        $ids   = array_filter(explode(',', $request->input('ids', '')));
        $sales = Sale::with('customer')->whereIn('id', $ids)->get();

        return view('sale::label-print', ['sales' => $sales, 'autoPrint' => false]);
    }
}
