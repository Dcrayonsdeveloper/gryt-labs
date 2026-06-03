<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ShiprocketService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncShiprocketCustomerDetails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 8;
    public array $backoff = [30, 120, 300, 900, 3600, 7200, 14400, 28800];

    public function __construct(
        public Order $order
    ) {}

    public function handle(ShiprocketService $srService): void
    {
        if (empty($this->order->shiprocket_order_id)) {
            return;
        }

        // Skip if customer details already populated and user linked
        if (!empty($this->order->guest_name) && !empty($this->order->guest_email) && !empty($this->order->user_id)) {
            return;
        }

        // Use Checkout API first (order IDs are Checkout hex IDs, not Shipping numeric IDs)
        $srOrder = $srService->getCheckoutOrder($this->order->shiprocket_order_id);

        // Fallback: search Shipping API by channel_order_id (checkout hex ID)
        if (!$srOrder) {
            $srOrder = $srService->findShippingOrderByCheckoutId($this->order->shiprocket_order_id);
        }

        if (!$srOrder) {
            Log::warning('SyncShiprocketCustomerDetails: API returned no data', [
                'order_id' => $this->order->id,
                'shiprocket_order_id' => $this->order->shiprocket_order_id,
                'attempt' => $this->attempts(),
            ]);
            // Let it retry via backoff
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 300);
            }
            return;
        }

        $srCustomer = $srOrder['customer_details'] ?? $srOrder;
        $updateData = [];

        if (empty($this->order->guest_name)) {
            $name = trim(($srCustomer['billing_customer_name'] ?? $srCustomer['customer_name'] ?? '') . ' ' . ($srCustomer['billing_last_name'] ?? ''));
            if (!empty($name)) {
                $updateData['guest_name'] = $name;
            }
        }

        if (empty($this->order->guest_email)) {
            $email = $srCustomer['billing_email'] ?? $srCustomer['customer_email'] ?? null;
            if (!empty($email)) {
                $updateData['guest_email'] = $email;
            }
        }

        if (empty($this->order->guest_phone)) {
            $phone = $srCustomer['billing_phone'] ?? $srCustomer['customer_phone'] ?? null;
            if (!empty($phone)) {
                $updateData['guest_phone'] = $phone;
            }
        }

        // Only build snapshot if the API actually returned address data.
        // The Checkout API often returns name/email/phone but NOT address fields.
        // Saving an empty-address snapshot blocks the webhook (which has the real
        // address) from filling it in later.
        $apiAddress = $srCustomer['billing_address'] ?? $srCustomer['customer_address'] ?? '';
        if ($this->shouldUpdateAddress($this->order) && !empty($apiAddress)) {
            $updateData['shipping_address_snapshot'] = [
                'name' => $updateData['guest_name'] ?? $this->order->guest_name ?? '',
                'phone' => $updateData['guest_phone'] ?? $this->order->guest_phone ?? '',
                'address_line_1' => $apiAddress,
                'address_line_2' => $srCustomer['billing_address_2'] ?? '',
                'city' => $srCustomer['billing_city'] ?? $srCustomer['customer_city'] ?? '',
                'state' => $srCustomer['billing_state'] ?? $srCustomer['customer_state'] ?? '',
                'postal_code' => $srCustomer['billing_pincode'] ?? $srCustomer['customer_pincode'] ?? '',
                'country' => $srCustomer['billing_country'] ?? 'India',
            ];
        }

        // Auto-link to existing user account by phone or email
        if (empty($this->order->user_id)) {
            $phone = $updateData['guest_phone'] ?? $this->order->guest_phone;
            $email = $updateData['guest_email'] ?? $this->order->guest_email;
            $matchedUser = $this->findUserByPhoneOrEmail($phone, $email);
            if ($matchedUser) {
                $updateData['user_id'] = $matchedUser->id;
                Log::info('SyncShiprocketCustomerDetails: linked order to existing user', [
                    'order_id' => $this->order->id,
                    'user_id' => $matchedUser->id,
                ]);
            }
        }

        // Correct payment status if Shipping API reveals COD but order was marked as paid.
        // Shiprocket sends ost=SUCCESS in callback even for COD orders.
        $srPaymentMethod = strtolower($srOrder['payment_method'] ?? $srOrder['payment_type'] ?? '');
        if ($this->order->payment_status === 'paid' && in_array($srPaymentMethod, ['cod', 'cash on delivery', 'cash_on_delivery'])) {
            $updateData['payment_status'] = 'pending';
            $updateData['paid_amount']    = 0;
            $meta = $this->order->metadata ?? [];
            $meta['payment_method'] = 'shiprocket_cod';
            $meta['payment_status_corrected_at'] = now()->toIso8601String();
            $updateData['metadata'] = $meta;
            Log::info('SyncShiprocketCustomerDetails: corrected COD payment status from Shipping API', [
                'order_id'          => $this->order->id,
                'sr_payment_method' => $srPaymentMethod,
            ]);
        }

        if (!empty($updateData)) {
            $this->order->update($updateData);
            Log::info('SyncShiprocketCustomerDetails: synced', [
                'order_id' => $this->order->id,
                'fields' => array_keys($updateData),
            ]);
        }

        // If linked user has a placeholder email but we now have a real one, update it
        // so OTP login works (delivers OTP to real email instead of @phone.* placeholder)
        $this->order->refresh();
        if ($this->order->user_id && !empty($updateData['guest_email'])) {
            $realEmail = $updateData['guest_email'];
            $user = \App\Models\User::find($this->order->user_id);
            if ($user && str_contains($user->email, '@phone.') && filter_var($realEmail, FILTER_VALIDATE_EMAIL)) {
                if (!\App\Models\User::where('email', $realEmail)->where('id', '!=', $user->id)->exists()) {
                    $user->update(['email' => $realEmail]);
                    Log::info('SyncShiprocketCustomerDetails: updated user placeholder email to real email', [
                        'user_id' => $user->id,
                        'email'   => $realEmail,
                    ]);
                }
            }
        }

        // If we got customer details but no existing user, create an account
        // so the customer can login for returns/refunds
        $this->order->refresh();
        if (empty($this->order->user_id) && (!empty($this->order->guest_phone) || !empty($this->order->guest_email))) {
            $this->createAccountForShiprocketGuest();
        }

        // Late notification: when the initial OrderPlaced fired with empty customer
        // fields, SendOrderNotification deferred. Now that we have data, re-trigger
        // ONLY that listener (NOT the OrderPlaced event — that would re-run BlueDart
        // booking, loyalty points, affiliate commission, etc.). The listener has its
        // own idempotency guard via metadata.confirmation_sent_at.
        $this->order->refresh();
        $alreadyNotified = !empty($this->order->metadata['confirmation_sent_at'] ?? null);
        $hasContact = $this->order->user_id || !empty($this->order->guest_phone) || !empty($this->order->guest_email);
        if (!$alreadyNotified && $hasContact) {
            try {
                app(\App\Listeners\SendOrderNotification::class)->handleOrderPlaced(
                    new \App\Events\OrderPlaced($this->order, 'shiprocket_checkout')
                );
            } catch (\Throwable $e) {
                Log::warning('SyncShiprocketCustomerDetails: late notification dispatch failed', [
                    'order_id' => $this->order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function findUserByPhoneOrEmail(?string $phone, ?string $email): ?\App\Models\User
    {
        if (empty($phone) && empty($email)) {
            return null;
        }

        $cleanPhone = $phone ? preg_replace('/\D/', '', $phone) : null;
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
     * Check if the order's shipping address snapshot is missing or has empty address fields.
     */
    private function shouldUpdateAddress(Order $order): bool
    {
        $snap = $order->shipping_address_snapshot;
        if (empty($snap)) {
            return true;
        }
        if (is_string($snap)) {
            $snap = json_decode($snap, true) ?? [];
        }
        // Snapshot exists but address fields are empty — allow overwrite
        return empty($snap['address_line_1']) && empty($snap['address']) && empty($snap['city']);
    }

    private function createAccountForShiprocketGuest(): void
    {
        $order = $this->order;
        $phone = $order->guest_phone;
        $email = $order->guest_email;
        $name = $order->guest_name ?? 'Customer';

        $cleanPhone = $phone ? preg_replace('/\D/', '', $phone) : null;

        // Synthesize email if not available
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

            // Save shipping address to user's address book
            if ($order->shipping_address_snapshot) {
                $addr = $order->shipping_address_snapshot;
                if (is_string($addr)) {
                    $addr = json_decode($addr, true) ?? [];
                }
                if (!empty($addr)) {
                    \App\Models\UserAddress::create([
                        'user_id' => $user->id,
                        'label' => 'Home',
                        'first_name' => $nameParts[0],
                        'last_name' => $nameParts[1] ?? '',
                        'phone' => $addr['phone'] ?? $cleanPhone ?? '',
                        'address_line_1' => $addr['address_line_1'] ?? '',
                        'address_line_2' => $addr['address_line_2'] ?? '',
                        'city' => $addr['city'] ?? '',
                        'state' => $addr['state'] ?? '',
                        'postal_code' => $addr['postal_code'] ?? '',
                        'country' => $addr['country'] ?? 'India',
                        'is_default' => true,
                    ]);
                }
            }

            Log::info('SyncShiprocketCustomerDetails: created user account for Shiprocket guest', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'email' => $userEmail,
            ]);
        } catch (\Exception $e) {
            Log::warning('SyncShiprocketCustomerDetails: guest account creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
