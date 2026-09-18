<?php

namespace Modules\AiAssistant\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Enums\Lab;
use Modules\AiAssistant\Models\AiRequestLog;
use Modules\AiAssistant\Services\AiProviderResolver;
use Modules\Setting\Models\Setting;

class AiSettingsController extends Controller
{
    protected const ALLOWED_KEYS = [
        // Toggles
        'enabled', 'voice_enabled',
        // Chat
        'chat_provider', 'chat_model',
        'chat_fallback_provider', 'chat_fallback_model',
        // Embeddings
        'embedding_provider', 'embedding_model', 'embedding_dimensions',
        // STT
        'stt_provider', 'stt_model',
        // TTS
        'tts_provider', 'tts_model', 'tts_voice',
    ];

    public function __construct(protected AiProviderResolver $resolver) {}

    public function index(): View
    {
        $current = [];
        foreach (self::ALLOWED_KEYS as $key) {
            $current[$key] = Setting::get('ai_assistant', $key, '') ?? '';
        }

        $usage = $this->usageSummary();
        $keyStatus = $this->apiKeyStatus();

        return view('aiassistant::admin.settings', [
            'current' => $current,
            'usage' => $usage,
            'keyStatus' => $keyStatus,
            'providerOptions' => $this->providerOptions(),
            'modelOptions' => $this->modelOptions(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => 'nullable|in:0,1',
            'voice_enabled' => 'nullable|in:0,1',
            'chat_provider' => 'nullable|in:gemini,openai,deepseek',
            'chat_model' => 'nullable|string|max:60',
            'chat_fallback_provider' => 'nullable|in:,gemini,openai,deepseek',
            'chat_fallback_model' => 'nullable|string|max:60',
            'embedding_provider' => 'nullable|in:jina,gemini,openai',
            'embedding_model' => 'nullable|string|max:60',
            'embedding_dimensions' => 'nullable|integer|min:64|max:4096',
            'stt_provider' => 'nullable|in:gemini,openai,browser',
            'stt_model' => 'nullable|string|max:60',
            'tts_provider' => 'nullable|in:browser,gemini,openai',
            'tts_model' => 'nullable|string|max:60',
            'tts_voice' => 'nullable|string|max:60',
        ]);

        foreach (self::ALLOWED_KEYS as $key) {
            $value = $validated[$key] ?? null;
            if ($value === null) {
                // Boolean toggles default to "0" when unchecked (the checkbox
                // simply isn't submitted). Everything else clears to empty.
                $value = in_array($key, ['enabled', 'voice_enabled'], true) ? '0' : '';
            }
            Setting::set('ai_assistant', $key, (string) $value);
        }

        return redirect()
            ->route('admin.ai-assistant.settings')
            ->with('success', 'AI assistant settings saved.');
    }

    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'feature' => 'required|in:chat,embedding',
        ]);

        $started = microtime(true);

        try {
            if ($request->input('feature') === 'embedding') {
                $response = Embeddings::for(['ping'])
                    ->dimensions($this->resolver->embeddingDimensions())
                    ->generate($this->resolver->embeddingLab(), $this->resolver->embeddingModel());

                $dim = isset($response->embeddings[0]) ? count($response->embeddings[0]) : 0;
                return response()->json([
                    'ok' => true,
                    'latency_ms' => (int) ((microtime(true) - $started) * 1000),
                    'detail' => "Got embedding vector with {$dim} dimensions.",
                ]);
            }

            $agentClass = \Modules\AiAssistant\Ai\Agents\ShoppingAssistant::class;
            $reply = (string) (new $agentClass())->prompt(
                'Say "OK" if you can hear me. Just the two letters.',
                provider: $this->resolver->chatLab(),
                model: $this->resolver->chatModel(),
            );

            return response()->json([
                'ok' => true,
                'latency_ms' => (int) ((microtime(true) - $started) * 1000),
                'detail' => 'Model replied: ' . mb_substr($reply, 0, 100),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'latency_ms' => (int) ((microtime(true) - $started) * 1000),
                'detail' => $this->scrubError($e->getMessage()),
            ]);
        }
    }

    protected function usageSummary(): array
    {
        $today = AiRequestLog::query()->where('created_at', '>=', now()->startOfDay());
        $thirtyDays = AiRequestLog::query()->where('created_at', '>=', now()->subDays(30));

        return [
            'requests_today' => (int) (clone $today)->count(),
            'requests_30d' => (int) (clone $thirtyDays)->count(),
            'tokens_30d' => (int) (clone $thirtyDays)->sum('total_tokens'),
            'cost_bdt_30d' => (float) (clone $thirtyDays)->sum('cost_bdt'),
            'failures_today' => (int) (clone $today)->where('success', false)->count(),
            'by_provider_30d' => (clone $thirtyDays)
                ->selectRaw('provider, count(*) as cnt, sum(total_tokens) as tokens')
                ->groupBy('provider')
                ->get()
                ->map(fn ($r) => [
                    'provider' => $r->provider,
                    'requests' => (int) $r->cnt,
                    'tokens' => (int) $r->tokens,
                ])
                ->all(),
        ];
    }

    protected function apiKeyStatus(): array
    {
        $masked = fn ($v) => $v ? substr($v, 0, 6) . '…' . substr($v, -3) : null;

        return [
            'gemini' => $masked(config('ai.providers.gemini.key')),
            'openai' => $masked(config('ai.providers.openai.key')),
            'deepseek' => $masked(config('ai.providers.deepseek.key')),
            'jina' => $masked(config('ai.providers.jina.key')),
        ];
    }

    protected function providerOptions(): array
    {
        return [
            'chat' => ['gemini' => 'Gemini', 'openai' => 'OpenAI', 'deepseek' => 'DeepSeek'],
            'embedding' => ['jina' => 'Jina (best Bangla)', 'gemini' => 'Gemini', 'openai' => 'OpenAI'],
            'stt' => ['gemini' => 'Gemini', 'openai' => 'OpenAI Whisper', 'browser' => 'Browser (free, on-device)'],
            'tts' => ['browser' => 'Browser (free, on-device)', 'gemini' => 'Gemini', 'openai' => 'OpenAI'],
        ];
    }

    protected function modelOptions(): array
    {
        return [
            'gemini_chat' => ['gemini-2.5-flash', 'gemini-2.5-flash-lite', 'gemini-2.5-pro', 'gemini-2.0-flash'],
            'openai_chat' => ['gpt-4o-mini', 'gpt-4o'],
            'deepseek_chat' => ['deepseek-chat'],
            'gemini_emb' => ['text-embedding-004', 'gemini-embedding-001'],
            'jina_emb' => ['jina-embeddings-v3', 'jina-embeddings-v4'],
            'openai_emb' => ['text-embedding-3-small', 'text-embedding-3-large'],
        ];
    }

    protected function scrubError(string $msg): string
    {
        // Strip any API key fragments before showing to admin
        $msg = preg_replace('/AIza[0-9A-Za-z_\-]{20,}|sk-[0-9A-Za-z_\-]{20,}|jina_[0-9A-Za-z_\-]{20,}/', '[KEY]', $msg);
        return mb_substr((string) $msg, 0, 400);
    }
}
