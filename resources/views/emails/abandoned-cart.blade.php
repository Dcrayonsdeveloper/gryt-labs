<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:16px 0;">
<tr><td align="center">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fff;border-radius:8px;overflow:hidden;">

<tr><td style="background:#fff;padding:16px 20px;text-align:center;border-bottom:3px solid var(--brand-700);">
<img src="{{ url('/' . \App\Models\Setting::get('store_logo', 'images/logo.png')) }}" alt="{{ \App\Models\Setting::get('store_name', config('app.name')) }}" style="height:32px;">
</td></tr>

<tr><td style="background:{{ \App\Models\Setting::get('primary_color', '') ?: '#334155' }};padding:20px;text-align:center;">
<h1 style="color:#fff;font-size:20px;margin:0 0 4px;">You forgot something!</h1>
<p style="color:rgba(255,255,255,0.7);font-size:13px;margin:0;">Your cart is waiting for you</p>
</td></tr>

<tr><td style="padding:20px;">
<p style="font-size:14px;color:#333;">Hi <strong>{{ $name }}</strong>,</p>
<p style="font-size:14px;color:#555;">We noticed you left items in your cart. No worries, we saved them for you!</p>

@php $abandonedPct = \App\Models\Setting::get('abandoned_cart_discount_pct', 5); @endphp
<div style="background:#F0F8F8;border:2px dashed {{ \App\Models\Setting::get('primary_color', '') ?: '#334155' }};border-radius:8px;padding:16px;text-align:center;margin:20px 0;">
<p style="margin:0 0 4px;font-size:12px;color:#888;">EXCLUSIVE OFFER - JUST FOR YOU</p>
<p style="margin:0;font-size:28px;font-weight:bold;color:{{ \App\Models\Setting::get('primary_color', '') ?: '#334155' }};">{{ $abandonedPct }}% OFF</p>
<p style="margin:4px 0;font-size:16px;font-weight:bold;color:#111;">Code: {{ $discountCode }}</p>
<p style="margin:4px 0 0;font-size:11px;color:#CC0C39;">Valid for 1 hour only!</p>
</div>

<div style="text-align:center;margin:24px 0;">
<a href="{{ $cartUrl }}" style="background:var(--brand-accent);color:#fff;text-decoration:none;padding:14px 40px;border-radius:6px;font-size:15px;font-weight:600;display:inline-block;">Complete Your Order</a>
</div>

<p style="font-size:11px;color:#999;text-align:center;">Need help? Reply to this email or WhatsApp {{ \App\Models\Setting::get('contact_phone', '') }}</p>
</td></tr>

<tr><td style="background:var(--brand-700);padding:12px 20px;text-align:center;">
<p style="color:#7a9a9e;font-size:10px;margin:0;">&copy; {{ date('Y') }} {{ \App\Models\Setting::get('store_name', config('app.name')) }}. All rights reserved.</p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
