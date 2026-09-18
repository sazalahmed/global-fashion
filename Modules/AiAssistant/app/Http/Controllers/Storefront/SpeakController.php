<?php

namespace Modules\AiAssistant\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Ai\Audio;
use Modules\AiAssistant\Http\Requests\SpeakRequest;
use Modules\AiAssistant\Models\AiRequestLog;
use Modules\AiAssistant\Services\AiProviderResolver;

class SpeakController extends Controller
{
    public function __construct(protected AiProviderResolver $resolver) {}

    /**
     * Generate audio for an AI message and return a public URL.
     *
     * When tts_provider is "browser" (the free, on-device default), this
     * route returns 410 Gone so the frontend falls back to the Web Speech
     * API instead of burning server cycles. When tts_provider is gemini or
     * openai, we synthesize on the server and stash the file in
     * storage/app/public/ai-tts so it survives a page reload.
     */
    public function store(SpeakRequest $request): JsonResponse
    {
        if (! $this->resolver->isEnabled()) {
            return response()->json(['error' => 'AI assistant is currently disabled.'], 503);
        }

        $lab = $this->resolver->ttsLab();

        if ($lab === null) {
            return response()->json([
                'mode' => 'browser',
                'message' => 'TTS is set to browser mode. Use the Web Speech API on the client.',
            ], 410);
        }

        $text = trim((string) $request->input('text'));
        $messageId = $request->input('message_id') ?: (string) Str::uuid();
        $startedAt = microtime(true);
        $customer = Auth::guard('customer')->user();
        $success = false;
        $errorMessage = null;
        $url = null;

        try {
            $pending = Audio::of($text);

            if ($voice = $this->resolver->ttsVoice()) {
                $pending->voice($voice);
            }

            $audio = $pending->generate($lab, $this->resolver->ttsModel());
            $extension = str_contains((string) $audio->mimeType(), 'wav') ? 'wav' : 'mp3';
            $relativePath = 'ai-tts/' . $messageId . '.' . $extension;

            $audio->storePubliclyAs($relativePath, disk: 'public');

            $url = url('storage/' . $relativePath);
            $success = true;
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
        } finally {
            try {
                AiRequestLog::create([
                    'customer_id' => $customer?->id,
                    'operation' => 'tts',
                    'provider' => $lab->value,
                    'model' => $this->resolver->ttsModel() ?: 'default',
                    'prompt_tokens' => 0,
                    'completion_tokens' => 0,
                    'total_tokens' => 0,
                    'latency_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                    'success' => $success,
                    'error' => $errorMessage,
                    'meta' => [
                        'text_length' => mb_strlen($text),
                        'message_id' => $messageId,
                    ],
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (! $success) {
            return response()->json(['error' => 'TTS generation failed.'], 502);
        }

        return response()->json([
            'mode' => 'server',
            'url' => $url,
            'message_id' => $messageId,
        ]);
    }
}
