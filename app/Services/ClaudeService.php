<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClaudeService
{
    private const MAX_HISTORY = 20;

    /** How long the rendered product catalogue stays cached (seconds). */
    private const CATALOG_TTL = 900;

    /**
     * Max chars of short_description kept per product line. Kept short on
     * purpose: the whole catalogue is sent on every cold-cache request and
     * counts against the org's input-tokens-per-minute rate limit, so a lean
     * catalogue keeps cache-creation calls well under that ceiling.
     */
    private const DESC_LIMIT = 70;

    /**
     * Sentinel returned when a message is NOT a product/sales enquiry.
     * MessagingService checks for this and sends nothing to the customer.
     */
    public const NO_REPLY = 'NO_REPLY';

    public function generateReply(Lead $lead, string $message): string
    {
        // Intent gate, layer 1 — a cheap no-API-call pre-filter. Greetings,
        // emoji-only messages and smalltalk never get an auto-reply.
        if ($this->isNonProductChatter($message)) {
            Log::info('Nia: skipped — non-product chatter (pre-filter)', ['lead_id' => $lead->id]);
            return self::NO_REPLY;
        }

        // Prefer per-tenant Setting so multi-tenant deployments don't share one key
        // (and so a per-tenant kill-switch is a single Setting write away).
        $apiKey = Setting::get('anthropic_api_key') ?: config('services.anthropic.key');

        if (empty($apiKey)) {
            Log::error('Nia: Anthropic API key not configured');
            return "I'm currently unavailable. Please try again later!";
        }

        $messages = $this->withCacheBreakpointOnLastMessage(
            $this->buildMessageHistory($lead, $message)
        );
        $model = Setting::get('nia_model', 'claude-haiku-4-5-20251001');

        // Two system blocks. The first holds everything identical for every
        // customer — persona, product catalogue, command list — and carries the
        // cache_control breakpoint, so that whole prefix is cached and shared
        // across ALL leads. The second holds the small per-lead context and is
        // left uncached so it can vary without busting the catalogue cache.
        $payload = [
            'model'      => $model,
            'max_tokens' => 1024,
            'system'     => [
                [
                    'type'          => 'text',
                    'text'          => $this->buildStaticSystemPrompt(),
                    'cache_control' => ['type' => 'ephemeral'],
                ],
                [
                    'type' => 'text',
                    'text' => $this->buildLeadContext($lead),
                ],
            ],
            'messages'   => $messages,
        ];

        try {
            $response = $this->postToAnthropic($apiKey, $payload);

            // A 429 right after an idle spell means the prompt cache was cold and
            // two cache-creation calls collided on the input-tokens-per-minute
            // limit. A short wait lets the cache turn warm; the retry is then a
            // cheap cache read that no longer counts against the limit.
            if ($response->status() === 429) {
                Log::warning('Nia: rate-limited (429), retrying after cache warm-up', [
                    'lead_id' => $lead->id,
                ]);
                sleep(5);
                $response = $this->postToAnthropic($apiKey, $payload);
            }

            if ($response->failed()) {
                Log::error('Nia: Anthropic API error', [
                    'status'  => $response->status(),
                    'body'    => $response->body(),
                    'lead_id' => $lead->id,
                ]);
                return "I'm having trouble right now. Let me get back to you shortly!";
            }

            $data = $response->json();

            $usage = $data['usage'] ?? [];
            Log::info('Nia: Claude usage', [
                'lead_id'                     => $lead->id,
                'model'                       => $model,
                'input_tokens'                => $usage['input_tokens'] ?? 0,
                'output_tokens'               => $usage['output_tokens'] ?? 0,
                'cache_creation_input_tokens' => $usage['cache_creation_input_tokens'] ?? 0,
                'cache_read_input_tokens'     => $usage['cache_read_input_tokens'] ?? 0,
            ]);

            $text = $data['content'][0]['text'] ?? '';

            // Intent gate, layer 2 — the model itself returns NO_REPLY for
            // non-product messages that slipped past the pre-filter (random
            // text, unrelated questions, spam). Tolerate stray quotes/period.
            $gate = strtoupper(trim($text, " \t\n\r\0\x0B.\"'"));
            if ($gate === self::NO_REPLY) {
                Log::info('Nia: skipped — non-product message (model gate)', ['lead_id' => $lead->id]);
                return self::NO_REPLY;
            }

            return trim($text) !== ''
                ? $text
                : "Sorry, I didn't catch that. Could you say that again?";

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Nia: Anthropic connection timeout', [
                'lead_id' => $lead->id,
                'error'   => $e->getMessage(),
            ]);
            return "I'm a little slow right now. Please try again in a moment!";
        }
    }

    /**
     * Single POST to the Anthropic Messages API. Extracted so the rate-limit
     * retry path can re-issue the identical request without duplicating setup.
     */
    private function postToAnthropic(string $apiKey, array $payload): \Illuminate\Http\Client\Response
    {
        return Http::timeout(40)
            ->withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', $payload);
    }

    /**
     * Cheap pre-filter (no API call): true when the message is obviously NOT a
     * product/sales enquiry — emoji-only, punctuation-only, or an exact-match
     * greeting / smalltalk / acknowledgement. Deliberately conservative: only
     * clear-cut chatter matches; anything ambiguous (e.g. "hi, beard price?")
     * falls through to the model's own intent gate.
     */
    private function isNonProductChatter(string $message): bool
    {
        // Keep only letters, numbers and spaces — drops emoji, punctuation, symbols.
        $stripped = preg_replace('/[^\p{L}\p{N}\s]/u', '', $message);
        $stripped = trim((string) preg_replace('/\s+/u', ' ', (string) $stripped));

        // Emoji-only / symbol-only / blank message → never a product enquiry.
        if ($stripped === '') {
            return true;
        }

        $normalized = mb_strtolower($stripped);

        // Exact-match casual phrases: greetings, smalltalk, acknowledgements.
        $casual = [
            'hi', 'hii', 'hiii', 'hiiii', 'hello', 'helo', 'hellow', 'hey', 'heyy', 'hy', 'yo',
            'hola', 'namaste', 'namaskar', 'namashkar', 'assalamualaikum',
            'good morning', 'good afternoon', 'good evening', 'good night', 'goodmorning',
            'gud morning', 'gud night', 'gm', 'gn',
            'how are you', 'how r u', 'how are u', 'how ru', 'hru', 'how are you doing',
            'wassup', 'whatsup', 'whats up', 'sup',
            'kaise ho', 'kaise hain', 'kaise ho aap', 'kya haal hai', 'kya hal hai',
            'kya haal', 'kaisa hai', 'kya kar rahe ho', 'kya kar rahe hain',
            'thanks', 'thank you', 'thank u', 'thanku', 'thnx', 'thx', 'ty', 'tysm',
            'ok', 'okay', 'okk', 'okie', 'k', 'kk', 'hmm', 'hmmm', 'hm',
            'acha', 'accha', 'achha', 'theek hai', 'thik hai', 'thik', 'theek', 'done',
            'great', 'good', 'nice', 'cool', 'fine', 'wow', 'very nice', 'superb', 'awesome',
            'bye', 'byee', 'goodbye', 'good bye', 'tata', 'see you', 'welcome',
        ];

        return in_array($normalized, $casual, true);
    }

    /**
     * Lead-independent system prompt: persona + live product catalogue +
     * command list. Byte-identical for every customer, so it caches once and
     * every subsequent message (any lead) reads from that cache.
     */
    private function buildStaticSystemPrompt(): string
    {
        $customPrompt = Setting::get('nia_system_prompt', '');

        $prompt = !empty($customPrompt)
            ? $this->substituteTenantVariables($customPrompt)
            : $this->defaultSystemPrompt();

        // Live product catalogue so Nia quotes real names, prices and links.
        $prompt .= "\n\n" . $this->buildProductCatalog();

        // Operator-controlled offers line (Shiprocket-side offers etc.).
        $offers = trim((string) Setting::get('nia_current_offers', ''));
        if ($offers !== '') {
            $prompt .= "\n\n## Current Offers\n" . $offers . "\n";
        }

        // AI command instructions
        $prompt .= "\n## Special Commands\n"
            . "You can embed these commands anywhere in your response. "
            . "They will be stripped before sending to the customer:\n"
            . "- [NIA_QUALIFIED] — Use when the customer shows strong buying intent or is ready to purchase.\n"
            . "- [SCHEDULE_CALL] — Use when the customer explicitly requests a callback or phone consultation.\n"
            . "- [LEAD_CONTEXT:description] — Use to save important context about this lead "
            . "(e.g., [LEAD_CONTEXT:Looking for party dresses for 5-year-old daughter, budget 2000-3000]).\n\n"
            . "Only use these when truly appropriate. Do not overuse them.\n";

        return $prompt;
    }

    /**
     * Per-lead context block. Small and uncached — varies per conversation.
     */
    private function buildLeadContext(Lead $lead): string
    {
        $ctx = "## Current Customer\n";
        $ctx .= "- Platform: {$lead->platform}\n";
        $ctx .= '- Name: ' . ($lead->name ?? 'Unknown') . "\n";
        $ctx .= "- Stage: {$lead->stage}\n";

        if ($lead->notes) {
            $ctx .= "- Previous context: {$lead->notes}\n";
        }

        if ($lead->tags) {
            $ctx .= '- Tags: ' . implode(', ', $lead->tags) . "\n";
        }

        return $ctx;
    }

    /**
     * Render the active product catalogue as a compact text block.
     *
     * Cached per tenant for CATALOG_TTL so we don't re-query 150+ rows on every
     * inbound DM. The text is deterministic (ordered by name) so the cached
     * value stays byte-identical within the window — which is what keeps the
     * Anthropic prompt cache warm. A price/stock edit naturally rolls in when
     * the Laravel cache expires (and the next prompt-cache write follows).
     */
    private function buildProductCatalog(): string
    {
        $tenantKey = (function_exists('tenant') && tenant()) ? tenant('id') : 'central';

        return Cache::remember('nia_product_catalog_' . $tenantKey, self::CATALOG_TTL, function () {
            $products = Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['name', 'slug', 'price', 'mrp', 'short_description', 'stock_status', 'stock_quantity']);

            if ($products->isEmpty()) {
                return "## Product Catalog\n(No products are currently listed.)";
            }

            $lines = [
                '## Product Catalog',
                'These are the ONLY products this store sells. Use these exact names, '
                    . 'prices and links when replying. Never invent products, prices, shades or URLs. '
                    . 'If a customer asks for something not in this list, say it is not available '
                    . 'and suggest the closest alternative from this list.',
                '',
            ];

            foreach ($products as $p) {
                $url = url('/products/' . $p->slug);

                $price = $this->formatPrice($p->price);
                $priceLine = '₹' . $price;
                if ($p->mrp && (float) $p->mrp > (float) $p->price) {
                    $priceLine .= ' (MRP ₹' . $this->formatPrice($p->mrp) . ')';
                }

                $inStock = $p->stock_status === 'in_stock' && (int) $p->stock_quantity > 0;
                $availability = $inStock ? 'In stock' : 'Out of stock';

                $line = '- ' . $p->name . ' — ' . $priceLine . ' — ' . $availability . ' — ' . $url;

                // Clean the description: drop tags, decode entities (&amp; → &),
                // collapse all whitespace/newlines to single spaces.
                $desc = html_entity_decode(strip_tags((string) $p->short_description), ENT_QUOTES | ENT_HTML5);
                $desc = trim(preg_replace('/\s+/', ' ', $desc));
                if ($desc !== '') {
                    $line .= "\n  " . Str::limit($desc, self::DESC_LIMIT);
                }

                $lines[] = $line;
            }

            return implode("\n", $lines);
        });
    }

    /** Format a decimal price: drop trailing ".00", keep paise otherwise. */
    private function formatPrice($value): string
    {
        $value = (float) $value;

        return $value == (int) $value
            ? number_format($value, 0)
            : number_format($value, 2);
    }

    /**
     * Substitute {placeholder} tokens in a custom system prompt with tenant Setting values.
     * Unknown tokens are left in place (visible) so the operator notices and adds the missing Setting.
     */
    private function substituteTenantVariables(string $prompt): string
    {
        $vars = [
            '{store_name}'        => Setting::get('store_name', config('app.name', 'Store')),
            '{store_url}'         => config('app.url', ''),
            '{cashback_amount}'   => Setting::get('cashback_amount', '50'),
            '{cashback_currency}' => Setting::get('cashback_currency', '₹'),
            '{order_id_format}'   => 'XXX-XXXXXXX-XXXXXXX',
        ];
        return str_replace(array_keys($vars), array_values($vars), $prompt);
    }

    private function defaultSystemPrompt(): string
    {
        $storeName = Setting::get('store_name', config('app.name', 'Store'));
        $storeUrl = config('app.url', '');
        $freeShippingThreshold = Setting::get('free_shipping_threshold', '');
        $shippingLine = $freeShippingThreshold ? "- Free shipping on orders above ₹{$freeShippingThreshold}" : '- Check website for shipping policy';

        return <<<PROMPT
You are Nia, the friendly AI sales and support assistant for {$storeName}, an online store in India.

## Your Personality
- Warm, caring, and enthusiastic about helping customers find the best products.
- Professional but conversational — this is social media messaging, keep it natural.
- Smart, persuasive but never pushy. Guide customers towards making a purchase.
- Concise: keep responses under 100 words for chat platforms. No long paragraphs.
- Use emojis sparingly and naturally (1-2 per message max).
- Never fabricate product details, prices, or policies.

## Language
- Detect the customer's language from their message and reply in the SAME language.
- Hindi message → reply in Hindi. Hinglish (Roman Hindi) → reply in Hinglish. English → reply in English.
- Keep it short, human and conversational — never robotic or templated.

## What You Do
- Answer questions about products, sizes, availability, pricing — using only the Product Catalog below.
- Recommend products based on the customer's needs and preferences.
- Qualify leads by understanding their needs, budget, and timeline.
- Handle objections gracefully (price concerns, sizing doubts, shipping questions).
- Close sales by guiding customers to the website or sharing the exact product link.
- Schedule callbacks when customers prefer speaking with a human.

## Store Information
- Website: {$storeUrl}
{$shippingLine}
- 7-day return policy (unused items with tags)
- Payments: UPI, cards, net banking, wallets, COD
- Contact: available via Instagram, Facebook, and WhatsApp

## Response Style
- Plain text only. Use bullet points (- ) for lists.
- Bold (**text**) only for prices, coupon codes, or critical info.
- No markdown headers. Keep it conversational.
- Always include the correct product link from the Product Catalog when discussing a product.
- End messages with a soft call-to-action when appropriate.
- If unsure about something, be honest and offer to connect with the team.
PROMPT;
    }

    /**
     * Tag the last message's content with a cache_control breakpoint so the
     * cumulative conversation prefix (system + tools + all prior messages)
     * becomes cacheable. Each subsequent turn appends a new bot reply + user
     * message; the new breakpoint reads the previous turn's cache and writes
     * a new one one turn forward.
     *
     * Anthropic accepts content as either a string or an array of typed
     * blocks; we promote only the last message to the block form so the rest
     * of the payload stays compact and we don't burn a breakpoint per turn.
     */
    private function withCacheBreakpointOnLastMessage(array $messages): array
    {
        if (empty($messages)) {
            return $messages;
        }

        $lastIdx = count($messages) - 1;
        $content = $messages[$lastIdx]['content'] ?? null;

        if (is_string($content)) {
            $messages[$lastIdx]['content'] = [
                [
                    'type'          => 'text',
                    'text'          => $content,
                    'cache_control' => ['type' => 'ephemeral'],
                ],
            ];
        }

        return $messages;
    }

    private function buildMessageHistory(Lead $lead, string $currentMessage): array
    {
        $chats = $lead->chats()
            ->orderBy('created_at', 'desc')
            ->limit(self::MAX_HISTORY)
            ->get()
            ->sortBy('created_at')
            ->values();

        $messages = [];
        $lastRole = null;

        foreach ($chats as $chat) {
            $role = $chat->sender === 'customer' ? 'user' : 'assistant';

            // Anthropic requires strictly alternating roles
            if ($role === $lastRole && !empty($messages)) {
                $messages[count($messages) - 1]['content'] .= "\n" . $chat->message;
            } else {
                $messages[] = [
                    'role'    => $role,
                    'content' => $chat->message,
                ];
                $lastRole = $role;
            }
        }

        // Append current message as user turn
        if ($lastRole === 'user' && !empty($messages)) {
            $messages[count($messages) - 1]['content'] .= "\n" . $currentMessage;
        } else {
            $messages[] = ['role' => 'user', 'content' => $currentMessage];
        }

        return $messages;
    }
}
