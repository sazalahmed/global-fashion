<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Modules\Ecommerce\Models\EcommerceOrder;
use Stringable;

/**
 * List recent orders for the logged-in storefront customer.
 * Guests get an empty list — they should use lookup_order with their
 * order number + phone instead.
 */
class ListMyOrders implements Tool
{
    use LogsToolInvocation;

    public function description(): Stringable|string
    {
        return "List the logged-in customer's recent orders. Returns the last 10 orders "
            . "with order number, status, date, total, and item count. "
            . "Returns empty for guests — they must use lookup_order with their order number.";
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'list_my_orders');

        $customer = Auth::guard('customer')->user();
        if (! $customer) {
            return json_encode([
                'success' => false,
                'message' => 'You need to be logged in to see your orders. Ask the customer to log in, or use lookup_order with their order number + phone.',
                'orders' => [],
            ]);
        }

        $orders = EcommerceOrder::query()
            ->where('customer_id', $customer->id)
            ->withCount('items')
            ->latest('created_at')
            ->limit(10)
            ->get(['id', 'order_number', 'status', 'payment_status', 'grand_total', 'created_at']);

        if ($orders->isEmpty()) {
            return json_encode([
                'success' => true,
                'message' => "You haven't placed any orders yet.",
                'orders' => [],
            ]);
        }

        return json_encode([
            'success' => true,
            'count' => $orders->count(),
            'orders' => $orders->map(fn ($o) => [
                'order_number' => $o->order_number,
                'status' => $o->status,
                'payment_status' => $o->payment_status,
                'placed_on' => $o->created_at?->format('d M Y'),
                'total' => currency_symbol() . ' ' . number_format((float) $o->grand_total, 2),
                'item_count' => (int) $o->items_count,
            ])->all(),
        ], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
