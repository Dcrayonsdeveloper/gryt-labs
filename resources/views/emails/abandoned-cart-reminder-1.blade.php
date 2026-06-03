@php
    $tplSubject   = \App\Models\Setting::get('abandoned_cart_r1_subject', 'You left something behind!');
    $tplPreheader = \App\Models\Setting::get('abandoned_cart_r1_preheader', 'Your cart is waiting for you');
    $tplHeading   = \App\Models\Setting::get('abandoned_cart_r1_heading', 'You left something behind!');
    $tplBodyRaw   = \App\Models\Setting::get('abandoned_cart_r1_body', "Hi {name},\n\nWe noticed you didn't finish checking out. No worries — your items are still saved and ready for you!");
    $tplCta       = \App\Models\Setting::get('abandoned_cart_r1_cta', 'Complete Your Order');

    // Replace merge tokens
    $discountPctVal = $discountPct ?? 0;
    $discountCodeVal = $discountCode ?? '';
    $bodyRendered = strtr($tplBodyRaw, [
        '{name}'     => $name ?? 'there',
        '{discount}' => $discountPctVal,
        '{code}'     => $discountCodeVal,
    ]);
    $ctaRendered = strtr($tplCta, [
        '{discount}' => $discountPctVal,
        '{code}'     => $discountCodeVal,
    ]);
    $paragraphs = array_filter(array_map('trim', preg_split("/\n\s*\n/", $bodyRendered)));
@endphp
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>{{ $tplSubject }}</title></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
{{-- Preheader (hidden preview text shown next to subject in inbox) --}}
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#f4f4f4;">{{ $tplPreheader }}</div>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:16px 0;">
<tr><td align="center">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fff;border-radius:8px;overflow:hidden;">

<tr><td style="background:#fff;padding:16px 20px;text-align:center;border-bottom:3px solid var(--brand-700);">
<img src="{{ url('/' . \App\Models\Setting::get('store_logo', 'images/logo.png')) }}" alt="{{ \App\Models\Setting::get('store_name', config('app.name')) }}" style="height:32px;">
</td></tr>

<tr><td style="background:{{ \App\Models\Setting::get('primary_color', '') ?: '#334155' }};padding:20px;text-align:center;">
<h1 style="color:#fff;font-size:20px;margin:0 0 4px;">{{ $tplHeading }}</h1>
<p style="color:rgba(255,255,255,0.7);font-size:13px;margin:0;">{{ $tplPreheader }}</p>
</td></tr>

<tr><td style="padding:20px;">
@foreach($paragraphs as $para)
<p style="font-size:14px;color:#555;margin:0 0 12px;">{!! nl2br(e($para)) !!}</p>
@endforeach

@if(!empty($cartSnapshot))
<div style="margin:16px 0;border:1px solid #eee;border-radius:6px;overflow:hidden;">
    <div style="background:#f9f9f9;padding:10px 14px;border-bottom:1px solid #eee;">
        <p style="margin:0;font-size:12px;font-weight:bold;color:#333;text-transform:uppercase;">Your Cart</p>
    </div>
    @foreach(array_slice($cartSnapshot, 0, 3) as $item)
    <div style="padding:10px 14px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;">
        <span style="font-size:13px;color:#333;">{{ $item['name'] ?? 'Product' }}</span>
        <span style="font-size:13px;color:#888;margin-left:auto;">x{{ $item['quantity'] ?? 1 }}</span>
    </div>
    @endforeach
    @if(count($cartSnapshot) > 3)
    <div style="padding:8px 14px;background:#f9f9f9;">
        <p style="margin:0;font-size:11px;color:#888;">+ {{ count($cartSnapshot) - 3 }} more item(s)</p>
    </div>
    @endif
</div>
@endif

<div style="text-align:center;margin:24px 0;">
<a href="{{ $cartUrl }}" style="background:{{ \App\Models\Setting::get('primary_color', '') ?: '#334155' }};color:#fff;text-decoration:none;padding:14px 40px;border-radius:6px;font-size:15px;font-weight:600;display:inline-block;">{{ $ctaRendered }}</a>
</div>

<p style="font-size:12px;color:#888;text-align:center;">Your cart total: <strong style="color:#333;">{{ config('app.currency_symbol', '') }}{{ number_format($cartTotal, 0) }}</strong></p>

<p style="font-size:11px;color:#999;text-align:center;margin-top:16px;">Need help? Reply to this email or WhatsApp {{ \App\Models\Setting::get('contact_phone', '') }}</p>
</td></tr>

<tr><td style="background:var(--brand-700, #1a3f44);padding:12px 20px;text-align:center;">
<p style="color:#7a9a9e;font-size:10px;margin:0;">&copy; {{ date('Y') }} {{ \App\Models\Setting::get('store_name', config('app.name')) }}. All rights reserved.</p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
