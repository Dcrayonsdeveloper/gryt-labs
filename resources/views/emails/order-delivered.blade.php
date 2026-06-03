@component('mail::message')
# Your Order Has Been Delivered!

Hi {{ $order->user?->first_name ?? $order->guest_name ?? 'Customer' }},

We are happy to let you know that your order **#{{ $order->order_number }}** has been successfully delivered!

**Delivered On:** {{ $order->delivered_at ? $order->delivered_at->format('M d, Y \a\t h:i A') : now()->format('M d, Y \a\t h:i A') }}

---

## Order Summary

@component('mail::table')
| Item | Qty | Price |
|:-----|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->product_name }}@if($item->variant_name) ({{ $item->variant_name }})@endif | {{ $item->quantity }} | {{ format_price($item->total) }} |
@endforeach
| | **Total:** | **{{ format_price($order->total) }}** |
@endcomponent

---

## We Would Love Your Feedback!

Your opinion matters to us. Please take a moment to review the items you received. Your reviews help other shoppers make great choices.

@component('mail::button', ['url' => url('/orders/' . $order->id)])
Review Your Purchase
@endcomponent

**Need to return an item?** You can initiate a return within {{ \App\Models\Setting::get('return_policy_days', 7) }} days of delivery through your order details page.

If anything is not quite right with your order, please do not hesitate to reach out to our support team. We are always here to help!

Thank you for choosing {{ \App\Models\Setting::get('store_name', config('app.name')) }}!

Warm regards,
**{{ \App\Models\Setting::get('store_name', config('app.name')) }}**
@endcomponent
