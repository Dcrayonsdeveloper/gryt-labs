<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private \App\Services\MessagingService $messaging,
        private \App\Services\BotFlowService $botFlow
    ) {}

    /**
     * Verify webhook (GET) - Meta sends this to verify your endpoint.
     */
    public function verify(Request $request): Response
    {
        $settingToken = Setting::get('whatsapp_verify_token');
        $verifyToken = $settingToken ?? config('services.whatsapp.verify_token');
        $source = $settingToken !== null ? 'setting' : 'config_fallback';

        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && is_string($token) && is_string($verifyToken) && hash_equals($verifyToken, $token)) {
            Log::info('WhatsApp webhook verified successfully', [
                'tenant' => $this->currentTenantId(),
                'source' => $source,
            ]);
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp webhook verification failed', [
            'tenant' => $this->currentTenantId(),
            'mode' => $mode,
            'source' => $source,
            'received_token_length' => is_string($token) ? strlen($token) : 0,
            'expected_token_length' => is_string($verifyToken) ? strlen($verifyToken) : 0,
        ]);

        return response('Forbidden', 403);
    }

    private function currentTenantId(): ?string
    {
        try {
            $tenant = function_exists('tenant') ? tenant() : null;
            return $tenant?->getTenantKey();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Handle incoming webhook events (POST).
     */
    public function handle(Request $request): Response
    {
        $payload = $request->all();

        Log::info('WhatsApp webhook received', ['payload' => $payload]);

        // Process messages
        $entries = $payload['entry'] ?? [];
        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                $messages = $value['messages'] ?? [];

                foreach ($messages as $message) {
                    $this->processMessage($message, $value);
                }

                // Handle status updates (sent, delivered, read)
                $statuses = $value['statuses'] ?? [];
                foreach ($statuses as $status) {
                    Log::info('WhatsApp message status', [
                        'id' => $status['id'] ?? '',
                        'status' => $status['status'] ?? '',
                        'recipient' => $status['recipient_id'] ?? '',
                    ]);
                }
            }
        }

        return response('OK', 200);
    }

    /**
     * Process an incoming WhatsApp message.
     */
    private function processMessage(array $message, array $value): void
    {
        $from = $message['from'] ?? '';
        $type = $message['type'] ?? '';
        $timestamp = $message['timestamp'] ?? '';
        $messageId = $message['id'] ?? '';

        // Get contact name (defensive — contacts may be missing or malformed)
        $contacts = $value['contacts'] ?? [];
        $firstContact = is_array($contacts) && isset($contacts[0]) && is_array($contacts[0]) ? $contacts[0] : [];
        $profile = is_array($firstContact['profile'] ?? null) ? $firstContact['profile'] : [];
        $contactName = $profile['name'] ?? 'Unknown';

        Log::info('WhatsApp message received', [
            'from' => $from,
            'name' => $contactName,
            'type' => $type,
            'message_id' => $messageId,
        ]);

        // Interactive button / list replies are first-class — always handled by BotFlow
        // (if tenant has opted in to Nia, which gates the whole structured experience).
        if ($type === 'interactive' && Setting::get('nia_whatsapp_enabled', false)) {
            $this->routeInteractiveToBotFlow($from, $message, $contactName);
            return;
        }

        // Handle text messages
        if ($type === 'text') {
            $text = $message['text']['body'] ?? '';
            Log::info("WhatsApp text from {$contactName} ({$from}): {$text}");

            // Nia-enabled tenants: BotFlow first (button/keyword flow), then Nia (Q&A fallback).
            if (Setting::get('nia_whatsapp_enabled', false)) {
                $this->routeTextToBotFlowOrNia($from, $text, $messageId, $contactName);
                return;
            }

            // Legacy hardcoded auto-reply (kept for tenants without Nia opted in).
            $this->autoReply($from, $text);
            return;
        }

        // Handle media (images, videos) — for cashback flow screenshot capture
        if (in_array($type, ['image', 'video'])) {
            $mediaId = $message[$type]['id'] ?? '';
            $caption = $message[$type]['caption'] ?? '';
            Log::info("WhatsApp {$type} from {$contactName} ({$from})", [
                'media_id' => $mediaId,
                'caption' => $caption,
            ]);

            if (Setting::get('nia_whatsapp_enabled', false)) {
                $this->routeMediaToBotFlow($from, $type, $mediaId, $caption, $contactName);
            }
        }
    }

    /**
     * Route an interactive (button_reply / list_reply) message to BotFlow.
     */
    private function routeInteractiveToBotFlow(string $from, array $message, ?string $contactName): void
    {
        $interactive = $message['interactive'] ?? [];
        $kind = $interactive['type'] ?? '';
        $reply = $interactive[$kind] ?? ($interactive['button_reply'] ?? $interactive['list_reply'] ?? []);

        $buttonId    = $reply['id']    ?? '';
        $buttonTitle = $reply['title'] ?? null;

        if (!$buttonId) {
            Log::warning('WhatsApp interactive payload missing id', ['from' => $from, 'payload' => $interactive]);
            return;
        }

        try {
            $lead = $this->messaging->findOrCreateLead('whatsapp', $from);
            $this->maybeUpdateLeadName($lead, $contactName);
            $this->botFlow->handleButtonPress($lead, $buttonId, $buttonTitle);
        } catch (\Throwable $e) {
            Log::error('BotFlow button routing failed', [
                'tenant'    => $this->currentTenantId(),
                'from'      => $from,
                'button_id' => $buttonId,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * Route a text message: BotFlow first; if it doesn't consume the message,
     * fall through to Nia for a Q&A reply.
     */
    private function routeTextToBotFlowOrNia(string $from, string $text, string $messageId, ?string $contactName): void
    {
        try {
            $lead = $this->messaging->findOrCreateLead('whatsapp', $from);
            $this->maybeUpdateLeadName($lead, $contactName);

            // BotFlow gets first chance to match flow keywords / continue a mid-flow state.
            // It self-manages chat-row logging (logs customer turn on consume; rolls back on
            // fall-through). On fall-through, processIncoming() below handles logging + dedupe.
            if ($this->botFlow->handleText($lead, $text, $messageId ?: null)) {
                return;
            }

            // Off-script → Nia answers as a strict Q&A assistant (no state changes).
            $this->routeToNia($from, $text, $messageId, $contactName);
        } catch (\Throwable $e) {
            Log::error('BotFlow/Nia routing failed', [
                'tenant' => $this->currentTenantId(),
                'from'   => $from,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * Route an image/video to BotFlow (cashback screenshot capture).
     */
    private function routeMediaToBotFlow(string $from, string $mediaType, string $mediaId, string $caption, ?string $contactName): void
    {
        try {
            $lead = $this->messaging->findOrCreateLead('whatsapp', $from);
            $this->maybeUpdateLeadName($lead, $contactName);
            $this->botFlow->handleMedia($lead, $mediaType, $mediaId, $caption ?: null);
        } catch (\Throwable $e) {
            Log::error('BotFlow media routing failed', [
                'tenant' => $this->currentTenantId(),
                'from'   => $from,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    private function maybeUpdateLeadName(\App\Models\Lead $lead, ?string $contactName): void
    {
        if ($contactName && $contactName !== 'Unknown' && !$lead->name) {
            $lead->update(['name' => $contactName]);
        }
    }

    /**
     * Route an incoming WhatsApp text to the Nia AI assistant (per-tenant opt-in).
     * On failure we stay silent rather than fall back to the legacy autoReply —
     * mixing two bot voices in the same conversation is worse than a missed turn.
     * Operator can set nia_whatsapp_enabled=0 to revert.
     */
    private function routeToNia(string $from, string $text, string $messageId, ?string $contactName): void
    {
        try {
            $result = $this->messaging->processIncoming('whatsapp', $from, $text, $messageId ?: null);

            if ($contactName && $contactName !== 'Unknown' && !empty($result['lead_id'])) {
                $lead = \App\Models\Lead::find($result['lead_id']);
                if ($lead && !$lead->name) {
                    $lead->update(['name' => $contactName]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Nia WhatsApp routing failed', [
                'tenant' => $this->currentTenantId(),
                'from' => $from,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send auto-reply based on message content.
     */
    private function autoReply(string $to, string $text): void
    {
        $textLower = strtolower(trim($text));

        $reply = null;

        $storeName = config('app.name', 'Store');
        $storeUrl = config('app.url');

        if (in_array($textLower, ['hi', 'hello', 'hey', 'hii'])) {
            $reply = "Hello! 👋 Welcome to {$storeName}! 🛍️\n\nHow can we help you today?\n\n1️⃣ Track my order\n2️⃣ Browse products\n3️⃣ Video review cashback\n4️⃣ Talk to support\n\nReply with a number or type your query!";
        } elseif (str_contains($textLower, 'track') || str_contains($textLower, 'order')) {
            $reply = "📦 To track your order, please share your Order ID (e.g., ORD-20260318001).\n\nYou can also track at: {$storeUrl}/orders";
        } elseif (str_contains($textLower, 'video') || str_contains($textLower, 'cashback') || str_contains($textLower, '100')) {
            $cashbackAmount = Setting::get('video_review_cashback_amount', '100');
            $reply = "🎥 *Video Review ₹{$cashbackAmount} Cashback Offer!*\n\n1. Record a 30-60 sec video of your {$storeName} product\n2. Send the video here on WhatsApp\n3. Get ₹{$cashbackAmount} cashback in your UPI within 48 hours!\n\nPlease send your video and your UPI ID. 💰";
        } elseif (str_contains($textLower, 'product') || str_contains($textLower, 'shop') || str_contains($textLower, 'buy')) {
            $reply = "🛍️ Browse our products at:\n👉 {$storeUrl}/products";
        }

        if ($reply) {
            $this->sendMessage($to, $reply);
        }
    }

    /**
     * Send a WhatsApp message.
     */
    private function sendMessage(string $to, string $text): void
    {
        $token = Setting::get('whatsapp_api_token', '') ?: config('services.whatsapp.token');
        $phoneId = Setting::get('whatsapp_phone_number_id', '') ?: config('services.whatsapp.phone_number_id');

        if (!$token || !$phoneId) {
            Log::warning('WhatsApp credentials not configured');
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->post("https://graph.facebook.com/v21.0/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'text',
                    'text' => ['body' => $text],
                ]);

            if (!$response->successful()) {
                Log::error('WhatsApp send failed', ['response' => $response->body()]);
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp send error: ' . $e->getMessage());
        }
    }
}
