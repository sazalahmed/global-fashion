@extends('core::layouts.master')

@section('title', 'AI Assistant Settings')
@section('page-title', 'AI Assistant Settings')

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>AI Assistant</span>
@endsection

@section('page-actions')
    <button type="submit" form="ai-settings-form" class="bp-btn bp-btn-success">
        <i class="fa-solid fa-save me-1"></i> Save Settings
    </button>
@endsection

@section('content')
<form id="ai-settings-form" method="POST" action="{{ route('admin.ai-assistant.settings.update') }}">
    @csrf
    @method('PUT')

    {{-- Master switch + usage summary --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-power-off me-2"></i>Status</h5>
                </div>
                <div class="bp-card-body">
                    <div class="form-check form-switch fs-13 mb-3">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="enabled-switch" name="enabled" value="1"
                               {{ ($current['enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-600" for="enabled-switch">
                            <i class="fa-solid fa-comments me-1 text-primary"></i> Enable Chat (master switch)
                        </label>
                        <div class="text-muted fs-12 ms-4">
                            Master toggle for the storefront chat widget. When off, no widget renders and the scroll-to-top button reappears.
                        </div>
                    </div>
                    <div class="form-check form-switch fs-13">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="voice-switch" name="voice_enabled" value="1"
                               {{ ($current['voice_enabled'] ?? '1') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-600" for="voice-switch">
                            <i class="fa-solid fa-microphone me-1 text-primary"></i> Enable Voice (mic + Listen)
                        </label>
                        <div class="text-muted fs-12 ms-4">
                            Show the microphone (voice input) and Listen (voice output) buttons. Turn off to make the widget text-only.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-gauge me-2"></i>Usage (last 30 days)</h5>
                </div>
                <div class="bp-card-body">
                    <div class="row g-3 text-center">
                        <div class="col-3">
                            <div class="fs-13 text-muted">Today</div>
                            <div class="fw-800 fs-4">{{ number_format($usage['requests_today']) }}</div>
                            <div class="fs-11 text-muted">requests</div>
                        </div>
                        <div class="col-3">
                            <div class="fs-13 text-muted">30 days</div>
                            <div class="fw-800 fs-4">{{ number_format($usage['requests_30d']) }}</div>
                            <div class="fs-11 text-muted">requests</div>
                        </div>
                        <div class="col-3">
                            <div class="fs-13 text-muted">Tokens</div>
                            <div class="fw-800 fs-4">{{ number_format($usage['tokens_30d']) }}</div>
                            <div class="fs-11 text-muted">last 30d</div>
                        </div>
                        <div class="col-3">
                            <div class="fs-13 text-muted">Failures</div>
                            <div class="fw-800 fs-4 {{ $usage['failures_today'] ? 'text-danger' : '' }}">{{ $usage['failures_today'] }}</div>
                            <div class="fs-11 text-muted">today</div>
                        </div>
                    </div>
                    @if(!empty($usage['by_provider_30d']))
                    <hr class="my-3">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($usage['by_provider_30d'] as $row)
                            <span class="bp-badge bp-badge-secondary">
                                {{ $row['provider'] }}: {{ number_format($row['requests']) }} req
                            </span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bp-card">
        <div class="bp-card-header p-0">
            <ul class="nav nav-tabs bp-nav-tabs w-100" id="aiTabs" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-chat"><i class="fa-solid fa-comments me-1"></i> Chat</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-embed"><i class="fa-solid fa-vector-square me-1"></i> Embeddings</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-voice"><i class="fa-solid fa-microphone me-1"></i> Voice</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-keys"><i class="fa-solid fa-key me-1"></i> API Keys</a></li>
            </ul>
        </div>
        <div class="bp-card-body">
            <div class="tab-content">

                {{-- CHAT --}}
                <div class="tab-pane fade show active" id="tab-chat">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="bp-form-label">Chat Provider</label>
                            <select name="chat_provider" class="bp-form-select w-100">
                                @foreach($providerOptions['chat'] as $val => $label)
                                    <option value="{{ $val }}" {{ ($current['chat_provider'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Provider used for conversation. Gemini = free tier, DeepSeek = cheapest paid, OpenAI = highest quality.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="bp-form-label">Chat Model</label>
                            <input list="chat-models" name="chat_model" class="bp-form-control"
                                   value="{{ $current['chat_model'] ?? '' }}" placeholder="gemini-2.5-flash">
                            <datalist id="chat-models">
                                @foreach(array_merge($modelOptions['gemini_chat'], $modelOptions['openai_chat'], $modelOptions['deepseek_chat']) as $m)
                                    <option value="{{ $m }}">
                                @endforeach
                            </datalist>
                        </div>

                        <div class="col-md-6">
                            <label class="bp-form-label">Failover Provider <span class="text-muted fs-12">(optional)</span></label>
                            <select name="chat_fallback_provider" class="bp-form-select w-100">
                                <option value="">None</option>
                                @foreach($providerOptions['chat'] as $val => $label)
                                    <option value="{{ $val }}" {{ ($current['chat_fallback_provider'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Used automatically when the primary hits rate limit or transient errors.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="bp-form-label">Failover Model</label>
                            <input name="chat_fallback_model" class="bp-form-control"
                                   value="{{ $current['chat_fallback_model'] ?? '' }}" placeholder="deepseek-chat">
                        </div>

                        <div class="col-12">
                            <button type="button" class="bp-btn bp-btn-outline bp-btn-sm" data-test-feature="chat">
                                <i class="fa-solid fa-vial me-1"></i> Test Connection
                            </button>
                            <span class="ms-2" data-test-result="chat"></span>
                        </div>
                    </div>
                </div>

                {{-- EMBEDDINGS --}}
                <div class="tab-pane fade" id="tab-embed">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="bp-form-label">Embedding Provider</label>
                            <select name="embedding_provider" class="bp-form-select w-100">
                                @foreach($providerOptions['embedding'] as $val => $label)
                                    <option value="{{ $val }}" {{ ($current['embedding_provider'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Used to embed products and user search queries for semantic search.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="bp-form-label">Embedding Model</label>
                            <input list="emb-models" name="embedding_model" class="bp-form-control"
                                   value="{{ $current['embedding_model'] ?? '' }}" placeholder="jina-embeddings-v3">
                            <datalist id="emb-models">
                                @foreach(array_merge($modelOptions['jina_emb'], $modelOptions['gemini_emb'], $modelOptions['openai_emb']) as $m)
                                    <option value="{{ $m }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-2">
                            <label class="bp-form-label">Dimensions</label>
                            <input type="number" name="embedding_dimensions" class="bp-form-control"
                                   value="{{ $current['embedding_dimensions'] ?? '1024' }}" min="64" max="4096">
                        </div>

                        <div class="col-12">
                            <div class="alert alert-warning fs-13 mb-3">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                Changing the embedding provider or model invalidates existing product embeddings. Run
                                <code>php artisan ai:embed-products</code> after switching.
                            </div>
                            <button type="button" class="bp-btn bp-btn-outline bp-btn-sm" data-test-feature="embedding">
                                <i class="fa-solid fa-vial me-1"></i> Test Connection
                            </button>
                            <span class="ms-2" data-test-result="embedding"></span>
                        </div>
                    </div>
                </div>

                {{-- VOICE (STT + TTS) --}}
                <div class="tab-pane fade" id="tab-voice">
                    <h6 class="fw-700 mb-3"><i class="fa-solid fa-microphone-lines me-1"></i> Speech-to-Text (voice input)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="bp-form-label">STT Provider</label>
                            <select name="stt_provider" class="bp-form-select w-100">
                                @foreach($providerOptions['stt'] as $val => $label)
                                    <option value="{{ $val }}" {{ ($current['stt_provider'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="bp-form-label">STT Model</label>
                            <input name="stt_model" class="bp-form-control"
                                   value="{{ $current['stt_model'] ?? '' }}" placeholder="gemini-2.5-flash">
                            <small class="text-muted">Leave blank when Provider = "browser".</small>
                        </div>
                    </div>

                    <h6 class="fw-700 mb-3"><i class="fa-solid fa-volume-high me-1"></i> Text-to-Speech (voice output, optional)</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="bp-form-label">TTS Provider</label>
                            <select name="tts_provider" class="bp-form-select w-100">
                                @foreach($providerOptions['tts'] as $val => $label)
                                    <option value="{{ $val }}" {{ ($current['tts_provider'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">"Browser" = free, on-device speechSynthesis.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="bp-form-label">TTS Model</label>
                            <input name="tts_model" class="bp-form-control"
                                   value="{{ $current['tts_model'] ?? '' }}" placeholder="gemini-2.5-flash-preview-tts">
                        </div>
                        <div class="col-md-4">
                            <label class="bp-form-label">Voice <span class="text-muted fs-12">(optional)</span></label>
                            <input name="tts_voice" class="bp-form-control"
                                   value="{{ $current['tts_voice'] ?? '' }}" placeholder="e.g. nova">
                        </div>
                    </div>
                </div>

                {{-- API KEYS --}}
                <div class="tab-pane fade" id="tab-keys">
                    <div class="alert alert-info fs-13">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        API keys are loaded from <code>.env</code> for safety (encrypted at rest with your filesystem permissions). To rotate a key, edit <code>.env</code> and run <code>php artisan config:clear</code>.
                    </div>
                    <table class="bp-table">
                        <thead>
                            <tr>
                                <th>Provider</th>
                                <th>Status</th>
                                <th>Key (masked)</th>
                                <th>Used for</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(['gemini' => 'Gemini', 'openai' => 'OpenAI', 'deepseek' => 'DeepSeek', 'jina' => 'Jina'] as $k => $label)
                                <tr>
                                    <td class="fw-600">{{ $label }}</td>
                                    <td>
                                        @if($keyStatus[$k] ?? null)
                                            <span class="bp-badge bp-badge-success">SET</span>
                                        @else
                                            <span class="bp-badge bp-badge-secondary">EMPTY</span>
                                        @endif
                                    </td>
                                    <td class="text-muted">{{ $keyStatus[$k] ?? '—' }}</td>
                                    <td class="fs-12 text-muted">
                                        @switch($k)
                                            @case('gemini') Chat / STT / TTS (free tier) @break
                                            @case('openai') Chat / STT / TTS (paid) @break
                                            @case('deepseek') Chat fallback (paid, cheap) @break
                                            @case('jina') Embeddings (10M token tier) @break
                                        @endswitch
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
'use strict';
$(function () {
    $('[data-test-feature]').on('click', function () {
        const feature = $(this).data('test-feature');
        const $result = $('[data-test-result="' + feature + '"]');
        const $btn = $(this);
        $btn.prop('disabled', true);
        $result.html('<i class="fa-solid fa-spinner fa-spin"></i> Testing…');

        $.ajax({
            url: '{{ route('admin.ai-assistant.settings.test') }}',
            method: 'POST',
            data: { feature: feature },
            dataType: 'json',
        }).done(function (data) {
            if (data.ok) {
                $result.html('<span class="text-success"><i class="fa-solid fa-circle-check"></i> ' + data.detail + ' <span class="text-muted fs-12">(' + data.latency_ms + 'ms)</span></span>');
            } else {
                $result.html('<span class="text-danger"><i class="fa-solid fa-circle-xmark"></i> ' + data.detail + '</span>');
            }
        }).fail(function () {
            $result.html('<span class="text-danger"><i class="fa-solid fa-circle-xmark"></i> Network error.</span>');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
</script>
@endpush

@endsection
