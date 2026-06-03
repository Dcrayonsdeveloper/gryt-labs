<?php

namespace App\Providers;

use App\Events\OrderDelivered;
use App\Events\OrderPlaced;
use App\Events\OrderShipped;
use App\Events\OrderStatusChanged;
use App\Events\PosSaleCompleted;
use App\Events\RefundProcessed;
use App\Events\ReturnRequested;
use App\Events\ReviewCreated;
use App\Listeners\AwardLoyaltyPoints;
use App\Listeners\CheckOrderFraud;
use App\Listeners\ProcessAffiliateCommission;
use App\Listeners\PushOrderToShiprocket;
use App\Listeners\RestoreGiftCardOnRefund;
use App\Listeners\SendCouponAfterReview;
use App\Listeners\SendOrderNotification;
use App\Listeners\SendPasswordChangedNotification;
use App\Listeners\SendReviewInvitationAfterDelivery;
use App\Listeners\SendRegistrationNotification;
use App\Listeners\UpdateRecommendationData;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendRegistrationNotification::class,
        ],
        OrderPlaced::class => [
            [CheckOrderFraud::class, 'handle'],
            [SendOrderNotification::class, 'handleOrderPlaced'],
            [UpdateRecommendationData::class, 'handleOrderPlaced'],
            [ProcessAffiliateCommission::class, 'handleOrderPlaced'],
            PushOrderToShiprocket::class,
        ],
        OrderStatusChanged::class => [
            [SendOrderNotification::class, 'handleOrderStatusChanged'],
        ],
        OrderShipped::class => [
            [SendOrderNotification::class, 'handleOrderShipped'],
        ],
        OrderDelivered::class => [
            [SendOrderNotification::class, 'handleOrderDelivered'],
            // TrackOrderAnalytics removed: Purchase CAPI must fire at checkout, not on delivery.
            // Firing here caused duplicate Purchase events days after the actual sale (PIR-2026-04-30).
            SendReviewInvitationAfterDelivery::class,
            [ProcessAffiliateCommission::class, 'handleOrderDelivered'],
            AwardLoyaltyPoints::class,
        ],
        ReturnRequested::class => [
            [SendOrderNotification::class, 'handleReturnRequested'],
        ],
        RefundProcessed::class => [
            [SendOrderNotification::class, 'handleRefundProcessed'],
            RestoreGiftCardOnRefund::class,
        ],
        PosSaleCompleted::class => [
            [UpdateRecommendationData::class, 'handlePosSaleCompleted'],
        ],
        PasswordReset::class => [
            SendPasswordChangedNotification::class,
        ],
        ReviewCreated::class => [
            SendCouponAfterReview::class,
        ],
    ];
}
