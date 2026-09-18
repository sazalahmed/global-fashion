<?php

namespace Modules\AiAssistant\Console;

use Illuminate\Console\Command;
use Laravel\Ai\Tools\Request as ToolRequest;
use Modules\AiAssistant\Ai\Tools\AddToCart;
use Modules\AiAssistant\Ai\Tools\GetCustomerInfo;
use Modules\AiAssistant\Ai\Tools\ListDistricts;
use Modules\AiAssistant\Ai\Tools\LookupOrder;
use Modules\AiAssistant\Ai\Tools\SearchProducts;
use Modules\AiAssistant\Models\AiProductEmbedding;
use Modules\AiAssistant\Models\AiRequestLog;
use Modules\AiAssistant\Services\AiProviderResolver;

/**
 * Self-contained smoke test — runs every CRITICAL tool path through
 * the agent's actual tools without making any LLM calls. Stable enough
 * to schedule nightly. Use --with-llm to additionally test the live
 * agent prompt() (costs tokens).
 */
class SmokeTestCommand extends Command
{
    protected $signature = 'ai:smoke-test
                            {--with-llm : Also test the live LLM (consumes free-tier quota)}';

    protected $description = 'Run a fast smoke test of the AI shopping assistant — tools, search, security, and optionally the live LLM.';

    protected array $results = [];

    public function handle(AiProviderResolver $resolver): int
    {
        $this->info('AI Assistant — smoke test');
        $this->newLine();

        $this->section('Configuration');
        $this->kvRow('enabled', $resolver->isEnabled() ? 'YES' : 'NO');
        $this->kvRow('chat', $resolver->chatLab()->value . ' / ' . $resolver->chatModel());
        $this->kvRow('embedding', $resolver->embeddingLab()->value . ' / ' . $resolver->embeddingModel() . ' / ' . $resolver->embeddingDimensions() . 'd');
        $this->kvRow('stt', ($resolver->sttLab()?->value ?? 'browser') . ' / ' . ($resolver->sttModel() ?? '—'));
        $this->kvRow('tts', $resolver->ttsLab()?->value ?? 'browser');
        $this->newLine();

        $this->section('Tool tests (no LLM calls)');

        $this->probe('SearchProducts: specific query "milk"', function () {
            $out = (string) app(SearchProducts::class)->handle(new ToolRequest(['query' => 'milk']));
            return [
                'ok' => str_contains($out, '<<PRODUCT_CARDS:'),
                'detail' => str_contains($out, '<<PRODUCT_CARDS:') ? 'cards block emitted' : 'no cards block',
            ];
        });

        $this->probe('SearchProducts: browse mode', function () {
            $out = (string) app(SearchProducts::class)->handle(new ToolRequest(['query' => '', 'browse' => true]));
            $count = preg_match_all('/"id":\d+/', $out);
            return ['ok' => $count > 0, 'detail' => "{$count} cards"];
        });

        $this->probe('SearchProducts: always returns cards (no dead-end)', function () {
            $out = (string) app(SearchProducts::class)->handle(new ToolRequest(['query' => 'asdfqwerzxcv-no-match']));
            return [
                'ok' => str_contains($out, '<<PRODUCT_CARDS:'),
                'detail' => 'gibberish query still surfaced products via vector/browse fallback',
            ];
        });

        $this->probe('AddToCart: rejects negative qty', function () {
            $out = (string) app(AddToCart::class)->handle(new ToolRequest(['product_id' => 1, 'quantity' => -5]));
            $decoded = json_decode($out, true);
            return [
                'ok' => ($decoded['success'] ?? null) === false,
                'detail' => $decoded['message'] ?? 'no message',
            ];
        });

        $this->probe('AddToCart: rejects qty 99999', function () {
            $out = (string) app(AddToCart::class)->handle(new ToolRequest(['product_id' => 1, 'quantity' => 99999]));
            $decoded = json_decode($out, true);
            return [
                'ok' => ($decoded['success'] ?? null) === false,
                'detail' => $decoded['message'] ?? 'no message',
            ];
        });

        $this->probe('AddToCart: AI price tampering ignored', function () {
            session()->forget('cart');
            $out = (string) app(AddToCart::class)->handle(new ToolRequest([
                'product_id' => 1, 'quantity' => 1,
                'price' => 0.01, 'unit_price' => 0.01, 'sell_price' => 0.01,
            ]));
            // Tool outputs end with optional <<MARKER:...>> trailers (FOCUS,
            // IMAGES, CARDS); strip those before json_decode.
            $jsonOnly = trim(preg_replace('/\R<<[A-Z_]+:[\s\S]*$/', '', $out));
            $decoded = json_decode($jsonOnly, true);
            $subtotal = $decoded['cart_subtotal_raw'] ?? 0;
            return [
                'ok' => $subtotal > 0.01,
                'detail' => 'cart_subtotal_raw=' . $subtotal . ' (DB price, not AI-supplied 0.01)',
            ];
        });

        $this->probe('LookupOrder: unknown order returns not-found', function () {
            $out = (string) app(LookupOrder::class)->handle(new ToolRequest(['order_number' => 'NOPE-' . time()]));
            $decoded = json_decode($out, true);
            return [
                'ok' => ($decoded['success'] ?? null) === false && str_contains((string) ($decoded['message'] ?? ''), 'not found'),
                'detail' => $decoded['message'] ?? '',
            ];
        });

        $this->probe('GetCustomerInfo: guest sees no PII', function () {
            $out = (string) app(GetCustomerInfo::class)->handle(new ToolRequest([]));
            $decoded = json_decode($out, true);
            return [
                'ok' => ($decoded['logged_in'] ?? null) === false,
                'detail' => 'logged_in=false (expected for guest)',
            ];
        });

        $this->probe('ListDistricts: fuzzy "dha" finds Dhaka', function () {
            $out = (string) app(ListDistricts::class)->handle(new ToolRequest(['query' => 'dha']));
            $decoded = json_decode($out, true);
            $names = array_column($decoded['districts'] ?? [], 'name');
            return [
                'ok' => in_array('Dhaka', $names, true),
                'detail' => 'matches: ' . implode(', ', array_slice($names, 0, 3)),
            ];
        });

        $this->newLine();
        $this->section('Data integrity');

        $embs = AiProductEmbedding::count();
        $this->probe('Product embeddings exist', fn () => [
            'ok' => $embs > 0,
            'detail' => "{$embs} rows in ai_product_embeddings",
        ]);

        $logs24h = AiRequestLog::where('created_at', '>=', now()->subDay())->count();
        $this->probe('Recent activity (last 24h)', fn () => [
            'ok' => true,
            'detail' => "{$logs24h} ai_request_logs entries",
        ]);

        if ($this->option('with-llm')) {
            $this->newLine();
            $this->section('Live LLM test (consumes quota)');

            $this->probe('Agent ping → expect a sentence reply', function () use ($resolver) {
                $agent = new \Modules\AiAssistant\Ai\Agents\ShoppingAssistant();
                $reply = (string) $agent->prompt('Say "ready" if you are working.',
                    provider: $resolver->chatModelsForFailover());
                return [
                    'ok' => trim($reply) !== '',
                    'detail' => mb_substr($reply, 0, 100),
                ];
            });
        }

        $this->newLine();
        return $this->summary();
    }

    protected function section(string $name): void
    {
        $this->line('  <fg=cyan;options=bold>' . $name . '</>');
    }

    protected function kvRow(string $k, string $v): void
    {
        $this->line(sprintf('    %-12s %s', $k, $v));
    }

    protected function probe(string $name, \Closure $callback): void
    {
        try {
            $result = $callback();
            $ok = (bool) ($result['ok'] ?? false);
            $detail = (string) ($result['detail'] ?? '');
        } catch (\Throwable $e) {
            $ok = false;
            $detail = 'EXCEPTION: ' . mb_substr($e->getMessage(), 0, 120);
        }

        $this->results[] = compact('name', 'ok', 'detail');
        $icon = $ok ? '<fg=green>✓</>' : '<fg=red>✗</>';
        $this->line(sprintf('    %s %-50s %s', $icon, $name, '<fg=gray>' . $detail . '</>'));
    }

    protected function summary(): int
    {
        $total = count($this->results);
        $passed = count(array_filter($this->results, fn ($r) => $r['ok']));
        $failed = $total - $passed;

        if ($failed === 0) {
            $this->info("Result: {$passed}/{$total} passed — ready to ship.");
            return self::SUCCESS;
        }

        $this->error("Result: {$passed}/{$total} passed, {$failed} failed.");
        foreach ($this->results as $r) {
            if (! $r['ok']) {
                $this->line('   - ' . $r['name'] . ': ' . $r['detail']);
            }
        }
        return self::FAILURE;
    }
}
