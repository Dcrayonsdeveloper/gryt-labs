<?php

namespace App\Models;

use App\Services\ShiprocketCheckout\ShiprocketWebhookEventData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable record of a single Shiprocket Checkout webhook event.
 *
 * One checkout session produces 4-5 rows (INIT ×2-3, PAYMENT_INITIATED,
 * ORDER_PLACED, Payment Complete). All share the same cart_id.
 *
 * Never update rows — only insert. Use the timeline() scope to get
 * all events for a checkout session in chronological order.
 */
class ShiprocketCheckoutEvent extends Model
{
    protected $table = 'shiprocket_checkout_events';

    protected $fillable = [
        'cart_id', 'stage', 'source_name', 'payload_hash', 'is_duplicate',
        'phone', 'phone_verified', 'email', 'full_name', 'first_name', 'last_name',
        'address_line_1', 'address_line_2', 'city', 'state', 'pincode', 'country', 'address_hash',
        'total_price', 'shipping_price', 'total_discount', 'net_payable', 'tax', 'item_count', 'items',
        'payment_mode', 'payment_gateway', 'payment_amount', 'transaction_id',
        'rto_prediction', 'risk_analysis',
        'ip_address', 'abandoned_checkout_id', 'order_id', 'raw_payload',
        'received_at', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_duplicate'   => 'boolean',
            'phone_verified' => 'boolean',
            'items'          => 'array',
            'risk_analysis'  => 'array',
            'raw_payload'    => 'array',
            'received_at'    => 'datetime',
            'processed_at'   => 'datetime',
            'total_price'    => 'float',
            'shipping_price' => 'float',
            'total_discount' => 'float',
            'net_payable'    => 'float',
            'tax'            => 'float',
            'payment_amount' => 'float',
            'item_count'     => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function abandonedCheckout(): BelongsTo
    {
        return $this->belongsTo(AbandonedCheckout::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /** All events for a checkout session, oldest first (timeline view). */
    public function scopeTimeline($query, string $cartId)
    {
        return $query->where('cart_id', $cartId)->orderBy('received_at');
    }

    /** Non-duplicate events only. */
    public function scopeUnique($query)
    {
        return $query->where('is_duplicate', false);
    }

    /** Events with payment data (ORDER_PLACED / Payment Complete). */
    public function scopeWithPayment($query)
    {
        return $query->whereNotNull('payment_mode');
    }

    // ─── Factory ──────────────────────────────────────────────────────────────

    /**
     * Build a model instance from a DTO + context flags.
     * Does NOT save — caller decides whether to persist.
     */
    public static function fromDTO(
        ShiprocketWebhookEventData $dto,
        bool $isDuplicate = false,
        bool $isNewCustomer = false,
        bool $addressChanged = false,
    ): self {
        $event = new self();
        $event->cart_id         = $dto->cartId;
        $event->stage           = $dto->stage;
        $event->source_name     = $dto->sourceName;
        $event->payload_hash    = $dto->payloadHash;
        $event->is_duplicate    = $isDuplicate;

        $event->phone           = $dto->phone;
        $event->phone_verified  = $dto->phoneVerified;
        $event->email           = $dto->email;
        $event->full_name       = $dto->fullName;
        $event->first_name      = $dto->firstName;
        $event->last_name       = $dto->lastName;

        $event->address_line_1  = $dto->addressLine1;
        $event->address_line_2  = $dto->addressLine2;
        $event->city            = $dto->city;
        $event->state           = $dto->state;
        $event->pincode         = $dto->pincode;
        $event->country         = $dto->country;
        $event->address_hash    = $dto->addressHash;

        $event->total_price     = $dto->totalPrice;
        $event->shipping_price  = $dto->shippingPrice;
        $event->total_discount  = $dto->totalDiscount;
        $event->net_payable     = $dto->netPayable;
        $event->tax             = $dto->tax;
        $event->item_count      = $dto->itemCount;
        $event->items           = $dto->items ?: null;

        $event->payment_mode    = $dto->paymentMode;
        $event->payment_gateway = $dto->paymentGateway;
        $event->payment_amount  = $dto->paymentAmount;
        $event->transaction_id  = $dto->transactionId;

        $event->rto_prediction  = $dto->rtoPrediction;
        $event->risk_analysis   = $dto->computeRiskAnalysis($isNewCustomer, $addressChanged);

        $event->ip_address      = $dto->ipAddress;
        $event->raw_payload     = $dto->rawPayload;
        $event->received_at     = now();

        return $event;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** Human-readable stage label for the admin timeline. */
    public function stageLabel(): string
    {
        return match ($this->stage) {
            'INIT'                => 'Cart Initiated',
            'PAYMENT_INITIATED'   => 'Payment Initiated',
            'ORDER_PLACED'        => 'Order Placed',
            'Payment Complete'    => 'Payment Complete',
            'Abandoned Cart'      => 'Cart Abandoned',
            default               => ucwords(strtolower(str_replace('_', ' ', $this->stage))),
        };
    }

    /** Icon for the admin timeline (Tailwind / Heroicons names). */
    public function stageIcon(): string
    {
        return match ($this->stage) {
            'INIT'              => 'shopping-cart',
            'PAYMENT_INITIATED' => 'credit-card',
            'ORDER_PLACED'      => 'check-circle',
            'Payment Complete'  => 'banknotes',
            'Abandoned Cart'    => 'x-circle',
            default             => 'information-circle',
        };
    }

    /** CSS colour class for the risk badge. */
    public function riskBadgeClass(): string
    {
        return match ($this->risk_analysis['level'] ?? 'low') {
            'high'   => 'bg-red-100 text-red-700',
            'medium' => 'bg-yellow-100 text-yellow-700',
            default  => 'bg-green-100 text-green-700',
        };
    }
}
