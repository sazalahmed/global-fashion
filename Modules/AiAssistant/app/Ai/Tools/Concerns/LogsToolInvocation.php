<?php

namespace Modules\AiAssistant\Ai\Tools\Concerns;

use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Models\AiRequestLog;

trait LogsToolInvocation
{
    /**
     * Persist a record of this tool invocation to ai_request_logs.
     *
     * Called from each tool's handle() at entry so we have a forensic
     * trail of every AI action against the customer/cart/orders. We
     * sanitize args before logging: any field that looks like a price,
     * key, or token is redacted to keep the log table safe to share.
     */
    protected function auditInvocation(Request $request, string $toolName): void
    {
        try {
            AiRequestLog::create([
                'customer_id' => Auth::guard('customer')->user()?->id,
                'operation' => 'tool:' . $toolName,
                'provider' => 'tool',
                'model' => $toolName,
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
                'latency_ms' => null,
                'success' => true,
                'meta' => [
                    'args' => $this->sanitizeArgs($request->all()),
                ],
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Redact any field that looks like a price/credential.
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    protected function sanitizeArgs(array $args): array
    {
        $blocklist = [
            'price', 'unit_price', 'sell_price', 'cost_price', 'discount',
            'grand_total', 'subtotal', 'shipping_charge',
            'password', 'api_key', 'token', 'secret', 'authorization',
        ];

        foreach ($args as $key => $value) {
            if (in_array(strtolower($key), $blocklist, true)) {
                $args[$key] = '[REDACTED]';
                continue;
            }
            if (is_array($value)) {
                $args[$key] = $this->sanitizeArgs($value);
            } elseif (is_string($value) && mb_strlen($value) > 500) {
                $args[$key] = mb_substr($value, 0, 500) . '…';
            }
        }

        return $args;
    }
}
