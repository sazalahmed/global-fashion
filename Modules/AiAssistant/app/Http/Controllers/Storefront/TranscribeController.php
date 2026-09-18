<?php

namespace Modules\AiAssistant\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Transcription;
use Modules\AiAssistant\Http\Requests\TranscribeAudioRequest;
use Modules\AiAssistant\Models\AiRequestLog;
use Modules\AiAssistant\Services\AiProviderResolver;

class TranscribeController extends Controller
{
    public function __construct(protected AiProviderResolver $resolver) {}

    /**
     * Transcribe an uploaded audio clip to text.
     *
     * Phase 7 widget records via MediaRecorder API and POSTs the blob here.
     * Returns plain text; the storefront pastes it into the chat input so
     * the user can edit before sending — never auto-sends transcripts.
     */
    public function store(TranscribeAudioRequest $request): JsonResponse
    {
        if (! $this->resolver->isEnabled()) {
            return response()->json([
                'error' => 'AI assistant is currently disabled by the store admin.',
            ], 503);
        }

        $lab = $this->resolver->sttLab();

        if ($lab === null) {
            return response()->json([
                'error' => 'STT is configured to run on-device. Use the browser Web Speech API instead.',
            ], 400);
        }

        $model = $this->resolver->sttModel();
        $startedAt = microtime(true);
        $customer = Auth::guard('customer')->user();
        $success = false;
        $errorMessage = null;
        $text = '';

        try {
            $pending = Transcription::fromUpload($request->file('audio'));

            if ($language = $request->input('language')) {
                $pending->language($language);
            }

            $response = $pending->generate($lab, $model);
            $text = trim((string) $response);
            $success = true;
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
        } finally {
            try {
                AiRequestLog::create([
                    'customer_id' => $customer?->id,
                    'operation' => 'transcription',
                    'provider' => $lab->value,
                    'model' => $model ?: 'auto',
                    'prompt_tokens' => 0,
                    'completion_tokens' => 0,
                    'total_tokens' => 0,
                    'latency_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                    'success' => $success,
                    'error' => $errorMessage,
                    'meta' => [
                        'file_size' => $request->file('audio')->getSize(),
                        'mime_type' => $request->file('audio')->getMimeType(),
                    ],
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (! $success) {
            return response()->json([
                'error' => 'Transcription failed. Please try recording again.',
            ], 502);
        }

        return response()->json(['text' => $text]);
    }
}
