@component('mail::message')
# Payment Failed ❌

Hi {{ $order->user?->first_name ?? $order->guest_name ?? 'Customer' }},

Unfortunately, the payment for your order could not be processed.

**Order Number:** #{{ $order->order_number }}
**Amount:** ₹{{ number_format($payment->amount, 2) }}
**Reason:** {{ $payment->failure_reason ?? 'Payment was declined' }}

Don't worry — your order is still reserved. You can retry the payment using the button below:

@component('mail::button', ['url' => url('/orders/' . $order->id), 'color' => 'primary'])
Retry Payment
@endcomponent

If you continue to face issues, please contact our support team and we'll be happy to help.

Warm regards,
**{{ \App\Models\Setting::get('store_name', config('app.name')) }}**
@endcomponent
