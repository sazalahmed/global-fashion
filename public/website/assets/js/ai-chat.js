'use strict';

/**
 * AI Shopping Assistant — Storefront Widget
 *
 * - Streams chat replies via the Vercel Data Protocol (POST /ai/chat).
 * - Parses <<PRODUCT_CARDS:JSON>> blocks emitted by the SearchProducts tool
 *   and replaces them with rendered product cards.
 * - MediaRecorder records voice → POST /ai/transcribe → text fills the input.
 *   Never auto-sends; user confirms first.
 */
(function () {
    const root = document.getElementById('bp-chat-root');
    if (!root) {
        return;
    }

    const routes = JSON.parse(root.getAttribute('data-routes') || '{}');
    const voiceEnabled = !!routes.voice;
    const districts = Array.isArray(routes.districts) ? routes.districts : [];
    const fab = document.getElementById('bp-chat-fab');
    const panel = document.getElementById('bp-chat-panel');
    const closeBtn = document.getElementById('bp-chat-close');
    const newChatBtn = document.getElementById('bp-chat-newchat');
    const messagesEl = document.getElementById('bp-chat-messages');
    const form = document.getElementById('bp-chat-form');
    const input = document.getElementById('bp-chat-input');
    const sendBtn = document.getElementById('bp-chat-send');
    const micBtn = document.getElementById('bp-chat-mic'); // null when voice is disabled

    const STORAGE_KEY = 'bp_chat_conversation_id';

    let conversationId = null;
    let isStreaming = false;
    let mediaRecorder = null;
    let recordedChunks = [];
    let recordingStream = null;
    // Stack of product IDs the customer has seen, MOST RECENT FIRST.
    // Sent back to the server with every chat POST so the agent can
    // resolve pronouns reliably:
    //   "this / aita / ei"          → stack[0]   (most recent)
    //   "previous / age / shei ta"  → stack[1]
    //   "the first one"             → stack[stack.length-1]
    // Capped at 5 entries to stay cheap on tokens.
    const FOCUS_STACK_MAX = 5;
    let productFocusStack = [];

    function pushFocus(id) {
        const n = Number(id);
        if (!n || isNaN(n)) return;
        // Remove any existing entry for this id, then unshift to the front.
        productFocusStack = productFocusStack.filter(x => x !== n);
        productFocusStack.unshift(n);
        if (productFocusStack.length > FOCUS_STACK_MAX) {
            productFocusStack.length = FOCUS_STACK_MAX;
        }
    }

    // ── Persistence ────────────────────────────────────────────────────────
    function loadStoredConversationId() {
        try { return localStorage.getItem(STORAGE_KEY) || null; } catch (e) { return null; }
    }
    function saveConversationId(id) {
        try { if (id) localStorage.setItem(STORAGE_KEY, id); } catch (e) {}
    }
    function clearConversationId() {
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
    }

    // ── Panel open/close ───────────────────────────────────────────────────
    function openPanel() {
        panel.classList.add('bp-chat-open');
        panel.setAttribute('aria-hidden', 'false');
        fab.setAttribute('aria-expanded', 'true');
        setTimeout(() => input.focus(), 250);
    }

    function closePanel() {
        panel.classList.remove('bp-chat-open');
        panel.setAttribute('aria-hidden', 'true');
        fab.setAttribute('aria-expanded', 'false');
    }

    fab.addEventListener('click', openPanel);
    closeBtn.addEventListener('click', closePanel);

    if (newChatBtn) {
        newChatBtn.addEventListener('click', () => {
            if (isStreaming) return;
            if (!confirm('Start a new chat? Your current conversation will be cleared from this device.')) return;
            startFreshConversation();
        });
    }

    function startFreshConversation() {
        conversationId = null;
        clearConversationId();
        // Remove every message bubble except the initial greeting (first child).
        while (messagesEl.children.length > 1) {
            messagesEl.removeChild(messagesEl.lastChild);
        }
        scrollToBottom();
    }

    // ── Restore previous conversation on widget mount ──────────────────────
    async function restoreConversation() {
        const stored = loadStoredConversationId();
        if (!stored) return;
        if (!routes.messages) return;

        try {
            const url = routes.messages.replace('__ID__', encodeURIComponent(stored));
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const data = await res.json();
            const msgs = Array.isArray(data.messages) ? data.messages : [];
            if (msgs.length === 0) {
                // Conversation no longer accessible (logged out, cleared) — drop the stale id.
                clearConversationId();
                return;
            }
            conversationId = data.conversation_id || stored;
            for (const m of msgs) {
                renderRestoredMessage(m);
            }
            scrollToBottom();
        } catch (e) {
            // Ignore restore failures; user can still chat fresh.
        }
    }

    function renderRestoredMessage(m) {
        if (m.role === 'user') {
            appendUserMessage(m.content || '');
        } else if (m.role === 'assistant') {
            const wrap = document.createElement('div');
            wrap.className = 'bp-chat-message bp-chat-message-assistant';
            const cleanText = (m.content || '')
                .replace(/<<PRODUCT_CARDS:[\s\S]*?>>|<<PRODUCT_CARDS:[\s\S]*$/g, '')
                .replace(/<<PRODUCT_IMAGES:[\s\S]*?>>|<<PRODUCT_IMAGES:[\s\S]*$/g, '')
                .replace(/<<PRODUCT_FOCUS:[\s\S]*?>>|<<PRODUCT_FOCUS:[\s\S]*$/g, '')
                .trim();
            let html = '';
            if (cleanText) {
                const formatted = applyInlineMarkdown(escapeHtml(cleanText)).replace(/\n/g, '<br>');
                html += `<div>${formatted}</div>`;
            }
            if (m.cards) html += renderProductCards(m.cards);
            const bubble = document.createElement('div');
            bubble.className = 'bp-chat-bubble';
            bubble.innerHTML = html || '<em>(empty)</em>';
            wrap.appendChild(bubble);
            messagesEl.appendChild(wrap);
        }
    }

    // Kick off restore at startup
    restoreConversation();

    // ── Helpers ────────────────────────────────────────────────────────────
    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    /**
     * Convert a SAFE subset of markdown to HTML.
     * Input must be ALREADY escaped (call escapeHtml first).
     * Supports:
     *   - **bold** → <strong>
     *   - *italic* → <em> (single asterisk, no whitespace inside)
     *   - [text](url) → <a target="_blank"> with URL scheme validation
     *   - `code` → <code>
     * Anything else (lists, headers, images, html) is left as plain text.
     */
    function applyInlineMarkdown(escaped) {
        let out = escaped;
        // Bold first (so the inner asterisks don't trigger italic)
        out = out.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
        // Italic: single * around non-whitespace
        out = out.replace(/(^|[^*])\*([^*\s][^*\n]*?)\*(?!\*)/g, '$1<em>$2</em>');
        // Inline code
        out = out.replace(/`([^`\n]+)`/g, '<code>$1</code>');
        // Markdown links — validate URL scheme so we never render javascript: etc.
        out = out.replace(/\[([^\]\n]+)\]\(([^)\s]+)\)/g, (match, text, url) => {
            const safe = safeUrl(url, null);
            if (!safe) return text; // strip the URL part if it's unsafe
            return `<a href="${safe}" target="_blank" rel="noopener">${text}</a>`;
        });
        return out;
    }

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function appendUserMessage(text) {
        const wrap = document.createElement('div');
        wrap.className = 'bp-chat-message bp-chat-message-user';
        wrap.innerHTML = `<div class="bp-chat-bubble">${escapeHtml(text)}</div>`;
        messagesEl.appendChild(wrap);
        scrollToBottom();
    }

    function appendAssistantPlaceholder() {
        const wrap = document.createElement('div');
        wrap.className = 'bp-chat-message bp-chat-message-assistant';
        wrap.innerHTML = `
            <div class="bp-chat-bubble">
                <div class="bp-chat-typing"><span></span><span></span><span></span></div>
            </div>`;
        messagesEl.appendChild(wrap);
        scrollToBottom();
        return wrap.querySelector('.bp-chat-bubble');
    }

    function appendErrorBubble(text) {
        const wrap = document.createElement('div');
        wrap.className = 'bp-chat-message bp-chat-message-assistant';
        wrap.innerHTML = `<div class="bp-chat-bubble bp-chat-bubble-error">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:4px;">
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>${escapeHtml(text)}</div>`;
        messagesEl.appendChild(wrap);
        scrollToBottom();
    }

    // ── PRODUCT_CARDS rendering ────────────────────────────────────────────
    // Cards are sourced from tool-output-available events (always complete),
    // not from the assistant's reproduced text (Gemini sometimes truncates
    // mid-JSON when the cards list is long).

    function safeUrl(url, fallback) {
        // Block any javascript:, data:, vbscript:, file: scheme injected via
        // a poisoned tool output. Allow only http(s) and same-origin paths.
        if (typeof url !== 'string') return fallback;
        const trimmed = url.trim();
        if (!trimmed) return fallback;
        const lower = trimmed.toLowerCase();
        if (lower.startsWith('javascript:') || lower.startsWith('vbscript:') ||
            lower.startsWith('data:') || lower.startsWith('file:')) {
            return fallback;
        }
        return trimmed;
    }

    function renderProductCards(json) {
        let cards;
        try {
            cards = JSON.parse(json);
        } catch (e) {
            return '';
        }
        if (!Array.isArray(cards) || cards.length === 0) {
            return '';
        }
        // Push EVERY card onto the focus stack, in REVERSE order so the
        // first card (the "primary" hit) ends up on top after all pushes.
        for (let i = cards.length - 1; i >= 0; i--) {
            if (cards[i] && cards[i].id) pushFocus(cards[i].id);
        }
        const items = cards.map(c => {
            const img = escapeHtml(safeUrl(c.image, '/website/assets/images/placeholder.png'));
            const name = escapeHtml(c.name || 'Product');
            const price = escapeHtml(c.price || '');
            const url = escapeHtml(safeUrl(c.url, '#'));
            // View opens in a new tab so the chat conversation isn't lost when
            // the customer wants to read the product page. rel="noopener" is
            // standard for any user-driven _blank target — prevents the new
            // tab from controlling our window.opener.
            return `
                <div class="bp-chat-product">
                    <a class="bp-chat-product-img-link" href="${url}" target="_blank" rel="noopener">
                        <img class="bp-chat-product-img" src="${img}" alt="${name}" loading="lazy">
                    </a>
                    <div class="bp-chat-product-body">
                        <div class="bp-chat-product-name">${name}</div>
                        <div class="bp-chat-product-price">${price}</div>
                    </div>
                    <div class="bp-chat-product-actions">
                        <a class="bp-chat-product-btn" href="${url}" target="_blank" rel="noopener">View</a>
                        <button type="button"
                                class="bp-chat-product-btn bp-chat-product-btn-primary"
                                data-buy-product-id="${escapeHtml(String(c.id))}"
                                data-buy-product-name="${name}">
                            Buy
                        </button>
                    </div>
                </div>`;
        }).join('');
        return `<div class="bp-chat-product-cards">${items}</div>`;
    }

    // ── Vercel AI SDK Data Stream v2 parser ────────────────────────────────
    // Format: SSE-style "data: {json}" lines.
    // We pull two things from the stream:
    //   1. text-delta events → the agent's conversational reply
    //   2. tool-output-available events with a PRODUCT_CARDS block → the
    //      authoritative product list. We render cards from THIS source
    //      (always complete) rather than the agent's text reproduction
    //      (which Gemini sometimes truncates mid-JSON for long lists).
    function parseVercelLine(line) {
        if (!line.startsWith('data:')) return null;
        const payload = line.slice(5).trim();
        if (!payload || payload === '[DONE]') return null;
        let evt;
        try { evt = JSON.parse(payload); } catch (e) { return null; }
        return evt;
    }

    function extractToolCards(evt) {
        if (!evt || evt.type !== 'tool-output-available') return null;
        const out = typeof evt.output === 'string' ? evt.output : '';
        if (!out) return null;
        const m = out.match(/<<PRODUCT_CARDS:(\[[\s\S]*?\])>>/);
        if (!m) return null;
        return m[1];
    }

    function extractToolImages(evt) {
        if (!evt || evt.type !== 'tool-output-available') return null;
        const out = typeof evt.output === 'string' ? evt.output : '';
        if (!out) return null;
        const m = out.match(/<<PRODUCT_IMAGES:(\[[\s\S]*?\])>>/);
        if (!m) return null;
        return m[1];
    }

    // Tools that focus the conversation on a single product (get_product_details,
    // add_to_cart) emit <<PRODUCT_FOCUS:{"id":N,"name":"X"}>> so the frontend
    // can push the id onto productFocusStack. The deterministic place-order
    // path reads stack[0] when the customer submits the delivery form; without
    // this signal the order would commit against whatever product was at the
    // top of an earlier SearchProducts result.
    function extractToolFocus(evt) {
        if (!evt || evt.type !== 'tool-output-available') return null;
        const out = typeof evt.output === 'string' ? evt.output : '';
        if (!out) return null;
        const m = out.match(/<<PRODUCT_FOCUS:(\{[\s\S]*?\})>>/);
        if (!m) return null;
        try {
            const obj = JSON.parse(m[1]);
            const id = Number(obj.id);
            return id ? id : null;
        } catch (e) { return null; }
    }

    // INFO_FORM can come from EITHER a tool-output OR (more commonly) the
    // agent's own text-delta stream. We scan fullText below in renderBubble.
    const INFO_FORM_RE = /<<INFO_FORM:(\{[\s\S]*?\})>>/;

    function renderInfoForm(json) {
        let cfg;
        try { cfg = JSON.parse(json); } catch (e) { return ''; }
        const fields = Array.isArray(cfg.fields) ? cfg.fields : [];
        if (fields.length === 0) return '';
        const title = escapeHtml(cfg.title || 'Please fill in your details');
        const submitLabel = escapeHtml(cfg.submit_label || 'Send');

        const inputs = fields.map(f => {
            const fname = (f.name || '').toLowerCase();
            const name = escapeHtml(f.name || '');
            const label = escapeHtml(f.label || f.name || '');
            const placeholder = escapeHtml(f.placeholder || '');
            const value = escapeHtml(f.value || '');
            const required = f.required === false ? '' : 'required';

            // Auto-render a district select when the field name is "district"
            // OR when the agent explicitly sets type="select" + name=district.
            // Districts come pre-loaded from the page bootstrap (no AJAX).
            const isDistrictSelect = (fname === 'district' || f.type === 'select') && districts.length > 0;
            if (isDistrictSelect) {
                const opts = ['<option value="">— ' + (label || 'Select') + ' —</option>']
                    .concat(districts.map(d => {
                        const dn = escapeHtml(d.name);
                        const sel = String(d.name).toLowerCase() === String(value).toLowerCase() ? ' selected' : '';
                        return `<option value="${dn}"${sel}>${dn}</option>`;
                    })).join('');
                return `<label class="bp-chat-form-row">
                            <span>${label}</span>
                            <select name="${name}" ${required}>${opts}</select>
                        </label>`;
            }

            const type = f.type === 'tel' ? 'tel' : (f.type === 'email' ? 'email' : 'text');
            const multiline = f.multiline ? true : false;
            if (multiline) {
                return `<label class="bp-chat-form-row">
                            <span>${label}</span>
                            <textarea name="${name}" placeholder="${placeholder}" rows="2" ${required}>${value}</textarea>
                        </label>`;
            }
            return `<label class="bp-chat-form-row">
                        <span>${label}</span>
                        <input type="${type}" name="${name}" placeholder="${placeholder}" value="${value}" ${required}>
                    </label>`;
        }).join('');

        return `<form class="bp-chat-form" data-info-form>
                    <div class="bp-chat-form-title">${title}</div>
                    ${inputs}
                    <button type="submit" class="bp-chat-form-submit">${submitLabel}</button>
                </form>`;
    }

    function renderProductImages(json) {
        let imgs;
        try { imgs = JSON.parse(json); } catch (e) { return ''; }
        if (!Array.isArray(imgs) || imgs.length === 0) return '';
        const items = imgs.slice(0, 8).map(img => {
            const src = escapeHtml(safeUrl(img.src, '/website/assets/images/placeholder.png'));
            const alt = escapeHtml(img.alt || 'Product image');
            return `<a class="bp-chat-gallery-item" href="${src}" target="_blank" rel="noopener">
                        <img src="${src}" alt="${alt}" loading="lazy">
                    </a>`;
        }).join('');
        return `<div class="bp-chat-gallery">${items}</div>`;
    }

    async function sendChatMessage(text) {
        if (isStreaming) return;
        isStreaming = true;
        sendBtn.disabled = true;

        appendUserMessage(text);
        const bubble = appendAssistantPlaceholder();

        // Hard ceiling on a single request so the typing indicator never
        // spins forever. If the upstream model takes longer than this,
        // the user gets a clear "took too long" message and can retry.
        const aborter = new AbortController();
        const TIMEOUT_MS = 60_000;
        const timeoutId = setTimeout(() => aborter.abort(), TIMEOUT_MS);
        let timedOut = false;

        let response;
        try {
            response = await fetch(routes.chat, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream, application/json',
                    'X-CSRF-TOKEN': routes.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: aborter.signal,
                body: JSON.stringify({
                    message: text,
                    conversation_id: conversationId,
                    focus_stack: productFocusStack,
                }),
            });
        } catch (err) {
            clearTimeout(timeoutId);
            timedOut = err && err.name === 'AbortError';
            bubble.parentElement.remove();
            appendErrorBubble(timedOut
                ? 'Took too long to respond. Please try again.'
                : 'Network error. Please try again.');
            isStreaming = false;
            sendBtn.disabled = false;
            return;
        }

        if (!response.ok) {
            bubble.parentElement.remove();
            let msg = 'Something went wrong.';
            try {
                const data = await response.json();
                msg = data.message || data.error || msg;
            } catch (_) {}
            appendErrorBubble(msg);
            isStreaming = false;
            sendBtn.disabled = false;
            return;
        }

        // Stream the body line-by-line, collecting:
        //   - text-delta chunks → fullText (the assistant's prose)
        //   - tool-output-available with PRODUCT_CARDS → cardsJson (authoritative)
        // We also track which tool each toolCallId belongs to (via the
        // earlier tool-input-available event), because when add_to_cart or
        // quick_checkout fires after search_products in the same turn, the
        // earlier search was just the agent looking up an ID — we don't want
        // to also show the customer those cards alongside an "added to cart"
        // confirmation.
        let fullText = '';
        let cardsJson = null;
        let imagesJson = null;
        const toolNames = {};   // toolCallId → toolName
        const CARD_SUPPRESSING_TOOLS = new Set(['AddToCart', 'QuickCheckout']);
        const reader = response.body.getReader();
        const decoder = new TextDecoder('utf-8');
        let buffer = '';

        const consumeLine = (line) => {
            const evt = parseVercelLine(line);
            if (!evt) return;
            if (evt.type === 'start' && evt.conversationId) {
                conversationId = evt.conversationId;
                saveConversationId(conversationId);
            } else if (evt.type === 'text-delta' && typeof evt.delta === 'string') {
                fullText += evt.delta;
                renderBubble();
            } else if (evt.type === 'tool-input-available' && evt.toolCallId && evt.toolName) {
                toolNames[evt.toolCallId] = evt.toolName;
                // If the agent just decided to mutate cart/order state, drop
                // any cards that earlier search_products calls in this turn
                // surfaced — the customer asked to buy, not to browse.
                if (CARD_SUPPRESSING_TOOLS.has(evt.toolName) && cardsJson) {
                    cardsJson = null;
                    renderBubble();
                }
            } else if (evt.type === 'tool-output-available') {
                const tool = toolNames[evt.toolCallId];
                // PRODUCT_FOCUS markers must apply even for CARD_SUPPRESSING
                // tools — add_to_cart in particular sets focus and is in
                // that suppression set. Process focus FIRST, then return for
                // suppressed tools so we don't render any cards/images.
                const focusId = extractToolFocus(evt);
                if (focusId) pushFocus(focusId);

                if (CARD_SUPPRESSING_TOOLS.has(tool)) {
                    return; // never surface cards for cart/checkout outputs
                }
                const cards = extractToolCards(evt);
                if (cards) {
                    cardsJson = cards;
                    renderBubble();
                }
                const images = extractToolImages(evt);
                if (images) {
                    imagesJson = images;
                    renderBubble();
                }
            }
        };

        const renderBubble = () => {
            // Extract any INFO_FORM block from the assistant's text stream
            // first — we render an inline form in its place.
            let infoFormJson = null;
            const formMatch = fullText.match(INFO_FORM_RE);
            if (formMatch) infoFormJson = formMatch[1];

            // Strip any PRODUCT_CARDS / PRODUCT_IMAGES / INFO_FORM blocks
            // from the displayed text — they render via their own paths.
            // Truncated reproductions are stripped too (the open-ended
            // branches handle mid-JSON cutoffs).
            let cleanText = fullText
                .replace(/<<PRODUCT_CARDS:[\s\S]*?>>|<<PRODUCT_CARDS:[\s\S]*$/g, '')
                .replace(/<<PRODUCT_IMAGES:[\s\S]*?>>|<<PRODUCT_IMAGES:[\s\S]*$/g, '')
                .replace(/<<PRODUCT_FOCUS:[\s\S]*?>>|<<PRODUCT_FOCUS:[\s\S]*$/g, '')
                .replace(/<<INFO_FORM:[\s\S]*?>>|<<INFO_FORM:[\s\S]*$/g, '')
                .trim();

            // Backstop: when cards are present, the agent should ONLY write a
            // short intro sentence. If it ignored the prompt and started a
            // numbered list / markdown link list / bullet list, drop everything
            // from the first list marker onward — the cards already display
            // the same info visually.
            if (cardsJson && cleanText) {
                cleanText = cleanText.replace(/\n\s*(?:\d+\.|[-*•])\s[\s\S]*$/, '').trim();
                // Also drop any standalone markdown link line "[name](url)"
                cleanText = cleanText.replace(/\n\s*\[[^\]]+\]\([^)]+\)[\s\S]*$/, '').trim();
            }

            let html = '';
            if (cleanText) {
                const formatted = applyInlineMarkdown(escapeHtml(cleanText)).replace(/\n/g, '<br>');
                html += `<div>${formatted}</div>`;
            }
            if (imagesJson) {
                html += renderProductImages(imagesJson);
            }
            if (cardsJson) {
                html += renderProductCards(cardsJson);
            }
            if (infoFormJson) {
                html += renderInfoForm(infoFormJson);
            }
            bubble.innerHTML = html || '<div class="bp-chat-typing"><span></span><span></span><span></span></div>';
            scrollToBottom();
        };

        try {
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';
                for (const line of lines) {
                    if (line) consumeLine(line);
                }
            }
            if (buffer) consumeLine(buffer);
        } catch (err) {
            // Stream aborted (timeout) or upstream cut off mid-response.
            timedOut = err && err.name === 'AbortError';
        } finally {
            clearTimeout(timeoutId);
        }

        if (!fullText.trim() && !cardsJson) {
            // Replace the placeholder with a friendlier error — distinguishes
            // upstream timeout/abort from a successful-but-silent reply.
            bubble.parentElement.remove();
            appendErrorBubble(timedOut
                ? 'Took too long to respond. Please try again.'
                : 'AI provider didn\'t reply. Please try again.');
        }

        scrollToBottom();
        isStreaming = false;
        sendBtn.disabled = false;
    }

    // ── Buy button (delegated) — fast path, no LLM ─────────────────────────
    // Customer clicked Buy on a product card. We already know the product
    // and have districts preloaded — pop the INFO_FORM immediately and skip
    // the 3-4 LLM round-trips the agent would otherwise spend asking for
    // info. The deterministic place-order endpoint commits at the end.
    messagesEl.addEventListener('click', (e) => {
        const buyBtn = e.target.closest('[data-buy-product-id]');
        if (!buyBtn) return;
        const id = Number(buyBtn.getAttribute('data-buy-product-id'));
        const name = buyBtn.getAttribute('data-buy-product-name') || 'this product';
        if (!id) return;

        // Anchor the focus stack on what they just clicked so the form
        // submit handler routes to place-order with the right product_id.
        pushFocus(id);

        // Echo the user's intent into the timeline as a normal user bubble.
        appendUserMessage(`Buy: ${name}`);

        // Render an assistant bubble carrying just the INFO_FORM — no LLM call.
        const wrap = document.createElement('div');
        wrap.className = 'bp-chat-message bp-chat-message-assistant';
        const bubble = document.createElement('div');
        bubble.className = 'bp-chat-bubble';
        const intro = `চমৎকার! ${escapeHtml(name)} অর্ডার দিতে নিচের তথ্যগুলো পূরণ করুন:`;
        const formJson = JSON.stringify({
            title: 'ডেলিভারির তথ্য',
            submit_label: 'অর্ডার পাঠান',
            fields: [
                { name: 'name', label: 'নাম', placeholder: 'আপনার পূর্ণ নাম', type: 'text' },
                { name: 'phone', label: 'ফোন', placeholder: '01XXXXXXXXX', type: 'tel' },
                { name: 'district', label: 'জেলা', type: 'select' },
                { name: 'address', label: 'পূর্ণ ঠিকানা', placeholder: 'বাসা, রোড, এলাকা', type: 'text', multiline: true },
            ],
        });
        bubble.innerHTML = `<div>${intro}</div>` + renderInfoForm(formJson);
        wrap.appendChild(bubble);
        messagesEl.appendChild(wrap);
        scrollToBottom();
    });

    // ── INFO_FORM submit (delegated) ───────────────────────────────────────
    messagesEl.addEventListener('submit', (e) => {
        const form = e.target.closest('[data-info-form]');
        if (!form) return;
        e.preventDefault();
        if (isStreaming) return;

        // Map form fields by name so we can detect "this looks like a
        // delivery form" and route to the deterministic order endpoint.
        const values = {};
        const lines = [];
        form.querySelectorAll('input, textarea, select').forEach(el => {
            const label = el.previousElementSibling?.textContent?.trim() || el.name;
            const name = (el.name || '').toLowerCase();
            const value = (el.value || '').trim();
            if (!value) return;
            values[name] = value;
            lines.push(`${label}: ${value}`);
        });
        if (lines.length === 0) return;

        form.querySelectorAll('input, textarea, select, button').forEach(el => el.disabled = true);
        form.classList.add('bp-chat-form-submitted');

        // If the form has the four delivery fields AND a product is in
        // focus, COMMIT THE ORDER DETERMINISTICALLY — bypass the LLM
        // entirely so a stream failure can't cost the sale.
        const looksLikeDelivery = values.name && values.phone && values.district && values.address;
        const focusedProductId = productFocusStack[0] || null;
        if (looksLikeDelivery && focusedProductId && routes.place_order) {
            placeOrderDeterministic({
                customer_name: values.name,
                customer_phone: values.phone,
                customer_email: values.email || null,
                district: values.district,
                address: values.address,
                product_id: focusedProductId,
                quantity: 1,
            }, lines.join('\n'));
            return;
        }

        // Fallback for any other form (e.g. just a phone for order
        // lookup) — route through the chat agent as before.
        sendChatMessage(lines.join('\n'));
    });

    async function placeOrderDeterministic(payload, originalText) {
        // Echo the user's input as a bubble so the timeline is consistent.
        appendUserMessage(originalText);
        const bubble = appendAssistantPlaceholder();

        let response;
        try {
            response = await fetch(routes.place_order, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': routes.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });
        } catch (err) {
            bubble.parentElement.remove();
            appendErrorBubble('Network error placing the order. Please try again.');
            return;
        }

        let data = {};
        try { data = await response.json(); } catch (_) {}

        bubble.parentElement.remove();

        if (!response.ok || !data.success) {
            const msg = data.error || data.message || 'Order placement failed. Please try again.';
            appendErrorBubble(msg);
            return;
        }

        // Render a celebratory confirmation bubble — no LLM needed.
        const wrap = document.createElement('div');
        wrap.className = 'bp-chat-message bp-chat-message-assistant';
        const b = document.createElement('div');
        b.className = 'bp-chat-bubble';

        const lines = [];
        lines.push(`<strong>✅ অর্ডার নং ${escapeHtml(data.order_number)} সফল হয়েছে!</strong>`);
        lines.push(`মোট: <strong>${escapeHtml(data.grand_total)}</strong> (Cash on Delivery)`);
        if (data.discount) lines.push(`ডিসকাউন্ট: -${escapeHtml(data.discount)} (${escapeHtml(data.coupon_code || '')})`);
        lines.push(`শিপিং: ${escapeHtml(data.shipping)} ${data.shipping_zone ? '· ' + escapeHtml(data.shipping_zone) : ''}`);
        if (data.estimated_delivery_days) lines.push(`আনুমানিক ডেলিভারি: ${data.estimated_delivery_days} দিন`);
        if (data.success_url) lines.push(`<a href="${escapeHtml(data.success_url)}" target="_blank" rel="noopener">অর্ডারের সম্পূর্ণ তথ্য দেখুন →</a>`);

        b.innerHTML = lines.join('<br>');
        wrap.appendChild(b);
        messagesEl.appendChild(wrap);
        scrollToBottom();

        // Clear the focus stack so the next message doesn't accidentally
        // think the customer wants to act on the just-purchased item.
        productFocusStack = [];
    }

    // ── Form submit ────────────────────────────────────────────────────────
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (!text || isStreaming) return;
        input.value = '';
        sendChatMessage(text);
    });

    // ── Voice input (MediaRecorder) ────────────────────────────────────────
    async function startRecording() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            appendErrorBubble('Voice input is not supported in this browser.');
            return;
        }
        try {
            recordingStream = await navigator.mediaDevices.getUserMedia({ audio: true });
        } catch (err) {
            appendErrorBubble('Microphone access denied.');
            return;
        }
        recordedChunks = [];
        const mime = MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : '';
        mediaRecorder = new MediaRecorder(recordingStream, mime ? { mimeType: mime } : undefined);
        mediaRecorder.ondataavailable = (ev) => {
            if (ev.data && ev.data.size > 0) recordedChunks.push(ev.data);
        };
        mediaRecorder.onstop = onRecordingStopped;
        mediaRecorder.start();
        micBtn.classList.add('bp-chat-recording');
    }

    async function onRecordingStopped() {
        micBtn.classList.remove('bp-chat-recording');
        recordingStream.getTracks().forEach(t => t.stop());
        recordingStream = null;

        if (recordedChunks.length === 0) return;
        const blob = new Blob(recordedChunks, { type: recordedChunks[0].type || 'audio/webm' });
        const fd = new FormData();
        fd.append('audio', blob, 'voice.webm');

        const originalPlaceholder = input.placeholder;
        input.placeholder = 'Transcribing…';
        input.disabled = true;

        try {
            const res = await fetch(routes.transcribe, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': routes.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: fd,
            });
            const data = await res.json();
            if (res.ok && data.text) {
                input.value = (input.value ? input.value + ' ' : '') + data.text;
                input.focus();
            } else {
                appendErrorBubble(data.error || 'Could not transcribe audio.');
            }
        } catch (err) {
            appendErrorBubble('Network error during transcription.');
        } finally {
            input.placeholder = originalPlaceholder;
            input.disabled = false;
        }
    }

    function stopRecording() {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            mediaRecorder.stop();
        }
    }

    // Hold-to-record (desktop) + tap-to-toggle (mobile)
    let pressTimer = null;
    let isHoldRecording = false;

    if (voiceEnabled && micBtn) {
        micBtn.addEventListener('mousedown', () => {
            pressTimer = setTimeout(() => { isHoldRecording = true; startRecording(); }, 150);
        });
        micBtn.addEventListener('mouseup', () => {
            clearTimeout(pressTimer);
            if (isHoldRecording) { stopRecording(); isHoldRecording = false; }
        });
        micBtn.addEventListener('mouseleave', () => {
            clearTimeout(pressTimer);
            if (isHoldRecording) { stopRecording(); isHoldRecording = false; }
        });
        // Mobile: tap to start/stop
        micBtn.addEventListener('touchstart', (e) => {
            e.preventDefault();
            if (mediaRecorder && mediaRecorder.state === 'recording') stopRecording();
            else startRecording();
        });
    }

})();
