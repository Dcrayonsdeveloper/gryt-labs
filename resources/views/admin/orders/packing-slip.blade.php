<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packing Slip - {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; color: #333; line-height: 1.5; }
        .slip { max-width: 800px; margin: 0 auto; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .header h1 { font-size: 24px; font-weight: 700; }
        .header .order-info { text-align: right; }
        .header .order-info p { margin: 2px 0; font-size: 13px; color: #666; }
        .header .order-info .order-number { font-size: 15px; font-weight: 600; color: #333; }
        .ship-to { margin-bottom: 30px; }
        .ship-to h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #999; margin-bottom: 8px; font-weight: 600; }
        .ship-to p { margin: 2px 0; font-size: 13px; }
        .ship-to .name { font-weight: 600; font-size: 15px; }
        .ship-from { margin-bottom: 18px; }
        .ship-from h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #999; margin-bottom: 8px; font-weight: 600; }
        .ship-from p { margin: 2px 0; font-size: 13px; }
        .ship-from .name { font-weight: 600; font-size: 15px; }
        .amount-box { margin-top: 10px; border: 1px solid #ddd; border-radius: 4px; padding: 8px 10px; display: inline-block; text-align: left; min-width: 190px; }
        .amount-box .row { display: flex; justify-content: space-between; gap: 18px; font-size: 12px; margin: 3px 0; }
        .amount-box .row span { color: #666; }
        .amount-box .row strong { color: #333; }
        .amount-box .row.collect { border-top: 1px solid #eee; margin-top: 5px; padding-top: 5px; }
        .amount-box .row.collect strong { color: #c0392b; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        thead th { background: #f5f5f5; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #666; font-weight: 600; border-bottom: 2px solid #ddd; }
        thead th.text-center { text-align: center; }
        tbody td { padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 13px; }
        tbody td.text-center { text-align: center; }
        .product-name { font-weight: 500; }
        .product-sku { font-size: 11px; color: #999; }
        .check-col { width: 40px; text-align: center; }
        .checkbox { width: 16px; height: 16px; border: 2px solid #999; display: inline-block; border-radius: 3px; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; }
        .footer .signature { margin-top: 40px; display: flex; justify-content: space-between; }
        .footer .signature .line { width: 200px; border-top: 1px solid #999; padding-top: 5px; font-size: 12px; color: #666; text-align: center; }
        @media print {
            body { font-size: 12px; }
            .slip { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; padding: 15px; background: #f5f5f5; border-bottom: 1px solid #ddd;">
        <button onclick="window.print()" style="padding: 8px 24px; background: #333; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">
            Print Packing Slip
        </button>
    </div>

    <div class="slip">
        @php
            $curSym         = config('app.currency_symbol', '₹');
            $amtTotal       = (float) $order->total;
            $amtPaid        = (float) ($order->paid_amount ?? 0);
            $amtCollectable = max(0, $amtTotal - $amtPaid);
            // Drive the label from actual money flow, not just payment_status:
            // if there's nothing left to collect on delivery the order IS
            // prepaid from the driver's perspective, regardless of any backend
            // status drift. The 0.01 tolerance absorbs floating-point rounding.
            $payType        = ($order->payment_status === 'paid' || $amtCollectable <= 0.01)
                ? 'Prepaid'
                : ($amtPaid > 0 ? 'Partial COD' : 'COD');
        @endphp
        <div class="header">
            <div>
                <h1>PACKING SLIP</h1>
                <p style="color: #666; font-size: 13px;">{{ config('app.name') }}</p>
            </div>
            <div class="order-info">
                <p class="order-number">{{ $order->order_number }}</p>
                <p>Date: {{ $order->created_at->format('M d, Y') }}</p>
                <p>Items: {{ $order->items->sum('quantity') }}</p>
                <div class="amount-box">
                    <div class="row"><span>Payment Type</span><strong>{{ $payType }}</strong></div>
                    <div class="row"><span>Total Amount</span><strong>{{ $curSym }}{{ number_format($amtTotal, 2) }}</strong></div>
                    <div class="row"><span>Paid Amount</span><strong>{{ $curSym }}{{ number_format($amtPaid, 2) }}</strong></div>
                    <div class="row collect"><span>Collectable</span><strong>{{ $curSym }}{{ number_format($amtCollectable, 2) }}</strong></div>
                </div>
            </div>
        </div>

        <div class="ship-from">
            <h3>From</h3>
            @php
                $fromName  = \App\Models\Setting::get('ship_from_name', '')    ?: \App\Models\Setting::get('store_name', config('app.name'));
                $fromAddr  = \App\Models\Setting::get('ship_from_address', '') ?: \App\Models\Setting::get('store_address', '');
                $fromPhone = \App\Models\Setting::get('ship_from_phone', '')   ?: \App\Models\Setting::get('store_phone', '');
            @endphp
            <p class="name">{{ $fromName }}</p>
            @if($fromAddr)<p>{{ $fromAddr }}</p>@endif
            @if($fromPhone)<p>Phone: {{ $fromPhone }}</p>@endif
        </div>

        <div class="ship-to">
            <h3>Ship To</h3>
            @php $shipping = $order->shipping_address_snapshot; @endphp
            @if($shipping)
                <p class="name">{{ $shipping['name'] ?? ($shipping['first_name'] ?? '') . ' ' . ($shipping['last_name'] ?? '') }}</p>
                @if(!empty($shipping['address'])) <p>{{ $shipping['address'] }}</p> @endif
                @if(!empty($shipping['address_line_1'])) <p>{{ $shipping['address_line_1'] }}</p> @endif
                <p>{{ $shipping['city'] ?? '' }}{{ !empty($shipping['state']) ? ', ' . $shipping['state'] : '' }} {{ $shipping['postal_code'] ?? $shipping['zip'] ?? '' }}</p>
                @if(!empty($shipping['phone'])) <p>Phone: {{ $shipping['phone'] }}</p> @endif
            @endif
        </div>

        <table>
            <thead>
                <tr>
                    <th class="check-col"></th>
                    <th style="width: 45%;">Product</th>
                    <th>SKU</th>
                    <th class="text-center">Quantity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td class="text-center"><span class="checkbox"></span></td>
                        <td>
                            <div class="product-name">{{ $item->product_name }}</div>
                            @if($item->variant_name)
                                <div class="product-sku">{{ $item->variant_name }}</div>
                            @endif
                        </td>
                        <td class="product-sku">{{ $item->sku }}</td>
                        <td class="text-center" style="font-weight: 600; font-size: 15px;">{{ $item->quantity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer">
            @if($order->notes)
                <div style="margin-bottom: 20px; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                    <strong style="font-size: 11px; text-transform: uppercase; color: #999;">Order Notes</strong>
                    <p style="margin-top: 4px; font-size: 13px;">{{ $order->notes }}</p>
                </div>
            @endif

            <div class="signature">
                <div class="line">Packed By</div>
                <div class="line">Date</div>
                <div class="line">Checked By</div>
            </div>
        </div>
    </div>
</body>
</html>
