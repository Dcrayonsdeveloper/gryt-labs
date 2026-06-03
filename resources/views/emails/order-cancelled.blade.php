@component('mail::message')
# Your Order Has Been Cancelled

Hi {{ $order->user?->first_name ?? $order->guest_name ?? 'Customer' }},

This is to confirm that your order **#{{ $order->order_number }}** has been cancelled.

**Cancelled On:** {{ now()->format('M d, Y \a\t h:i A') }}

---

## Cancelled Order Summary

@component('mail::table')
| Item | Qty | Price |
|:-----|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->product_name }}@if($item->variant_name) ({{ $item->variant_name }})@endif | {{ $item->quantity }} | {{ format_price($item->total) }} |
@endforeach
| | **Total:** | **{{ format_price($order->total) }}** |
@endcomponent

---

@if (in_array($order->payment_status, ['paid', 'partial_refund']) || $order->paid_amount > 0)
## Refund

A refund of **{{ format_price($order->paid_amount > 0 ? $order->paid_amount : $order->total) }}** will be processed to your original payment method. Refunds typically take 5–7 business days to reflect, depending on your bank.
@else
You have not been charged for this order, so there is nothing to refund.
@endif

If you did not request this cancellation, or you have any questions about it, please contact our support team right away — we are here to help.

@component('mail::button', ['url' => url('/')])
Continue Shopping
@endcomponent

We are sorry to see this order go, and we hope to serve you again soon.

Warm regards,
**{{ \App\Models\Setting::get('store_name', config('app.name')) }}**
@endcomponent
