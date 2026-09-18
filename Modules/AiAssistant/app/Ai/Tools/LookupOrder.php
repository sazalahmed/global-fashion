<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Modules\Ecommerce\Services\StorefrontService;
use Stringable;

class LookupOrder implements Tool
{
    use LogsToolInvocation;

    public function __construct(protected StorefrontService $storefront) {}

    public function description(): Stringable|string
    {
        return 'Get the status of an order by number. Requires the customer\'s phone for guest lookups.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'lookup_order');

        $orderNumber = trim((string) ($request['order_number'] ?? ''));
        $providedPhone = trim((string) ($request['customer_phone'] ?? ''));

        if ($orderNumber === '') {
            return json_encode(['success' => false, 'message' => 'Order number is required.']);
        }

        $order = $this->storefront->findOrderByNumber($orderNumber);
        if (! $order) {
            return json_encode(['success' => false, 'message' => "Order {$orderNumber} not found."]);
        }

        $customer = Auth::guard('customer')->user();
        $ownsOrder = ($customer && $order->customer_id === $customer->id)
            || ($providedPhone !== '' && $order->customer_phone === $providedPhone);

        if (! $ownsOrder) {
            // Distinguish "no phone supplied yet" (needs collection) from
            // "phone supplied but doesn't match" (real privacy block) so
            // the agent knows what to do next.
            $needsCollection = ($providedPhone === '');

            // On a confirmed mismatch, drop the bad phone from the slot
            // stack so the next turn doesn't re-inject the same wrong
            // phone (we'd loop forever otherwise). Keep the order_number
            // so the customer can retry with a different phone.
            if (! $needsCollection) {
                $stack = (array) Session::get('ai_order_lookup_stack', ['orders' => [], 'phones' => []]);
                $stack['phones'] = array_values(array_diff(($stack['phones'] ?? []), [$providedPhone]));
                Session::put('ai_order_lookup_stack', $stack);
            }
            return json_encode([
                'success' => false,
                'needs_phone' => $needsCollection,
                'order_number' => $orderNumber,
                'message' => $needsCollection
                    ? "PHONE_REQUIRED. The order is real but you haven't passed customer_phone yet. "
                        . "Step 1: Ask the customer for their phone (one short sentence). "
                        . "Step 2: As SOON as they reply with a phone, your VERY NEXT action "
                        . "is another lookup_order call with order_number='{$orderNumber}' "
                        . "AND customer_phone=<exact digits they typed>. "
                        . "Do not chat. Do not verify. Do not say 'phone looks correct'. "
                        . "Do not ask for the order number again — you have it. "
                        . "Just run lookup_order with both fields. The TOOL validates."
                    : "PHONE_MISMATCH. Customer's phone doesn't match what's on order {$orderNumber}. "
                        . "Tell the customer 'the phone you gave doesn't match our records, "
                        . "please double-check the number you used when placing the order'. "
                        . "Do NOT loop — wait for them to send a different phone before retrying.",
            ]);
        }

        // Lookup succeeded — clear the slot stack so we don't keep
        // injecting the same order into the next turn's prompt. If the
        // customer asks about another order, they'll mention it again
        // and the controller will repopulate.
        Session::forget('ai_order_lookup_stack');

        return json_encode([
            'success' => true,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'grand_total' => currency_symbol() . ' ' . number_format($order->grand_total, 2),
            'placed_on' => $order->created_at?->format('d M Y'),
            'shipped_on' => $order->shipped_at?->format('d M Y'),
            'delivered_on' => $order->delivered_at?->format('d M Y'),
            'tracking_number' => $order->tracking_number,
            'tracking_url' => $order->tracking_url,
            'courier_status' => $order->courier_status,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_number' => $schema->string()
                ->description('The order number (e.g. ECO-2026-00001) the user wants to look up.')
                ->required(),
            'customer_phone' => $schema->string()
                ->description('The phone number on the order. Required for guest customers to verify ownership.'),
        ];
    }
}
