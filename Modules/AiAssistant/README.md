# AI Assistant Module

Bilingual (Bangla / English / Banglish) shopping assistant for the BizPOS Pro
storefront. Customers chat or speak to find products, place orders, and track
deliveries — without leaving the page.

```
┌─────────────────────────────────────────────────────────────────┐
│  Customer types or speaks                                       │
│        │                                                         │
│        ▼                                                         │
│  POST /ai/chat   ──► ShoppingAssistant (Promptable + RemembersConversations)
│                          │                                       │
│                          ▼                                       │
│                     6 tools the agent can call                   │
│                     ├─ search_products    (hybrid RAG)           │
│                     ├─ add_to_cart        (session cart)         │
│                     ├─ quick_checkout     (creates EcommerceOrder│
│                     ├─ lookup_order                              │
│                     ├─ get_customer_info                         │
│                     └─ list_districts                            │
│                          │                                       │
│                          ▼                                       │
│                   Vercel AI SDK stream                           │
│                          │                                       │
│                          ▼                                       │
│                   Widget renders cards + reply                   │
└─────────────────────────────────────────────────────────────────┘
```

## Configure

1. Set in `.env`:
   - `GEMINI_API_KEY` (chat + STT, free tier)
   - `JINA_API_KEY` (embeddings, 10M tokens free)
   - Optional: `OPENAI_API_KEY`, `DEEPSEEK_API_KEY` for failover

2. Open **Admin → Settings → AI Assistant**, enable the toggle, pick providers.

3. Backfill product embeddings:
   ```bash
   php artisan ai:embed-products
   ```

## Tables

| Table | Purpose |
|---|---|
| `ai_product_embeddings` | One vector per (product, provider, model). MySQL JSON column. |
| `ai_request_logs` | One row per AI call (chat, embedding, tool, STT, TTS). Token + latency + provider for billing. |
| `ai_search_logs` | Per user search: vector_top_id vs keyword_top_id vs fused_top_id for tuning the RRF fusion. |
| `agent_conversations` + `agent_conversation_messages` | Conversation memory (SDK-managed). |

## Endpoints

| Route | Method | Notes |
|---|---|---|
| `/ai/chat` | POST | Streaming agent response (Vercel Data Protocol). Throttle 30/min/IP. |
| `/ai/transcribe` | POST | Audio file → text. Throttle 10/min/IP. |
| `/ai/speak` | POST | Text → audio URL. Returns 410 when in browser mode. Throttle 20/min/IP. |
| `/ai/conversations` | GET | Past conversations for the logged-in customer. |
| `/admin/ai-assistant` | GET | Admin settings page. Auth-gated. |

## Commands

```bash
php artisan ai:embed-products [--chunk=100] [--force]
php artisan ai:smoke-test [--with-llm]
```

## Diagnosing problems

| Symptom | First place to check |
|---|---|
| "No response" empty bubble | `storage/logs/laravel.log` for the latest SDK exception |
| Product cards not rendering | Stream output via DevTools Network tab — look for `tool-output-available` events |
| Same query keeps returning unrelated products | Embeddings stale: `php artisan ai:embed-products --force` |
| Bangla replies look garbled | Frontend Content-Type / charset — check `<meta charset="UTF-8">` is present |
| Rate-limit errors persist | `ai_request_logs` GROUP BY provider — see who's getting throttled |
| Chat answers cost prices | System prompt may have drifted — check `Modules/AiAssistant/app/Ai/Agents/ShoppingAssistant.php` |

## Where to extend

- New tool → drop a class in `app/Ai/Tools/` implementing `Laravel\Ai\Contracts\Tool`, add to `ShoppingAssistant::tools()`.
- New provider → already supported by the SDK. Add the API key in `.env`, expose it in the admin panel's provider radio.
- Different storefront → swap `Modules\Ecommerce\Services\StorefrontService` in the tool constructors.

## Related docs

- [`docs/AI_SHOPPING_ASSISTANT_PLAN.md`](../../docs/AI_SHOPPING_ASSISTANT_PLAN.md) — full architectural plan
- [`docs/AI_ASSISTANT_LAUNCH_CHECKLIST.md`](../../docs/AI_ASSISTANT_LAUNCH_CHECKLIST.md) — pre-launch verification
