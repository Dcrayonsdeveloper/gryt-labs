<?php

namespace App\Http\Controllers;

use App\Events\OrderPlaced;
use App\Models\AbandonedCheckout;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Affiliate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\UserAddress;
use App\Services\AnalyticsService;
use App\Services\CashfreeService;
use App\Services\DelhiveryService;
use App\Services\RecommendationService;
use App\Services\ShiprocketService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Check if cart contains a product that qualifies for free shipping.
     */
    private function cartHasFreeShippingProduct(Cart $cart): bool
    {
        $ids = Setting::get('free_shipping_product_ids', '');
        if (!$ids) {
            return false;
        }
        $freeShipProductIds = array_map('intval', array_filter(explode(',', $ids)));
        if (empty($freeShipProductIds)) {
            return false;
        }
        return $cart->items->whereIn('product_id', $freeShipProductIds)->isNotEmpty();
    }

    private function logActivity(string $event, array $details = [], ?Request $request = null): void
    {
        try {
            $r = $request ?? request();
            DB::table('customer_activity_logs')->insert([
                'session_id' => session()->getId(),
                'user_id' => auth()->id(),
                'guest_email' => $r->input('guest_email'),
                'guest_phone' => $r->input('guest_phone'),
                'event' => $event,
                'details' => json_encode($details),
                'ip_address' => $r->ip(),
                'user_agent' => $r->userAgent(),
                'page_url' => $r->fullUrl(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Activity log failed', ['event' => $event, 'error' => $e->getMessage()]);
        }
    }

    public function index(): View|RedirectResponse
    {
        $this->logActivity('checkout_viewed');
        $cart = $this->getCart();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $isGuest = !auth()->check();
        $addresses = $isGuest ? collect() : UserAddress::where('user_id', auth()->id())->get();
        $defaultAddress = $addresses->where('is_default', true)->first() ?? $addresses->first();

        $paymentSettings = Setting::where('group', 'payment')->pluck('value', 'key');

        // Fetch only coupons that are valid for this cart's subtotal
        $cartSubtotal = $cart->subtotal;
        $availableCoupons = Coupon::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereColumn('times_used', '<', 'usage_limit');
            })
            ->where(function ($q) use ($cartSubtotal) {
                $q->where('min_order_amount', '<=', $cartSubtotal)
                  ->orWhere('min_order_amount', 0);
            })
            ->orderByDesc('value')
            ->get();

        // Navratri offer active check
        $navratriActive = Setting::get('navratri_offer_active', '0') === '1';

        // Shipping fee calculation for display
        $freeShipThreshold = (float) Setting::get('free_shipping_threshold', 499);
        $hasFreeShippingCoupon = $cart->coupon && ($cart->coupon->type === 'free_shipping' || $cart->discount >= $cart->subtotal);
        $flatShipRate = (float) Setting::get('flat_rate_amount', 50);
        $shippingFee = ($hasFreeShippingCoupon || ($cart->subtotal - $cart->discount) >= $freeShipThreshold || $this->cartHasFreeShippingProduct($cart)) ? 0 : $flatShipRate;

        // Record abandoned checkout
        $this->recordAbandonedCheckout($cart, 'checkout');

        // Facebook CAPI: InitiateCheckout
        $fbEventId = AnalyticsService::generateEventId('ic');
        $contentIds = $cart->items->pluck('product_id')->map(fn ($id) => (string) $id)->toArray();
        app(AnalyticsService::class)->trackInitiateCheckout(
            (float) ($cart->subtotal - $cart->discount),
            $cart->items->sum('quantity'),
            $contentIds,
            request(),
            $fbEventId
        );

        // One-click checkout: check if user has preferences saved
        $oneClickReady = false;
        $checkoutPreference = null;
        if (!$isGuest) {
            $checkoutPreference = \App\Models\UserCheckoutPreference::where('user_id', auth()->id())->first();
            $oneClickReady = $checkoutPreference
                && $checkoutPreference->enable_one_click
                && $checkoutPreference->default_shipping_address_id
                && $defaultAddress;
        }

        // Loyalty points
        $loyaltyPoints = 0;
        $loyaltyValue = 0;
        $loyaltyEnabled = (bool) Setting::get('loyalty_enabled', true);
        if (!$isGuest && $loyaltyEnabled) {
            $loyaltyPoints = auth()->user()->loyalty_points_balance ?? 0;
            $loyaltyValue = round($loyaltyPoints * (float) Setting::get('loyalty_redeem_rate', 0.25), 2);
        }

        // Upsell / cross-sell recommendations
        $cartProductIds = $cart->items->pluck('product_id')->toArray();
        $upsellProducts = collect();
        if (!empty($cartProductIds)) {
            $recService = app(RecommendationService::class);
            // Try "frequently bought together" first for each cart product
            foreach ($cartProductIds as $pid) {
                $fbt = $recService->frequentlyBoughtTogether($pid, 4);
                $upsellProducts = $upsellProducts->merge($fbt);
            }
            $upsellProducts = $upsellProducts->unique('id')->whereNotIn('id', $cartProductIds)->take(4)->values();

            // Fallback: same-category products if not enough FBT results
            if ($upsellProducts->count() < 4) {
                $categoryIds = $cart->items->pluck('product.category_id')->filter()->unique()->toArray();
                if (!empty($categoryIds)) {
                    $excludeIds = array_merge($cartProductIds, $upsellProducts->pluck('id')->toArray());
                    $fallback = \App\Models\Product::whereIn('category_id', $categoryIds)
                        ->where('is_active', true)
                        ->whereNull('deleted_at')
                        ->whereNotIn('id', $excludeIds)
                        ->orderByDesc('sales_count')
                        ->with(['primaryImage'])
                        ->limit(4 - $upsellProducts->count())
                        ->get();
                    $upsellProducts = $upsellProducts->merge($fallback)->take(4)->values();
                }
            }
        }

        // Pre-compute payment availability (Shopify-like: controller handles all business logic)
        $razorpayAvailable = ($paymentSettings['razorpay_enabled'] ?? '0') === '1'
            && !empty(Setting::get('razorpay_key_id'));
        $codAvailable = ($paymentSettings['cod_enabled'] ?? '1') === '1';
        $firstMethod = $razorpayAvailable ? 'razorpay' : ($codAvailable ? 'cod' : 'razorpay');

        // Pre-compute display values
        $displayTotal = max(0, $cart->total + $shippingFee);
        $codMinOrder = (int) Setting::get('cod_min_order', 199);
        $codMinAmt = (int) Setting::get('cod_minimum_amount', 199);
        $codAdvanceAmt = (int) Setting::get('cod_advance_amount', 100);
        $prepaidDiscountPct = (float) Setting::get('prepaid_discount_percent', 0);
        $taxCalculation = Setting::get('tax_calculation', 'inclusive');

        // Config for frontend JS (routes + computed data)
        $jsConfig = [
            'csrfToken' => csrf_token(),
            'routes' => [
                'process' => route('checkout.process'),
                'razorpayCreate' => route('checkout.razorpay.create'),
                'razorpayVerify' => route('checkout.razorpay.verify'),
                'abandonedCapture' => route('checkout.abandoned.capture'),
                'cartAdd' => route('cart.add'),
                'applyCoupon' => route('cart.apply-coupon'),
                'removeCoupon' => route('cart.remove-coupon'),
                'addressStore' => route('account.addresses.store'),
            ],
            'cart' => [
                'subtotal' => (float) $cart->subtotal,
                'discount' => (float) $cart->discount,
                'total' => (float) $cart->total,
                'couponCode' => $cart->coupon->code ?? '',
                'couponLabel' => $cart->coupon ? ($cart->coupon->description ?: $cart->coupon->code) : '',
                'items' => $cart->items->map(fn ($i) => [
                    'id' => $i->id,
                    'product_id' => $i->product_id,
                    'name' => $i->product->name ?? '',
                    'image' => $i->product->primary_image_url ?? '',
                    'price' => (float) $i->price,
                    'mrp' => (float) ($i->product->mrp ?? $i->price),
                    'quantity' => $i->quantity,
                    'url' => $i->product ? route('products.show', $i->product->slug) : '#',
                ])->values(),
            ],
            'shipping' => [
                'fee' => (float) $shippingFee,
                'freeThreshold' => (float) $freeShipThreshold,
            ],
            'payment' => [
                'razorpayAvailable' => $razorpayAvailable,
                'codAvailable' => $codAvailable,
                'firstMethod' => $firstMethod,
                'codMinOrder' => $codMinOrder,
                'codMinAmt' => $codMinAmt,
                'codAdvanceAmt' => $codAdvanceAmt,
                'prepaidDiscountPct' => $prepaidDiscountPct,
                'razorpayKeyId' => Setting::get('razorpay_key_id', ''),
            ],
            'theme' => [
                'storeLogo' => asset(Setting::get('store_logo', 'images/logo.png')),
                'primaryColor' => Setting::get('primary_color', '') ?: '#334155',
                'storeName' => Setting::get('store_name', config('app.name')),
            ],
            'displayTotal' => (float) $displayTotal,
        ];

        return view('checkout.index', compact(
            'cart', 'addresses', 'defaultAddress', 'paymentSettings',
            'isGuest', 'availableCoupons', 'navratriActive', 'fbEventId',
            'oneClickReady', 'checkoutPreference', 'loyaltyPoints', 'loyaltyValue',
            'upsellProducts', 'razorpayAvailable', 'codAvailable', 'firstMethod',
            'shippingFee', 'freeShipThreshold', 'flatShipRate', 'displayTotal', 'codMinOrder',
            'codMinAmt', 'codAdvanceAmt', 'prepaidDiscountPct', 'taxCalculation',
            'jsConfig'
        ));
    }

    public function process(Request $request): RedirectResponse
    {
        $isGuest = !auth()->check();

        $rules = [
            'same_billing_address' => ['nullable', 'boolean'],
            'payment_method' => ['required', 'string', 'in:cod,partial_pay,razorpay,upi,cashfree,free'],
            'gift_card_code' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        if ($isGuest) {
            $rules['guest_email'] = ['required', 'email', 'max:255'];
            $rules['guest_name'] = ['required', 'string', 'max:255'];
            $rules['guest_phone'] = ['required', 'string', 'regex:/^[6-9]\d{9}$/'];
            $rules['shipping_name'] = ['required', 'string', 'max:255'];
            // shipping_phone uses guest_phone
            $rules['shipping_address_line_1'] = ['required', 'string', 'max:255'];
            $rules['shipping_address_line_2'] = ['nullable', 'string', 'max:255'];
            $rules['shipping_city'] = ['required', 'string', 'max:100'];
            $rules['shipping_state'] = ['required', 'string', 'max:100'];
            $rules['shipping_postal_code'] = ['required', 'string', 'max:10'];
        } else {
            $rules['shipping_address_id'] = ['required', 'exists:user_addresses,id'];
            $rules['billing_address_id'] = ['nullable', 'integer'];
        }

        $validated = $request->validate($rules);

        $cart = $this->getCart(['items.product', 'items.variant', 'coupon']);

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        // Hard guard: refuse checkout when this tenant has NO payment gateway configured.
        // Without this, when Razorpay keys are empty the COD path silently falls through
        // to a ₹0 advance (see codAdvance block below), letting customers place orders
        // with zero verification — exploitable for fake orders.
        $hasRazorpay = !empty(Setting::get('razorpay_key_id')) && !empty(Setting::get('razorpay_key_secret'));
        $hasCashfree = !empty(Setting::get('cashfree_app_id')) && !empty(Setting::get('cashfree_secret_key'));
        // Compute the would-be total cheaply to know if this is a free order (100% discount).
        // Free orders bypass payment gateway entirely.
        $previewSubtotal = (float) $cart->subtotal;
        $previewDiscount = (float) $cart->discount;
        $isLikelyFreeOrder = ($previewSubtotal - $previewDiscount) <= 0 && $cart->coupon_id;

        if (!$hasRazorpay && !$hasCashfree && !$isLikelyFreeOrder) {
            Log::warning('Checkout attempted on tenant with no payment gateway configured', [
                'tenant' => function_exists('tenant') && tenant() ? tenant()->getTenantKey() : 'central',
                'cart_id' => $cart->id,
                'payment_method' => $request->input('payment_method'),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Online checkout is temporarily unavailable. Please try again later or contact support.');
        }

        // Re-validate stock and prices against current product data
        foreach ($cart->items as $item) {
            $available = $item->variant_id
                ? $item->variant->stock_quantity
                : $item->product->stock_quantity;

            if ($available < $item->quantity) {
                return redirect()->route('cart.index')
                    ->with('error', "\"{$item->product->name}\" only has {$available} item(s) in stock. Please update your cart.");
            }

            // Verify price hasn't changed since item was added to cart
            $currentPrice = $item->variant_id
                ? ($item->variant->price ?? $item->product->price)
                : $item->product->price;

            if (abs((float) $item->price - (float) $currentPrice) > 0.01) {
                // Update cart item to current price and recalculate
                $item->update(['price' => $currentPrice]);
                $cart->recalculate();
                $cart->refresh();

                return redirect()->route('checkout.index')
                    ->with('error', "The price of \"{$item->product->name}\" has changed. Your cart has been updated. Please review and try again.");
            }
        }

        // Server-side pincode serviceability check
        $shippingPincode = $isGuest
            ? $validated['shipping_postal_code']
            : UserAddress::where('user_id', auth()->id())->findOrFail($validated['shipping_address_id'])->postal_code;

        try {
            if (!$this->isPincodeServiceable($shippingPincode)) {
                return redirect()->route('checkout.index')
                    ->with('error', "Sorry, we don't deliver to pincode {$shippingPincode}. Please use a different address.");
            }
        } catch (\Throwable $e) {
            Log::warning('Pincode serviceability check failed during checkout', [
                'pincode' => $shippingPincode,
                'error' => $e->getMessage(),
            ]);
            // Allow checkout to proceed if the API is down — don't block sales
        }

        // Build address snapshots
        if ($isGuest) {
            $shippingSnapshot = [
                'name' => $validated['shipping_name'],
                'phone' => $validated['guest_phone'] ?? '',
                'address_line_1' => $validated['shipping_address_line_1'],
                'address_line_2' => $validated['shipping_address_line_2'] ?? '',
                'city' => $validated['shipping_city'],
                'state' => $validated['shipping_state'],
                'postal_code' => $validated['shipping_postal_code'],
                'country' => 'India',
            ];
            $billingSnapshot = $shippingSnapshot;
            $shippingAddressId = null;
            $billingAddressId = null;
        } else {
            $shippingAddress = UserAddress::where('user_id', auth()->id())->findOrFail($validated['shipping_address_id']);
            $billingAddressId = $validated['same_billing_address']
                ? $shippingAddress->id
                : ($validated['billing_address_id'] ?? $shippingAddress->id);
            $billingAddress = UserAddress::where('user_id', auth()->id())->findOrFail($billingAddressId);

            $shippingSnapshot = [
                'name' => $shippingAddress->full_name,
                'phone' => $shippingAddress->phone,
                'address_line_1' => $shippingAddress->address_line_1,
                'address_line_2' => $shippingAddress->address_line_2,
                'city' => $shippingAddress->city,
                'state' => $shippingAddress->state,
                'postal_code' => $shippingAddress->postal_code,
                'country' => $shippingAddress->country,
            ];
            $billingSnapshot = [
                'name' => $billingAddress->full_name,
                'address_line_1' => $billingAddress->address_line_1,
                'city' => $billingAddress->city,
                'state' => $billingAddress->state,
                'postal_code' => $billingAddress->postal_code,
                'country' => $billingAddress->country,
            ];
            $shippingAddressId = $shippingAddress->id;
            $billingAddressId = $billingAddress->id;
        }

        // Navratri offer: 5% extra off on all orders (after coupon discounts)
        $paymentMethod = $validated['payment_method'];
        $navratriDiscount = 0;
        $navratriActive = Setting::get('navratri_offer_active', '0') === '1';
        if ($navratriActive) {
            $navratriDiscount = round(($cart->subtotal - $cart->discount) * 0.05, 2);
        }

        $totalDiscount = $cart->discount + $navratriDiscount;

        // Loyalty points redemption
        $loyaltyPointsUsed = 0;
        $loyaltyDiscount = 0;
        if (!$isGuest && $request->boolean('use_loyalty_points') && (bool) Setting::get('loyalty_enabled', true)) {
            $user = auth()->user();
            $pointsAvailable = $user->loyalty_points_balance ?? 0;
            $redeemRate = (float) Setting::get('loyalty_redeem_rate', 0.25);
            $maxDiscount = $pointsAvailable * $redeemRate;
            $loyaltyDiscount = min($maxDiscount, $cart->subtotal - $totalDiscount); // Can't exceed order value
            $loyaltyPointsUsed = (int) ceil($loyaltyDiscount / $redeemRate);
            $totalDiscount += $loyaltyDiscount;
        }

        // Gift card redemption — prefer session (set via applyGiftCard), fallback to legacy form field
        $giftCardDiscount = 0;
        $giftCard = null;
        $giftCardCode = session('gift_card.code') ?: $request->input('gift_card_code');
        if ($giftCardCode) {
            $giftCard = \App\Models\GiftCard::where('code', strtoupper(trim($giftCardCode)))->first();
            if ($giftCard && $giftCard->isValid()) {
                $remainingAfterDiscounts = $cart->subtotal - $totalDiscount;
                // Cap at min(giftCardBalance, finalTotal) — gift card can't exceed what's owed
                $giftCardDiscount = min((float) $giftCard->current_balance, max(0, $remainingAfterDiscounts));
                $totalDiscount += $giftCardDiscount;
            } else {
                // Invalid/expired gift card in session or form — clear it and inform user
                session()->forget('gift_card');
                return redirect()->route('checkout.index')
                    ->with('error', 'Invalid or expired gift card code.');
            }
        }

        // Shipping fee: free above threshold, free_shipping coupon, free-shipping product, or else flat rate
        $freeShipThreshold = (float) Setting::get('free_shipping_threshold', 499);
        $hasFreeShippingCoupon = $cart->coupon && ($cart->coupon->type === 'free_shipping' || $cart->discount >= $cart->subtotal);
        $flatShipRate = (float) Setting::get('flat_rate_amount', 50);
        $shippingFee = ($hasFreeShippingCoupon || ($cart->subtotal - $cart->discount) >= $freeShipThreshold || $this->cartHasFreeShippingProduct($cart)) ? 0 : $flatShipRate;

        $rawTotal = $cart->subtotal - $totalDiscount + $shippingFee;
        // Cap discount so customer always pays at least ₹1 — prevents coupon overshoot from bypassing payment
        if ($rawTotal < 1 && $cart->subtotal > 0) {
            $totalDiscount = max(0, $cart->subtotal + $shippingFee - 1);
            $rawTotal = $cart->subtotal - $totalDiscount + $shippingFee;
        }
        $isFreeOrder = $rawTotal <= 0;
        $finalTotal = $isFreeOrder ? 0 : max(1, $rawTotal);

        // Tax: use the cart's calculated tax (inclusive = extracted from price, exclusive = added)
        $orderTax = $cart->tax;

        // COD: check availability and calculate advance
        $codAdvance = 0;
        $razorpayEnabled = (bool) Setting::get('razorpay_enabled') && !empty(Setting::get('razorpay_key_id')) && !empty(Setting::get('razorpay_key_secret'));
        $cashfreeEnabled = (bool) Setting::get('cashfree_enabled') && !empty(Setting::get('cashfree_app_id')) && !empty(Setting::get('cashfree_secret_key'));
        $hasOnlineGateway = $razorpayEnabled || $cashfreeEnabled;
        $codMinimum = (int) Setting::get('cod_minimum_amount', 199);
        $codAdvanceAmt = (int) Setting::get('cod_advance_amount', 100);
        $codAvailable = $finalTotal >= $codMinimum;

        // Free order (100% discount): bypass payment, treat as paid
        if ($isFreeOrder) {
            $paymentMethod = 'free';
            $codAdvance = 0;
        } elseif ($paymentMethod === 'cod') {
            if (!$codAvailable && $hasOnlineGateway) {
                return redirect()->route('checkout.index')
                    ->with('error', "COD is not available for orders below ₹{$codMinimum}. Please choose online payment.");
            }
            // If no online gateway enabled, pure COD (no advance payment)
            $codAdvance = $hasOnlineGateway ? min($codAdvanceAmt, $finalTotal) : 0;
        }

        // Resolve affiliate from cookie/session
        $affiliateId = null;
        $affiliateRefCode = null;
        $refCode = session('affiliate_ref') ?? request()->cookie(config('affiliate.cookie_name', 'store_ref'));
        if ($refCode) {
            $affiliate = Affiliate::where('referral_code', $refCode)->where('status', 'approved')->first();
            if ($affiliate) {
                $affiliateId = $affiliate->id;
                $affiliateRefCode = $refCode;
            }
        }

        $order = DB::transaction(function () use ($cart, $shippingSnapshot, $billingSnapshot, $shippingAddressId, $billingAddressId, $validated, $isGuest, $finalTotal, $totalDiscount, $paymentMethod, $navratriDiscount, $codAdvance, $affiliateId, $affiliateRefCode, $shippingFee, $loyaltyPointsUsed, $loyaltyDiscount, $orderTax, $giftCard, $giftCardDiscount) {
            $metadata = ['payment_method' => $paymentMethod];
            if ($navratriDiscount > 0) {
                $metadata['navratri_discount'] = $navratriDiscount;
            }
            if ($codAdvance > 0) {
                $metadata['cod_advance'] = $codAdvance;
                $metadata['cod_balance'] = $finalTotal - $codAdvance;
            }
            if ($affiliateRefCode) {
                $metadata['affiliate_referral_code'] = $affiliateRefCode;
            }
            if ($loyaltyPointsUsed > 0) {
                $metadata['loyalty_points_used'] = $loyaltyPointsUsed;
                $metadata['loyalty_discount'] = $loyaltyDiscount;
            }
            if ($giftCardDiscount > 0 && $giftCard) {
                $metadata['gift_card_code'] = $giftCard->code;
                $metadata['gift_card_discount'] = $giftCardDiscount;
            }

            $isFreeOrderInner = $finalTotal <= 0;
            $order = Order::create([
                'user_id' => $isGuest ? null : auth()->id(),
                'guest_email' => $validated['guest_email'] ?? null,
                'guest_name' => $validated['guest_name'] ?? null,
                'guest_phone' => $validated['guest_phone'] ?? null,
                'status' => 'confirmed',
                'payment_status' => $isFreeOrderInner ? 'paid' : 'pending',
                'subtotal' => $cart->subtotal,
                'discount' => $totalDiscount,
                'shipping_cost' => $shippingFee,
                'tax' => $orderTax,
                'total' => $finalTotal,
                'paid_amount' => $isFreeOrderInner ? 0 : $codAdvance,
                'coupon_id' => $cart->coupon_id,
                'affiliate_id' => $affiliateId,
                'affiliate_referral_code' => $affiliateRefCode,
                'shipping_address_id' => $shippingAddressId,
                'billing_address_id' => $billingAddressId,
                'shipping_address_snapshot' => $shippingSnapshot,
                'billing_address_snapshot' => $billingSnapshot,
                'notes' => $validated['notes'] ?? null,
                'metadata' => $metadata,
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'seller_id' => $item->product->seller_id,
                    'product_name' => $item->product->name,
                    'sku' => $item->product->sku ?? '',
                    'variant_name' => $item->variant?->attributeValues->pluck('value')->join(' / '),
                    'quantity' => $item->quantity,
                    'mrp' => $item->product->mrp ?? $item->price,
                    'price' => $item->price,
                    'tax' => 0,
                    'discount' => 0,
                    'total' => $item->price * $item->quantity,
                ]);

                // Atomic stock decrement with pessimistic lock to prevent race conditions
                if ($item->variant_id) {
                    $variant = DB::table('product_variants')
                        ->where('id', $item->variant_id)
                        ->lockForUpdate()
                        ->first();
                    $updated = $variant && $variant->stock_quantity >= $item->quantity
                        ? DB::table('product_variants')
                            ->where('id', $item->variant_id)
                            ->update(['stock_quantity' => DB::raw('stock_quantity - ' . (int) $item->quantity)])
                        : 0;
                } else {
                    $product = DB::table('products')
                        ->where('id', $item->product_id)
                        ->lockForUpdate()
                        ->first();
                    $updated = $product && $product->stock_quantity >= $item->quantity
                        ? DB::table('products')
                            ->where('id', $item->product_id)
                            ->update(['stock_quantity' => DB::raw('stock_quantity - ' . (int) $item->quantity)])
                        : 0;
                }

                if (!$updated) {
                    throw new \RuntimeException("Insufficient stock for \"{$item->product->name}\". Please try again.");
                }

                // Auto-update stock_status when stock hits 0
                DB::table('products')
                    ->where('id', $item->product_id)
                    ->where('stock_quantity', '<=', 0)
                    ->update(['stock_status' => 'out_of_stock']);

                $item->product->increment('sales_count', $item->quantity);
            }

            // Re-validate coupon at order creation — remove discount if invalid
            if ($cart->coupon) {
                $coupon = $cart->coupon;
                if (!$coupon->is_active || ($coupon->expires_at && $coupon->expires_at < now()) || ($coupon->usage_limit && $coupon->times_used >= $coupon->usage_limit)) {
                    Log::warning('Coupon expired/exhausted at checkout — removing discount', ['coupon' => $coupon->code]);
                    $order->update([
                        'coupon_id' => null,
                        'discount' => $order->discount - $cart->discount,
                        'total' => $order->total + $cart->discount,
                    ]);
                } else {
                    $coupon->increment('times_used');
                }
            }

            // Deduct gift card balance and record usage — re-lock the row to prevent race conditions
            // (two concurrent orders racing to spend the same balance).
            if ($giftCardDiscount > 0 && $giftCard) {
                $locked = \App\Models\GiftCard::where('id', $giftCard->id)->lockForUpdate()->first();
                if (!$locked || !$locked->isValid() || (float) $locked->current_balance < $giftCardDiscount) {
                    throw new \RuntimeException('Gift card balance changed — please retry.');
                }
                $locked->deduct($giftCardDiscount);
                $locked->usages()->create([
                    'order_id' => $order->id,
                    'amount' => $giftCardDiscount,
                ]);
            }

            // Redeem loyalty points INSIDE transaction to maintain consistency
            if ($loyaltyPointsUsed > 0 && !$isGuest) {
                app(\App\Services\LoyaltyService::class)->redeem(auth()->user(), $loyaltyPointsUsed, $order);
            }

            $cart->items()->delete();
            $cart->update(['coupon_id' => null, 'discount' => 0]);

            return $order;
        });

        // Clear gift card from session — it's now recorded against the order
        session()->forget('gift_card');

        // Mark abandoned checkout as recovered → linked to order
        $this->markAbandonedRecovered($cart, $order);

        if ($isGuest) {
            session()->put('guest_order_id', $order->id);

            // Auto-create account for guest and send credentials
            $this->createAccountForGuest($order, $validated);
        } else {
            // Save checkout preferences for one-click checkout next time
            \App\Models\UserCheckoutPreference::updateOrCreate(
                ['user_id' => auth()->id()],
                [
                    'default_shipping_address_id' => $order->shipping_address_id ?? ($request->input('shipping_address_id')),
                    'default_payment_method' => $request->input('payment_method', 'cod'),
                    'same_as_shipping' => $request->boolean('same_billing_address', true),
                    'enable_one_click' => true,
                ]
            );
        }

        $order->load('items.product', 'user');

        try {
            OrderPlaced::dispatch($order, 'web');
        } catch (\Exception $e) {
            Log::error('OrderPlaced event failed (COD)', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        return redirect()->route('checkout.success', $order->checkout_token);
    }

    public function success(string $token): View
    {
        $order = Order::where('checkout_token', $token)->firstOrFail();

        $order->load(['items.product']);

        // Use order_number as event_id for consistent Meta dedup across all checkout paths
        $fbPurchaseEventId = $order->order_number;

        $meta = $order->metadata ?? [];
        if (empty($meta['capi_sent_at'])) {
            app(AnalyticsService::class)->trackPurchase($order, request(), $fbPurchaseEventId);
            $meta['capi_sent_at'] = now()->toIso8601String();
            $meta['fb_event_id']  = $fbPurchaseEventId;
            $meta['capi_source']  = 'checkout_native';
            $order->metadata = $meta;
            $order->save();
        }

        return view('checkout.success', compact('order', 'fbPurchaseEventId'));
    }

    /**
     * Handle Shiprocket Checkout SDK return. The SDK redirects here with ?oid=...&ost=SUCCESS.
     * Creates an Order in our DB from the abandoned checkout cart snapshot,
     * dispatches OrderPlaced event for email notifications.
     */
    public function shiprocketCheckoutSuccess(Request $request): View
    {
        $shiprocketOrderId = $request->query('oid');
        $status = $request->query('ost', '');

        // Log ALL callback params so we can see exactly what Shiprocket Checkout sends
        Log::info('Shiprocket checkout callback received', [
            'all_params' => $request->query(),
        ]);

        // Accept: SUCCESS (prepaid), COD / COD_PLACED / ORDER_PLACED (COD), PARTIAL_PAID (COD advance), empty/unknown
        // Reject: explicitly failed statuses only
        $failedStatuses = ['FAILED', 'CANCELLED', 'PAYMENT_FAILED', 'PAYMENT_CANCELLED'];
        if (!$shiprocketOrderId || in_array(strtoupper($status), $failedStatuses, true)) {
            return view('checkout.failed');
        }

        // Find the abandoned checkout that tracks this Shiprocket order
        $abandoned = AbandonedCheckout::where('shiprocket_order_id', $shiprocketOrderId)->first();

        // Fallback: match by session ID when shiprocket_order_id wasn't stored at token-creation time
        // (happens when Shiprocket's token API doesn't return an order_id in its response)
        if (!$abandoned) {
            $abandoned = AbandonedCheckout::where('session_id', session()->getId())
                ->where('source', 'shiprocket_checkout')
                ->whereNull('order_id')
                ->latest()
                ->first();

            if ($abandoned && $shiprocketOrderId) {
                $abandoned->update(['shiprocket_order_id' => $shiprocketOrderId]);
                Log::info('Shiprocket checkout: matched abandoned checkout by session, updated shiprocket_order_id', [
                    'abandoned_id' => $abandoned->id,
                    'shiprocket_order_id' => $shiprocketOrderId,
                ]);
            }
        }

        // Try to get customer details from: 1) callback params, 2) abandoned checkout, 3) logged-in user, 4) API later
        $loggedUser = auth()->user();
        $customerName = $request->query('customer_name')
            ?? $request->query('name')
            ?? $abandoned->name ?? ($loggedUser ? $loggedUser->full_name : null) ?: null;
        $customerEmail = $request->query('customer_email')
            ?? $request->query('email')
            ?? $abandoned->email ?? $loggedUser?->email ?? null ?: null;
        $customerPhone = $request->query('customer_phone')
            ?? $request->query('phone')
            ?? $abandoned->phone ?? $loggedUser?->phone ?? null ?: null;

        try {
            // Wrap idempotency check + order creation in one transaction with advisory lock
            // to prevent duplicate orders from concurrent callbacks / page refreshes
            $order = DB::transaction(function () use ($abandoned, $shiprocketOrderId, $customerName, $customerEmail, $customerPhone, $loggedUser, $status) {
                // B1: Idempotency check — use 'api' source (matches PostgreSQL enum constraint)
                $existingOrder = Order::where('source', 'api')
                    ->whereJsonContains('metadata->shiprocket_checkout_id', $shiprocketOrderId)
                    ->first();
                if ($existingOrder) {
                    return $existingOrder;
                }

                if (!$abandoned || empty($abandoned->cart_snapshot)) {
                    return null;
                }

                $cartSnapshot = $abandoned->cart_snapshot;
                $orderTotal = 0;
                foreach ($cartSnapshot as $item) {
                    $orderTotal += ((float) ($item['price'] ?? 0)) * ((int) ($item['quantity'] ?? 1));
                }

                // Determine payment status from Shiprocket callback ost param:
                // SUCCESS → prepaid (paid in full online)
                // COD / COD_PLACED / ORDER_PLACED / empty / unknown → COD (pending, collected on delivery)
                // PARTIAL_PAID / PARTIAL_COD / ADVANCE_PAID → COD with advance (partial, balance on delivery)
                $statusUpper = strtoupper($status);
                $codStatuses = ['COD', 'COD_PLACED', 'ORDER_PLACED'];
                $partialStatuses = ['PARTIAL_PAID', 'PARTIAL_COD', 'ADVANCE_PAID'];

                if (in_array($statusUpper, $partialStatuses, true)) {
                    $paymentStatus = 'pending'; // balance on delivery; advance stored in metadata
                    $paidAmount = 0; // advance captured separately by Cashfree webhook
                    $paymentMethod = 'shiprocket_cod_partial';
                } elseif ($statusUpper === 'SUCCESS') {
                    $paymentStatus = 'paid';
                    $paidAmount = $orderTotal;
                    $paymentMethod = 'shiprocket_checkout';
                } else {
                    // COD, COD_PLACED, ORDER_PLACED, empty, or any unknown status
                    $paymentStatus = 'pending';
                    $paidAmount = 0;
                    $paymentMethod = 'shiprocket_cod';
                }

                // B1: source='api' to satisfy enum constraint (same as Cashfree)
                // B2: shiprocket_order_id set so PushOrderToShiprocket listener skips this order
                $order = Order::create([
                    'user_id' => $abandoned->user_id ?? $loggedUser?->id,
                    'guest_name' => $customerName,
                    'guest_email' => $customerEmail,
                    'guest_phone' => $customerPhone,
                    'status' => 'confirmed',
                    'payment_status' => $paymentStatus,
                    'subtotal' => $orderTotal,
                    'discount' => 0,
                    'shipping_cost' => 0,
                    'tax' => 0,
                    'total' => $orderTotal,
                    'paid_amount' => $paidAmount,
                    'source' => 'api',
                    'shiprocket_order_id' => $shiprocketOrderId,
                    'metadata' => [
                        'payment_method' => $paymentMethod,
                        'payment_gateway' => 'shiprocket',
                        'shiprocket_checkout_id' => $shiprocketOrderId,
                        'shiprocket_ost' => $status,
                    ],
                ]);

                foreach ($cartSnapshot as $item) {
                    $productId = $item['product_id'] ?? null;
                    $product = $productId ? \App\Models\Product::find($productId) : null;

                    // Shiprocket cart uses variant_id as product_id
                    if (!$product && isset($item['variant_id'])) {
                        $product = \App\Models\Product::find($item['variant_id']);
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product?->id,
                        'product_name' => $product?->name ?? $item['name'] ?? 'Product',
                        'sku' => $product?->sku ?? $item['sku'] ?? '',
                        'quantity' => (int) ($item['quantity'] ?? 1),
                        'mrp' => $product?->mrp ?? $item['price'] ?? 0,
                        'price' => (float) ($item['price'] ?? 0),
                        'tax' => 0,
                        'discount' => 0,
                        'total' => ((float) ($item['price'] ?? 0)) * ((int) ($item['quantity'] ?? 1)),
                    ]);

                    // B3: Decrement stock with row lock and insufficient-stock guard
                    if ($product) {
                        $qty = (int) ($item['quantity'] ?? 1);
                        $locked = \App\Models\Product::where('id', $product->id)->lockForUpdate()->first();

                        if ($locked && $locked->stock_quantity >= $qty) {
                            $locked->decrement('stock_quantity', $qty);
                            $locked->increment('sales_count', $qty);
                            if ($locked->fresh()->stock_quantity <= 0) {
                                $locked->update(['stock_status' => 'out_of_stock']);
                            }
                        } else {
                            Log::warning('Shiprocket checkout: insufficient stock', [
                                'product_id' => $product->id,
                                'requested' => $qty,
                                'available' => $locked?->stock_quantity ?? 0,
                            ]);
                            // Don't block order — product was already paid via Shiprocket
                            $product->increment('sales_count', $qty);
                        }
                    }
                }

                return $order;
            });

            // If existing order was returned by idempotency guard
            if ($order && $order->wasRecentlyCreated === false) {
                $order->load('items.product', 'user');
                $fbPurchaseEventId = $order->order_number;

                $meta = $order->metadata ?? [];
                if (empty($meta['capi_sent_at'])) {
                    app(AnalyticsService::class)->trackPurchase($order, $request, $fbPurchaseEventId);
                    $meta['capi_sent_at'] = now()->toIso8601String();
                    $meta['fb_event_id']  = $fbPurchaseEventId;
                    $meta['capi_source']  = 'checkout_idempotent';
                    $order->metadata = $meta;
                    $order->save();
                }

                return view('checkout.shiprocket-success', [
                    'shiprocketOrderId' => $shiprocketOrderId,
                    'customerName' => $order->guest_name,
                    'order' => $order,
                    'fbPurchaseEventId' => $fbPurchaseEventId,
                ]);
            }

            // No abandoned checkout found — show generic success
            if (!$order) {
                Log::warning('Shiprocket checkout success: abandoned checkout not found', ['oid' => $shiprocketOrderId]);
                return view('checkout.shiprocket-success', [
                    'shiprocketOrderId' => $shiprocketOrderId,
                    'customerName' => null,
                    'order' => null,
                ]);
            }

            // Mark abandoned checkout as recovered
            $abandoned->update([
                'step' => 'completed',
                'recovered' => true,
                'order_id' => $order->id,
                'recovered_at' => now(),
            ]);

            // Fetch customer details from Shiprocket APIs.
            // Chain: 1) Checkout API  2) Shipping API search by channel_order_id  3) Queued retry
            $customerSynced = false;
            try {
                $srService = app(\App\Services\ShiprocketService::class);
                $srOrder = $srService->getCheckoutOrder($shiprocketOrderId);

                // Fallback: search Shipping API by checkout hex ID (stored as channel_order_id).
                // The old getOrder() passed the hex ID to /orders/show/ which expects numeric IDs — always failed.
                if (!$srOrder) {
                    $srOrder = $srService->findShippingOrderByCheckoutId($shiprocketOrderId);
                }

                if ($srOrder) {
                    $srCustomer = $srOrder['customer_details'] ?? $srOrder;
                    $updateData = [];

                    if (empty($order->guest_name) && !empty($srCustomer['billing_customer_name'] ?? $srCustomer['customer_name'] ?? null)) {
                        $name = trim(($srCustomer['billing_customer_name'] ?? $srCustomer['customer_name'] ?? '') . ' ' . ($srCustomer['billing_last_name'] ?? ''));
                        $updateData['guest_name'] = $name;
                        $customerName = $name;
                    }
                    if (empty($order->guest_email) && !empty($srCustomer['billing_email'] ?? $srCustomer['customer_email'] ?? null)) {
                        $updateData['guest_email'] = $srCustomer['billing_email'] ?? $srCustomer['customer_email'];
                    }
                    if (empty($order->guest_phone) && !empty($srCustomer['billing_phone'] ?? $srCustomer['customer_phone'] ?? null)) {
                        $updateData['guest_phone'] = $srCustomer['billing_phone'] ?? $srCustomer['customer_phone'];
                    }

                    // Build shipping address snapshot from Shiprocket data
                    if (empty($order->shipping_address_snapshot)) {
                        $updateData['shipping_address_snapshot'] = [
                            'name' => $updateData['guest_name'] ?? $order->guest_name ?? '',
                            'phone' => $updateData['guest_phone'] ?? $order->guest_phone ?? '',
                            'address_line_1' => $srCustomer['billing_address'] ?? $srCustomer['customer_address'] ?? '',
                            'address_line_2' => $srCustomer['billing_address_2'] ?? '',
                            'city' => $srCustomer['billing_city'] ?? $srCustomer['customer_city'] ?? '',
                            'state' => $srCustomer['billing_state'] ?? $srCustomer['customer_state'] ?? '',
                            'postal_code' => $srCustomer['billing_pincode'] ?? $srCustomer['customer_pincode'] ?? '',
                            'country' => $srCustomer['billing_country'] ?? 'India',
                        ];
                    }

                    // Auto-link to existing user account by phone or email (enables returns/refunds)
                    if (empty($order->user_id)) {
                        $phone = $updateData['guest_phone'] ?? $order->guest_phone;
                        $email = $updateData['guest_email'] ?? $order->guest_email;
                        $matchedUser = $this->findUserByPhoneOrEmail($phone, $email);
                        if ($matchedUser) {
                            $updateData['user_id'] = $matchedUser->id;
                            Log::info('Shiprocket checkout: linked order to existing user', [
                                'order_id' => $order->id,
                                'user_id' => $matchedUser->id,
                                'matched_by' => $phone && $matchedUser->phone === $phone ? 'phone' : 'email',
                            ]);
                        }
                    }

                    if (!empty($updateData)) {
                        $order->update($updateData);
                        $customerSynced = true;
                        Log::info('Shiprocket checkout: customer data synced from API', [
                            'order_id' => $order->id,
                            'fields' => array_keys($updateData),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Shiprocket checkout: failed to fetch customer data from API', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Ensure user linking regardless of Shiprocket API result.
            // The order already has guest_phone/email from the callback — use them directly.
            $order->refresh();
            if (empty($order->user_id) && (!empty($order->guest_phone) || !empty($order->guest_email))) {
                $matchedUser = $this->findUserByPhoneOrEmail($order->guest_phone, $order->guest_email);
                if ($matchedUser) {
                    $order->update(['user_id' => $matchedUser->id]);
                    Log::info('Shiprocket checkout: linked order to user (post-API fallback)', [
                        'order_id' => $order->id,
                        'user_id' => $matchedUser->id,
                    ]);
                } else {
                    // No existing user — create account so order appears in dashboard
                    $this->createAccountForShiprocketGuest($order);
                }
            }

            // Queued retry: sync missing customer details or address from Shiprocket API
            if (!$customerSynced && empty($order->fresh()->guest_name)) {
                \App\Jobs\SyncShiprocketCustomerDetails::dispatch($order)->delay(now()->addSeconds(30));
                Log::info('Shiprocket checkout: queued delayed customer sync', ['order_id' => $order->id]);
            }

            // Dispatch OrderPlaced for admin + customer email notifications
            $order->load('items.product', 'user');
            try {
                OrderPlaced::dispatch($order, 'shiprocket_checkout');
            } catch (\Throwable $e) {
                Log::error('OrderPlaced event failed (Shiprocket Checkout)', ['order' => $order->id, 'error' => $e->getMessage()]);
            }

            Log::info('Shiprocket checkout: order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'shiprocket_checkout_id' => $shiprocketOrderId,
                'total' => $order->total,
            ]);

        } catch (\Throwable $e) {
            Log::error('Shiprocket checkout: order creation failed', [
                'oid' => $shiprocketOrderId,
                'error' => $e->getMessage(),
            ]);
            $order = null;
        }

        $finalOrder = $order ?? null;

        // Server-side CAPI + GA4 + Google Ads Purchase tracking (mirrors normal checkout success)
        $fbPurchaseEventId = null;
        if ($finalOrder) {
            $finalOrder->load('items.product', 'user');
            $fbPurchaseEventId = $finalOrder->order_number;

            $meta = $finalOrder->metadata ?? [];
            if (empty($meta['capi_sent_at'])) {
                app(AnalyticsService::class)->trackPurchase($finalOrder, $request, $fbPurchaseEventId);
                $meta['capi_sent_at'] = now()->toIso8601String();
                $meta['fb_event_id']  = $fbPurchaseEventId;
                $meta['capi_source']  = 'checkout_shiprocket';
                $finalOrder->metadata = $meta;
                $finalOrder->save();
            }
        }

        return view('checkout.shiprocket-success', [
            'shiprocketOrderId' => $shiprocketOrderId,
            'customerName' => $customerName,
            'order' => $finalOrder,
            'fbPurchaseEventId' => $fbPurchaseEventId,
        ]);
    }

    /**
     * Save customer details for a Shiprocket checkout order (guest detail capture form).
     */
    public function saveShiprocketCustomerDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|integer',
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
        ]);

        $order = Order::find($validated['order_id']);
        if (!$order || $order->source !== 'api') {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        // Verify this session owns this order (match via shiprocket source + recent creation)
        $isOwner = $order->created_at->diffInMinutes(now()) < 60;
        if (!$isOwner) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $updateData = [];
        if (empty($order->guest_name)) {
            $updateData['guest_name'] = $validated['name'];
        }
        if (empty($order->guest_phone)) {
            $updateData['guest_phone'] = $validated['phone'];
        }
        if (empty($order->guest_email) && !empty($validated['email'])) {
            $updateData['guest_email'] = $validated['email'];
        }

        if (empty($order->shipping_address_snapshot) && !empty($validated['address'])) {
            $updateData['shipping_address_snapshot'] = [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'address_line_1' => $validated['address'],
                'address_line_2' => '',
                'city' => $validated['city'] ?? '',
                'state' => $validated['state'] ?? '',
                'postal_code' => $validated['pincode'] ?? '',
                'country' => 'India',
            ];
        }

        // Link to existing user or create account
        if (empty($order->user_id)) {
            $matchedUser = $this->findUserByPhoneOrEmail($validated['phone'], $validated['email'] ?? null);
            if ($matchedUser) {
                $updateData['user_id'] = $matchedUser->id;
            }
        }

        if (!empty($updateData)) {
            $order->update($updateData);
        }

        // Create account if no user was linked
        $order->refresh();
        if (empty($order->user_id)) {
            $this->createAccountForGuest($order, [
                'guest_name' => $validated['name'],
                'guest_phone' => $validated['phone'],
                'guest_email' => $validated['email'] ?? null,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function failed(): View
    {
        return view('checkout.failed');
    }

    /**
     * Create a Razorpay order for inline checkout (AJAX).
     */
    public function createRazorpayOrder(Request $request): JsonResponse
    {
        $this->logActivity('payment_initiated', ['method' => $request->input('payment_method')], $request);
        $isGuest = !auth()->check();

        $rules = [
            'same_billing_address' => ['nullable', 'boolean'],
            'payment_method' => ['required', 'string', 'in:razorpay,upi,cod,partial_pay,cashfree'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        if ($isGuest) {
            $rules['guest_email'] = ['required', 'email', 'max:255'];
            $rules['guest_name'] = ['required', 'string', 'max:255'];
            $rules['guest_phone'] = ['required', 'string', 'regex:/^[6-9]\d{9}$/'];
            $rules['shipping_name'] = ['required', 'string', 'max:255'];
            // shipping_phone uses guest_phone
            $rules['shipping_address_line_1'] = ['required', 'string', 'max:255'];
            $rules['shipping_address_line_2'] = ['nullable', 'string', 'max:255'];
            $rules['shipping_city'] = ['required', 'string', 'max:100'];
            $rules['shipping_state'] = ['required', 'string', 'max:100'];
            $rules['shipping_postal_code'] = ['required', 'string', 'max:10'];
        } else {
            $rules['shipping_address_id'] = ['required', 'exists:user_addresses,id'];
            $rules['billing_address_id'] = ['nullable', 'integer'];
        }

        $validated = $request->validate($rules);

        $cart = $this->getCart(['items.product', 'items.variant', 'coupon']);

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['error' => 'Your cart is empty.'], 422);
        }

        // Re-validate stock
        foreach ($cart->items as $item) {
            $available = $item->variant_id
                ? $item->variant->stock_quantity
                : $item->product->stock_quantity;

            if ($available < $item->quantity) {
                return response()->json([
                    'error' => "\"{$item->product->name}\" only has {$available} item(s) in stock.",
                ], 422);
            }
        }

        // Calculate total (same logic as COD flow)
        $paymentMethod = $validated['payment_method'];
        $navratriDiscount = 0;
        $navratriActive = Setting::get('navratri_offer_active', '0') === '1';
        if ($navratriActive) {
            $navratriDiscount = round(($cart->subtotal - $cart->discount) * 0.05, 2);
        }
        $totalDiscount = $cart->discount + $navratriDiscount;

        // Shipping fee: free above threshold, free_shipping coupon, free-shipping product, or else flat rate
        $freeShipThreshold = (float) Setting::get('free_shipping_threshold', 499);
        $hasFreeShippingCoupon = $cart->coupon && ($cart->coupon->type === 'free_shipping' || $cart->discount >= $cart->subtotal);
        $flatShipRate = (float) Setting::get('flat_rate_amount', 50);
        $shippingFee = ($hasFreeShippingCoupon || ($cart->subtotal - $cart->discount) >= $freeShipThreshold || $this->cartHasFreeShippingProduct($cart)) ? 0 : $flatShipRate;

        // Prepaid discount: extra % off when paying fully online (not COD)
        $prepaidDiscount = 0;
        $prepaidPct = (float) Setting::get('prepaid_discount_percent', 0);
        if ($prepaidPct > 0 && $paymentMethod === 'razorpay') {
            $prepaidDiscount = round(($cart->subtotal - $cart->discount) * $prepaidPct / 100, 2);
            $totalDiscount += $prepaidDiscount;
        }

        // Gift card redemption (Razorpay path) — pull from session applied earlier
        $giftCardDiscount = 0;
        $giftCardId = null;
        $giftCardCode = session('gift_card.code');
        if ($giftCardCode) {
            $giftCard = \App\Models\GiftCard::where('code', strtoupper(trim($giftCardCode)))->first();
            if ($giftCard && $giftCard->isValid()) {
                $remainingAfterDiscounts = $cart->subtotal - $totalDiscount;
                $giftCardDiscount = min((float) $giftCard->current_balance, max(0, $remainingAfterDiscounts));
                $totalDiscount += $giftCardDiscount;
                $giftCardId = $giftCard->id;
            } else {
                session()->forget('gift_card');
                return response()->json(['error' => 'Invalid or expired gift card. Please re-apply.'], 422);
            }
        }

        $rawTotal = $cart->subtotal - $totalDiscount + $shippingFee;
        // Cap discount so customer always pays at least ₹1 — prevents coupon overshoot from bypassing payment
        if ($rawTotal < 1 && $cart->subtotal > 0) {
            $totalDiscount = max(0, $cart->subtotal + $shippingFee - 1);
            $rawTotal = $cart->subtotal - $totalDiscount + $shippingFee;
        }
        $isFreeOrder = $rawTotal <= 0;
        $finalTotal = $isFreeOrder ? 0 : max(1, $rawTotal);
        $orderTax = $cart->tax;

        // Free order short-circuit: tell the front-end to submit the form normally
        // (which will hit process() with the 'free' payment method).
        if ($isFreeOrder) {
            return response()->json([
                'free_order' => true,
                'message' => 'Free order — no payment required.',
            ]);
        }

        // For COD: only charge the advance via Razorpay, rest collected on delivery
        $codAdvanceAmt = (int) Setting::get('cod_advance_amount', 100);
        $chargeAmount = $finalTotal;
        if ($paymentMethod === 'cod') {
            $chargeAmount = min($codAdvanceAmt, $finalTotal);
        }
        $amountInPaise = (int) round($chargeAmount * 100);

        // Create Razorpay order via REST API
        $razorpayKey = Setting::get('razorpay_key_id', '');
        $razorpaySecret = Setting::get('razorpay_key_secret', '');
        if (empty($razorpayKey) || empty($razorpaySecret)) {
            return response()->json(['error' => 'Payment gateway not configured. Please contact store admin.'], 422);
        }

        try {
            $response = Http::timeout(10)->retry(2, 500)->withBasicAuth(
                $razorpayKey,
                $razorpaySecret
            )->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'receipt' => 'cart_' . $cart->id . '_' . time(),
                'notes' => [
                    'cart_id' => $cart->id,
                    'user_id' => auth()->id() ?? 'guest',
                ],
            ]);

            if (!$response->successful()) {
                Log::error('Razorpay order creation failed', [
                    'response' => $response->json(),
                    'tenant' => tenant('id') ?? 'central',
                    'domain' => request()->getHost(),
                    'key_prefix' => substr($razorpayKey, 0, 12) . '...',
                ]);
                $this->logActivity('payment_error', ['stage' => 'razorpay_order_create', 'error' => $response->json()], $request);
                return response()->json(['error' => 'Failed to create payment order. Please try again.'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Razorpay order creation exception', [
                'message' => $e->getMessage(),
                'tenant' => tenant('id') ?? 'central',
                'domain' => request()->getHost(),
            ]);
            return response()->json(['error' => 'Payment service is temporarily unavailable. Please try again.'], 503);
        }

        $razorpayOrder = $response->json();

        // Store checkout data in session for verification step
        session()->put('razorpay_checkout', [
            'razorpay_order_id' => $razorpayOrder['id'],
            'validated' => $validated,
            'final_total' => $finalTotal,
            'total_discount' => $totalDiscount,
            'navratri_discount' => $navratriDiscount,
            'order_tax' => $orderTax,
            'shipping_fee' => $shippingFee,
            'payment_method' => $paymentMethod,
            'prepaid_discount' => $prepaidDiscount,
            'cart_subtotal' => $cart->subtotal,
            'gift_card_id' => $giftCardId,
            'gift_card_discount' => $giftCardDiscount,
        ]);

        $contactName = $isGuest
            ? $validated['guest_name']
            : (auth()->user()->name ?? '');
        $contactEmail = $isGuest
            ? $validated['guest_email']
            : (auth()->user()->email ?? '');
        $contactPhone = $isGuest
            ? $validated['guest_phone']
            : (auth()->user()->phone ?? '');

        // Facebook CAPI: AddPaymentInfo
        $fbEventId = AnalyticsService::generateEventId('api');
        app(AnalyticsService::class)->trackAddPaymentInfo($finalTotal, $paymentMethod, $request, $fbEventId);

        $responseData = [
            'order_id' => $razorpayOrder['id'],
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'key' => $razorpayKey,
            'name' => Setting::get('store_name', config('app.name', 'Store')),
            'description' => 'Order from ' . Setting::get('store_name', config('app.name')),
            'prefill' => [
                'name' => $contactName,
                'email' => $contactEmail,
                'contact' => $contactPhone,
            ],
            'fb_event_id' => $fbEventId,
        ];

        $configId = Setting::get('razorpay_config_id', '');
        if ($configId) {
            $responseData['config_id'] = $configId;
        }

        return response()->json($responseData);
    }

    /**
     * Verify Razorpay payment and create the order.
     */
    public function verifyRazorpayPayment(Request $request): JsonResponse
    {
        $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $checkoutData = session('razorpay_checkout');
        if (!$checkoutData || $checkoutData['razorpay_order_id'] !== $request->razorpay_order_id) {
            return response()->json(['error' => 'Invalid session. Please try again.'], 422);
        }

        // Verify signature
        $expectedSignature = hash_hmac(
            'sha256',
            $request->razorpay_order_id . '|' . $request->razorpay_payment_id,
            Setting::get('razorpay_key_secret', '')
        );

        if (!hash_equals($expectedSignature, $request->razorpay_signature)) {
            Log::warning('Razorpay signature verification failed', [
                'order_id' => $request->razorpay_order_id,
            ]);
            $this->logActivity('payment_verification_failed', ['razorpay_order_id' => $request->razorpay_order_id], $request);
            return response()->json(['error' => 'Payment verification failed.'], 422);
        }

        // Payment verified - create the order
        $validated = $checkoutData['validated'];
        $isGuest = !auth()->check();

        // Idempotency: check if order already created for this Razorpay order
        $existingOrder = Order::where('razorpay_order_id', $request->razorpay_order_id)->first();
        if ($existingOrder) {
            session()->forget('razorpay_checkout');
            return response()->json([
                'success' => true,
                'redirect' => route('checkout.success', $existingOrder->checkout_token),
            ]);
        }

        $cart = $this->getCart(['items.product', 'items.variant', 'coupon']);

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['error' => 'Your cart is empty.'], 422);
        }

        // Re-verify cart total matches what was charged
        if (abs($cart->subtotal - ($checkoutData['cart_subtotal'] ?? 0)) > 0.01) {
            Log::warning('Cart modified between payment creation and verification', [
                'expected_subtotal' => $checkoutData['cart_subtotal'],
                'actual_subtotal' => $cart->subtotal,
            ]);
            return response()->json(['error' => 'Cart was modified. Please try again.'], 422);
        }

        // Re-validate stock before creating order
        foreach ($cart->items as $item) {
            $available = $item->variant_id
                ? $item->variant->stock_quantity
                : $item->product->stock_quantity;
            if ($available < $item->quantity) {
                return response()->json([
                    'error' => "\"{$item->product->name}\" is now out of stock. Your payment will be refunded automatically.",
                ], 422);
            }
        }

        // Build address snapshots
        if ($isGuest) {
            $shippingSnapshot = [
                'name' => $validated['shipping_name'],
                'phone' => $validated['guest_phone'] ?? '',
                'address_line_1' => $validated['shipping_address_line_1'],
                'address_line_2' => $validated['shipping_address_line_2'] ?? '',
                'city' => $validated['shipping_city'],
                'state' => $validated['shipping_state'],
                'postal_code' => $validated['shipping_postal_code'],
                'country' => 'India',
            ];
            $billingSnapshot = $shippingSnapshot;
            $shippingAddressId = null;
            $billingAddressId = null;
        } else {
            $shippingAddress = UserAddress::where('user_id', auth()->id())->findOrFail($validated['shipping_address_id']);
            $billingAddressId = $validated['same_billing_address']
                ? $shippingAddress->id
                : ($validated['billing_address_id'] ?? $shippingAddress->id);
            $billingAddress = UserAddress::where('user_id', auth()->id())->findOrFail($billingAddressId);

            $shippingSnapshot = [
                'name' => $shippingAddress->full_name,
                'phone' => $shippingAddress->phone,
                'address_line_1' => $shippingAddress->address_line_1,
                'address_line_2' => $shippingAddress->address_line_2,
                'city' => $shippingAddress->city,
                'state' => $shippingAddress->state,
                'postal_code' => $shippingAddress->postal_code,
                'country' => $shippingAddress->country,
            ];
            $billingSnapshot = [
                'name' => $billingAddress->full_name,
                'address_line_1' => $billingAddress->address_line_1,
                'city' => $billingAddress->city,
                'state' => $billingAddress->state,
                'postal_code' => $billingAddress->postal_code,
                'country' => $billingAddress->country,
            ];
            $shippingAddressId = $shippingAddress->id;
            $billingAddressId = $billingAddress->id;
        }

        // Recalculate total from actual DB prices to prevent session tampering
        $cart->load('items.product');
        $recalculatedSubtotal = $cart->items->sum(fn($i) => $i->product->price * $i->quantity);
        if (abs($recalculatedSubtotal - ($checkoutData['cart_subtotal'] ?? $recalculatedSubtotal)) > 2) {
            Log::critical('Razorpay payment amount mismatch — possible tampering', [
                'session_subtotal' => $checkoutData['cart_subtotal'] ?? 'N/A',
                'recalculated' => $recalculatedSubtotal,
                'cart_id' => $cart->id,
            ]);
            return response()->json(['error' => 'Cart amounts changed. Please retry.'], 422);
        }

        $finalTotal = $checkoutData['final_total'];
        $totalDiscount = $checkoutData['total_discount'];
        $navratriDiscount = $checkoutData['navratri_discount'];
        $prepaidDiscount = $checkoutData['prepaid_discount'] ?? 0;
        $orderTax = $checkoutData['order_tax'] ?? $cart->tax ?? 0;
        $shippingFee = $checkoutData['shipping_fee'] ?? 0;
        $paymentMethod = $checkoutData['payment_method'];
        $giftCardId = $checkoutData['gift_card_id'] ?? null;
        $giftCardDiscount = (float) ($checkoutData['gift_card_discount'] ?? 0);

        // Resolve affiliate from cookie/session
        $affiliateId = null;
        $affiliateRefCode = null;
        $refCode = session('affiliate_ref') ?? request()->cookie(config('affiliate.cookie_name', 'store_ref'));
        if ($refCode) {
            $razorpayAffiliate = Affiliate::where('referral_code', $refCode)->where('status', 'approved')->first();
            if ($razorpayAffiliate) {
                $affiliateId = $razorpayAffiliate->id;
                $affiliateRefCode = $refCode;
            }
        }

        $order = DB::transaction(function () use ($cart, $shippingSnapshot, $billingSnapshot, $shippingAddressId, $billingAddressId, $validated, $isGuest, $finalTotal, $totalDiscount, $paymentMethod, $navratriDiscount, $prepaidDiscount, $request, $affiliateId, $affiliateRefCode, $shippingFee, $orderTax, $giftCardId, $giftCardDiscount) {
            // For COD partial pay: only the advance was paid via Razorpay
            $codAdvanceAmt = (int) Setting::get('cod_advance_amount', 100);
            $isPartialCod = $paymentMethod === 'cod';
            $codAdvanceAmount = $isPartialCod ? min($codAdvanceAmt, $finalTotal) : 0;
            $actualPaidAmount = $isPartialCod ? $codAdvanceAmount : $finalTotal;

            $metadata = [
                'payment_method' => $paymentMethod,
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
            ];
            if ($isPartialCod) {
                $metadata['cod_advance'] = $codAdvanceAmount;
                $metadata['cod_balance'] = $finalTotal - $codAdvanceAmount;
            }
            if ($navratriDiscount > 0) {
                $metadata['navratri_discount'] = $navratriDiscount;
            }
            if ($prepaidDiscount > 0) {
                $metadata['prepaid_discount'] = $prepaidDiscount;
            }
            if ($affiliateRefCode) {
                $metadata['affiliate_referral_code'] = $affiliateRefCode;
            }

            // Gift card: lock + deduct inside this transaction to prevent race conditions
            $lockedGiftCard = null;
            if ($giftCardId && $giftCardDiscount > 0) {
                $lockedGiftCard = \App\Models\GiftCard::where('id', $giftCardId)->lockForUpdate()->first();
                if (!$lockedGiftCard || !$lockedGiftCard->isValid() || (float) $lockedGiftCard->current_balance < $giftCardDiscount) {
                    throw new \RuntimeException('Gift card is no longer valid. Please remove it and retry.');
                }
                $metadata['gift_card_code'] = $lockedGiftCard->code;
                $metadata['gift_card_discount'] = $giftCardDiscount;
            }

            $order = Order::create([
                'user_id' => $isGuest ? null : auth()->id(),
                'guest_email' => $validated['guest_email'] ?? null,
                'guest_name' => $validated['guest_name'] ?? null,
                'guest_phone' => $validated['guest_phone'] ?? null,
                'status' => 'confirmed',
                'payment_status' => $isPartialCod ? 'pending' : 'paid',
                'subtotal' => $cart->subtotal,
                'discount' => $totalDiscount,
                'shipping_cost' => $shippingFee,
                'tax' => $orderTax,
                'total' => $finalTotal,
                'paid_amount' => $actualPaidAmount,
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'coupon_id' => $cart->coupon_id,
                'affiliate_id' => $affiliateId,
                'affiliate_referral_code' => $affiliateRefCode,
                'shipping_address_id' => $shippingAddressId,
                'billing_address_id' => $billingAddressId,
                'shipping_address_snapshot' => $shippingSnapshot,
                'billing_address_snapshot' => $billingSnapshot,
                'notes' => $validated['notes'] ?? null,
                'metadata' => $metadata,
            ]);

            // PR #18 — Phase 0 Path A: always create a Payment row when an Order is paid via Razorpay.
            // Previously only the webhook created Payment rows, and the webhook lookup was broken (looked up by
            // notes.order_id which was never set — see RazorpayWebhookController fix). So the payments ledger
            // was effectively empty for most orders. Anupam/Himanshu-class audit trail bugs stem from here.
            if ($actualPaidAmount > 0) {
                Payment::create([
                    'order_id' => $order->id,
                    'transaction_id' => $request->razorpay_payment_id,
                    'gateway' => 'razorpay',
                    'gateway_transaction_id' => $request->razorpay_payment_id,
                    'method' => $isPartialCod ? 'upi_cod_advance' : 'online',
                    'amount' => $actualPaidAmount,
                    'currency' => 'INR',
                    'status' => 'captured',
                    'captured_at' => now(),
                ]);
            }

            // Deduct gift card + record usage after order row exists (foreign key needed)
            if ($lockedGiftCard && $giftCardDiscount > 0) {
                $lockedGiftCard->deduct($giftCardDiscount);
                $lockedGiftCard->usages()->create([
                    'order_id' => $order->id,
                    'amount' => $giftCardDiscount,
                ]);
            }

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'seller_id' => $item->product->seller_id,
                    'product_name' => $item->product->name,
                    'sku' => $item->product->sku ?? '',
                    'variant_name' => $item->variant?->attributeValues->pluck('value')->join(' / '),
                    'quantity' => $item->quantity,
                    'mrp' => $item->product->mrp ?? $item->price,
                    'price' => $item->price,
                    'tax' => 0,
                    'discount' => 0,
                    'total' => $item->price * $item->quantity,
                ]);

                // Atomic stock decrement with pessimistic lock to prevent race conditions
                if ($item->variant_id) {
                    $variant = DB::table('product_variants')->where('id', $item->variant_id)->lockForUpdate()->first();
                    $updated = $variant && $variant->stock_quantity >= $item->quantity
                        ? DB::table('product_variants')->where('id', $item->variant_id)
                            ->update(['stock_quantity' => DB::raw('stock_quantity - ' . (int) $item->quantity)])
                        : 0;
                } else {
                    $lockedProduct = DB::table('products')->where('id', $item->product_id)->lockForUpdate()->first();
                    $updated = $lockedProduct && $lockedProduct->stock_quantity >= $item->quantity
                        ? DB::table('products')->where('id', $item->product_id)
                            ->update(['stock_quantity' => DB::raw('stock_quantity - ' . (int) $item->quantity)])
                        : 0;
                }

                if (!$updated) {
                    throw new \RuntimeException("Insufficient stock for \"{$item->product->name}\".");
                }

                // Auto-update stock_status when stock hits 0
                DB::table('products')
                    ->where('id', $item->product_id)
                    ->where('stock_quantity', '<=', 0)
                    ->update(['stock_status' => 'out_of_stock']);

                $item->product->increment('sales_count', $item->quantity);
            }

            // Re-validate coupon
            if ($cart->coupon) {
                $coupon = $cart->coupon;
                if ($coupon->is_active && (!$coupon->expires_at || $coupon->expires_at >= now()) && (!$coupon->usage_limit || $coupon->times_used < $coupon->usage_limit)) {
                    $coupon->increment('times_used');
                }
            }

            $cart->items()->delete();
            $cart->update(['coupon_id' => null, 'discount' => 0]);

            return $order;
        });

        // Clean up
        session()->forget('razorpay_checkout');
        session()->forget('gift_card');
        $this->markAbandonedRecovered($cart, $order);

        if ($isGuest) {
            session()->put('guest_order_id', $order->id);
            $this->createAccountForGuest($order, $validated);
        }

        $this->logActivity('order_placed', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'total' => $order->total,
            'payment_method' => $paymentMethod,
            'razorpay_payment_id' => $request->razorpay_payment_id,
        ], $request);

        $order->load('items.product', 'user');

        try {
            OrderPlaced::dispatch($order, 'web');
        } catch (\Exception $e) {
            Log::error('OrderPlaced event failed (Razorpay)', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'redirect' => route('checkout.success', $order->checkout_token),
        ]);
    }

    /**
     * Create a Cashfree order and return payment_session_id for the JS SDK.
     */
    public function createCashfreeOrder(Request $request, CashfreeService $cashfree): JsonResponse
    {
        if (!$cashfree->isConfigured()) {
            return response()->json(['error' => 'Payment gateway not configured. Please contact store admin.'], 422);
        }

        $this->logActivity('payment_initiated', ['method' => 'cashfree'], $request);
        $isGuest = !auth()->check();

        $rules = [
            'same_billing_address' => ['nullable', 'boolean'],
            'payment_method' => ['required', 'string', 'in:cashfree,cod'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        if ($isGuest) {
            $rules['guest_email'] = ['required', 'email', 'max:255'];
            $rules['guest_name'] = ['required', 'string', 'max:255'];
            $rules['guest_phone'] = ['required', 'string', 'regex:/^[6-9]\d{9}$/'];
            $rules['shipping_name'] = ['required', 'string', 'max:255'];
            $rules['shipping_address_line_1'] = ['required', 'string', 'max:255'];
            $rules['shipping_address_line_2'] = ['nullable', 'string', 'max:255'];
            $rules['shipping_city'] = ['required', 'string', 'max:100'];
            $rules['shipping_state'] = ['required', 'string', 'max:100'];
            $rules['shipping_postal_code'] = ['required', 'string', 'max:10'];
        } else {
            $rules['shipping_address_id'] = ['required', 'exists:user_addresses,id'];
            $rules['billing_address_id'] = ['nullable', 'integer'];
        }

        try {
            $validated = $request->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Cashfree checkout validation failed', [
                'errors' => $e->errors(),
                'input' => $request->except(['_token']),
                'is_guest' => $isGuest,
            ]);
            return response()->json(['error' => collect($e->errors())->flatten()->first()], 422);
        }

        $cart = $this->getCart(['items.product', 'items.variant', 'coupon']);
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['error' => 'Your cart is empty.'], 422);
        }

        // Re-validate stock
        foreach ($cart->items as $item) {
            $available = $item->variant_id ? $item->variant->stock_quantity : $item->product->stock_quantity;
            if ($available < $item->quantity) {
                return response()->json(['error' => "\"{$item->product->name}\" only has {$available} item(s) in stock."], 422);
            }
        }

        // Calculate totals (mirrors Razorpay flow)
        $paymentMethod = $validated['payment_method'];
        $navratriDiscount = 0;
        if (Setting::get('navratri_offer_active', '0') === '1') {
            $navratriDiscount = round(($cart->subtotal - $cart->discount) * 0.05, 2);
        }
        $totalDiscount = $cart->discount + $navratriDiscount;

        $freeShipThreshold = (float) Setting::get('free_shipping_threshold', 499);
        $hasFreeShippingCoupon = $cart->coupon && ($cart->coupon->type === 'free_shipping' || $cart->discount >= $cart->subtotal);
        $flatShipRate = (float) Setting::get('flat_rate_amount', 50);
        $shippingFee = ($hasFreeShippingCoupon || ($cart->subtotal - $cart->discount) >= $freeShipThreshold) ? 0 : $flatShipRate;

        $prepaidDiscount = 0;
        $prepaidPct = (float) Setting::get('prepaid_discount_percent', 0);
        if ($prepaidPct > 0 && $paymentMethod === 'cashfree') {
            $prepaidDiscount = round(($cart->subtotal - $cart->discount) * $prepaidPct / 100, 2);
            $totalDiscount += $prepaidDiscount;
        }

        // Gift card redemption (Cashfree path) — mirrors Razorpay path
        $giftCardDiscount = 0;
        $giftCardId = null;
        $giftCardCode = session('gift_card.code');
        if ($giftCardCode) {
            $giftCard = \App\Models\GiftCard::where('code', strtoupper(trim($giftCardCode)))->first();
            if ($giftCard && $giftCard->isValid()) {
                $remainingAfterDiscounts = $cart->subtotal - $totalDiscount;
                $giftCardDiscount = min((float) $giftCard->current_balance, max(0, $remainingAfterDiscounts));
                $totalDiscount += $giftCardDiscount;
                $giftCardId = $giftCard->id;
            } else {
                session()->forget('gift_card');
                return response()->json(['error' => 'Invalid or expired gift card. Please re-apply.'], 422);
            }
        }

        $rawTotal = $cart->subtotal - $totalDiscount + $shippingFee;
        // Cap discount so customer always pays at least ₹1 — prevents coupon overshoot from bypassing payment
        if ($rawTotal < 1 && $cart->subtotal > 0) {
            $totalDiscount = max(0, $cart->subtotal + $shippingFee - 1);
            $rawTotal = $cart->subtotal - $totalDiscount + $shippingFee;
        }
        $isFreeOrder = $rawTotal <= 0;
        $finalTotal = $isFreeOrder ? 0 : max(1, $rawTotal);
        $orderTax = $cart->tax;

        // Free order short-circuit (Cashfree path)
        if ($isFreeOrder) {
            return response()->json([
                'free_order' => true,
                'message' => 'Free order — no payment required.',
            ]);
        }

        $codAdvanceAmt = (int) Setting::get('cod_advance_amount', 100);
        $chargeAmount = $finalTotal;
        if ($paymentMethod === 'cod') {
            $chargeAmount = min($codAdvanceAmt, $finalTotal);
        }

        // Customer details
        $contactName = $isGuest ? $validated['guest_name'] : (auth()->user()->name ?? 'Customer');
        $contactEmail = $isGuest ? ($validated['guest_email'] ?? null) : (auth()->user()->email ?? null);
        $contactPhone = $isGuest ? $validated['guest_phone'] : (auth()->user()->phone ?? '');

        if (empty($contactEmail)) {
            // Cashfree requires email — synthesize one for guests who didn't provide
            $contactEmail = 'guest_' . substr(md5($contactPhone . $cart->id), 0, 10) . '@noreply.local';
        }

        $cfOrderId = 'CART_' . $cart->id . '_' . time();
        $customerId = $isGuest ? 'guest_' . substr(md5($contactPhone), 0, 12) : 'user_' . auth()->id();

        $payload = [
            'order_id' => $cfOrderId,
            'order_amount' => round((float) $chargeAmount, 2),
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id' => $customerId,
                'customer_name' => $contactName,
                'customer_email' => $contactEmail,
                'customer_phone' => $contactPhone,
            ],
            'order_meta' => [
                'return_url' => route('checkout.cashfree.return') . '?cf_order_id={order_id}',
                'notify_url' => route('checkout.cashfree.webhook'),
            ],
            'order_note' => 'Order from ' . Setting::get('store_name', config('app.name')),
        ];

        $cfResponse = $cashfree->createOrder($payload);
        if (!$cfResponse || empty($cfResponse['payment_session_id'])) {
            return response()->json(['error' => 'Failed to create payment order. Please try again.'], 500);
        }

        // Store checkout data in session for verification step
        $checkoutPayload = [
            'cf_order_id' => $cfOrderId,
            'validated' => $validated,
            'final_total' => $finalTotal,
            'total_discount' => $totalDiscount,
            'navratri_discount' => $navratriDiscount,
            'order_tax' => $orderTax,
            'shipping_fee' => $shippingFee,
            'payment_method' => $paymentMethod,
            'prepaid_discount' => $prepaidDiscount,
            'cart_subtotal' => $cart->subtotal,
            'gift_card_id' => $giftCardId,
            'gift_card_discount' => $giftCardDiscount,
            'cart_id' => $cart->id,
            'user_id' => auth()->id(),
            'is_guest' => $isGuest,
        ];
        session()->put('cashfree_checkout', $checkoutPayload);

        // Also persist in cache so the webhook can create the order
        // if the browser return fails (tab closed, network drop, etc.)
        Cache::put('cf_checkout:' . $cfOrderId, $checkoutPayload, now()->addHours(24));

        // FB CAPI
        $fbEventId = AnalyticsService::generateEventId('api');
        try {
            app(AnalyticsService::class)->trackAddPaymentInfo($finalTotal, $paymentMethod, $request, $fbEventId);
        } catch (\Throwable $e) { /* non-fatal */ }

        return response()->json([
            'payment_session_id' => $cfResponse['payment_session_id'],
            'cf_order_id' => $cfOrderId,
            'mode' => Setting::get('cashfree_mode', 'production'),
            'fb_event_id' => $fbEventId,
            'payment_link' => $cfResponse['payment_link'] ?? null,
        ]);
    }

    /**
     * Fallback redirect page when Cashfree JS SDK is blocked by ad-blockers.
     * Renders a minimal page that loads the SDK and triggers checkout.
     */
    public function cashfreeRedirect(Request $request)
    {
        $sessionId = $request->query('session_id', '');
        $mode = $request->query('mode', 'production');

        if (empty($sessionId)) {
            return redirect()->route('checkout')->with('error', 'Invalid payment session.');
        }

        return response()->view('checkout.cashfree-redirect', [
            'sessionId' => $sessionId,
            'mode' => $mode,
        ]);
    }

    /**
     * Cashfree return URL handler. After payment, browser redirects here.
     * We verify payment status server-side via Cashfree API and create the order.
     */
    public function cashfreeReturn(Request $request, CashfreeService $cashfree)
    {
        $cfOrderId = (string) $request->query('cf_order_id', '');
        if (empty($cfOrderId)) {
            return redirect()->route('checkout.failed')->with('error', 'Invalid payment session.');
        }

        $checkoutData = session('cashfree_checkout');
        if (!$checkoutData || $checkoutData['cf_order_id'] !== $cfOrderId) {
            return redirect()->route('checkout.failed')->with('error', 'Invalid session. Please try again.');
        }

        // Idempotency: if order already created (e.g. by webhook), redirect to success
        $existingOrder = Order::where('razorpay_order_id', $cfOrderId)->first();
        if ($existingOrder) {
            session()->forget('cashfree_checkout');
            Cache::forget('cf_checkout:' . $cfOrderId);
            return redirect()->route('checkout.success', $existingOrder->checkout_token);
        }

        // Verify payment with Cashfree
        $cfOrder = $cashfree->getOrder($cfOrderId);
        if (!$cfOrder) {
            return redirect()->route('checkout.failed')->with('error', 'Could not verify payment. Please contact support.');
        }

        $orderStatus = $cfOrder['order_status'] ?? '';
        if ($orderStatus !== 'PAID') {
            $this->logActivity('payment_verification_failed', ['cf_order_id' => $cfOrderId, 'status' => $orderStatus], $request);
            return redirect()->route('checkout.failed')->with('error', 'Payment was not completed. Status: ' . $orderStatus);
        }

        // Get payment details
        $payments = $cashfree->getOrderPayments($cfOrderId);
        $cfPaymentId = $payments[0]['cf_payment_id'] ?? null;

        // Build the order using shared logic
        return $this->finalizeCashfreeOrder($request, $checkoutData, $cfOrderId, $cfPaymentId);
    }

    /**
     * Cashfree webhook handler — async confirmation. Creates order if not already created.
     */
    public function cashfreeWebhook(Request $request, CashfreeService $cashfree): JsonResponse
    {
        $rawBody = $request->getContent();
        $timestamp = (string) $request->header('x-webhook-timestamp', '');
        $signature = (string) $request->header('x-webhook-signature', '');

        if (!$cashfree->verifyWebhookSignature($rawBody, $timestamp, $signature)) {
            Log::warning('Cashfree webhook signature invalid');
            return response()->json(['ok' => false], 401);
        }

        $payload = json_decode($rawBody, true);
        $type = $payload['type'] ?? '';
        $cfOrderId = $payload['data']['order']['order_id'] ?? null;

        if ($type !== 'PAYMENT_SUCCESS_WEBHOOK' || !$cfOrderId) {
            return response()->json(['ok' => true]);
        }

        // Idempotency: order already created by return URL or previous webhook.
        // Check both razorpay_order_id (Cashfree orders use this column) and
        // metadata->cashfree_order_id (set by Shiprocket Checkout advance payments).
        $orderExists = Order::where('razorpay_order_id', $cfOrderId)->exists()
            || Order::whereJsonContains('metadata->cashfree_order_id', $cfOrderId)->exists();
        if ($orderExists) {
            Log::info('Cashfree webhook: order already exists', ['cf_order_id' => $cfOrderId]);
            return response()->json(['ok' => true]);
        }

        // Verify payment status with Cashfree API
        $cfOrder = $cashfree->getOrder($cfOrderId);
        if (!$cfOrder || ($cfOrder['order_status'] ?? '') !== 'PAID') {
            Log::warning('Cashfree webhook: order not PAID', ['cf_order_id' => $cfOrderId, 'status' => $cfOrder['order_status'] ?? 'unknown']);
            return response()->json(['ok' => true]);
        }

        $payments = $cashfree->getOrderPayments($cfOrderId);
        $cfPaymentId = $payments[0]['cf_payment_id'] ?? null;
        $paymentMethod = $payments[0]['payment_method'] ?? null;

        // Determine if this is a website checkout order (CART_*) or external (payment link, dashboard)
        $isWebsiteOrder = str_starts_with($cfOrderId, 'CART_');

        try {
            if ($isWebsiteOrder) {
                // Website order: use cached checkout data for full order with items/address
                $checkoutData = Cache::get('cf_checkout:' . $cfOrderId);
                if (!$checkoutData) {
                    Log::error('Cashfree webhook: checkout data not found in cache', ['cf_order_id' => $cfOrderId]);
                    return response()->json(['ok' => false, 'error' => 'checkout data missing'], 200);
                }
                $order = $this->createOrderFromWebhook($checkoutData, $cfOrderId, $cfPaymentId);
                Cache::forget('cf_checkout:' . $cfOrderId);
            } else {
                // When Shiprocket Checkout is enabled, it uses the same Cashfree account
                // to process advance/COD payments. Any non-CART Cashfree payment is
                // Shiprocket-originated — skip it to prevent duplicate orders.
                // Shiprocket's CallbackController handles actual order creation.
                if (Setting::get('shiprocket_checkout_enabled', false)) {
                    Log::info('Cashfree webhook: skipped — Shiprocket Checkout payment (not a website CART order)', [
                        'cf_order_id' => $cfOrderId,
                        'cf_amount'   => $cfOrder['order_amount'] ?? 0,
                    ]);
                    return response()->json(['ok' => true]);
                }

                // External order (payment link, dashboard, direct API)
                $order = $this->createOrderFromCashfreeExternal($cfOrder, $cfPaymentId, $paymentMethod);
            }

            Log::info('Cashfree webhook: order created successfully', [
                'cf_order_id' => $cfOrderId,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'source' => $isWebsiteOrder ? 'website' : 'cashfree_external',
            ]);

            // Enrich abandoned checkout with customer details from Cashfree
            // Only for website orders — external orders have no abandoned checkout record
            try {
                $cartId = $isWebsiteOrder ? ($checkoutData['cart_id'] ?? null) : null;
                if ($cartId) {
                    $abandoned = \App\Models\AbandonedCheckout::where('cart_id', $cartId)
                        ->where('step', '!=', 'completed')
                        ->latest()
                        ->first();

                    if ($abandoned) {
                        $customerDetails = $cfOrder['customer_details'] ?? [];
                        $updates = ['step' => 'completed'];
                        if ($customerDetails['customer_name'] ?? null) {
                            $updates['name'] = $customerDetails['customer_name'];
                        }
                        if ($customerDetails['customer_email'] ?? null) {
                            $updates['email'] = $customerDetails['customer_email'];
                        }
                        if ($customerDetails['customer_phone'] ?? null) {
                            $updates['phone'] = $customerDetails['customer_phone'];
                        }
                        $abandoned->update($updates);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Cashfree webhook: abandoned checkout enrichment failed', ['error' => $e->getMessage()]);
            }
        } catch (\Throwable $e) {
            Log::error('Cashfree webhook: order creation failed', [
                'cf_order_id' => $cfOrderId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['ok' => false], 200);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Create order from webhook when browser return URL failed.
     * Uses cached checkout data + cart to build the order.
     */
    private function createOrderFromWebhook(array $checkoutData, string $cfOrderId, ?string $cfPaymentId): Order
    {
        $validated = $checkoutData['validated'];
        $cartId = $checkoutData['cart_id'];
        $userId = $checkoutData['user_id'] ?? null;
        $isGuest = $checkoutData['is_guest'] ?? true;

        $cart = Cart::with(['items.product', 'items.variant', 'coupon'])->find($cartId);
        if (!$cart || $cart->items->isEmpty()) {
            throw new \RuntimeException('Cart is empty or not found for webhook order creation.');
        }

        // Build address snapshots
        if ($isGuest) {
            $shippingSnapshot = [
                'name' => $validated['shipping_name'] ?? $validated['guest_name'] ?? '',
                'phone' => $validated['guest_phone'] ?? '',
                'address_line_1' => $validated['shipping_address_line_1'] ?? '',
                'address_line_2' => $validated['shipping_address_line_2'] ?? '',
                'city' => $validated['shipping_city'] ?? '',
                'state' => $validated['shipping_state'] ?? '',
                'postal_code' => $validated['shipping_postal_code'] ?? '',
                'country' => 'India',
            ];
            $billingSnapshot = $shippingSnapshot;
            $shippingAddressId = null;
            $billingAddressId = null;
        } else {
            $shippingAddress = UserAddress::where('user_id', $userId)->findOrFail($validated['shipping_address_id']);
            $billingAddressId = ($validated['same_billing_address'] ?? true)
                ? $shippingAddress->id
                : ($validated['billing_address_id'] ?? $shippingAddress->id);
            $billingAddress = UserAddress::where('user_id', $userId)->findOrFail($billingAddressId);

            $shippingSnapshot = [
                'name' => $shippingAddress->full_name,
                'phone' => $shippingAddress->phone,
                'address_line_1' => $shippingAddress->address_line_1,
                'address_line_2' => $shippingAddress->address_line_2,
                'city' => $shippingAddress->city,
                'state' => $shippingAddress->state,
                'postal_code' => $shippingAddress->postal_code,
                'country' => $shippingAddress->country,
            ];
            $billingSnapshot = [
                'name' => $billingAddress->full_name,
                'address_line_1' => $billingAddress->address_line_1,
                'city' => $billingAddress->city,
                'state' => $billingAddress->state,
                'postal_code' => $billingAddress->postal_code,
                'country' => $billingAddress->country,
            ];
            $shippingAddressId = $shippingAddress->id;
        }

        $finalTotal = $checkoutData['final_total'];
        $totalDiscount = $checkoutData['total_discount'];
        $navratriDiscount = $checkoutData['navratri_discount'] ?? 0;
        $prepaidDiscount = $checkoutData['prepaid_discount'] ?? 0;
        $orderTax = $checkoutData['order_tax'] ?? $cart->tax ?? 0;
        $shippingFee = $checkoutData['shipping_fee'] ?? 0;
        $paymentMethod = $checkoutData['payment_method'];
        $giftCardId = $checkoutData['gift_card_id'] ?? null;
        $giftCardDiscount = (float) ($checkoutData['gift_card_discount'] ?? 0);

        // Resolve affiliate from cookie/ref stored in checkout
        $affiliateId = null;
        $affiliateRefCode = null;

        $order = DB::transaction(function () use ($cart, $shippingSnapshot, $billingSnapshot, $shippingAddressId, $billingAddressId, $validated, $isGuest, $userId, $finalTotal, $totalDiscount, $paymentMethod, $navratriDiscount, $cfOrderId, $cfPaymentId, $affiliateId, $affiliateRefCode, $shippingFee, $orderTax, $prepaidDiscount, $giftCardId, $giftCardDiscount) {
            $codAdvanceAmt = (int) Setting::get('cod_advance_amount', 100);
            $isPartialCod = $paymentMethod === 'cod';
            $codAdvanceAmount = $isPartialCod ? min($codAdvanceAmt, $finalTotal) : 0;
            $actualPaidAmount = $isPartialCod ? $codAdvanceAmount : $finalTotal;

            $metadata = [
                'payment_method' => $paymentMethod,
                'payment_gateway' => 'cashfree',
                'cashfree_order_id' => $cfOrderId,
                'cashfree_payment_id' => $cfPaymentId,
                'created_by' => 'webhook',
            ];
            if ($isPartialCod) {
                $metadata['cod_advance'] = $codAdvanceAmount;
                $metadata['cod_balance'] = $finalTotal - $codAdvanceAmount;
            }
            if ($navratriDiscount > 0) $metadata['navratri_discount'] = $navratriDiscount;
            if ($prepaidDiscount > 0) $metadata['prepaid_discount'] = $prepaidDiscount;

            $lockedGiftCard = null;
            if ($giftCardId && $giftCardDiscount > 0) {
                $lockedGiftCard = \App\Models\GiftCard::where('id', $giftCardId)->lockForUpdate()->first();
                if (!$lockedGiftCard || !$lockedGiftCard->isValid() || (float) $lockedGiftCard->current_balance < $giftCardDiscount) {
                    throw new \RuntimeException('Gift card is no longer valid.');
                }
                $metadata['gift_card_code'] = $lockedGiftCard->code;
                $metadata['gift_card_discount'] = $giftCardDiscount;
            }

            $order = Order::create([
                'user_id' => $isGuest ? null : $userId,
                'guest_email' => $validated['guest_email'] ?? null,
                'guest_name' => $validated['guest_name'] ?? null,
                'guest_phone' => $validated['guest_phone'] ?? null,
                'status' => 'confirmed',
                'payment_status' => $isPartialCod ? 'pending' : 'paid',
                'subtotal' => $cart->subtotal,
                'discount' => $totalDiscount,
                'shipping_cost' => $shippingFee,
                'tax' => $orderTax,
                'total' => $finalTotal,
                'paid_amount' => $actualPaidAmount,
                'razorpay_order_id' => $cfOrderId,
                'razorpay_payment_id' => $cfPaymentId,
                'coupon_id' => $cart->coupon_id,
                'affiliate_id' => $affiliateId,
                'affiliate_referral_code' => $affiliateRefCode,
                'shipping_address_id' => $shippingAddressId,
                'billing_address_id' => $billingAddressId,
                'shipping_address_snapshot' => $shippingSnapshot,
                'billing_address_snapshot' => $billingSnapshot,
                'notes' => $validated['notes'] ?? null,
                'metadata' => $metadata,
            ]);

            if ($cfPaymentId && $actualPaidAmount > 0) {
                Payment::create([
                    'order_id' => $order->id,
                    'transaction_id' => $cfPaymentId,
                    'gateway' => 'cashfree',
                    'gateway_transaction_id' => $cfPaymentId,
                    'method' => $isPartialCod ? 'upi_cod_advance' : 'online',
                    'amount' => $actualPaidAmount,
                    'currency' => 'INR',
                    'status' => 'captured',
                    'captured_at' => now(),
                ]);
            }

            if ($lockedGiftCard && $giftCardDiscount > 0) {
                $lockedGiftCard->deduct($giftCardDiscount);
                $lockedGiftCard->usages()->create([
                    'order_id' => $order->id,
                    'amount' => $giftCardDiscount,
                ]);
            }

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'seller_id' => $item->product->seller_id,
                    'product_name' => $item->product->name,
                    'sku' => $item->product->sku ?? '',
                    'variant_name' => $item->variant?->attributeValues->pluck('value')->join(' / '),
                    'quantity' => $item->quantity,
                    'mrp' => $item->product->mrp ?? $item->price,
                    'price' => $item->price,
                    'tax' => 0,
                    'discount' => 0,
                    'total' => $item->price * $item->quantity,
                ]);

                if ($item->variant_id) {
                    $variant = DB::table('product_variants')->where('id', $item->variant_id)->lockForUpdate()->first();
                    $updated = $variant && $variant->stock_quantity >= $item->quantity
                        ? DB::table('product_variants')->where('id', $item->variant_id)
                            ->update(['stock_quantity' => DB::raw('stock_quantity - ' . (int) $item->quantity)])
                        : 0;
                } else {
                    $lockedProduct = DB::table('products')->where('id', $item->product_id)->lockForUpdate()->first();
                    $updated = $lockedProduct && $lockedProduct->stock_quantity >= $item->quantity
                        ? DB::table('products')->where('id', $item->product_id)
                            ->update(['stock_quantity' => DB::raw('stock_quantity - ' . (int) $item->quantity)])
                        : 0;
                }

                if (!$updated) {
                    throw new \RuntimeException("Insufficient stock for \"{$item->product->name}\".");
                }

                DB::table('products')
                    ->where('id', $item->product_id)
                    ->where('stock_quantity', '<=', 0)
                    ->update(['stock_status' => 'out_of_stock']);

                $item->product->increment('sales_count', $item->quantity);
            }

            if ($cart->coupon) {
                $coupon = $cart->coupon;
                if ($coupon->is_active && (!$coupon->expires_at || $coupon->expires_at >= now()) && (!$coupon->usage_limit || $coupon->times_used < $coupon->usage_limit)) {
                    $coupon->increment('times_used');
                }
            }

            $cart->items()->delete();
            $cart->update(['coupon_id' => null, 'discount' => 0]);

            return $order;
        });

        // Fire order event for emails/notifications
        $order->load('items.product', 'user');
        try {
            OrderPlaced::dispatch($order, 'webhook');
        } catch (\Throwable $e) {
            Log::error('OrderPlaced event failed (webhook)', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        return $order;
    }

    /**
     * Create order from external Cashfree payment (payment link, dashboard, direct API).
     * These don't have cart/address data — just customer + amount from Cashfree.
     */
    private function createOrderFromCashfreeExternal(array $cfOrder, ?string $cfPaymentId, $paymentMethod): Order
    {
        $cfOrderId = $cfOrder['order_id'];
        $amount = (float) ($cfOrder['order_amount'] ?? 0);
        $customer = $cfOrder['customer_details'] ?? [];

        $customerName = $customer['customer_name'] ?? 'Cashfree Customer';
        $customerEmail = $customer['customer_email'] ?? null;
        $customerPhone = $customer['customer_phone'] ?? null;

        // Determine payment method string from Cashfree response
        $methodStr = 'online';
        if (is_array($paymentMethod)) {
            $methodStr = array_key_first($paymentMethod) ?? 'online';
        }

        $order = DB::transaction(function () use ($cfOrderId, $cfPaymentId, $amount, $customerName, $customerEmail, $customerPhone, $methodStr) {
            $order = Order::create([
                'user_id' => null,
                'guest_email' => $customerEmail,
                'guest_name' => $customerName,
                'guest_phone' => $customerPhone,
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'subtotal' => $amount,
                'discount' => 0,
                'shipping_cost' => 0,
                'tax' => 0,
                'total' => $amount,
                'paid_amount' => $amount,
                'razorpay_order_id' => $cfOrderId,
                'razorpay_payment_id' => $cfPaymentId,
                'shipping_address_snapshot' => ['name' => $customerName, 'phone' => $customerPhone ?? ''],
                'billing_address_snapshot' => ['name' => $customerName],
                'notes' => 'Auto-created from Cashfree payment link/dashboard',
                'source' => 'api',
                'metadata' => [
                    'payment_method' => $methodStr,
                    'payment_gateway' => 'cashfree',
                    'cashfree_order_id' => $cfOrderId,
                    'cashfree_payment_id' => $cfPaymentId,
                    'created_by' => 'webhook_external',
                    'order_note' => $cfOrder['order_note'] ?? null,
                ],
            ]);

            if ($cfPaymentId && $amount > 0) {
                Payment::create([
                    'order_id' => $order->id,
                    'transaction_id' => (string) $cfPaymentId,
                    'gateway' => 'cashfree',
                    'gateway_transaction_id' => (string) $cfPaymentId,
                    'method' => $methodStr,
                    'amount' => $amount,
                    'currency' => 'INR',
                    'status' => 'captured',
                    'captured_at' => now(),
                ]);
            }

            return $order;
        });

        $order->load('items', 'user');
        try {
            OrderPlaced::dispatch($order, 'webhook');
        } catch (\Throwable $e) {
            Log::error('OrderPlaced event failed (external)', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        return $order;
    }

    /**
     * Shared logic to create the order after Cashfree payment is verified.
     */
    private function finalizeCashfreeOrder(Request $request, array $checkoutData, string $cfOrderId, ?string $cfPaymentId)
    {
        $validated = $checkoutData['validated'];
        $isGuest = !auth()->check();

        $cart = $this->getCart(['items.product', 'items.variant', 'coupon']);
        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('checkout.failed')->with('error', 'Your cart is empty.');
        }

        if (abs($cart->subtotal - ($checkoutData['cart_subtotal'] ?? 0)) > 0.01) {
            Log::warning('Cart modified between Cashfree payment creation and verification', [
                'expected_subtotal' => $checkoutData['cart_subtotal'],
                'actual_subtotal' => $cart->subtotal,
            ]);
            return redirect()->route('checkout.failed')->with('error', 'Cart was modified during payment.');
        }

        foreach ($cart->items as $item) {
            $available = $item->variant_id ? $item->variant->stock_quantity : $item->product->stock_quantity;
            if ($available < $item->quantity) {
                return redirect()->route('checkout.failed')->with('error', "\"{$item->product->name}\" is out of stock. Refund will be processed.");
            }
        }

        // Build address snapshots
        if ($isGuest) {
            $shippingSnapshot = [
                'name' => $validated['shipping_name'],
                'phone' => $validated['guest_phone'] ?? '',
                'address_line_1' => $validated['shipping_address_line_1'],
                'address_line_2' => $validated['shipping_address_line_2'] ?? '',
                'city' => $validated['shipping_city'],
                'state' => $validated['shipping_state'],
                'postal_code' => $validated['shipping_postal_code'],
                'country' => 'India',
            ];
            $billingSnapshot = $shippingSnapshot;
            $shippingAddressId = null;
            $billingAddressId = null;
        } else {
            $shippingAddress = UserAddress::where('user_id', auth()->id())->findOrFail($validated['shipping_address_id']);
            $billingAddressId = $validated['same_billing_address']
                ? $shippingAddress->id
                : ($validated['billing_address_id'] ?? $shippingAddress->id);
            $billingAddress = UserAddress::where('user_id', auth()->id())->findOrFail($billingAddressId);

            $shippingSnapshot = [
                'name' => $shippingAddress->full_name,
                'phone' => $shippingAddress->phone,
                'address_line_1' => $shippingAddress->address_line_1,
                'address_line_2' => $shippingAddress->address_line_2,
                'city' => $shippingAddress->city,
                'state' => $shippingAddress->state,
                'postal_code' => $shippingAddress->postal_code,
                'country' => $shippingAddress->country,
            ];
            $billingSnapshot = [
                'name' => $billingAddress->full_name,
                'address_line_1' => $billingAddress->address_line_1,
                'city' => $billingAddress->city,
                'state' => $billingAddress->state,
                'postal_code' => $billingAddress->postal_code,
                'country' => $billingAddress->country,
            ];
            $shippingAddressId = $shippingAddress->id;
            $billingAddressId = $billingAddress->id;
        }

        $finalTotal = $checkoutData['final_total'];
        $totalDiscount = $checkoutData['total_discount'];
        $navratriDiscount = $checkoutData['navratri_discount'];
        $prepaidDiscount = $checkoutData['prepaid_discount'] ?? 0;
        $orderTax = $checkoutData['order_tax'] ?? $cart->tax ?? 0;
        $shippingFee = $checkoutData['shipping_fee'] ?? 0;
        $paymentMethod = $checkoutData['payment_method'];
        $giftCardId = $checkoutData['gift_card_id'] ?? null;
        $giftCardDiscount = (float) ($checkoutData['gift_card_discount'] ?? 0);

        // Resolve affiliate
        $affiliateId = null;
        $affiliateRefCode = null;
        $refCode = session('affiliate_ref') ?? request()->cookie(config('affiliate.cookie_name', 'store_ref'));
        if ($refCode) {
            $aff = Affiliate::where('referral_code', $refCode)->where('status', 'approved')->first();
            if ($aff) {
                $affiliateId = $aff->id;
                $affiliateRefCode = $refCode;
            }
        }

        $order = DB::transaction(function () use ($cart, $shippingSnapshot, $billingSnapshot, $shippingAddressId, $billingAddressId, $validated, $isGuest, $finalTotal, $totalDiscount, $paymentMethod, $navratriDiscount, $cfOrderId, $cfPaymentId, $affiliateId, $affiliateRefCode, $shippingFee, $orderTax, $prepaidDiscount, $giftCardId, $giftCardDiscount) {
            $codAdvanceAmt = (int) Setting::get('cod_advance_amount', 100);
            $isPartialCod = $paymentMethod === 'cod';
            $codAdvanceAmount = $isPartialCod ? min($codAdvanceAmt, $finalTotal) : 0;
            $actualPaidAmount = $isPartialCod ? $codAdvanceAmount : $finalTotal;

            $metadata = [
                'payment_method' => $paymentMethod,
                'payment_gateway' => 'cashfree',
                'cashfree_order_id' => $cfOrderId,
                'cashfree_payment_id' => $cfPaymentId,
            ];
            if ($isPartialCod) {
                $metadata['cod_advance'] = $codAdvanceAmount;
                $metadata['cod_balance'] = $finalTotal - $codAdvanceAmount;
            }
            if ($navratriDiscount > 0) $metadata['navratri_discount'] = $navratriDiscount;
            if ($prepaidDiscount > 0) $metadata['prepaid_discount'] = $prepaidDiscount;
            if ($affiliateRefCode) $metadata['affiliate_referral_code'] = $affiliateRefCode;

            // Gift card: lock + validate inside transaction
            $lockedGiftCard = null;
            if ($giftCardId && $giftCardDiscount > 0) {
                $lockedGiftCard = \App\Models\GiftCard::where('id', $giftCardId)->lockForUpdate()->first();
                if (!$lockedGiftCard || !$lockedGiftCard->isValid() || (float) $lockedGiftCard->current_balance < $giftCardDiscount) {
                    throw new \RuntimeException('Gift card is no longer valid. Please remove it and retry.');
                }
                $metadata['gift_card_code'] = $lockedGiftCard->code;
                $metadata['gift_card_discount'] = $giftCardDiscount;
            }

            $order = Order::create([
                'user_id' => $isGuest ? null : auth()->id(),
                'guest_email' => $validated['guest_email'] ?? null,
                'guest_name' => $validated['guest_name'] ?? null,
                'guest_phone' => $validated['guest_phone'] ?? null,
                'status' => 'confirmed',
                'payment_status' => $isPartialCod ? 'pending' : 'paid',
                'subtotal' => $cart->subtotal,
                'discount' => $totalDiscount,
                'shipping_cost' => $shippingFee,
                'tax' => $orderTax,
                'total' => $finalTotal,
                'paid_amount' => $actualPaidAmount,
                'razorpay_order_id' => $cfOrderId,        // reused column for idempotency lookup
                'razorpay_payment_id' => $cfPaymentId,    // reused column
                'coupon_id' => $cart->coupon_id,
                'affiliate_id' => $affiliateId,
                'affiliate_referral_code' => $affiliateRefCode,
                'shipping_address_id' => $shippingAddressId,
                'billing_address_id' => $billingAddressId,
                'shipping_address_snapshot' => $shippingSnapshot,
                'billing_address_snapshot' => $billingSnapshot,
                'notes' => $validated['notes'] ?? null,
                'metadata' => $metadata,
            ]);

            // PR #18 — Phase 0: same payment-ledger fix as Razorpay path. This was tonight's Himanshu bug:
            // his Cashfree partial-COD order was created but no Payment row existed, so admin had no audit
            // trail for the ₹100 advance. Backfilled manually; this prevents it for every future order.
            if ($cfPaymentId && $actualPaidAmount > 0) {
                Payment::create([
                    'order_id' => $order->id,
                    'transaction_id' => $cfPaymentId,
                    'gateway' => 'cashfree',
                    'gateway_transaction_id' => $cfPaymentId,
                    'method' => $isPartialCod ? 'upi_cod_advance' : 'online',
                    'amount' => $actualPaidAmount,
                    'currency' => 'INR',
                    'status' => 'captured',
                    'captured_at' => now(),
                ]);
            }

            // Deduct gift card now that we have order id
            if ($lockedGiftCard && $giftCardDiscount > 0) {
                $lockedGiftCard->deduct($giftCardDiscount);
                $lockedGiftCard->usages()->create([
                    'order_id' => $order->id,
                    'amount' => $giftCardDiscount,
                ]);
            }

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'seller_id' => $item->product->seller_id,
                    'product_name' => $item->product->name,
                    'sku' => $item->product->sku ?? '',
                    'variant_name' => $item->variant?->attributeValues->pluck('value')->join(' / '),
                    'quantity' => $item->quantity,
                    'mrp' => $item->product->mrp ?? $item->price,
                    'price' => $item->price,
                    'tax' => 0,
                    'discount' => 0,
                    'total' => $item->price * $item->quantity,
                ]);

                // Atomic stock decrement with pessimistic lock to prevent race conditions
                if ($item->variant_id) {
                    $variant = DB::table('product_variants')->where('id', $item->variant_id)->lockForUpdate()->first();
                    $updated = $variant && $variant->stock_quantity >= $item->quantity
                        ? DB::table('product_variants')->where('id', $item->variant_id)
                            ->update(['stock_quantity' => DB::raw('stock_quantity - ' . (int) $item->quantity)])
                        : 0;
                } else {
                    $lockedProduct = DB::table('products')->where('id', $item->product_id)->lockForUpdate()->first();
                    $updated = $lockedProduct && $lockedProduct->stock_quantity >= $item->quantity
                        ? DB::table('products')->where('id', $item->product_id)
                            ->update(['stock_quantity' => DB::raw('stock_quantity - ' . (int) $item->quantity)])
                        : 0;
                }

                if (!$updated) {
                    throw new \RuntimeException("Insufficient stock for \"{$item->product->name}\".");
                }

                DB::table('products')
                    ->where('id', $item->product_id)
                    ->where('stock_quantity', '<=', 0)
                    ->update(['stock_status' => 'out_of_stock']);

                $item->product->increment('sales_count', $item->quantity);
            }

            if ($cart->coupon) {
                $coupon = $cart->coupon;
                if ($coupon->is_active && (!$coupon->expires_at || $coupon->expires_at >= now()) && (!$coupon->usage_limit || $coupon->times_used < $coupon->usage_limit)) {
                    $coupon->increment('times_used');
                }
            }

            $cart->items()->delete();
            $cart->update(['coupon_id' => null, 'discount' => 0]);

            return $order;
        });

        session()->forget('cashfree_checkout');
        session()->forget('gift_card');
        Cache::forget('cf_checkout:' . $cfOrderId);
        $this->markAbandonedRecovered($cart, $order);

        if ($isGuest) {
            session()->put('guest_order_id', $order->id);
            $this->createAccountForGuest($order, $validated);
        }

        $this->logActivity('order_placed', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'total' => $order->total,
            'payment_method' => $paymentMethod,
            'cashfree_order_id' => $cfOrderId,
            'cashfree_payment_id' => $cfPaymentId,
        ], $request);

        $order->load('items.product', 'user');

        try {
            OrderPlaced::dispatch($order, 'web');
        } catch (\Throwable $e) {
            Log::error('OrderPlaced event failed (Cashfree)', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        return redirect()->route('checkout.success', $order->checkout_token);
    }

    private function getCart(array $with = ['items.product', 'items.variant']): ?Cart
    {
        if (auth()->check()) {
            return Cart::where('user_id', auth()->id())->with($with)->first();
        }

        $sessionId = session()->getId();
        return Cart::where('session_id', $sessionId)->whereNull('user_id')->with($with)->first();
    }

    private function recordAbandonedCheckout(Cart $cart, string $step = 'checkout'): void
    {
        AbandonedCheckout::updateOrCreate(
            ['cart_id' => $cart->id],
            [
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'cart_total' => $cart->subtotal - $cart->discount,
                'items_count' => $cart->items->count(),
                'step' => $step,
                'cart_snapshot' => $cart->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ])->toArray(),
            ]
        );
    }

    private function markAbandonedRecovered(Cart $cart, Order $order): void
    {
        AbandonedCheckout::where('cart_id', $cart->id)->update([
            'recovered' => true,
            'order_id' => $order->id,
            'recovered_at' => now(),
        ]);
    }

    /**
     * Server-side pincode serviceability check (returns bool).
     */
    private function isPincodeServiceable(string $pincode): bool
    {
        $shiprocket = app(ShiprocketService::class);
        $delhivery = app(DelhiveryService::class);

        if (Setting::get('shiprocket_enabled', false) && $shiprocket->isConfigured()) {
            $pickup = Setting::get('shiprocket_pickup_pincode', '');
            if (!empty($pickup)) {
                $result = $shiprocket->checkServiceability($pickup, $pincode);
                return $result['available'] ?? false;
            }
        }

        if (!$delhivery->isConfigured()) {
            return true; // No shipping provider configured — allow
        }

        $result = $delhivery->checkPincode($pincode);
        return $result['serviceable'] ?? false;
    }

    /**
     * Check pincode serviceability via Delhivery API.
     */
    public function checkPincode(string $pincode, DelhiveryService $delhivery, ShiprocketService $shiprocket): JsonResponse
    {
        // Prefer Shiprocket when enabled and configured
        if (Setting::get('shiprocket_enabled', false) && $shiprocket->isConfigured()) {
            $pickup = Setting::get('shiprocket_pickup_pincode', '');
            if (!empty($pickup)) {
                $result = $shiprocket->checkServiceability($pickup, $pincode);
                if ($result['available'] ?? false) {
                    return response()->json([
                        'serviceable' => true,
                        'message' => 'Delivery available in ' . ($result['fastest']['estimated_days'] ?? 5) . ' days',
                        'prepaid' => true,
                        'cod' => collect($result['all'] ?? [])->contains(fn ($c) => ($c['cod'] ?? 0) == 1),
                        'estimated_days' => $result['fastest']['estimated_days'] ?? null,
                    ]);
                }
                return response()->json([
                    'serviceable' => false,
                    'message' => $result['message'] ?? 'Delivery not available to this pincode',
                    'prepaid' => false,
                    'cod' => false,
                ]);
            }
        }

        // Fallback to Delhivery
        if (!$delhivery->isConfigured()) {
            return response()->json([
                'serviceable' => true,
                'message' => 'Delivery available',
                'prepaid' => true,
                'cod' => true,
            ]);
        }

        $result = $delhivery->checkPincode($pincode);

        return response()->json($result);
    }

    /**
     * Capture guest email/phone for abandoned checkout recovery (AJAX).
     */
    public function captureAbandoned(Request $request): JsonResponse
    {
        $cart = $this->getCart();
        if (!$cart) {
            return response()->json(['ok' => false], 404);
        }

        $email = $request->input('email');
        $phone = $request->input('phone');
        $name = $request->input('name');

        if (!$email && !$phone) {
            return response()->json(['ok' => false], 422);
        }

        AbandonedCheckout::updateOrCreate(
            ['cart_id' => $cart->id],
            array_filter([
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'email' => $email,
                'phone' => $phone,
                'name' => $name,
                'cart_total' => $cart->subtotal - $cart->discount,
                'items_count' => $cart->items->count(),
                'step' => 'contact_captured',
                'cart_snapshot' => $cart->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ])->toArray(),
            ])
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Apply a gift card to the current checkout session.
     * Validates the gift card and stores gift_card_id + preview amount in session so
     * subsequent process() / verifyPayment() calls can deduct it inside a DB transaction.
     */
    public function applyGiftCard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $cart = $this->getCart();
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['error' => 'Your cart is empty.'], 422);
        }

        $code = strtoupper(trim($validated['code']));
        $giftCard = \App\Models\GiftCard::where('code', $code)->first();

        if (!$giftCard || !$giftCard->isValid()) {
            return response()->json(['error' => 'Invalid or expired gift card code.'], 422);
        }

        // Compute an illustrative applied amount (capped at current cart total — final cap happens
        // in process()/verifyPayment() where the real order total is known).
        $cartTotalAfterDiscount = max(0, (float) $cart->subtotal - (float) $cart->discount);
        $previewAmount = min((float) $giftCard->current_balance, $cartTotalAfterDiscount);

        session()->put('gift_card', [
            'id' => $giftCard->id,
            'code' => $giftCard->code,
            'amount' => round($previewAmount, 2),
            'balance' => (float) $giftCard->current_balance,
        ]);

        $this->logActivity('gift_card_applied', [
            'gift_card_id' => $giftCard->id,
            'code' => $giftCard->code,
            'preview_amount' => $previewAmount,
        ], $request);

        return response()->json([
            'success' => true,
            'message' => 'Gift card applied.',
            'gift_card' => [
                'masked_code' => $this->maskGiftCardCode($giftCard->code),
                'amount' => round($previewAmount, 2),
                'balance' => (float) $giftCard->current_balance,
            ],
        ]);
    }

    /**
     * Remove the gift card from the current checkout session.
     */
    public function removeGiftCard(Request $request): JsonResponse
    {
        session()->forget('gift_card');
        $this->logActivity('gift_card_removed', [], $request);

        return response()->json([
            'success' => true,
            'message' => 'Gift card removed.',
        ]);
    }

    /**
     * Mask a gift card code for UI display (e.g. "ABCD********WXYZ").
     */
    private function maskGiftCardCode(string $code): string
    {
        $len = strlen($code);
        if ($len <= 8) {
            return str_repeat('*', max(0, $len - 4)) . substr($code, -4);
        }
        return substr($code, 0, 4) . str_repeat('*', $len - 8) . substr($code, -4);
    }

    /**
     * Auto-create user account for guest orders and send credentials via email + WhatsApp.
     */
    /**
     * Save the guest's checkout shipping address into user_addresses
     * and link it as the order's shipping_address_id + checkout preference default.
     */
    private function saveGuestAddress(\App\Models\User $user, Order $order, array $validated, string $name, ?string $cleanPhone): void
    {
        if (empty($validated['shipping_address_line_1'])) {
            return;
        }

        try {
            $nameParts = explode(' ', trim($name), 2);

            $address = UserAddress::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'address_line_1' => $validated['shipping_address_line_1'],
                    'postal_code' => $validated['shipping_postal_code'] ?? '',
                ],
                [
                    'first_name' => $nameParts[0] ?? $name,
                    'last_name' => $nameParts[1] ?? '',
                    'phone' => $cleanPhone ?? '',
                    'address_line_2' => $validated['shipping_address_line_2'] ?? null,
                    'city' => $validated['shipping_city'] ?? '',
                    'state' => $validated['shipping_state'] ?? '',
                    'country' => $validated['shipping_country'] ?? 'India',
                    'is_default' => true,
                    'type' => 'shipping',
                ]
            );

            // Link the order to the saved address (snapshot is preserved as-is)
            $order->update([
                'shipping_address_id' => $address->id,
                'billing_address_id' => $order->billing_address_id ?? $address->id,
            ]);

            // Set as default shipping for next checkout
            \App\Models\UserCheckoutPreference::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'default_shipping_address_id' => $address->id,
                    'same_as_shipping' => true,
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Guest address save failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    private function createAccountForGuest(Order $order, array $validated): void
    {
        $providedEmail = $validated['guest_email'] ?? null;
        $phone = $validated['guest_phone'] ?? null;
        $name = $validated['guest_name'] ?? 'Customer';

        // Need at least a phone (system supports OTP/phone login)
        if (!$providedEmail && !$phone) {
            return;
        }

        $cleanPhone = $phone ? preg_replace('/\D/', '', $phone) : null;

        // If no email provided, synthesize one (mirrors Cashfree contact handling)
        $email = $providedEmail ?: ('guest_' . substr(md5($cleanPhone . $order->id), 0, 12) . '@noreply.local');

        // Check if account already exists by email or phone — link order if so
        $existing = \App\Models\User::where('email', $email)
            ->when($cleanPhone, fn($q) => $q->orWhere('phone', $cleanPhone))
            ->first();
        if ($existing) {
            if (!$order->user_id) {
                $order->update(['user_id' => $existing->id]);
            }
            $this->saveGuestAddress($existing, $order, $validated, $name, $cleanPhone);
            return;
        }

        try {
            $password = \Illuminate\Support\Str::random(16);
            $nameParts = explode(' ', $name, 2);

            $user = \App\Models\User::create([
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1] ?? '',
                'email' => $email,
                'phone' => $cleanPhone,
                'password' => bcrypt($password),
                'email_verified_at' => now(),
            ]);

            // Link order to new user
            $order->update(['user_id' => $user->id]);

            // Save the shipping address into the user's address book
            $this->saveGuestAddress($user, $order, $validated, $name, $cleanPhone);

            // Skip credentials email if synthesized (no real inbox)
            if (!$providedEmail) {
                return;
            }

            // Send credentials via email
            $storeName = Setting::get('store_name', config('app.name', 'Store'));
            $brandColor = Setting::get('primary_color', '') ?: '#334155';
            \Illuminate\Support\Facades\Mail::send([], [], function ($m) use ($email, $name, $password, $order, $storeName, $brandColor) {
                $m->to($email)
                  ->subject("Your {$storeName} Account is Ready!")
                  ->html("<div style='font-family:sans-serif;max-width:450px;margin:0 auto;padding:20px;'>
                    <h2 style='color:{$brandColor};'>Welcome to {$storeName}, {$name}!</h2>
                    <p style='font-size:14px;color:#333;'>Your account has been created with your recent order #{$order->order_number}.</p>
                    <div style='background:#f5f5f5;border-radius:8px;padding:15px;margin:15px 0;'>
                        <p style='font-size:13px;color:#555;margin:0 0 5px;'><strong>Email:</strong> {$email}</p>
                        <p style='font-size:13px;color:#555;margin:0 0 5px;'><strong>Password:</strong> {$password}</p>
                    </div>
                    <p style='font-size:13px;color:#333;'>You can also login using OTP via WhatsApp — no password needed!</p>
                    <a href='" . url('/login') . "' style='display:inline-block;background:{$brandColor};color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:bold;margin-top:10px;'>Login Now</a>
                    <p style='font-size:11px;color:#999;margin-top:20px;'>We recommend changing your password after first login.</p>
                  </div>");
            });

            // Send credentials via WhatsApp
            if ($phone) {
                app(WhatsAppService::class)->sendText(
                    $phone,
                    "Hi {$name}! Your {$storeName} account is ready.\n\nEmail: {$email}\nPassword: {$password}\n\nYou can also login with OTP — just enter your phone number on the login page.\n\nLogin: " . url('/login')
                );
            }
        } catch (\Exception $e) {
            Log::warning('Guest account creation failed', ['email' => $email, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Find an existing user by phone or email for order linking.
     */
    private function findUserByPhoneOrEmail(?string $phone, ?string $email): ?\App\Models\User
    {
        if (empty($phone) && empty($email)) {
            return null;
        }

        $cleanPhone = $phone ? preg_replace('/\D/', '', $phone) : null;
        // Strip country code for matching (users may have 10-digit phone stored)
        $shortPhone = $cleanPhone && strlen($cleanPhone) > 10 ? substr($cleanPhone, -10) : $cleanPhone;

        return \App\Models\User::where(function ($q) use ($email, $cleanPhone, $shortPhone) {
            if ($email) {
                $q->orWhere('email', $email);
            }
            if ($cleanPhone) {
                $q->orWhere('phone', $cleanPhone);
            }
            if ($shortPhone && $shortPhone !== $cleanPhone) {
                $q->orWhere('phone', $shortPhone);
            }
        })->first();
    }

    /**
     * Create a user account for a Shiprocket Checkout guest and link the order.
     * Mirrors SyncShiprocketCustomerDetails::createAccountForShiprocketGuest().
     */
    private function createAccountForShiprocketGuest(Order $order): void
    {
        $phone = $order->guest_phone;
        $email = $order->guest_email;
        $name = $order->guest_name ?? 'Customer';

        $cleanPhone = $phone ? preg_replace('/\D/', '', $phone) : null;
        $userEmail = $email ?: ('guest_' . substr(md5(($cleanPhone ?? '') . $order->id), 0, 12) . '@noreply.local');

        // Double-check no user exists (race condition guard)
        $existing = \App\Models\User::where('email', $userEmail)
            ->when($cleanPhone, fn($q) => $q->orWhere('phone', $cleanPhone))
            ->first();

        if ($existing) {
            $order->update(['user_id' => $existing->id]);
            return;
        }

        try {
            $password = \Illuminate\Support\Str::random(16);
            $nameParts = explode(' ', $name, 2);

            $user = \App\Models\User::create([
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1] ?? '',
                'email' => $userEmail,
                'phone' => $cleanPhone,
                'password' => bcrypt($password),
                'email_verified_at' => now(),
            ]);

            $order->update(['user_id' => $user->id]);

            Log::info('Shiprocket checkout: created user account for guest', [
                'order_id' => $order->id,
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::warning('Shiprocket checkout: guest account creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate a Shiprocket Checkout token for the HeadlessCheckout SDK.
     *
     * Supports two modes:
     *  - Single product: { product_id, quantity }
     *  - Full cart:      { source: "cart" }
     */
    public function shiprocketCheckoutToken(Request $request): JsonResponse
    {
        $request->validate([
            'source' => 'sometimes|in:cart',
            'product_id' => 'required_without:source|integer',
            'quantity' => 'required_without:source|integer|min:1|max:10',
        ]);

        $cartItems = [];
        $cartTotal = 0;
        $itemsCount = 0;
        $metaProducts = [];

        if ($request->input('source') === 'cart') {
            $cart = Cart::with('items.product')
                ->where(auth()->check()
                    ? ['user_id' => auth()->id()]
                    : ['session_id' => session()->getId()])
                ->first();

            if (! $cart || $cart->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty',
                ], 422);
            }

            foreach ($cart->items as $item) {
                $product = $item->product;
                if (! $product) continue;

                $cartItems[] = [
                    'variant_id' => (string) $product->id,
                    'product_id' => $product->id,
                    'quantity' => (int) $item->quantity,
                    'price' => (float) $item->price,
                    'name' => $product->name,
                    'product_name' => $product->name,
                    'sku' => $product->sku ?? ('SKU-' . $product->id),
                    'image' => url($product->primary_image_url),
                ];
                $cartTotal += (float) $item->price * (int) $item->quantity;
                $itemsCount += (int) $item->quantity;
                $metaProducts[] = ['id' => $product->id, 'name' => $product->name, 'qty' => $item->quantity];
            }
        } else {
            $product = \App\Models\Product::findOrFail($request->product_id);

            $cartItems = [
                [
                    'variant_id' => (string) $product->id,
                    'product_id' => $product->id,
                    'quantity' => (int) $request->quantity,
                    'price' => (float) $product->price,
                    'name' => $product->name,
                    'product_name' => $product->name,
                    'sku' => $product->sku ?? ('SKU-' . $product->id),
                    'image' => url($product->primary_image_url),
                ],
            ];
            $cartTotal = (float) $product->price * (int) $request->quantity;
            $itemsCount = (int) $request->quantity;
            $metaProducts = [['id' => $product->id, 'name' => $product->name, 'qty' => $request->quantity]];
        }

        $redirectUrl = url('/checkout/success/shiprocket');

        $service = app(ShiprocketService::class);
        $result = $service->getCheckoutToken($cartItems, $redirectUrl);

        // Track A (PIR 2026-04-22): capture EVERY Shiprocket-Checkout click as an
        // abandoned_checkouts row before handoff. Even if Shiprocket later orphans
        // the order, we have the customer's IP/UA/UTM/cart for recovery.
        // Wrapped in try/catch so the customer's checkout never breaks if our
        // logging fails.
        try {
            $referer = $request->headers->get('referer', '');
            $utm = [];
            if ($referer) {
                $parts = parse_url($referer);
                if (! empty($parts['query'])) {
                    parse_str($parts['query'], $qs);
                    foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'utm_id', 'fbclid', 'gclid'] as $key) {
                        if (! empty($qs[$key])) {
                            $utm[$key] = $qs[$key];
                        }
                    }
                }
            }

            $user = auth()->user();

            \App\Models\AbandonedCheckout::create([
                'session_id' => $request->session()->getId(),
                'user_id' => $user?->id,
                'name' => $user ? $user->full_name : null,
                'email' => $user?->email,
                'phone' => $user?->phone,
                'cart_total' => $cartTotal,
                'items_count' => $itemsCount,
                'step' => 'shiprocket_handoff',
                'source' => 'shiprocket_checkout',
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'metadata' => array_merge($utm, [
                    'referer' => $referer,
                    'products' => $metaProducts,
                ]),
                'shiprocket_cart_token' => $result['token'] ?? null,
                'shiprocket_order_id' => $result['order_id'] ?? null,
                'cart_snapshot' => $cartItems,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('shiprocket_pretoken_capture_failed', [
                'error' => $e->getMessage(),
                'source' => $request->input('source', 'product'),
            ]);
        }

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'token' => $result['token'],
                'order_id' => $result['order_id'] ?? '',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to generate checkout token',
        ], 422);
    }
}
