<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadChat;
use App\Models\Setting;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessagingService
{
    public function __construct(
        private ClaudeService $claude
    ) {}

    /**
     * Orchestrate the full incoming message flow:
     * Deduplicate → find/create lead → store message → AI reply → send → store reply
     */
    public function processIncoming(
        string $platform,
        string $senderId,
        string $message,
        ?string $messageId = null
    ): array {
        // Deduplicate by platform_message_id
        if ($messageId && LeadChat::where('platform_message_id', $messageId)->exists()) {
            Log::info('Nia: Duplicate message skipped', ['message_id' => $messageId]);
            return ['status' => 'duplicate'];
        }

        $lead = $this->findOrCreateLead($platform, $senderId);

        // Store customer message
        LeadChat::create([
            'lead_id'             => $lead->id,
            'sender'              => 'customer',
            'message'             => $message,
            'platform_message_id' => $messageId,
        ]);

        // Check if Nia is enabled
        if (!Setting::get('nia_enabled', true)) {
            return ['status' => 'bot_disabled', 'lead_id' => $lead->id];
        }

        // Generate AI reply
        $rawReply = $this->claude->generateReply($lead, $message);

        // Intent gate: non-product messages (greetings, smalltalk, emoji-only,
        // spam, unrelated questions) get NO auto-reply at all — nothing is sent
        // and no bot row is stored. The inbound customer message is still kept.
        if (trim($rawReply) === ClaudeService::NO_REPLY) {
            Log::info('Nia: no auto-reply — non-product message', [
                'lead_id'  => $lead->id,
                'platform' => $platform,
            ]);
            return ['status' => 'no_reply', 'lead_id' => $lead->id];
        }

        // Process AI commands (strips them from reply, executes side effects)
        $cleanReply = $this->processAiCommands($lead, $rawReply);

        // Guard: if commands stripped everything, send nothing.
        if (trim($cleanReply) === '') {
            Log::info('Nia: no auto-reply — empty after command strip', ['lead_id' => $lead->id]);
            return ['status' => 'no_reply', 'lead_id' => $lead->id];
        }

        // Store bot reply
        LeadChat::create([
            'lead_id' => $lead->id,
            'sender'  => 'bot',
            'message' => $cleanReply,
        ]);

        // Send reply back to platform
        $sent = $this->sendReply($lead, $cleanReply);

        return [
            'status'  => $sent ? 'sent' : 'send_failed',
            'lead_id' => $lead->id,
            'reply'   => $cleanReply,
        ];
    }

    /**
     * Find existing lead or create new one by platform + platform_id.
     */
    public function findOrCreateLead(string $platform, string $platformId): Lead
    {
        return Lead::firstOrCreate(
            ['platform' => $platform, 'platform_id' => $platformId],
            ['stage' => 'new']
        );
    }

    /**
     * Send reply back to the customer via the appropriate Meta API.
     */
    public function sendReply(Lead $lead, string $reply): bool
    {
        try {
            if ($lead->platform === 'whatsapp') {
                return app(WhatsAppService::class)->sendText($lead->platform_id, $reply);
            }

            // Instagram (Instagram Login API) — send via graph.instagram.com.
            // Required for IGAA-prefixed Instagram user tokens; the Facebook Graph
            // host rejects them.
            if ($lead->platform === 'instagram') {
                $igToken = Setting::get('instagram_access_token', '') ?: (string) config('services.instagram.access_token');

                if (empty($igToken)) {
                    Log::error('Nia: instagram_access_token not configured');
                    return false;
                }

                return $this->sendInstagramMessage($lead->platform_id, $reply, $igToken);
            }

            // Facebook Messenger uses the Graph Send API
            $token = Setting::get('whatsapp_api_token', '') ?: config('services.meta.page_access_token');

            if (empty($token)) {
                Log::error('Nia: META_PAGE_ACCESS_TOKEN not configured');
                return false;
            }

            return $this->sendMessengerMessage($lead->platform_id, $reply, $token);

        } catch (\Throwable $e) {
            Log::error('Nia: Failed to send reply', [
                'lead_id'  => $lead->id,
                'platform' => $lead->platform,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Parse AI commands from the response and execute side effects.
     * Returns the cleaned reply with commands stripped.
     */
    public function processAiCommands(Lead $lead, string $reply): string
    {
        $clean = $reply;

        // [NIA_QUALIFIED] — advance lead stage
        if (str_contains($clean, '[NIA_QUALIFIED]')) {
            $lead->update(['stage' => 'qualified']);
            $clean = str_replace('[NIA_QUALIFIED]', '', $clean);
            Log::info('Nia: Lead qualified', ['lead_id' => $lead->id]);
        }

        // [SCHEDULE_CALL] — flag for follow-up
        if (str_contains($clean, '[SCHEDULE_CALL]')) {
            $existingTags = $lead->tags ?? [];
            if (!in_array('callback_requested', $existingTags)) {
                $lead->update([
                    'tags' => array_merge($existingTags, ['callback_requested']),
                ]);
            }
            $clean = str_replace('[SCHEDULE_CALL]', '', $clean);
            Log::info('Nia: Callback requested', ['lead_id' => $lead->id]);
        }

        // [LEAD_CONTEXT:...] — save context to notes
        if (preg_match_all('/\[LEAD_CONTEXT:(.+?)\]/', $clean, $matches)) {
            foreach ($matches[1] as $context) {
                $context = trim($context);
                $existing = $lead->notes ?? '';
                $lead->update([
                    'notes' => trim($existing . "\n" . $context),
                ]);
            }
            $clean = preg_replace('/\[LEAD_CONTEXT:.+?\]/', '', $clean);
            Log::info('Nia: Lead context saved', ['lead_id' => $lead->id]);
        }

        return trim($clean);
    }

    /**
     * Send message via Facebook Messenger Send API.
     */
    private function sendMessengerMessage(string $recipientId, string $message, string $token): bool
    {
        $response = Http::post(
            "https://graph.facebook.com/v21.0/me/messages?access_token={$token}",
            [
                'recipient' => ['id' => $recipientId],
                'message'   => ['text' => $message],
            ]
        );

        if ($response->failed()) {
            Log::error('Nia: Messenger send failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Send a direct message via the Instagram Messaging API (Instagram Login).
     * Uses graph.instagram.com — required for IGAA-prefixed Instagram user tokens.
     */
    private function sendInstagramMessage(string $recipientId, string $message, string $token): bool
    {
        $response = Http::asJson()->post(
            'https://graph.instagram.com/v21.0/me/messages',
            [
                'recipient'    => ['id' => $recipientId],
                'message'      => ['text' => $message],
                'access_token' => $token,
            ]
        );

        if ($response->failed()) {
            Log::error('Nia: Instagram send failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        }

        return true;
    }

}
