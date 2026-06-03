<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8f8f8; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e5e5;">
        {{-- Header --}}
        <div style="background: #1a1a1a; color: #fff; padding: 24px; text-align: center;">
            <h1 style="margin: 0; font-size: 20px; font-weight: 600;">Invoice</h1>
            <p style="margin: 4px 0 0; font-size: 14px; opacity: 0.8;">Draft Order #D{{ $draftOrder->id }}</p>
        </div>

        <div style="padding: 24px;">
            @if($draftOrder->customer_name)
                <p style="font-size: 15px; color: #333; margin: 0 0 16px;">
                    Hi {{ $draftOrder->customer_name }},
                </p>
            @endif

            <p style="font-size: 14px; color: #666; margin: 0 0 24px; line-height: 1.5;">
                Please find below the details of your order. Click the button below to complete your payment.
            </p>

            {{-- Items --}}
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e5e5;">
                        <th style="text-align: left; padding: 8px 0; font-size: 12px; color: #999; text-transform: uppercase;">Item</th>
                        <th style="text-align: center; padding: 8px 0; font-size: 12px; color: #999; text-transform: uppercase;">Qty</th>
                        <th style="text-align: right; padding: 8px 0; font-size: 12px; color: #999; text-transform: uppercase;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($draftOrder->items ?? [] as $item)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 10px 0; font-size: 14px; color: #333;">{{ $item['product_name'] ?? 'Product' }}</td>
                            <td style="padding: 10px 0; font-size: 14px; color: #666; text-align: center;">{{ $item['quantity'] }}</td>
                            <td style="padding: 10px 0; font-size: 14px; color: #333; text-align: right;">{{ number_format($item['price'] * $item['quantity'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Totals --}}
            <div style="border-top: 2px solid #e5e5e5; padding-top: 12px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 4px 0; font-size: 14px; color: #666;">Subtotal</td>
                        <td style="padding: 4px 0; font-size: 14px; color: #333; text-align: right;">{{ number_format($draftOrder->subtotal, 2) }}</td>
                    </tr>
                    @if($draftOrder->discount > 0)
                        <tr>
                            <td style="padding: 4px 0; font-size: 14px; color: #666;">Discount</td>
                            <td style="padding: 4px 0; font-size: 14px; color: #16a34a; text-align: right;">-{{ number_format($draftOrder->discount, 2) }}</td>
                        </tr>
                    @endif
                    @if($draftOrder->shipping_cost > 0)
                        <tr>
                            <td style="padding: 4px 0; font-size: 14px; color: #666;">Shipping</td>
                            <td style="padding: 4px 0; font-size: 14px; color: #333; text-align: right;">{{ number_format($draftOrder->shipping_cost, 2) }}</td>
                        </tr>
                    @endif
                    @if($draftOrder->tax > 0)
                        <tr>
                            <td style="padding: 4px 0; font-size: 14px; color: #666;">Tax</td>
                            <td style="padding: 4px 0; font-size: 14px; color: #333; text-align: right;">{{ number_format($draftOrder->tax, 2) }}</td>
                        </tr>
                    @endif
                    <tr style="border-top: 1px solid #e5e5e5;">
                        <td style="padding: 10px 0 4px; font-size: 16px; font-weight: 600; color: #111;">Total</td>
                        <td style="padding: 10px 0 4px; font-size: 16px; font-weight: 600; color: #111; text-align: right;">{{ number_format($draftOrder->total, 2) }}</td>
                    </tr>
                </table>
            </div>

            @if($draftOrder->payment_link)
                <div style="text-align: center; margin: 28px 0 16px;">
                    <a href="{{ $draftOrder->payment_link }}" style="display: inline-block; background: #1a1a1a; color: #fff; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-size: 15px; font-weight: 600;">
                        Pay Now
                    </a>
                </div>
            @endif

            @if($draftOrder->notes)
                <div style="margin-top: 20px; padding: 12px; background: #f8f8f8; border-radius: 6px;">
                    <p style="margin: 0; font-size: 12px; color: #999; text-transform: uppercase; margin-bottom: 4px;">Notes</p>
                    <p style="margin: 0; font-size: 13px; color: #666;">{{ $draftOrder->notes }}</p>
                </div>
            @endif
        </div>

        <div style="padding: 16px 24px; background: #f8f8f8; text-align: center; border-top: 1px solid #e5e5e5;">
            <p style="margin: 0; font-size: 12px; color: #999;">
                {{ \App\Models\Setting::get('store_name', config('app.name')) }}
            </p>
        </div>
    </div>
</body>
</html>
