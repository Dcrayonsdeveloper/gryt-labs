<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadChat;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * BotFlowService — structured button-driven WhatsApp flow for GetSetNova.
 *
 * Mirrors the existing Nova Chatbot script (Register Warranty / Claim Cashback
 * / Raise Complaint) using WhatsApp interactive button + list messages.
 *
 * Coexists with Nia (the conversational AI in MessagingService/ClaudeService):
 *   - This service owns the state machine and never asks Claude anything.
 *   - When a customer goes off-script (text input that doesn't match the
 *     expected step), this service returns false and the caller routes the
 *     message to Nia for a Q&A reply. Nia is STRICT — she answers questions
 *     but never advances the flow state. The customer must explicitly press
 *     a button (or type a flow keyword) to start/continue a flow.
 *
 * State machine: see $this->statePolicy() for the canonical map.
 */
class BotFlowService
{
    // ───────── State constants ─────────
    public const STATE_INITIAL                    = null;
    public const STATE_MAIN_MENU_SENT             = 'MAIN_MENU_SENT';

    public const STATE_WARRANTY_TC_SENT           = 'WARRANTY_TC_SENT';
    public const STATE_WARRANTY_AWAITING_ORDER_ID = 'WARRANTY_AWAITING_ORDER_ID';
    public const STATE_WARRANTY_COMPLETE          = 'WARRANTY_COMPLETE';

    public const STATE_CASHBACK_TC_SENT           = 'CASHBACK_TC_SENT';
    public const STATE_CASHBACK_AWAITING_ORDER_ID = 'CASHBACK_AWAITING_ORDER_ID';
    public const STATE_CASHBACK_AWAITING_SCREEN   = 'CASHBACK_AWAITING_SCREEN';
    public const STATE_CASHBACK_AWAITING_UPI      = 'CASHBACK_AWAITING_UPI';
    public const STATE_CASHBACK_COMPLETE          = 'CASHBACK_COMPLETE';

    public const STATE_COMPLAINT_CATEGORY_SENT    = 'COMPLAINT_CATEGORY_SENT';
    public const STATE_COMPLAINT_AWAITING_ORDER_ID = 'COMPLAINT_AWAITING_ORDER_ID';
    public const STATE_COMPLAINT_AWAITING_DETAILS = 'COMPLAINT_AWAITING_DETAILS';
    public const STATE_COMPLAINT_COMPLETE         = 'COMPLAINT_COMPLETE';

    public const STATE_HUMAN_HANDOFF              = 'HUMAN_HANDOFF';

    // Stale flow expires after 24h of inactivity (matches WhatsApp service window)
    public const FLOW_STALE_AFTER_SECONDS = 86400;

    // Amazon order ID format
    private const ORDER_ID_REGEX = '/^\d{3}-\d{7}-\d{7}$/';
    private const UPI_REGEX = '/^[A-Za-z0-9._\-]{2,}@[A-Za-z][A-Za-z0-9]{2,}$/';

    /**
     * Dispatch an interactive button or list reply.
     * Returns true if BotFlow handled it (always, for known button IDs).
     */
    public function handleButtonPress(Lead $lead, string $buttonId, ?string $buttonTitle = null): bool
    {
        $this->logCustomerTurn($lead, '[button: ' . $buttonId . ($buttonTitle ? ' — ' . $buttonTitle : '') . ']');
        $this->expireStaleFlow($lead);

        return match (true) {
            $buttonId === 'flow_warranty'     => $this->startWarrantyFlow($lead),
            $buttonId === 'flow_cashback'     => $this->startCashbackFlow($lead),
            $buttonId === 'flow_complaint'    => $this->startComplaintFlow($lead),
            $buttonId === 'flow_cancel'       => $this->cancelFlow($lead),
            $buttonId === 'accept_tc'         => $this->handleTcAccept($lead),
            str_starts_with($buttonId, 'comp_') => $this->handleComplaintCategory($lead, $buttonId, $buttonTitle ?? ''),
            default => $this->unknownButton($lead, $buttonId),
        };
    }

    /**
     * Dispatch a text message.
     * Returns true if BotFlow handled it; false → caller should route to Nia.
     *
     * We pre-log the customer turn before dispatch so that chat history stays
     * chronological when BotFlow replies (customer → bot). If BotFlow doesn't
     * consume the message, we delete the row we just created so the caller's
     * downstream processIncoming() can log + dedupe it normally.
     */
    public function handleText(Lead $lead, string $text, ?string $messageId = null): bool
    {
        $this->expireStaleFlow($lead);

        $customerChat = LeadChat::create([
            'lead_id'             => $lead->id,
            'sender'              => 'customer',
            'message'             => $text,
            'platform_message_id' => $messageId,
        ]);

        $consumed = $this->dispatchText($lead, $text);

        if (!$consumed) {
            $customerChat->delete();
        }

        return $consumed;
    }

    private function dispatchText(Lead $lead, string $text): bool
    {
        $trim = trim($text);
        $lower = mb_strtolower($trim);

        // 1) Human-handoff keyword has priority over everything else.
        if ($this->isHumanHandoffKeyword($lower)) {
            return $this->triggerHumanHandoff($lead);
        }

        // 2) Flow-trigger keywords (only honored when not already mid-flow,
        //    or when the customer explicitly restarts).
        $isMidFlow = $lead->bot_state !== null && !$this->isTerminalState($lead->bot_state);
        $isExplicitRestart = in_array($lower, ['menu', 'start', 'restart', 'main menu', 'मुख्य मेनू'], true);

        if (!$isMidFlow || $isExplicitRestart) {
            if ($this->isGreetingKeyword($lower) || $isExplicitRestart) {
                return $this->sendWelcomeMenu($lead);
            }
            if ($this->isWarrantyKeyword($lower)) {
                return $this->startWarrantyFlow($lead);
            }
            if ($this->isCashbackKeyword($lower)) {
                return $this->startCashbackFlow($lead);
            }
            if ($this->isComplaintKeyword($lower)) {
                return $this->startComplaintFlow($lead);
            }
        }

        // 3) Mid-flow text input — depends on the current state.
        return match ($lead->bot_state) {
            self::STATE_WARRANTY_AWAITING_ORDER_ID  => $this->handleOrderIdInput($lead, $trim, 'warranty'),
            self::STATE_CASHBACK_AWAITING_ORDER_ID  => $this->handleOrderIdInput($lead, $trim, 'cashback'),
            self::STATE_CASHBACK_AWAITING_UPI       => $this->handleUpiInput($lead, $trim),
            self::STATE_COMPLAINT_AWAITING_ORDER_ID => $this->handleOrderIdInput($lead, $trim, 'complaint'),
            self::STATE_COMPLAINT_AWAITING_DETAILS  => $this->handleComplaintDetails($lead, $trim),
            // No state, no keyword match → let Nia answer the question.
            default => false,
        };
    }

    /**
     * Dispatch an inbound image/video (used in the cashback flow to capture
     * the screenshot of the Amazon review). Returns true if BotFlow used it.
     */
    public function handleMedia(Lead $lead, string $mediaType, string $mediaId, ?string $caption = null): bool
    {
        $this->expireStaleFlow($lead);
        $this->logCustomerTurn($lead, '[' . $mediaType . ': ' . $mediaId . ($caption ? ' — ' . $caption : '') . ']');

        if ($lead->bot_state === self::STATE_CASHBACK_AWAITING_SCREEN) {
            $this->mergeStateData($lead, ['cashback_screenshot_media_id' => $mediaId]);
            $this->setState($lead, self::STATE_CASHBACK_AWAITING_UPI);

            $body = "✅ Screenshot received. Thank you!\n\n"
                  . "Last step — please share your **UPI ID** (e.g., `9988776655@ybl`) so we can send your {$this->cashbackLabel()} cashback.\n\n"
                  . "✅ स्क्रीनशॉट मिल गया, धन्यवाद!\n\nआखिरी चरण — कृपया अपना **UPI ID** भेजें (जैसे `9988776655@ybl`) ताकि हम {$this->cashbackLabel()} कैशबैक भेज सकें।";
            $this->sendText($lead, $body);
            return true;
        }

        // Media outside cashback flow → no special action; let it log only.
        return true;
    }

    // ────────────────────────────────────────────────────────────
    // FLOW STARTERS
    // ────────────────────────────────────────────────────────────

    public function sendWelcomeMenu(Lead $lead): bool
    {
        $this->setState($lead, self::STATE_MAIN_MENU_SENT);

        // First-message AI disclosure (Meta WhatsApp 2026 AI policy) embedded
        // in the welcome screen the very first time we ever message this lead.
        $isFirstContact = $lead->chats()->where('sender', 'bot')->count() === 0;
        $disclosure = $isFirstContact
            ? "👋 Hi *" . ($lead->name ?: 'there') . "*! Welcome to **GetSetNova** Support — I'm an automated assistant. Type *human* anytime to reach our team.\n\n"
              . "👋 नमस्ते! GetSetNova Support में स्वागत है — मैं एक स्वचालित सहायक हूँ। टीम से बात करने के लिए *human* टाइप करें।\n\n"
            : '';

        $body = $disclosure . "Please select an option below:\n\nनीचे एक विकल्प चुनें:";

        return $this->sendButtons($lead, $body, [
            ['id' => 'flow_warranty',  'title' => 'Register Warranty'],
            ['id' => 'flow_cashback',  'title' => 'Claim Cashback'],
            ['id' => 'flow_complaint', 'title' => 'Raise Complaint'],
        ]);
    }

    private function startWarrantyFlow(Lead $lead): bool
    {
        $this->setState($lead, self::STATE_WARRANTY_TC_SENT);

        $body = "📋 *Warranty Registration*\n\n"
              . "To get the 1-year warranty, register the product within 10 days of purchase. If you don't register in time, the warranty won't be available.\n\n"
              . "1 साल की वारंटी पाने के लिए प्रोडक्ट को खरीदने के 10 दिनों के भीतर रजिस्टर करना ज़रूरी है। अगर समय पर रजिस्ट्रेशन नहीं किया गया तो वारंटी का लाभ नहीं मिलेगा।\n\n"
              . "Tap *Accept* to proceed.\nजारी रखने के लिए *Accept* दबाएँ।";

        return $this->sendButtons($lead, $body, [
            ['id' => 'accept_tc',   'title' => 'Accept'],
            ['id' => 'flow_cancel', 'title' => 'Cancel'],
        ]);
    }

    private function startCashbackFlow(Lead $lead): bool
    {
        $this->setState($lead, self::STATE_CASHBACK_TC_SENT);

        $body = "🎉 *{$this->cashbackLabel()} Cashback Offer*\n\n"
              . "We offer {$this->cashbackLabel()} cashback for a genuine *4 or 5-star Amazon review* of your GetSetNova product. You'll need:\n"
              . "1. Your Amazon Order ID (format: `XXX-XXXXXXX-XXXXXXX`)\n"
              . "2. Screenshot of your posted Amazon review\n"
              . "3. Your UPI ID\n\n"
              . "Cashback is sent within 48 hours after verification.\n\n"
              . "हम आपके GetSetNova प्रोडक्ट के *4 या 5-स्टार Amazon रिव्यू* पर {$this->cashbackLabel()} कैशबैक देते हैं। जरूरत होगी:\n"
              . "1. Amazon Order ID\n2. रिव्यू का स्क्रीनशॉट\n3. UPI ID\n\n"
              . "Tap *Accept* to begin.\nशुरू करने के लिए *Accept* दबाएँ।";

        return $this->sendButtons($lead, $body, [
            ['id' => 'accept_tc',   'title' => 'Accept'],
            ['id' => 'flow_cancel', 'title' => 'Cancel'],
        ]);
    }

    private function startComplaintFlow(Lead $lead): bool
    {
        $this->setState($lead, self::STATE_COMPLAINT_CATEGORY_SENT);

        return $this->sendList(
            $lead,
            "We're sorry for the trouble. Please pick the issue closest to yours.\n\nहमें खेद है। कृपया अपनी समस्या चुनें।",
            'Choose issue',
            [[
                'title' => 'Issue type',
                'rows' => [
                    ['id' => 'comp_not_working', 'title' => 'Not working',     'description' => 'Product stopped working / motor not turning'],
                    ['id' => 'comp_damaged',     'title' => 'Damaged on arrival', 'description' => 'Item arrived broken or damaged'],
                    ['id' => 'comp_missing',     'title' => 'Missing part',    'description' => 'Something was missing from the package'],
                    ['id' => 'comp_wrong_item',  'title' => 'Wrong item',      'description' => 'I received a different product'],
                    ['id' => 'comp_other',       'title' => 'Other',           'description' => 'Something else'],
                ],
            ]]
        );
    }

    // ────────────────────────────────────────────────────────────
    // STATE HANDLERS
    // ────────────────────────────────────────────────────────────

    private function handleTcAccept(Lead $lead): bool
    {
        return match ($lead->bot_state) {
            self::STATE_WARRANTY_TC_SENT, self::STATE_CASHBACK_TC_SENT => $this->askForOrderId($lead),
            default => $this->unknownButton($lead, 'accept_tc'),
        };
    }

    private function askForOrderId(Lead $lead): bool
    {
        $nextState = match ($lead->bot_state) {
            self::STATE_WARRANTY_TC_SENT => self::STATE_WARRANTY_AWAITING_ORDER_ID,
            self::STATE_CASHBACK_TC_SENT => self::STATE_CASHBACK_AWAITING_ORDER_ID,
            default => self::STATE_WARRANTY_AWAITING_ORDER_ID,
        };
        $this->setState($lead, $nextState);

        $body = "Please send your *Amazon Order ID*.\n"
              . "Format: `XXX-XXXXXXX-XXXXXXX` (example: `402-1330906-1677956`)\n"
              . "Find it at: https://www.amazon.in/gp/your-account/order-history\n\n"
              . "कृपया अपना *Amazon Order ID* भेजें।\n"
              . "फॉर्मेट: `XXX-XXXXXXX-XXXXXXX` (उदाहरण: `402-1330906-1677956`)";

        $this->sendText($lead, $body);
        return true;
    }

    private function handleOrderIdInput(Lead $lead, string $text, string $flow): bool
    {
        // Try to extract a valid Amazon order ID from anywhere in the message.
        if (!preg_match('/(\d{3}-\d{7}-\d{7})/', $text, $matches)) {
            $body = "❌ That doesn't look like a valid Amazon Order ID. Please send it in the format `XXX-XXXXXXX-XXXXXXX` (example: `402-1330906-1677956`).\n\n"
                  . "❌ यह सही फॉर्मेट में नहीं है। कृपया इस फॉर्मेट में भेजें: `XXX-XXXXXXX-XXXXXXX`";
            $this->sendText($lead, $body);
            return true;
        }

        $orderId = $matches[1];
        $this->mergeStateData($lead, ['amazon_order_id' => $orderId]);

        if ($flow === 'warranty') {
            $this->setState($lead, self::STATE_WARRANTY_COMPLETE);
            $body = "✅ *Warranty registered!*\nOrder ID: `{$orderId}`\nYour 1-year warranty (manufacturing defects) is now active.\n\n"
                  . "✅ *वारंटी रजिस्टर हो गई!*\nOrder ID: `{$orderId}`\n1 साल की वारंटी (manufacturing defects) अब सक्रिय है।\n\n"
                  . "If you loved the product, please leave us a quick *4-5 star Amazon review* — it helps a lot 🙏\nhttps://www.amazon.in/review/review-your-purchases\n\n"
                  . "(To claim {$this->cashbackLabel()} cashback for that review, type *cashback*)";
            $this->sendText($lead, $body);
            $this->logBotMarker($lead, '[LEAD_CONTEXT:warranty_registered_order_id=' . $orderId . ']');
            return true;
        }

        if ($flow === 'cashback') {
            $this->setState($lead, self::STATE_CASHBACK_AWAITING_SCREEN);
            $body = "✅ Order ID `{$orderId}` recorded.\n\n"
                  . "Now please send us a *screenshot of your posted Amazon review* as an image in this chat.\n\n"
                  . "✅ Order ID `{$orderId}` मिल गया।\n\nअब कृपया अपने Amazon रिव्यू का *स्क्रीनशॉट* इस चैट में भेजें।";
            $this->sendText($lead, $body);
            return true;
        }

        if ($flow === 'complaint') {
            $this->setState($lead, self::STATE_COMPLAINT_AWAITING_DETAILS);
            $body = "✅ Order ID `{$orderId}` recorded.\n\n"
                  . "Please briefly describe the issue (1-2 sentences). You can also attach a photo or video if it helps.\n\n"
                  . "✅ Order ID मिल गया।\n\nकृपया समस्या का संक्षेप में वर्णन करें (1-2 वाक्य)। फोटो/वीडियो भी भेज सकते हैं।";
            $this->sendText($lead, $body);
            return true;
        }

        return false;
    }

    private function handleUpiInput(Lead $lead, string $text): bool
    {
        // Match a UPI ID inside the message; relaxed enough to accept common typos.
        if (!preg_match('/([A-Za-z0-9._\-]{2,}@[A-Za-z][A-Za-z0-9]{2,})/', $text, $matches)) {
            $body = "❌ That doesn't look like a UPI ID. Please send something like `9988776655@ybl` or `name@okhdfcbank`.\n\n"
                  . "❌ कृपया UPI ID भेजें (उदाहरण: `9988776655@ybl`)";
            $this->sendText($lead, $body);
            return true;
        }

        $upi = $matches[1];
        $this->mergeStateData($lead, ['upi_id' => $upi]);

        $data = $lead->bot_state_data ?? [];
        $orderId = $data['amazon_order_id'] ?? 'unknown';

        $this->setState($lead, self::STATE_CASHBACK_COMPLETE);
        $body = "🎉 *Cashback claim submitted!*\n\n"
              . "Order ID: `{$orderId}`\nUPI: `{$upi}`\nAmount: *{$this->cashbackLabel()}*\n\n"
              . "Our team will verify your review and send the cashback to your UPI within *48 hours*. You'll get a confirmation here on WhatsApp.\n\n"
              . "🎉 *कैशबैक क्लेम जमा हो गया!*\nहमारी टीम आपके रिव्यू की पुष्टि करके *48 घंटों* में आपकी UPI पर {$this->cashbackLabel()} भेज देगी।\n\n"
              . "Thanks for supporting our small family-owned business 🙏";
        $this->sendText($lead, $body);

        // Markers for downstream handling (team queue / lead enrichment)
        $this->logBotMarker($lead, '[NIA_QUALIFIED][SCHEDULE_CALL][LEAD_CONTEXT:cashback_claim_amazon_order_id=' . $orderId . ',upi=' . $upi . ']');
        $this->upgradeStage($lead, 'qualified');
        return true;
    }

    private function handleComplaintCategory(Lead $lead, string $buttonId, string $buttonTitle): bool
    {
        if ($lead->bot_state !== self::STATE_COMPLAINT_CATEGORY_SENT) {
            return $this->unknownButton($lead, $buttonId);
        }
        $this->mergeStateData($lead, ['complaint_category' => $buttonTitle ?: $buttonId]);
        $this->setState($lead, self::STATE_COMPLAINT_AWAITING_ORDER_ID);

        $body = "Sorry to hear that. We've noted: *" . ($buttonTitle ?: $buttonId) . "*.\n\n"
              . "Please share your *Amazon Order ID* so our team can look up your order.\nFormat: `XXX-XXXXXXX-XXXXXXX`\n\n"
              . "कृपया अपना Amazon Order ID भेजें।";
        $this->sendText($lead, $body);
        return true;
    }

    private function handleComplaintDetails(Lead $lead, string $text): bool
    {
        $this->mergeStateData($lead, ['complaint_details' => mb_substr($text, 0, 1000)]);
        $this->setState($lead, self::STATE_COMPLAINT_COMPLETE);

        $body = "🙏 Thank you for the details. We're sorry again for the trouble.\n\n"
              . "*A team member will reach out on this WhatsApp within 24 hours* to resolve this. You don't need to do anything else right now.\n\n"
              . "🙏 जानकारी के लिए धन्यवाद। *हमारी टीम 24 घंटों के अंदर इसी WhatsApp पर संपर्क करेगी।*";
        $this->sendText($lead, $body);

        $this->logBotMarker($lead, '[SCHEDULE_CALL][LEAD_CONTEXT:complaint=' . json_encode($lead->bot_state_data ?? []) . ']');
        $this->upgradeStage($lead, 'qualified');
        return true;
    }

    private function triggerHumanHandoff(Lead $lead): bool
    {
        $this->setState($lead, self::STATE_HUMAN_HANDOFF);
        $body = "👤 Got it — connecting you to our team. *A teammate will reach out here on WhatsApp within 24 hours.*\n\n"
              . "👤 ठीक है — हमारी टीम 24 घंटों के अंदर यहीं WhatsApp पर संपर्क करेगी।\n\n"
              . "Meanwhile, if you'd like to use the menu again, just type *menu*.";
        $this->sendText($lead, $body);
        $this->logBotMarker($lead, '[SCHEDULE_CALL][LEAD_CONTEXT:human_handoff_requested]');
        $this->upgradeStage($lead, 'qualified');
        return true;
    }

    private function cancelFlow(Lead $lead): bool
    {
        $this->setState($lead, null);
        $this->sendText($lead, "Cancelled. Type *menu* to start again, or ask me anything.\n\nरद्द किया। *menu* टाइप करें, या कुछ भी पूछें।");
        return true;
    }

    private function unknownButton(Lead $lead, string $buttonId): bool
    {
        Log::warning('BotFlow: unknown button pressed', [
            'lead_id'   => $lead->id,
            'button_id' => $buttonId,
            'state'     => $lead->bot_state,
        ]);
        return $this->sendWelcomeMenu($lead);
    }

    // ────────────────────────────────────────────────────────────
    // KEYWORD MATCHERS
    // ────────────────────────────────────────────────────────────

    private function isGreetingKeyword(string $lower): bool
    {
        $greetings = ['hi', 'hii', 'hiii', 'hello', 'helo', 'hey', 'heyy', 'namaste', 'नमस्ते', 'नमस्कार', 'हाय'];
        return in_array($lower, $greetings, true);
    }

    private function isWarrantyKeyword(string $lower): bool
    {
        return str_contains($lower, 'warranty')
            || str_contains($lower, 'register')
            || str_contains($lower, 'वारंटी')
            || str_contains($lower, 'वारेंटी');
    }

    private function isCashbackKeyword(string $lower): bool
    {
        return str_contains($lower, 'cashback')
            || str_contains($lower, 'cash back')
            || str_contains($lower, 'cash-back')
            || str_contains($lower, 'कैशबैक')
            || str_contains($lower, 'कैश बैक');
    }

    private function isComplaintKeyword(string $lower): bool
    {
        return str_contains($lower, 'complaint')
            || str_contains($lower, 'complain')
            || str_contains($lower, 'issue')
            || str_contains($lower, 'problem')
            || str_contains($lower, 'not working')
            || str_contains($lower, 'damaged')
            || str_contains($lower, 'broken')
            || str_contains($lower, 'शिकायत')
            || str_contains($lower, 'खराब');
    }

    private function isHumanHandoffKeyword(string $lower): bool
    {
        $words = ['human', 'agent', 'real person', 'talk to person', 'talk to team', 'team', 'executive', 'support staff', 'इंसान', 'आदमी', 'व्यक्ति'];
        foreach ($words as $w) {
            if ($lower === $w || str_contains($lower, $w)) {
                return true;
            }
        }
        return false;
    }

    // ────────────────────────────────────────────────────────────
    // STATE PERSISTENCE
    // ────────────────────────────────────────────────────────────

    private function setState(Lead $lead, ?string $state): void
    {
        $lead->update([
            'bot_state'            => $state,
            'bot_state_updated_at' => now(),
        ]);
    }

    private function mergeStateData(Lead $lead, array $patch): void
    {
        $merged = array_merge($lead->bot_state_data ?? [], $patch);
        $lead->update(['bot_state_data' => $merged]);
    }

    private function expireStaleFlow(Lead $lead): void
    {
        if ($lead->bot_state === null || $this->isTerminalState($lead->bot_state)) {
            return;
        }
        if (!$lead->bot_state_updated_at) {
            return;
        }
        $ageSeconds = now()->diffInSeconds($lead->bot_state_updated_at);
        if ($ageSeconds > self::FLOW_STALE_AFTER_SECONDS) {
            Log::info('BotFlow: expiring stale flow', ['lead_id' => $lead->id, 'state' => $lead->bot_state, 'age_seconds' => $ageSeconds]);
            $this->setState($lead, null);
            $lead->refresh();
        }
    }

    private function isTerminalState(?string $state): bool
    {
        return in_array($state, [
            self::STATE_WARRANTY_COMPLETE,
            self::STATE_CASHBACK_COMPLETE,
            self::STATE_COMPLAINT_COMPLETE,
            self::STATE_HUMAN_HANDOFF,
        ], true);
    }

    private function upgradeStage(Lead $lead, string $stage): void
    {
        if ($lead->stage !== $stage) {
            $lead->update(['stage' => $stage]);
        }
    }

    // ────────────────────────────────────────────────────────────
    // CHAT HISTORY
    // ────────────────────────────────────────────────────────────

    private function logCustomerTurn(Lead $lead, string $message): void
    {
        LeadChat::create([
            'lead_id' => $lead->id,
            'sender'  => 'customer',
            'message' => $message,
        ]);
    }

    private function logBotTurn(Lead $lead, string $message): void
    {
        LeadChat::create([
            'lead_id' => $lead->id,
            'sender'  => 'bot',
            'message' => $message,
        ]);
    }

    private function logBotMarker(Lead $lead, string $marker): void
    {
        // Markers are operational metadata, not customer-facing text.
        // Stored as a bot turn so they appear in chat history for the team UI.
        $this->logBotTurn($lead, $marker);
    }

    private function cashbackLabel(): string
    {
        $currency = Setting::get('cashback_currency', '₹');
        $amount   = Setting::get('cashback_amount', '50');
        return $currency . $amount;
    }

    // ────────────────────────────────────────────────────────────
    // WHATSAPP SENDERS
    // ────────────────────────────────────────────────────────────

    private function sendText(Lead $lead, string $body): bool
    {
        $this->logBotTurn($lead, $body);
        return $this->sendToWhatsApp($lead->platform_id, [
            'messaging_product' => 'whatsapp',
            'to'                => $lead->platform_id,
            'type'              => 'text',
            'text'              => ['body' => $body],
        ]);
    }

    /**
     * @param array<int, array{id: string, title: string}> $buttons  Max 3.
     */
    private function sendButtons(Lead $lead, string $body, array $buttons): bool
    {
        // WhatsApp button title hard limit: 20 chars. Truncate to be safe.
        $payloadButtons = array_map(fn ($b) => [
            'type'  => 'reply',
            'reply' => [
                'id'    => $b['id'],
                'title' => mb_substr($b['title'], 0, 20),
            ],
        ], array_slice($buttons, 0, 3));

        $this->logBotTurn($lead, $body . "\n\n[buttons: " . implode(' | ', array_map(fn ($b) => $b['title'], $buttons)) . ']');

        return $this->sendToWhatsApp($lead->platform_id, [
            'messaging_product' => 'whatsapp',
            'to'                => $lead->platform_id,
            'type'              => 'interactive',
            'interactive'       => [
                'type' => 'button',
                'body' => ['text' => mb_substr($body, 0, 1024)],
                'action' => ['buttons' => $payloadButtons],
            ],
        ]);
    }

    /**
     * @param array<int, array{title: string, rows: array<int, array{id: string, title: string, description?: string}>}> $sections
     */
    private function sendList(Lead $lead, string $body, string $buttonLabel, array $sections): bool
    {
        $payloadSections = array_map(fn ($s) => [
            'title' => mb_substr($s['title'], 0, 24),
            'rows'  => array_map(fn ($r) => [
                'id'          => $r['id'],
                'title'       => mb_substr($r['title'], 0, 24),
                'description' => isset($r['description']) ? mb_substr($r['description'], 0, 72) : '',
            ], $s['rows']),
        ], $sections);

        $rowTitles = [];
        foreach ($sections as $s) {
            foreach ($s['rows'] as $r) {
                $rowTitles[] = $r['title'];
            }
        }
        $this->logBotTurn($lead, $body . "\n\n[list: " . implode(' | ', $rowTitles) . ']');

        return $this->sendToWhatsApp($lead->platform_id, [
            'messaging_product' => 'whatsapp',
            'to'                => $lead->platform_id,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'list',
                'body'   => ['text' => mb_substr($body, 0, 1024)],
                'action' => [
                    'button'   => mb_substr($buttonLabel, 0, 20),
                    'sections' => $payloadSections,
                ],
            ],
        ]);
    }

    private function sendToWhatsApp(string $to, array $payload): bool
    {
        $token   = Setting::get('whatsapp_api_token', '') ?: config('services.whatsapp.token');
        $phoneId = Setting::get('whatsapp_phone_number_id', '') ?: config('services.whatsapp.phone_number_id');

        if (!$token || !$phoneId) {
            Log::warning('BotFlow: WhatsApp credentials missing', ['to' => $to]);
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->post("https://graph.facebook.com/v21.0/{$phoneId}/messages", $payload);

            if (!$response->successful()) {
                Log::error('BotFlow: WhatsApp send failed', [
                    'to'     => $to,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('BotFlow: WhatsApp send exception', ['to' => $to, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
