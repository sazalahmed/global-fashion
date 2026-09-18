<?php

namespace Modules\Ecommerce\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Models\CourierProvider;
use Modules\Ecommerce\Models\EcommerceOrder;
use Modules\Sale\Models\CourierTrackingEvent;
use Modules\Sale\Models\Sale;
use Modules\Sale\Services\SaleService;

/**
 * Receives webhook notifications from Steadfast Courier.
 *
 * Steadfast's delivery webhook posts a flat JSON body:
 *   { consignment_id, invoice, status, cod_amount, updated_at }
 * (notification_type / tracking_message are optional and only sent by some
 * setups, so we infer the event from the presence of `status`.)
 *
 * Endpoint configured on the Steadfast dashboard:
 *   {APP_URL}/api/webhooks/steadfast
 * No auth token is verified — requests are accepted as long as an active
 * Steadfast CourierProvider row exists with an API key saved.
 */
class SteadfastWebhookController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService,
    ) {}

    /**
     * Map Steadfast delivery status values onto Sale workflow statuses.
     * Unmapped values (the *_approval_pending interim states, hold, in_review,
     * unknown) intentionally leave the sale status unchanged — we only record
     * the raw courier_status for them and wait for a terminal status.
     */
    private const STATUS_MAP = [
        'delivered'         => 'delivered',
        'partial_delivered' => 'partial_cancelled',
        'cancelled'         => 'cancelled',
        'pending'           => 'courier',
    ];

    public function handle(Request $request): JsonResponse
    {
        // Concise audit trail of every inbound webhook (no secrets).
        Log::info('Steadfast webhook', [
            'ip'   => $request->ip(),
            'body' => $request->all(),
        ]);

        $provider = CourierProvider::query()
            ->where('slug', 'steadfast')
            ->where('is_active', true)
            ->first();

        if (!$provider || !$provider->hasApiKey()) {
            return $this->error('Steadfast courier is not configured.', 401);
        }

        $payload = $request->all();

        $sale = $this->resolveSale($payload);
        if (!$sale) {
            Log::warning('Steadfast webhook: sale not found', ['payload' => $payload]);
            return $this->error('Order not found for the given invoice/consignment.', 404);
        }

        $type = $payload['notification_type'] ?? null;
        $hasStatus = isset($payload['status']) && $payload['status'] !== '';

        if ($type === 'tracking_update' || (!$hasStatus && !empty($payload['tracking_message']))) {
            $this->handleTrackingUpdate($sale, $payload);
        } elseif ($hasStatus || $type === 'delivery_status') {
            $this->handleDeliveryStatus($sale, $payload);
        } else {
            // Nothing actionable — record it so retries are acknowledged.
            $this->recordEvent($sale, 'unknown', $payload, null, $payload['tracking_message'] ?? null);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Webhook received successfully.',
        ]);
    }

    /**
     * Resolve the Sale a webhook refers to. The admin "Send to courier" path
     * sends the sale invoice_number; the ecommerce order dispatch sends the
     * order_number. Fall back to the consignment id so we match regardless.
     */
    private function resolveSale(array $payload): ?Sale
    {
        $invoice = $payload['invoice'] ?? null;

        if ($invoice) {
            $sale = Sale::where('invoice_number', $invoice)->first()
                ?? optional(EcommerceOrder::where('order_number', $invoice)->first())->sale
                ?? Sale::where('reference_number', $invoice)->first();

            if ($sale) {
                return $sale;
            }
        }

        if (!empty($payload['consignment_id'])) {
            return Sale::where('courier_consignment_id', (string) $payload['consignment_id'])->first();
        }

        return null;
    }

    private function handleDeliveryStatus(Sale $sale, array $payload): void
    {
        $incoming = strtolower((string) ($payload['status'] ?? ''));
        $mapped = self::STATUS_MAP[$incoming] ?? null;

        // Courier metadata only — the workflow status is moved separately via
        // changeStatus() so stock is deducted/restored idempotently.
        $meta = [
            'courier_name'              => 'Steadfast',
            'courier_status'            => $payload['status'] ?? null,
            'courier_status_updated_at' => now(),
            // Partial delivery = some items came back. The webhook has no line
            // items, so the returned stock can't be auto-restored — flag the
            // sale so staff file a SaleReturn. Any other terminal state clears it.
            'needs_return'              => $incoming === 'partial_delivered',
        ];
        // What the courier charges us for this consignment — stored for
        // cashflow reporting; no sale totals are derived from it.
        if (isset($payload['delivery_charge']) && is_numeric($payload['delivery_charge'])) {
            $meta['courier_delivery_charge'] = (float) $payload['delivery_charge'];
        }
        if (!empty($payload['consignment_id'])) {
            $meta['courier_consignment_id'] = (string) $payload['consignment_id'];
            $meta['courier_tracking_url'] = 'https://steadfast.com.bd/user/consignment/' . $payload['consignment_id'];
        }
        $sale->update($meta);

        // Route the status change through the central service so stock moves
        // (deduct on delivered, restore on cancelled) — idempotently.
        if ($mapped !== null && $mapped !== $sale->status) {
            $this->saleService->changeStatus($sale, $mapped);
        }

        // COD is only money in hand once the courier delivers. The webhook's
        // cod_amount is the parcel's COD *value* (present on pending/cancelled
        // events too), so it must NOT count as collected/paid until delivery.
        //   delivered / partial_delivered → record collected COD as paid
        //   anything else (pending, cancelled, unmapped) → nothing collected
        $this->applyCodPayment($sale, $incoming, $payload);

        $this->recordEvent($sale, 'delivery_status', $payload, $payload['status'] ?? null, $payload['tracking_message'] ?? null);
    }

    /**
     * Reflect courier-collected COD on the sale. Stored on the sale itself
     * (not as a Payment record) because the webhook has no auth user, payment
     * account, or customer party to build a journal entry from. The sale's
     * paid/due totals are then recomputed from all sources by SaleService.
     */
    private function applyCodPayment(Sale $sale, string $incomingStatus, array $payload): void
    {
        $delivered = in_array($incomingStatus, ['delivered', 'partial_delivered'], true);

        $cod = (array_key_exists('cod_amount', $payload) && is_numeric($payload['cod_amount']))
            ? (float) $payload['cod_amount']
            : 0.0;

        $collected = $delivered ? $cod : 0.0;       // 0 for pending/cancelled/unmapped
        $this->setCollectedAmount($sale, $collected);
    }

    /**
     * Record the amount the courier collected, then let SaleService recompute
     * paid/due/payment_status from ALL money sources. COD is only one source —
     * advance/partial payments recorded through the Payment module must not be
     * erased when the delivery webhook lands.
     */
    private function setCollectedAmount(Sale $sale, float $collected): void
    {
        $sale->update(['courier_collected_amount' => $collected]);

        $this->saleService->updatePaymentStatus($sale);

        // A delivered parcel settled below the sale total (door-step bargain)
        // is a price concession — auto-record the gap as a discount so it
        // doesn't linger as a phantom customer due. No-op for partial
        // deliveries (goods return via SaleReturn) and exact settlements.
        $this->saleService->settleCodShortfallAsDiscount($sale);
    }

    private function handleTrackingUpdate(Sale $sale, array $payload): void
    {
        $updates = ['courier_status_updated_at' => now()];
        if (!empty($payload['consignment_id']) && !$sale->courier_consignment_id) {
            $updates['courier_consignment_id'] = (string) $payload['consignment_id'];
            $updates['courier_tracking_url'] = 'https://steadfast.com.bd/user/consignment/' . $payload['consignment_id'];
        }
        if (!empty($payload['tracking_message'])) {
            $updates['courier_status'] = (string) $payload['tracking_message'];
        }
        $sale->update($updates);

        // Steadfast revises the collectable COD via a tracking message —
        // "Amount has been changed from 1770 to 50" — which is how a partial
        // delivery reports what was *actually* collected (the rest is returned).
        // Apply it as the collected/paid amount only once goods are delivered.
        $this->applyAmountChange($sale, $payload['tracking_message'] ?? '');

        $this->recordEvent($sale, 'tracking_update', $payload, null, $payload['tracking_message'] ?? null);
    }

    private function applyAmountChange(Sale $sale, string $message): void
    {
        // Collected only once goods are out: a full delivery, or a partial
        // (stored as partial_cancelled) where this message reports the real
        // amount actually collected.
        if (!in_array($sale->status, ['delivered', 'partial_cancelled'], true)) {
            return; // not collected yet — a pre-delivery COD revision, ignore for payment
        }
        if (preg_match('/Amount has been changed\s+from\s+"?[\d.]+"?\s+to\s+"?([\d.]+)"?/iu', $message, $m)) {
            $this->setCollectedAmount($sale, (float) $m[1]);
        }
    }

    private function recordEvent(Sale $sale, string $eventType, array $payload, ?string $status, ?string $message): void
    {
        $occurredAt = !empty($payload['updated_at'])
            ? \Carbon\Carbon::parse($payload['updated_at'])
            : now();

        CourierTrackingEvent::create([
            'sale_id'          => $sale->id,
            'courier_provider' => 'steadfast',
            'event_type'       => $eventType,
            'status'           => $status,
            'message'          => $message,
            'consignment_id'   => isset($payload['consignment_id']) ? (string) $payload['consignment_id'] : null,
            'payload'          => $payload,
            'occurred_at'      => $occurredAt,
        ]);

        Log::info('Steadfast ' . $eventType, [
            'sale_id'        => $sale->id,
            'invoice'        => $sale->invoice_number,
            'consignment_id' => $payload['consignment_id'] ?? null,
            'status'         => $status,
            'message'        => $message,
            'updated_at'     => $payload['updated_at'] ?? null,
        ]);
    }

    private function error(string $message, int $code): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
        ], $code);
    }
}
