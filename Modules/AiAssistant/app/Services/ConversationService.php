<?php

namespace Modules\AiAssistant\Services;

use Modules\AiAssistant\Models\AiRequestLog;

class ConversationService
{
    public function logRequest(array $data): AiRequestLog
    {
        return AiRequestLog::create([
            'conversation_id' => $data['conversation_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'operation' => $data['operation'],
            'provider' => $data['provider'],
            'model' => $data['model'],
            'prompt_tokens' => $data['prompt_tokens'] ?? 0,
            'completion_tokens' => $data['completion_tokens'] ?? 0,
            'total_tokens' => ($data['prompt_tokens'] ?? 0) + ($data['completion_tokens'] ?? 0),
            'cost_bdt' => $data['cost_bdt'] ?? 0,
            'latency_ms' => $data['latency_ms'] ?? null,
            'success' => $data['success'] ?? true,
            'error' => $data['error'] ?? null,
            'meta' => $data['meta'] ?? null,
            'created_at' => now(),
        ]);
    }

    public function startConversation(?int $customerId = null): string
    {
        // Phase 4 — wraps the SDK's RemembersConversations trait.
        throw new \LogicException('ConversationService::startConversation() — to be implemented in Phase 4.');
    }
}
