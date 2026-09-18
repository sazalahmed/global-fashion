<?php

namespace Modules\AiAssistant\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Modules\AiAssistant\Ai\Agents\ShoppingAssistant;
use Modules\AiAssistant\Http\Requests\ChatMessageRequest;
use Modules\AiAssistant\Http\Requests\PlaceOrderRequest;
use Modules\AiAssistant\Models\AiRequestLog;
use Modules\AiAssistant\Services\AiProviderResolver;
use Modules\Customer\Models\Area;
use Modules\Ecommerce\Models\ShippingZone;
use Modules\Ecommerce\Services\StorefrontService;
use Modules\Product\Models\Product;
use Modules\Variant\Models\ProductVariant;

class ChatController extends Controller
{
    /**
     * Hard ceiling for a single conversation's lifetime token usage.
     * When exceeded, we refuse and instruct the user to start a new chat —
     * caps cost exposure if a customer drives a runaway recursive loop.
     */
    protected const MAX_TOKENS_PER_CONVERSATION = 50_000;

    /**
     * Hard ceiling per minute per IP across all conversations (defense in
     * depth on top of the route's throttle:30,1). Stops a single attacker
     * from burning the free tier in one minute even if they rotate
     * conversation IDs.
     */
    protected const MAX_TOKENS_PER_MINUTE_PER_IP = 20_000;

    public function __construct(protected AiProviderResolver $resolver) {}

    /**
     * Stream a chat reply using the Vercel Data Protocol.
     * The frontend (Phase 7) consumes this via EventSource / fetch streaming.
     */
    public function stream(ChatMessageRequest $request): Responsable|JsonResponse
    {
        if (! $this->resolver->isEnabled()) {
            return response()->json([
                'error' => 'AI assistant is currently disabled by the store admin.',
            ], 503);
        }

        $customer = Auth::guard('customer')->user();
        $startedAt = microtime(true);
        $message = $request->input('message');
        $conversationId = $request->input('conversation_id');

        // The frontend reports the customer's "product focus stack" —
        // recently-shown product IDs in newest-first order. We validate
        // each ID against the active catalog and store the stack so the
        // agent's system prompt can resolve pronouns:
        //   "this/aita"          → top of stack
        //   "previous/age"       → stack[1]
        //   "the first one"      → stack last
        $rawStack = (array) $request->input('focus_stack', []);
        $validStack = [];
        foreach (array_slice($rawStack, 0, 5) as $id) {
            $product = \Modules\Product\Models\Product::query()->find((int) $id);
            if ($product) {
                $validStack[] = ['id' => $product->id, 'name' => $product->name];
            }
        }
        if ($validStack) {
            session([
                'ai_focus_stack' => $validStack,
                // Keep the legacy single-slot keys for tools that still
                // read them (SearchProducts / GetProductDetails write the
                // same keys when they emit cards).
                'ai_last_shown_product_id' => $validStack[0]['id'],
                'ai_last_shown_product_name' => $validStack[0]['name'],
            ]);
        }

        // Server-tracked ORDER LOOKUP slots. The model regularly forgot the
        // order number between turns and never re-called lookup_order once
        // the customer typed their phone. Mirror the product-focus-stack
        // approach: detect order numbers and BD phone numbers in the raw
        // user message ourselves, persist into session, and inject into
        // every prompt. The model just reads the slots — it can't lose them.
        $this->captureOrderLookupSlots((string) $message);

        if ($conversationId && $this->conversationTokensExceeded($conversationId)) {
            return response()->json([
                'error' => 'This conversation has hit its message limit. Please start a new chat.',
            ], 429);
        }

        if ($this->ipTokensThisMinuteExceeded($request->ip())) {
            return response()->json([
                'error' => 'Slow down — too many messages right now. Try again in a minute.',
            ], 429);
        }

        $agent = new ShoppingAssistant($customer);

        // The SDK's RemembersConversations trait persists ONLY when a user
        // object is supplied to forUser() / continue(). For guests we hand
        // it a synthetic participant tied to the storefront session so the
        // same browser keeps the same agent_conversations.user_id across
        // requests — letting guests resume threads and giving us a single
        // place to query conversation history.
        $participant = $customer ?: (object) [
            'id' => $this->guestParticipantId(),
        ];

        $pending = $conversationId
            ? $agent->continue($conversationId, as: $participant)
            : $agent->forUser($participant);

        // SDK exceptions thrown during streaming (rate limit, no credits)
        // are handled by the renderable callbacks in bootstrap/app.php,
        // which return clean JSON for /ai/* routes.
        return $pending
            ->stream(
                $message,
                provider: $this->resolver->chatModelsForFailover(),
            )
            ->then(function (StreamedAgentResponse $response) use ($customer, $startedAt) {
                $this->logUsage($response, $customer?->id, $startedAt);
            })
            ->usingVercelDataProtocol();
    }

    /**
     * Deterministic order-placement endpoint — bypasses the LLM entirely.
     *
     * The chat widget calls this when the customer submits the INFO_FORM
     * (after the agent has gathered all delivery details). It does the
     * exact same work StorefrontService::createOrder does for the regular
     * checkout page, so even if the LLM's stream dies mid-response the
     * order STILL goes into the database. Critical for revenue protection:
     * once the customer hits Send on the form, they expect a confirmed
     * order — anything less is a lost sale.
     *
     * The agent is only used for discovery + collecting info. Commit
     * always goes through here.
     */
    public function placeOrder(PlaceOrderRequest $request, StorefrontService $storefront): JsonResponse
    {
        if (! $this->resolver->isEnabled()) {
            return response()->json(['error' => 'AI assistant is currently disabled.'], 503);
        }

        $customer = Auth::guard('customer')->user();
        $validated = $request->validated();

        // Resolve district (accepts canonical name OR numeric id).
        $district = is_numeric($validated['district'])
            ? Area::query()->where('level', 'district')->find((int) $validated['district'])
            : Area::query()->where('level', 'district')->whereRaw('LOWER(name) = ?', [mb_strtolower($validated['district'])])->first();

        if (! $district) {
            return response()->json([
                'error' => "District \"{$validated['district']}\" not found. Please check the spelling.",
            ], 422);
        }

        // Build/update the cart. If product_id was supplied (customer
        // explicitly chose it via the chat), make sure it's in the cart.
        $cart = session('cart', []);
        if (! empty($validated['product_id'])) {
            $product = Product::query()->where('status', 'active')->find($validated['product_id']);
            if (! $product) {
                return response()->json(['error' => 'Selected product is no longer available.'], 422);
            }
            $qty = max(1, (int) ($validated['quantity'] ?? 1));
            $variantId = $validated['variant_id'] ?? null;
            $price = $storefront->calculateEffectivePrice($product);
            $variantName = null;
            if ($variantId) {
                $variant = ProductVariant::query()->find($variantId);
                if ($variant && $variant->product_id === $product->id) {
                    if ($variant->sell_price) {
                        $price = (float) $variant->sell_price;
                    }
                    $variantName = $variant->variant_name;
                }
            }
            $key = $variantId ? $product->id . '-' . $variantId : (string) $product->id;
            $cart[$key] = [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'variant_name' => $variantName,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $price,
                'sell_price' => (float) $product->sell_price,
                'image' => $product->image,
                'quantity' => $qty,
                'sku' => $product->sku,
            ];
        }

        if (empty($cart)) {
            return response()->json([
                'error' => 'Cart is empty. Please pick a product first.',
            ], 422);
        }

        // Re-validate prices vs the database (defense against session tamper).
        foreach ($cart as $key => $item) {
            $p = Product::query()->where('status', 'active')->find($item['product_id']);
            if (! $p) {
                unset($cart[$key]);
                continue;
            }
            $cart[$key]['price'] = $storefront->calculateEffectivePrice($p);
        }
        if (empty($cart)) {
            return response()->json([
                'error' => 'All cart items are no longer available.',
            ], 422);
        }
        session(['cart' => $cart]);

        $subtotal = $storefront->calculateCartSubtotal($cart);

        // Apply coupon if present (re-validate against current subtotal).
        $discount = 0.0;
        $couponCode = null;
        if ($couponSession = session('coupon')) {
            $coupon = $storefront->findValidCoupon($couponSession['code'] ?? '', $subtotal);
            if ($coupon) {
                $discount = $coupon->calculateDiscount($subtotal);
                $couponCode = $coupon->code;
            }
        }

        // Resolve shipping zone.
        $shippingCharge = 0.0;
        $shippingZone = null;
        foreach (ShippingZone::active()->get() as $zone) {
            if ($zone->containsDistrict($district->id)) {
                $shippingCharge = (float) $zone->flat_rate;
                $shippingZone = $zone;
                if ($zone->free_shipping_threshold && ($subtotal - $discount) >= (float) $zone->free_shipping_threshold) {
                    $shippingCharge = 0.0;
                }
                break;
            }
        }

        $shippingAddress = $validated['address'] . ', ' . $district->name;

        $orderData = [
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
            'customer_phone' => $validated['customer_phone'],
            'shipping_address' => $shippingAddress,
            'billing_address' => $shippingAddress,
            'payment_method' => 'cod',
            'coupon_code' => $couponCode,
            'notes' => 'Placed via AI chat assistant (deterministic path).',
        ];
        if ($customer) {
            $orderData['customer_id'] = $customer->id;
        }

        try {
            $order = $storefront->createOrder(
                $orderData,
                $cart,
                $subtotal,
                discountAmount: $discount,
                shippingCharge: $shippingCharge,
            );
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Order placement failed. Please try again.'], 500);
        }

        session()->forget(['cart', 'coupon']);

        // Server-side (CAPI) Purchase at placement — the AI flow never lands on the
        // success page with the checkout flash, so CAPI is the only Purchase signal
        // for these orders. Deferred + once-guarded inside the service.
        app(\Modules\Ecommerce\Services\TrackingService::class)
            ->reportPurchaseAtPlacement($order, $request);

        $eta = $shippingZone?->estimated_days;

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'grand_total' => currency_symbol() . ' ' . number_format($order->grand_total, 2),
            'grand_total_raw' => (float) $order->grand_total,
            'subtotal' => currency_symbol() . ' ' . number_format($subtotal, 2),
            'discount' => $discount > 0 ? currency_symbol() . ' ' . number_format($discount, 2) : null,
            'coupon_code' => $couponCode,
            'shipping' => $shippingCharge > 0 ? currency_symbol() . ' ' . number_format($shippingCharge, 2) : 'Free',
            'shipping_zone' => $shippingZone?->name,
            'estimated_delivery_days' => $eta,
            'payment_method' => 'Cash on Delivery',
            'payment_instructions' => sprintf(
                'Order #%s confirmed. Pay ' . currency_symbol() . ' %s in cash to the delivery agent when the order arrives at %s.',
                $order->order_number,
                number_format($order->grand_total, 2),
                $shippingAddress
            ),
            'success_url' => route('storefront.checkout.success', $order->order_number),
        ]);
    }

    /**
     * Return the customer's past conversations (most recent first).
     * Guests get an empty list.
     */
    public function history(Request $request): JsonResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return response()->json(['conversations' => []]);
        }

        $conversationsTable = config('ai.conversations.tables.conversations', 'agent_conversations');

        $rows = DB::table($conversationsTable)
            ->where('user_id', $customer->id)
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get(['id', 'title', 'created_at', 'updated_at']);

        return response()->json([
            'conversations' => $rows->map(fn ($row) => [
                'id' => $row->id,
                'title' => $row->title,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]),
        ]);
    }

    /**
     * Fetch the message history for a single conversation so the chat
     * widget can restore the thread after a page reload.
     *
     * Ownership rules:
     *   - Logged-in customer: must own the conversation (user_id match).
     *   - Guest: the conversation must be present in session('ai_guest_conversation_ids'),
     *     which the SDK call's then() callback registers when a new
     *     conversation_id is observed.
     */
    public function messages(Request $request, string $conversationId): JsonResponse
    {
        if (! preg_match('/^[a-zA-Z0-9\-]{20,40}$/', $conversationId)) {
            return response()->json(['messages' => []]);
        }

        $customer = Auth::guard('customer')->user();
        $conversationsTable = config('ai.conversations.tables.conversations', 'agent_conversations');
        $messagesTable = config('ai.conversations.tables.messages', 'agent_conversation_messages');

        // Query-builder values are not cast, and on some MySQL/PDO setups the
        // user_id comes back as a string ("5"), which would break the strict
        // comparisons below against integer ids. Normalise to int (keep null).
        $owner = DB::table($conversationsTable)->where('id', $conversationId)->value('user_id');
        $owner = $owner === null ? null : (int) $owner;

        $ownsIt = $customer
            ? ($owner === $customer->id)
            : ($owner === $this->guestParticipantId()
                || in_array($conversationId, (array) session('ai_guest_conversation_ids', []), true));

        if (! $ownsIt) {
            return response()->json(['messages' => []], 200);
        }

        $rows = DB::table($messagesTable)
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at')
            ->limit(200)
            ->get(['role', 'content', 'tool_results', 'created_at']);

        return response()->json([
            'conversation_id' => $conversationId,
            'messages' => $rows->map(function ($row) {
                $toolResults = $this->extractCardsFromToolResults($row->tool_results);
                return [
                    'role' => $row->role,
                    'content' => (string) $row->content,
                    'cards' => $toolResults,
                    'at' => $row->created_at,
                ];
            })->filter(fn ($m) => in_array($m['role'], ['user', 'assistant'], true))->values(),
        ]);
    }

    /**
     * Pull the most recent <<PRODUCT_CARDS:[...]>> JSON out of a stored
     * tool_results blob so the widget can re-render cards on restore.
     */
    protected function extractCardsFromToolResults(?string $blob): ?string
    {
        if (! $blob) {
            return null;
        }

        if (! preg_match('/<<PRODUCT_CARDS:(\[[\s\S]*\])>>/', $blob, $m)) {
            return null;
        }

        return $m[1];
    }

    /**
     * Pluck order numbers (ORD- or ECO- prefixed) and BD phone numbers
     * (^01[3-9]\d{8}$) out of the customer's raw message and push them
     * onto the server-side order-lookup stack. The top of each stack is
     * the active slot — what the agent will pass to lookup_order on the
     * next turn.
     */
    protected function captureOrderLookupSlots(string $message): void
    {
        $slots = (array) session('ai_order_lookup_stack', ['orders' => [], 'phones' => []]);
        $slots['orders'] = $slots['orders'] ?? [];
        $slots['phones'] = $slots['phones'] ?? [];

        if (preg_match_all('/\b((?:ORD|ECO)-[A-Z0-9\-]{4,})\b/i', $message, $m)) {
            foreach ($m[1] as $orderNumber) {
                $orderNumber = strtoupper(trim($orderNumber));
                $slots['orders'] = array_values(array_unique(array_merge([$orderNumber], array_diff($slots['orders'], [$orderNumber]))));
            }
        }

        if (preg_match_all('/\b(01[3-9]\d{8})\b/', $message, $m)) {
            foreach ($m[1] as $phone) {
                $slots['phones'] = array_values(array_unique(array_merge([$phone], array_diff($slots['phones'], [$phone]))));
            }
        }

        $slots['orders'] = array_slice($slots['orders'], 0, 3);
        $slots['phones'] = array_slice($slots['phones'], 0, 3);

        session(['ai_order_lookup_stack' => $slots]);
    }

    /**
     * Stable positive integer "user id" for a guest, derived from their
     * session id. The SDK's agent_conversations.user_id column is
     * unsignedBigInteger (no negatives allowed), so we pack the session
     * hash into the high range (>1 billion) to avoid any realistic
     * collision with a real customers.id auto-increment.
     *
     * Range used: 2_000_000_000 + (sha256 prefix mod 1_000_000_000)
     * → guest ids live in [2e9, 3e9), real customers stay below ~1e9.
     */
    protected function guestParticipantId(): int
    {
        $sid = session()->getId() ?: '';
        if ($sid === '') {
            return 2_000_000_001;
        }
        $hex = substr(hash('sha256', $sid), 0, 8);
        return 2_000_000_000 + ((int) hexdec($hex) % 1_000_000_000);
    }

    protected function conversationTokensExceeded(string $conversationId): bool
    {
        $sum = (int) AiRequestLog::query()
            ->where('conversation_id', $conversationId)
            ->where('operation', 'chat')
            ->sum('total_tokens');

        return $sum >= self::MAX_TOKENS_PER_CONVERSATION;
    }

    protected function ipTokensThisMinuteExceeded(?string $ip): bool
    {
        if (! $ip) {
            return false;
        }

        $sum = (int) AiRequestLog::query()
            ->where('operation', 'chat')
            ->whereJsonContains('meta->ip', $ip)
            ->where('created_at', '>=', now()->subMinute())
            ->sum('total_tokens');

        return $sum >= self::MAX_TOKENS_PER_MINUTE_PER_IP;
    }

    protected function logUsage(StreamedAgentResponse $response, ?int $customerId, float $startedAt): void
    {
        try {
            $usage = $response->usage ?? null;

            AiRequestLog::create([
                'conversation_id' => $response->conversationId ?? null,
                'customer_id' => $customerId,
                'operation' => 'chat',
                'provider' => $this->resolver->chatLab()->value,
                'model' => $this->resolver->chatModel(),
                'prompt_tokens' => $usage?->promptTokens ?? 0,
                'completion_tokens' => $usage?->completionTokens ?? 0,
                'total_tokens' => $usage?->totalTokens ?? 0,
                'latency_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'success' => true,
                'meta' => ['ip' => request()->ip()],
                'created_at' => now(),
            ]);

            // For guests: tag the conversation_id into session so the same
            // browser can resume on next page load. Logged-in customers
            // don't need this (user_id on the conversation is enough).
            if (! $customerId && ($cid = $response->conversationId ?? null)) {
                $ids = (array) session('ai_guest_conversation_ids', []);
                if (! in_array($cid, $ids, true)) {
                    $ids[] = $cid;
                    session(['ai_guest_conversation_ids' => array_slice($ids, -5)]);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
