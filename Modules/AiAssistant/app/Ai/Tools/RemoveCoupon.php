<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Stringable;

/**
 * Remove any coupon currently applied to the session cart. Idempotent
 * — fine to call when no coupon is set.
 */
class RemoveCoupon implements Tool
{
    use LogsToolInvocation;

    public function description(): Stringable|string
    {
        return 'Remove the currently applied coupon from the cart. Idempotent.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'remove_coupon');

        $previous = session('coupon');
        session()->forget('coupon');

        if (! $previous) {
            return json_encode([
                'success' => true,
                'message' => 'No coupon was applied.',
            ]);
        }

        return json_encode([
            'success' => true,
            'message' => "Removed coupon '{$previous['code']}'.",
            'removed_code' => $previous['code'],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
