@component('mail::message')
# Payment Successful! ✅

Hi {{ $order->user?->first_name ?? $order->guest_name ?? 'Customer' }},

Your payment has been successfully processed for your order. Here are the details:

**Order Number:** #{{ $order->order_number }}
**Payment Amount:** ₹{{ number_format($payment->amount, 2) }}
**Payment Method:** {{ ucfirst($payment->method) }}
**Transaction ID:** {{ $payment->gateway_transaction_id }}
**Date:** {{ $payment->captured_at?->format('M d, Y \a\t h:i A') ?? $payment->authorized_at?->format('M d, Y \a\t h:i A') ?? now()->format('M d, Y \a\t h:i A') }}

---

## Order Summary

@component('mail::table')
| Item | Qty | Price |
|:-----|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->product_name }}@if($item->variant_name) ({{ $item->variant_name }})@endif | {{ $item->quantity }} | {{ format_price($item->total) }} |
@endforeach
@endcomponent

@component('mail::table')
| | |
|:---|---:|
| **Total** | **{{ format_price($order->total) }}** |
@endcomponent

@component('mail::button', ['url' => url('/orders/' . $order->id)])
Track Your Order
@endcomponent

Thank you for shopping with us!

Warm regards,
**{{ \App\Models\Setting::get('store_name', config('app.name')) }}**
@endcomponent
