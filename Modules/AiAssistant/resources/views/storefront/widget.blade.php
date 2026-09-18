{{--
    AI Shopping Assistant — Floating Chat Widget
    Included from Modules/Ecommerce/resources/views/storefront/layouts/master.blade.php
    Renders nothing if the assistant is disabled in settings.
    Uses inline SVG icons (not FA classes) so dynamically inserted icons
    render correctly even though the storefront uses FA5 JS+SVG replacement
    which only scans the initial DOM.
--}}
@php
    use Modules\Setting\Models\Setting;
    use Modules\Customer\Models\Area;
    $aiEnabled = (bool) Setting::get('ai_assistant', 'enabled', false);
    $voiceEnabled = filter_var(Setting::get('ai_assistant', 'voice_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
    // Preload the 64 BD districts so the inline form's district select
    // fills instantly without a second round-trip. ~1.5KB payload.
    $aiDistricts = $aiEnabled
        ? Area::query()
            ->where('level', 'district')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])
            ->all()
        : [];
    $aiRoutes = [
        'chat' => route('storefront.ai.chat'),
        'transcribe' => route('storefront.ai.transcribe'),
        'speak' => route('storefront.ai.speak'),
        'messages' => url('/ai/conversations/__ID__/messages'),
        'place_order' => route('storefront.ai.place-order'),
        'csrf' => csrf_token(),
        'voice' => $voiceEnabled,
        'districts' => $aiDistricts,
    ];
@endphp

@if($aiEnabled)
<link rel="stylesheet" href="{{ asset('website/assets/css/ai-chat.css') }}?v={{ filemtime(public_path('website/assets/css/ai-chat.css')) }}">

<div id="bp-chat-root" data-routes="{{ json_encode($aiRoutes) }}">
    <button type="button"
            id="bp-chat-fab"
            class="bp-chat-fab"
            aria-label="Open Shopping Assistant"
            aria-expanded="false">
        <svg class="bp-chat-icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
        </svg>
        <span class="bp-chat-fab-pulse" aria-hidden="true"></span>
    </button>

    <div id="bp-chat-panel" class="bp-chat-panel" aria-hidden="true" role="dialog" aria-label="Shopping Assistant">
        <div class="bp-chat-header" role="banner">
            <div class="bp-chat-header-info">
                <span class="bp-chat-avatar" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="10" rx="2"/>
                        <circle cx="12" cy="5" r="2"/>
                        <path d="M12 7v4"/>
                        <line x1="8" y1="16" x2="8" y2="16"/>
                        <line x1="16" y1="16" x2="16" y2="16"/>
                    </svg>
                </span>
                <div class="bp-chat-header-text">
                    <strong>Shopping Assistant</strong>
                    <small>Ask in Bangla or English</small>
                </div>
            </div>
            <div class="bp-chat-header-actions">
                <button type="button" class="bp-chat-newchat" id="bp-chat-newchat" aria-label="Start new chat" title="Start new chat">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 20h9"/>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                    </svg>
                </button>
                <button type="button" class="bp-chat-close" id="bp-chat-close" aria-label="Close">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="bp-chat-messages" id="bp-chat-messages" aria-live="polite">
            <div class="bp-chat-message bp-chat-message-assistant">
                <div class="bp-chat-bubble">
                    <span class="bp-chat-greeting-emoji">👋</span> Hi! Ask me about products, place an order, or check your order status.
                    <br><span class="bp-chat-hint">আপনি বাংলাতেও লিখতে পারেন।</span>
                </div>
            </div>
        </div>

        <form class="bp-chat-input-wrap" id="bp-chat-form" autocomplete="off">
            @if($voiceEnabled)
            <button type="button"
                    class="bp-chat-mic"
                    id="bp-chat-mic"
                    aria-label="Record voice"
                    title="Hold to record voice">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                    <line x1="12" y1="19" x2="12" y2="23"/>
                    <line x1="8" y1="23" x2="16" y2="23"/>
                </svg>
            </button>
            @endif
            <input type="text"
                   class="bp-chat-input"
                   id="bp-chat-input"
                   maxlength="2000"
                   placeholder="Ask anything..."
                   aria-label="Message">
            <button type="submit"
                    class="bp-chat-send"
                    id="bp-chat-send"
                    aria-label="Send">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </button>
        </form>
    </div>
</div>

<script src="{{ asset('website/assets/js/ai-chat.js') }}?v={{ filemtime(public_path('website/assets/js/ai-chat.js')) }}" defer></script>
@endif
