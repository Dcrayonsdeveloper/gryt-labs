<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt - {{ $sale->sale_number }}</title>
    <style>
        @page { margin: 5mm; size: 80mm auto; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            max-width: 300px;
            margin: 0 auto;
            padding: 10px;
            color: #000;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; }
        .item-row { margin: 3px 0; }
        .item-name { font-weight: bold; }
        .item-detail { font-size: 11px; color: #333; }
        .total-row { font-size: 14px; font-weight: bold; }
        .footer { font-size: 10px; color: #666; margin-top: 10px; text-align: center; }
        @media print {
            body { max-width: 100%; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    {{-- Store Header --}}
    <div class="center">
        <div class="bold" style="font-size: 16px;">{{ $sale->store->name ?? config('app.name') }}</div>
        @if($sale->store?->address)
        <div style="font-size: 10px; margin-top: 2px;">{{ $sale->store->address }}</div>
        @endif
        @if($sale->store?->gst_number)
        <div style="font-size: 10px;">GSTIN: {{ $sale->store->gst_number }}</div>
        @endif
    </div>

    <div class="divider"></div>

    {{-- Sale Info --}}
    <div class="row"><span>Bill #</span><span class="bold">{{ $sale->sale_number }}</span></div>
    <div class="row"><span>Date</span><span>{{ $sale->created_at->format('d/m/Y g:i A') }}</span></div>
    <div class="row"><span>Cashier</span><span>{{ $sale->staff?->user?->first_name ?? 'Staff' }}</span></div>
    @if($sale->customer)
    <div class="row"><span>Customer</span><span>{{ $sale->customer->first_name ?? $sale->customer->name }}</span></div>
    @endif

    <div class="divider"></div>

    {{-- Items --}}
    @foreach($sale->items as $item)
    <div class="item-row">
        <div class="item-name">{{ $item->product_name }}</div>
        <div class="row item-detail">
            <span>{{ $item->quantity }} × ₹{{ number_format($item->price, 2) }}</span>
            <span>₹{{ number_format($item->total, 2) }}</span>
        </div>
        @if($item->hsn_code)
        <div class="item-detail">HSN: {{ $item->hsn_code }} | GST: {{ $item->tax_rate }}%</div>
        @endif
    </div>
    @endforeach

    <div class="divider"></div>

    {{-- Totals --}}
    <div class="row"><span>Subtotal</span><span>₹{{ number_format($sale->subtotal, 2) }}</span></div>
    @if($sale->discount > 0)
    <div class="row"><span>Discount</span><span>-₹{{ number_format($sale->discount, 2) }}</span></div>
    @endif
    @if($sale->tax > 0)
    <div class="row"><span>GST</span><span>₹{{ number_format($sale->tax, 2) }}</span></div>
    @endif

    <div class="divider"></div>
    <div class="row total-row"><span>TOTAL</span><span>₹{{ number_format($sale->total, 2) }}</span></div>
    <div class="divider"></div>

    {{-- Payment --}}
    <div class="row"><span>Payment</span><span>{{ strtoupper($sale->payment_method) }}</span></div>
    <div class="row"><span>Paid</span><span>₹{{ number_format($sale->paid_amount, 2) }}</span></div>
    @if($sale->change_amount > 0)
    <div class="row bold"><span>Change</span><span>₹{{ number_format($sale->change_amount, 2) }}</span></div>
    @endif

    {{-- GST Summary --}}
    @php
        $gstItems = $sale->items->where('tax_rate', '>', 0);
    @endphp
    @if($gstItems->count() > 0)
    <div class="divider"></div>
    <div class="center bold" style="font-size: 10px; margin-bottom: 3px;">GST BREAKUP</div>
    @foreach($gstItems->groupBy('tax_rate') as $rate => $items)
    <div class="row" style="font-size: 10px;">
        <span>GST @ {{ $rate }}%</span>
        <span>CGST ₹{{ number_format($items->sum('cgst'), 2) }} + SGST ₹{{ number_format($items->sum('sgst'), 2) }}</span>
    </div>
    @endforeach
    @endif

    <div class="divider"></div>

    {{-- Footer --}}
    <div class="footer">
        <p>Thank you for shopping with us!</p>
        <p>Exchange/Return within {{ \App\Models\Setting::get('return_policy_days', 7) }} days with receipt.</p>
        <p style="margin-top: 4px;">{{ config('app.url') }}</p>
    </div>

    {{-- Print Button (screen only) --}}
    <div class="center no-print" style="margin-top: 20px;">
        <button onclick="window.print()" style="padding: 8px 24px; font-size: 14px; cursor: pointer; background: var(--color-primary-600); color: white; border: none; border-radius: 6px;">
            Print Receipt
        </button>
        <button onclick="window.close()" style="padding: 8px 24px; font-size: 14px; cursor: pointer; background: #E2E8F0; color: #333; border: none; border-radius: 6px; margin-left: 8px;">
            Close
        </button>
    </div>
</body>
</html>
