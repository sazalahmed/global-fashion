<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Modules\AiAssistant\Models\AiHandoffRequest;
use Stringable;

/**
 * Escalate to a human staff member when the assistant can't help
 * (or the customer explicitly asks for one). Logs a row in
 * ai_handoff_requests so the admin sees it in their dashboard.
 *
 * Reasons to call this:
 *   - "talk to a human" / "manusher sathe kotha bolte chai"
 *   - Customer is angry or frustrated repeatedly
 *   - Complex return/refund request the assistant can't process
 *   - Anything where the assistant said "I'm not sure" twice
 */
class RequestHumanAgent implements Tool
{
    use LogsToolInvocation;

    public function description(): Stringable|string
    {
        return 'Escalate the conversation to a human staff member. Use when the customer asks '
            . 'to talk to a person, when the assistant cannot resolve their issue, or for '
            . 'sensitive issues (complaints, refunds). Creates an admin notification.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'request_human_agent');

        $customer = Auth::guard('customer')->user();
        $reason = trim((string) ($request['reason'] ?? 'Customer requested human assistance.'));
        $customerName = trim((string) ($request['customer_name'] ?? $customer?->name ?? ''));
        $customerPhone = trim((string) ($request['customer_phone'] ?? $customer?->phone ?? ''));

        if ($customerPhone === '' && ! $customer) {
            return json_encode([
                'success' => false,
                'message' => 'Need the customer\'s phone number first so the human team can call back. Ask the customer for it.',
            ]);
        }

        try {
            $req = AiHandoffRequest::create([
                'customer_id' => $customer?->id,
                'customer_name' => $customerName ?: null,
                'customer_phone' => $customerPhone ?: null,
                'reason' => mb_substr($reason, 0, 1000),
                'status' => 'open',
                'meta' => [
                    'ip' => request()->ip(),
                    'user_agent' => mb_substr((string) request()->userAgent(), 0, 200),
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
            return json_encode([
                'success' => false,
                'message' => 'Could not record the handoff request. Please share your phone and we will call you back.',
            ]);
        }

        return json_encode([
            'success' => true,
            'message' => 'Our team has been notified and will contact you within 15 minutes. Reference: HO-' . $req->id,
            'reference' => 'HO-' . $req->id,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'reason' => $schema->string()
                ->description('Brief reason for escalation (what the customer was trying to do, what got stuck).')
                ->required(),
            'customer_name' => $schema->string()
                ->description('Customer name if not logged in.'),
            'customer_phone' => $schema->string()
                ->description('Customer phone number (BD format) if not logged in. Required for guests.'),
        ];
    }
}
