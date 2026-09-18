<?php

namespace Modules\AiAssistant\Services;

use Laravel\Ai\Enums\Lab;
use Modules\Setting\Models\Setting;

class AiProviderResolver
{
    protected const GROUP = 'ai_assistant';

    public function chatLab(): Lab
    {
        return $this->labFromString($this->get('chat_provider', 'gemini'));
    }

    public function chatModel(): string
    {
        return $this->get('chat_model', 'gemini-2.5-flash');
    }

    /**
     * Provider/model list for chat with automatic failover.
     *
     * The Laravel AI SDK iterates this list on rate-limit or transient
     * errors. We default to Gemini free-tier first, DeepSeek paid second —
     * so a normal day costs $0 but customers never hit a hard 429 wall.
     *
     * @return array<string, string>  ['gemini' => 'gemini-2.5-flash', 'deepseek' => 'deepseek-chat']
     */
    public function chatModelsForFailover(): array
    {
        $primary = [$this->chatLab()->value => $this->chatModel()];

        $fallbackProvider = $this->get('chat_fallback_provider', '');
        if (! $fallbackProvider) {
            return $primary;
        }

        $fallbackModel = $this->get('chat_fallback_model', match ($fallbackProvider) {
            'gemini' => 'gemini-2.5-flash',
            'openai' => 'gpt-4o-mini',
            'deepseek' => 'deepseek-chat',
            default => null,
        });

        if (! $fallbackModel || $fallbackProvider === $this->chatLab()->value) {
            return $primary;
        }

        return $primary + [$fallbackProvider => $fallbackModel];
    }

    public function embeddingLab(): Lab
    {
        return $this->labFromString($this->get('embedding_provider', 'jina'));
    }

    public function embeddingModel(): string
    {
        return $this->get('embedding_model', 'jina-embeddings-v3');
    }

    public function embeddingDimensions(): int
    {
        return (int) $this->get('embedding_dimensions', '1024');
    }

    public function sttLab(): ?Lab
    {
        $provider = $this->get('stt_provider', 'gemini');

        return $provider === 'browser' ? null : $this->labFromString($provider);
    }

    public function sttModel(): ?string
    {
        $provider = $this->get('stt_provider', 'gemini');

        if ($provider === 'browser') {
            return null;
        }

        return $this->get('stt_model', match ($provider) {
            'gemini' => 'gemini-2.5-flash',
            'openai' => 'whisper-1',
            default => 'whisper-1',
        });
    }

    public function ttsLab(): ?Lab
    {
        $provider = $this->get('tts_provider', 'browser');

        return $provider === 'browser' ? null : $this->labFromString($provider);
    }

    public function ttsModel(): ?string
    {
        $provider = $this->get('tts_provider', 'browser');

        if ($provider === 'browser') {
            return null;
        }

        return $this->get('tts_model', match ($provider) {
            'gemini' => 'gemini-2.5-flash-preview-tts',
            'openai' => 'tts-1',
            default => 'tts-1',
        });
    }

    public function ttsVoice(): ?string
    {
        return $this->get('tts_voice', null);
    }

    public function isEnabled(): bool
    {
        return filter_var($this->get('enabled', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Whether voice input (microphone → STT) and voice output (Listen
     * button → TTS) should be exposed in the widget. Defaults to true
     * when the master switch is on, so existing deployments aren't
     * silently degraded.
     */
    public function isVoiceEnabled(): bool
    {
        return filter_var($this->get('voice_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        try {
            return Setting::get(self::GROUP, $key, $default) ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function labFromString(string $provider): Lab
    {
        return match ($provider) {
            'gemini' => Lab::Gemini,
            'openai' => Lab::OpenAI,
            'deepseek' => Lab::DeepSeek,
            'jina' => Lab::Jina,
            'groq' => Lab::Groq,
            default => throw new \InvalidArgumentException("Unknown AI provider: {$provider}"),
        };
    }
}
