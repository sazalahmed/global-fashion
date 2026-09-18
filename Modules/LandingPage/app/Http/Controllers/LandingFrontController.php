<?php

namespace Modules\LandingPage\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Customer\Models\Customer;
use Modules\Ecommerce\Models\EcommerceOrder;
use Modules\LandingPage\Http\Requests\SubmitOrderRequest;
use Modules\LandingPage\Services\LandingPageService;
use Modules\Product\Models\Product;

class LandingFrontController extends Controller
{
    public function __construct(
        private readonly LandingPageService $service,
    ) {}

    public function show()
    {
        $page = $this->service->getActive();

        if (!$page) {
            abort(404, 'No active landing page configured.');
        }

        $products = $page->products;
        $templateView = 'landingpage::templates.' . $page->template;

        return view($templateView, compact('page', 'products'));
    }

    public function submitOrder(SubmitOrderRequest $request)
    {
        $validated = $request->validated();

        $page = $this->service->find($validated['landing_page_id']);
        $product = Product::findOrFail($request->product_id);
        $quantity = $request->input('quantity', 1);
        $shippingZone = $request->input('shipping_zone', 'inside');
        $deliveryCharge = $shippingZone === 'inside'
            ? (float) $page->delivery_inside_dhaka
            : (float) $page->delivery_outside_dhaka;

        $unitPrice = $page->offer_price ?? $product->sell_price;
        $subtotal = $unitPrice * $quantity;
        $total = $subtotal + $deliveryCharge;

        // Find or create customer
        $customer = Customer::firstOrCreate(
            ['phone' => $request->customer_phone],
            [
                'name'    => $request->customer_name,
                'address' => $request->customer_address,
            ]
        );

        // Create ecommerce order
        $orderNumber = 'LP' . now()->format('YmdHis') . rand(100, 999);

        $order = EcommerceOrder::create([
            'order_number'     => $orderNumber,
            'customer_id'      => $customer->id,
            'customer_name'    => $request->customer_name,
            'customer_phone'   => $request->customer_phone,
            'shipping_address' => $request->customer_address . ', ' . $request->district,
            'status'           => 'pending',
            'payment_status'   => 'unpaid',
            'payment_method'   => $request->input('payment_method', 'cod'),
            'subtotal'         => $subtotal,
            'discount_amount'  => 0,
            'tax_amount'       => 0,
            'shipping_charge'  => $deliveryCharge,
            'grand_total'      => $total,
            'source'           => 'landing_page',
            'notes'            => "Landing page: {$page->name}",
        ]);

        $order->items()->create([
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'quantity'     => $quantity,
            'unit_price'   => $unitPrice,
            'subtotal'     => $subtotal,
        ]);

        return redirect()->route('landing.order.success', $orderNumber)
            ->with('success', __('আপনার অর্ডারটি সফলভাবে সম্পন্ন হয়েছে!'));
    }

    public function orderSuccess(string $orderNumber)
    {
        return view('landingpage::order-success', compact('orderNumber'));
    }
}
