@php
    $tplSubject   = \App\Models\Setting::get('abandoned_cart_r2_subject', "Still thinking? Here's 5% off to help you decide");
    $tplPreheader = \App\Models\Setting::get('abandoned_cart_r2_preheader', 'We saved your cart — and added a treat');
    $tplHeading   = \App\Models\Setting::get('abandoned_cart_r2_heading', 'Still thinking about it?');
    $tplBodyRaw   = \App\Models\Setting::get('abandoned_cart_r2_body', "Hi {name},\n\nYour items are still in your cart. To make it easier, here's a special discount just for you.");
    $tplCta       = \App\Models\Setting::get('abandoned_cart_r2_cta', 'Claim {discount}% Off Now');

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

<div style="background:#F0F8F8;border:2px dashed {{ \App\Models\Setting::get('primary_color', '') ?: '#334155' }};border-radius:8px;padding:16px;text-align:center;margin:20px 0;">
<p style="margin:0 0 4px;font-size:12px;color:#888;">EXCLUSIVE OFFER - JUST FOR YOU</p>
<p style="margin:0;font-size:28px;font-weight:bold;color:{{ \App\Models\Setting::get('primary_color', '') ?: '#334155' }};">{{ $discountPct }}% OFF</p>
<p style="margin:4px 0;font-size:16px;font-weight:bold;color:#111;">Code: {{ $discountCode }}</p>
<p style="margin:4px 0 0;font-size:11px;color:#CC0C39;">Valid for 24 hours only!</p>
</div>

@if(!empty($cartSnapshot))
<div style="margin:16px 0;border:1px solid #eee;border-radius:6px;overflow:hidden;">
    <div style="background:#f9f9f9;padding:10px 14px;border-bottom:1px solid #eee;">
        <p style="margin:0;font-size:12px;font-weight:bold;color:#333;text-transform:uppercase;">Your Cart</p>
    </div>
    @foreach(array_slice($cartSnapshot, 0, 3) as $item)
    <div style="padding:10px 14px;border-bottom:1px solid #f0f0f0;">
        <span style="font-size:13px;color:#333;">{{ $item['name'] ?? 'Product' }}</span>
        <span style="font-size:13px;color:#888;float:right;">x{{ $item['quantity'] ?? 1 }}</span>
    </div>
    @endforeach
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
