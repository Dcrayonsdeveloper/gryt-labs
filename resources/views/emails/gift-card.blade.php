<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>You received a gift card!</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; padding: 40px 20px;">
    <div style="max-width: 500px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <div style="background: #111; color: #fff; padding: 30px; text-align: center;">
            <h1 style="margin: 0; font-size: 24px;">Gift Card</h1>
            <p style="margin: 8px 0 0; opacity: 0.8; font-size: 14px;">You have received a gift!</p>
        </div>
        <div style="padding: 30px;">
            <p style="font-size: 16px; color: #333;">Hi {{ $giftCard->recipient_name }},</p>

            @if($giftCard->message)
                <div style="background: #f9f9f9; border-left: 3px solid #ddd; padding: 12px 16px; margin: 20px 0; font-style: italic; color: #555;">
                    "{{ $giftCard->message }}"
                </div>
            @endif

            <div style="text-align: center; margin: 30px 0;">
                <p style="font-size: 14px; color: #666; margin: 0;">Your gift card code:</p>
                <p style="font-size: 28px; font-weight: bold; font-family: monospace; letter-spacing: 2px; color: #111; margin: 10px 0;">
                    {{ $giftCard->code }}
                </p>
                <p style="font-size: 14px; color: #666; margin: 0;">Value: <strong>{{ $giftCard->currency }} {{ number_format($giftCard->initial_balance, 2) }}</strong></p>
                @if($giftCard->expires_at)
                    <p style="font-size: 12px; color: #999; margin: 8px 0 0;">Expires: {{ $giftCard->expires_at->format('M d, Y') }}</p>
                @endif
            </div>

            <p style="font-size: 14px; color: #666;">Enter this code at checkout to apply the balance to your order.</p>
        </div>
    </div>
</body>
</html>
