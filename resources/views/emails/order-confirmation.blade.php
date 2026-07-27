@php
    $customerName = $order->user->first_name ?? $order->guest_name ?? 'there';
    $paymentMethod = $order->metadata['payment_method'] ?? 'cod';
    $paidOnline = (float) ($order->paid_amount ?? 0);
    $remaining = max(0, (float) $order->total - $paidOnline);
    $isPartialPay = $paymentMethod === 'cod' && $paidOnline > 0 && $remaining > 0;
    $isFullyPaid = $order->payment_status === 'paid' && $remaining <= 0;
    $shipping = $order->shipping_address_snapshot;
    $trackUrl = $order->user_id ? url('/account/orders') : url('/');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Confirmed - {{ $order->order_number }}</title>
</head>
<body style="margin:0;padding:0;background:#f6f6f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;-webkit-font-smoothing:antialiased;">

{{-- Wrapper --}}
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f6f6f6;padding:24px 0;">
<tr><td align="center">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.06);">

{{-- Logo Header --}}
<tr><td style="padding:24px 32px;text-align:center;border-bottom:1px solid #f0f0f0;">
    <a href="{{ url('/') }}" style="text-decoration:none;">
        <img src="{{ url('/' . \App\Models\Setting::get('store_logo', 'images/logo.png')) }}" alt="{{ \App\Models\Setting::get('store_name', config('app.name')) }}" style="height:36px;display:inline-block;">
    </a>
</td></tr>

{{-- Success Banner --}}
<tr><td style="padding:32px 32px 24px;text-align:center;">
    <div style="width:56px;height:56px;background:#ecfdf5;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <span style="font-size:28px;color:#059669;">&#10003;</span>
    </div>
    @if($isAdmin)
        <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0 0 6px;">{{ __('New Order Received!') }}</h1>
        <p style="font-size:14px;color:#6b7280;margin:0;">{{ __('A new order has been placed on your store.') }}</p>
    @else
        <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0 0 6px;">{{ __('Thank you, :name!', ['name' => $customerName]) }}</h1>
        <p style="font-size:14px;color:#6b7280;margin:0;">{{ __('Your order has been confirmed and is being processed.') }}</p>
    @endif
</td></tr>

{{-- Customer Details (Admin only) --}}
@if($isAdmin)
<tr><td style="padding:0 32px 24px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#eff6ff;border-radius:10px;border:1px solid #bfdbfe;">
    <tr><td style="padding:14px 20px;">
        <p style="font-size:13px;font-weight:700;color:#1e40af;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 8px;">{{ __('Customer Details') }}</p>
        <p style="font-size:14px;color:#1e3a5f;margin:0 0 4px;"><strong>{{ __('Name') }}:</strong> {{ $order->user?->name ?? $order->guest_name ?? '—' }}</p>
        <p style="font-size:14px;color:#1e3a5f;margin:0 0 4px;"><strong>{{ __('Email') }}:</strong> {{ $order->user?->email ?? $order->guest_email ?? '—' }}</p>
        <p style="font-size:14px;color:#1e3a5f;margin:0;"><strong>{{ __('Phone') }}:</strong> {{ $order->user?->phone ?? $order->guest_phone ?? '—' }}</p>
    </td></tr>
    </table>
</td></tr>
@endif

{{-- Order Info Cards --}}
<tr><td style="padding:0 32px 24px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border-radius:10px;border:1px solid #f3f4f6;">
    <tr>
        <td style="padding:16px 20px;width:50%;border-right:1px solid #f3f4f6;">
            <p style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 4px;">Order Number</p>
            <p style="font-size:15px;font-weight:700;color:#111827;margin:0;">{{ $order->order_number }}</p>
        </td>
        <td style="padding:16px 20px;width:50%;">
            <p style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 4px;">Order Date</p>
            <p style="font-size:15px;font-weight:600;color:#111827;margin:0;">{{ $order->created_at->format('d M Y, h:i A') }}</p>
        </td>
    </tr>
    <tr>
        <td style="padding:16px 20px;width:50%;border-right:1px solid #f3f4f6;border-top:1px solid #f3f4f6;">
            <p style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 4px;">Payment</p>
            @if($isPartialPay)
                <p style="font-size:14px;font-weight:600;color:#111827;margin:0;">Partial Pay</p>
                <p style="font-size:12px;color:#059669;margin:2px 0 0;">Paid: {{ format_price($paidOnline) }}</p>
                <p style="font-size:12px;color:#d97706;margin:2px 0 0;">Due on delivery: {{ format_price($remaining) }}</p>
            @elseif($isFullyPaid)
                <p style="font-size:14px;font-weight:600;color:#059669;margin:0;">Paid Online</p>
                <p style="font-size:12px;color:#6b7280;margin:2px 0 0;">{{ format_price($order->total) }}</p>
            @else
                <p style="font-size:14px;font-weight:600;color:#d97706;margin:0;">Pay on Delivery</p>
                <p style="font-size:12px;color:#6b7280;margin:2px 0 0;">Pay {{ format_price($order->total) }} on delivery</p>
            @endif
        </td>
        <td style="padding:16px 20px;width:50%;border-top:1px solid #f3f4f6;">
            <p style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 4px;">Total</p>
            <p style="font-size:22px;font-weight:700;color:#111827;margin:0;">{{ format_price($order->total) }}</p>
        </td>
    </tr>
    </table>
</td></tr>

{{-- Items --}}
<tr><td style="padding:0 32px 24px;">
    <p style="font-size:13px;font-weight:700;color:#111827;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 12px;padding-bottom:8px;border-bottom:2px solid #111827;">Items Ordered</p>

    @foreach($order->items as $item)
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;{{ !$loop->last ? 'padding-bottom:12px;border-bottom:1px solid #f3f4f6;' : '' }}">
    <tr>
        {{-- Product Image --}}
        <td style="vertical-align:top;width:64px;padding-right:14px;">
            @if($item->product && $item->product->primary_image_url)
                {{-- Emails need an absolute URL; the accessor returns a root-relative /storage/... path --}}
                @php($imgSrc = \Illuminate\Support\Str::startsWith($item->product->primary_image_url, 'http') ? $item->product->primary_image_url : url($item->product->primary_image_url))
                <img src="{{ $imgSrc }}" alt="{{ $item->product_name }}"
                     style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid #f3f4f6;display:block;">
            @else
                <div style="width:64px;height:64px;background:#f3f4f6;border-radius:8px;text-align:center;line-height:64px;font-size:24px;color:#d1d5db;">&#128722;</div>
            @endif
        </td>
        {{-- Product Details --}}
        <td style="vertical-align:top;">
            <p style="margin:0;font-size:14px;font-weight:600;color:#111827;line-height:1.4;">{{ $item->product_name }}</p>
            @if($item->variant_name)
                <p style="margin:3px 0 0;font-size:12px;color:#9ca3af;">{{ $item->variant_name }}</p>
            @endif
            <p style="margin:3px 0 0;font-size:12px;color:#9ca3af;">Qty: {{ $item->quantity }}</p>
        </td>
        {{-- Price --}}
        <td style="vertical-align:top;text-align:right;white-space:nowrap;">
            <p style="margin:0;font-size:15px;font-weight:700;color:#111827;">{{ format_price($item->total) }}</p>
            @if($item->quantity > 1)
                <p style="margin:2px 0 0;font-size:11px;color:#9ca3af;">{{ format_price($item->price) }} each</p>
            @endif
        </td>
    </tr>
    </table>
    @endforeach
</td></tr>

{{-- Price Breakdown --}}
<tr><td style="padding:0 32px 24px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border-radius:10px;border:1px solid #f3f4f6;">
        <tr>
            <td style="padding:10px 20px;font-size:14px;color:#6b7280;">Subtotal</td>
            <td style="padding:10px 20px;text-align:right;font-size:14px;color:#374151;">{{ format_price($order->subtotal) }}</td>
        </tr>
        @if($order->discount > 0)
        <tr>
            <td style="padding:4px 20px;font-size:14px;color:#059669;">Discount</td>
            <td style="padding:4px 20px;text-align:right;font-size:14px;color:#059669;font-weight:600;">-{{ format_price($order->discount) }}</td>
        </tr>
        @endif
        @if($order->tax > 0)
        <tr>
            <td style="padding:4px 20px;font-size:14px;color:#6b7280;">Tax (incl.)</td>
            <td style="padding:4px 20px;text-align:right;font-size:14px;color:#374151;">{{ format_price($order->tax) }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding:4px 20px;font-size:14px;color:#6b7280;">Shipping</td>
            <td style="padding:4px 20px;text-align:right;font-size:14px;color:{{ $order->shipping_cost > 0 ? '#374151' : '#059669' }};font-weight:{{ $order->shipping_cost > 0 ? '400' : '600' }};">
                {{ $order->shipping_cost > 0 ? format_price($order->shipping_cost) : 'FREE' }}
            </td>
        </tr>
        <tr><td colspan="2" style="padding:0 20px;"><div style="border-top:1px solid #e5e7eb;"></div></td></tr>
        <tr>
            <td style="padding:12px 20px;font-size:16px;font-weight:700;color:#111827;">Total</td>
            <td style="padding:12px 20px;text-align:right;font-size:18px;font-weight:700;color:#111827;">{{ format_price($order->total) }}</td>
        </tr>
        @if($isPartialPay)
        <tr><td colspan="2" style="padding:0 20px;"><div style="border-top:1px solid #e5e7eb;"></div></td></tr>
        <tr>
            <td style="padding:8px 20px;font-size:13px;color:#059669;">Paid Online</td>
            <td style="padding:8px 20px;text-align:right;font-size:13px;color:#059669;font-weight:600;">{{ format_price($paidOnline) }}</td>
        </tr>
        <tr>
            <td style="padding:4px 20px 10px;font-size:13px;color:#d97706;font-weight:600;">Due on Delivery</td>
            <td style="padding:4px 20px 10px;text-align:right;font-size:13px;color:#d97706;font-weight:600;">{{ format_price($remaining) }}</td>
        </tr>
        @endif
    </table>
</td></tr>

{{-- Delivery Address --}}
@if($shipping)
<tr><td style="padding:0 32px 24px;">
    <p style="font-size:13px;font-weight:700;color:#111827;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 10px;padding-bottom:8px;border-bottom:2px solid #111827;">Delivery Address</p>
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border-radius:10px;border:1px solid #f3f4f6;">
    <tr><td style="padding:14px 20px;font-size:14px;color:#374151;line-height:1.6;">
        <strong style="color:#111827;">{{ $shipping['name'] ?? '' }}</strong><br>
        {{ $shipping['address_line_1'] ?? '' }}@if(!empty($shipping['address_line_2'])), {{ $shipping['address_line_2'] }}@endif<br>
        {{ $shipping['city'] ?? '' }}, {{ $shipping['state'] ?? '' }} {{ $shipping['postal_code'] ?? '' }}<br>
        @if(!empty($shipping['phone']))<span style="color:#6b7280;">{{ $shipping['phone'] }}</span>@endif
    </td></tr>
    </table>
</td></tr>
@endif

{{-- CTA Button --}}
<tr><td style="padding:0 32px 32px;text-align:center;">
    @if($isAdmin)
        <a href="{{ url('/admin/orders/' . $order->id) }}" style="background:#111827;color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:8px;font-size:14px;font-weight:600;display:inline-block;letter-spacing:0.3px;">
            {{ __('View Order in Admin') }}
        </a>
    @else
        <a href="{{ $trackUrl }}" style="background:#111827;color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:8px;font-size:14px;font-weight:600;display:inline-block;letter-spacing:0.3px;">
            {{ __('Continue Shopping') }}
        </a>
    @endif
</td></tr>

{{-- Savings Badge --}}
@if($order->discount > 0)
<tr><td style="padding:0 32px 24px;">
    <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;padding:12px 20px;text-align:center;">
        <p style="font-size:14px;font-weight:600;color:#065f46;margin:0;">You saved {{ format_price($order->discount) }} on this order!</p>
    </div>
</td></tr>
@endif

{{-- Help (customer only) --}}
@if(!$isAdmin)
<tr><td style="padding:0 32px 24px;">
    <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:12px 20px;text-align:center;">
        <p style="font-size:13px;color:#92400e;margin:0;">{{ __('Need help?') }} {{ __('WhatsApp us at') }} <strong>{{ \App\Models\Setting::get('contact_phone', '') }}</strong> {{ __('or reply to this email') }}</p>
    </div>
</td></tr>
@endif

{{-- Footer --}}
<tr><td style="background:#111827;padding:20px 32px;text-align:center;border-radius:0 0 12px 12px;">
    <table width="100%" cellpadding="0" cellspacing="0"><tr>
        <td style="text-align:center;">
            <a href="{{ url('/') }}" style="color:#f59e0b;font-size:12px;text-decoration:none;font-weight:500;margin:0 10px;">Shop</a>
            <a href="{{ url('/collections') }}" style="color:#f59e0b;font-size:12px;text-decoration:none;font-weight:500;margin:0 10px;">Collections</a>
            <a href="{{ url('/contact') }}" style="color:#f59e0b;font-size:12px;text-decoration:none;font-weight:500;margin:0 10px;">Contact</a>
        </td>
    </tr></table>
    <p style="color:#6b7280;font-size:11px;margin:12px 0 0;">&copy; {{ date('Y') }} {{ \App\Models\Setting::get('store_name', config('app.name')) }} ({{ \App\Models\Setting::get('legal_name', config('app.name')) }}). All rights reserved.</p>
    <p style="color:#4b5563;font-size:10px;margin:4px 0 0;">{{ url('/') }}</p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
