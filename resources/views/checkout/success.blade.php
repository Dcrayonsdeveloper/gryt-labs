<x-layouts.app>
    <x-slot name="title">Order Confirmed - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="robots" content="noindex, nofollow">
        @php
            $orderSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'Order',
                'orderNumber' => $order->order_number,
                'orderDate' => $order->created_at->toIso8601String(),
                'orderStatus' => 'https://schema.org/OrderProcessing',
                'priceCurrency' => \App\Models\Setting::get('currency', '') ?: config('app.currency', 'INR'),
                'price' => number_format($order->total, 2, '.', ''),
                'acceptedOffer' => $order->items->map(fn($item) => [
                    '@type' => 'Offer',
                    'itemOffered' => ['@type' => 'Product', 'name' => $item->product_name],
                    'price' => number_format($item->price, 2, '.', ''),
                    'priceCurrency' => \App\Models\Setting::get('currency', '') ?: config('app.currency', 'INR'),
                    'eligibleQuantity' => ['@type' => 'QuantitativeValue', 'value' => $item->quantity],
                ])->values()->toArray(),
                'seller' => ['@type' => 'Organization', 'name' => config('app.name')],
            ];
        @endphp
        <script type="application/ld+json">{!! json_encode($orderSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endpush

    @php
        $shipping = $order->shipping_address_snapshot;
        $billing = $order->billing_address_snapshot;
        $paymentMethod = $order->metadata['payment_method'] ?? 'cod';
        $customerName = $shipping['name'] ?? ($order->guest_name ?? 'there');
        $firstName = explode(' ', $customerName)[0];
        $contactEmail = auth()->check() ? auth()->user()->email : ($order->guest_email ?? '');
        $contactPhone = $shipping['phone'] ?? ($order->guest_phone ?? '');
        $addressLine = trim(($shipping['address_line_1'] ?? '') . ', ' . ($shipping['address_line_2'] ?? ''), ', ');
        $cityLine = trim(($shipping['postal_code'] ?? '') . ' ' . ($shipping['city'] ?? '') . ' ' . ($shipping['state'] ?? ''));
        $mapQuery = urlencode($addressLine . ', ' . $cityLine . ', India');
    @endphp

    <x-slot name="styles">
    <style>
        .success-wrap { width:100%;margin:0 auto;display:flex;min-height:80vh; }
        .success-left { flex:55%;min-width:0;padding:40px 40px 40px 40px;border-right:1px solid #e5e5e5; }
        .success-right { flex:45%;background:#faf8f3;padding:40px 40px 40px 40px;flex-shrink:0; }
        .success-details-grid { display:grid;grid-template-columns:1fr 1fr;gap:24px; }
        .success-actions { display:flex;gap:12px;flex-wrap:wrap; }
        .success-actions a { flex:1;text-align:center; }
        .success-map { height:220px; }
        @media (max-width: 768px) {
            .success-wrap { flex-direction:column-reverse; }
            .success-left { padding:24px 16px;border-right:none;border-top:1px solid #e5e5e5; }
            .success-right { width:100%;padding:24px 16px; }
            .success-details-grid { grid-template-columns:1fr;gap:16px; }
            .success-actions { flex-direction:column; }
            .success-actions a { flex:none;width:100%; }
            .success-map { height:180px; }
            .success-header-text h1 { font-size:20px !important; }
        }
    </style>
    </x-slot>

    <div style="min-height:100vh;background:#fff">
        <div class="success-wrap">

            {{-- LEFT COLUMN --}}
            <div class="success-left">

                {{-- Header --}}
                <div style="display:flex;align-items:flex-start;gap:16px;margin-bottom:28px">
                    <div style="width:50px;height:50px;border-radius:50%;border:2px solid #333;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <span style="font-size:24px;color:#333">&#10003;</span>
                    </div>
                    <div class="success-header-text">
                        <p style="font-size:13px;color:#737373;margin:0">Confirmation {{ $order->order_number }}</p>
                        <h1 style="font-size:24px;font-weight:400;color:#333;margin:4px 0 0 0">Thank you, {{ $firstName }}!</h1>
                    </div>
                </div>

                {{-- Map + Order Confirmed --}}
                <div style="border:1px solid #e5e5e5;border-radius:10px;overflow:hidden;margin-bottom:24px">
                    {{-- Map --}}
                    <div class="success-map" style="background:#e8f4e8;position:relative;overflow:hidden">
                        <iframe src="https://www.google.com/maps?q={{ $mapQuery }}&z=14&output=embed"
                                style="width:100%;height:100%;border:0;pointer-events:none" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        <div style="position:absolute;top:12px;left:50%;transform:translateX(-50%);background:#fff;padding:6px 16px;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,0.15);z-index:2">
                            <p style="font-size:11px;color:#737373;margin:0;text-align:center">Shipping address</p>
                            <p style="font-size:14px;font-weight:600;color:#333;margin:2px 0 0 0;text-align:center">{{ $shipping['city'] ?? '' }} {{ $shipping['state'] ?? '' }}</p>
                        </div>
                    </div>

                    {{-- Order Confirmed Message --}}
                    <div style="padding:20px">
                        <h2 style="font-size:18px;font-weight:600;color:#333;margin:0 0 4px 0">Your order is confirmed</h2>
                        <p style="font-size:14px;color:#737373;margin:0">You'll receive a confirmation email soon</p>
                    </div>
                </div>

                {{-- Order Details --}}
                <div style="border:1px solid #e5e5e5;border-radius:10px;padding:24px;margin-bottom:24px">
                    <h2 style="font-size:18px;font-weight:600;color:#333;margin:0 0 20px 0">Order details</h2>

                    <div class="success-details-grid">
                        {{-- Contact Information --}}
                        <div>
                            <h3 style="font-size:14px;font-weight:600;color:#333;margin:0 0 8px 0">Contact information</h3>
                            @if($contactEmail)
                                <p style="font-size:14px;color:#545454;margin:0">{{ $contactEmail }}</p>
                            @endif
                            @if($contactPhone)
                                <p style="font-size:14px;color:#545454;margin:2px 0 0 0">{{ $contactPhone }}</p>
                            @endif
                        </div>

                        {{-- Payment Method --}}
                        <div>
                            <h3 style="font-size:14px;font-weight:600;color:#333;margin:0 0 8px 0">Payment method</h3>
                            @php
                                $paidOnline = (float) ($order->paid_amount ?? 0);
                                $remaining = max(0, (float) $order->total - $paidOnline);
                                $isPartialPay = $paymentMethod === 'cod' && $paidOnline > 0 && $remaining > 0;
                            @endphp
                            @if($isPartialPay)
                                <div style="font-size:14px;color:#545454;line-height:1.8">
                                    <p style="margin:0;font-weight:500;color:#333">Partial Pay (&#8377;{{ \App\Models\Setting::get('cod_advance_amount', 100) }} advance + pay on delivery)</p>
                                    <p style="margin:0">Paid online: <span style="font-weight:600;color:#2e7d32">@price($paidOnline)</span></p>
                                    <p style="margin:0">Pay on delivery: <span style="font-weight:600;color:#333">@price($remaining)</span></p>
                                </div>
                            @elseif($paymentMethod === 'cod')
                                <div style="display:flex;align-items:center;gap:8px">
                                    <span style="width:38px;height:24px;background:#f0f0f0;border-radius:4px;border:1px solid #ddd;font-size:9px;display:flex;align-items:center;justify-content:center;color:#666;font-weight:700">POD</span>
                                    <span style="font-size:14px;color:#545454">Pay on Delivery &middot; @price($order->total) INR</span>
                                </div>
                            @else
                                <div style="display:flex;align-items:center;gap:8px">
                                    <span style="width:38px;height:24px;background:#1a1f71;border-radius:4px;display:flex;align-items:center;justify-content:center">
                                        <span style="color:#fff;font-size:9px;font-weight:700">PAID</span>
                                    </span>
                                    <span style="font-size:14px;color:#545454">Online Payment &middot; @price($order->total) INR</span>
                                </div>
                            @endif
                        </div>

                        {{-- Shipping Address --}}
                        <div>
                            <h3 style="font-size:14px;font-weight:600;color:#333;margin:0 0 8px 0">Shipping address</h3>
                            @if($shipping)
                                <div style="font-size:14px;color:#545454;line-height:1.6">
                                    <p style="margin:0">{{ $shipping['name'] ?? '' }}</p>
                                    @if(!empty($shipping['company'])) <p style="margin:0">{{ $shipping['company'] }}</p> @endif
                                    <p style="margin:0">{{ $shipping['address_line_1'] ?? '' }}</p>
                                    @if(!empty($shipping['address_line_2'])) <p style="margin:0">{{ $shipping['address_line_2'] }}</p> @endif
                                    <p style="margin:0">{{ $shipping['postal_code'] ?? '' }} {{ $shipping['city'] ?? '' }} {{ $shipping['state'] ?? '' }}</p>
                                    <p style="margin:0">India</p>
                                    @if(!empty($shipping['phone'])) <p style="margin:0">{{ $shipping['phone'] }}</p> @endif
                                </div>
                            @endif
                        </div>

                        {{-- Billing Address --}}
                        <div>
                            <h3 style="font-size:14px;font-weight:600;color:#333;margin:0 0 8px 0">Billing address</h3>
                            @php $bill = $billing ?: $shipping; @endphp
                            @if($bill)
                                <div style="font-size:14px;color:#545454;line-height:1.6">
                                    <p style="margin:0">{{ $bill['name'] ?? '' }}</p>
                                    @if(!empty($bill['company'])) <p style="margin:0">{{ $bill['company'] }}</p> @endif
                                    <p style="margin:0">{{ $bill['address_line_1'] ?? '' }}</p>
                                    @if(!empty($bill['address_line_2'])) <p style="margin:0">{{ $bill['address_line_2'] }}</p> @endif
                                    <p style="margin:0">{{ $bill['postal_code'] ?? '' }} {{ $bill['city'] ?? '' }} {{ $bill['state'] ?? '' }}</p>
                                    <p style="margin:0">India</p>
                                    @if(!empty($bill['phone'])) <p style="margin:0">{{ $bill['phone'] }}</p> @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="success-actions">
                    @if(auth()->check())
                        <a href="{{ route('account.orders.show', $order) }}"
                           style="display:inline-flex;align-items:center;gap:6px;padding:10px 24px;background:#333;color:#fff;font-size:14px;font-weight:500;border-radius:6px;text-decoration:none">
                            Track Order
                        </a>
                    @endif
                    <a href="{{ route('products.index') }}"
                       style="display:inline-flex;align-items:center;gap:6px;padding:10px 24px;{{ auth()->check() ? 'background:#fff;color:#333;border:1px solid #d9d9d9' : 'background:#333;color:#fff' }};font-size:14px;font-weight:500;border-radius:6px;text-decoration:none">
                        Continue shopping
                    </a>
                </div>

                {{-- Need Help --}}
                <p style="font-size:13px;color:#737373;margin-top:20px">
                    Need help? <a href="{{ route('contact') }}" style="color:var(--color-primary-600);text-decoration:underline">Contact us</a>
                </p>
            </div>

            {{-- RIGHT COLUMN (cream/yellow background) --}}
            <div class="success-right">

                {{-- Order Items --}}
                <div style="margin-bottom:24px">
                    @foreach($order->items as $item)
                        <div style="display:flex;gap:14px;margin-bottom:16px">
                            {{-- Product Image with Quantity Badge --}}
                            <div style="position:relative;flex-shrink:0">
                                <div style="width:64px;height:64px;border-radius:8px;border:1px solid #e5e5e5;overflow:hidden;background:#fff">
                                    @if($item->product && $item->product->primary_image_url)
                                        <img src="{{ $item->product->primary_image_url }}" alt="{{ $item->product_name }}"
                                             style="width:64px;height:64px;object-fit:cover;display:block"
                                             width="64" height="64" decoding="async">
                                    @else
                                        <div style="width:64px;height:64px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;color:#ccc;font-size:24px">&#128722;</div>
                                    @endif
                                </div>
                                @if($item->quantity > 0)
                                    <span style="position:absolute;top:-8px;right:-8px;background:#808080;color:#fff;font-size:11px;font-weight:600;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center">{{ $item->quantity }}</span>
                                @endif
                            </div>

                            {{-- Product Info --}}
                            <div style="flex:1;min-width:0">
                                <p style="font-size:14px;font-weight:500;color:#333;margin:0;line-height:1.4">{{ $item->product_name }}</p>
                                @if($item->variant_name)
                                    <p style="font-size:13px;color:#737373;margin:2px 0 0 0">{{ $item->variant_name }}</p>
                                @endif
                            </div>

                            {{-- Price --}}
                            <div style="flex-shrink:0;text-align:right">
                                <p style="font-size:14px;font-weight:500;color:#333;margin:0">@price($item->total)</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Divider --}}
                <div style="border-top:1px solid #e1ddd5;margin-bottom:16px"></div>

                {{-- Price Summary --}}
                <div style="margin-bottom:16px">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                        <span style="font-size:14px;color:#545454">Subtotal</span>
                        <span style="font-size:14px;font-weight:500;color:#333">@price($order->subtotal)</span>
                    </div>

                    @if($order->discount > 0)
                        <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                            <span style="font-size:14px;color:#545454">Discount</span>
                            <span style="font-size:14px;font-weight:500;color:#2e7d32">-@price($order->discount)</span>
                        </div>
                    @endif

                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span style="font-size:14px;color:#545454">Shipping & handling</span>
                        <span style="font-size:14px;font-weight:500;color:#333">
                            @if($order->shipping_cost > 0) @price($order->shipping_cost) @else <span style="color:#2e7d32">Free</span> @endif
                        </span>
                    </div>
                </div>

                {{-- Divider --}}
                <div style="border-top:1px solid #e1ddd5;margin-bottom:16px"></div>

                {{-- Total --}}
                <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px">
                    <span style="font-size:16px;font-weight:600;color:#333">Total</span>
                    <div style="text-align:right">
                        <span style="font-size:13px;color:#737373;margin-right:8px">INR</span>
                        <span style="font-size:22px;font-weight:600;color:#333">@price($order->total)</span>
                    </div>
                </div>

                @if($order->tax > 0)
                    <p style="font-size:13px;color:#737373;margin:0;text-align:right">Including @price($order->tax) in taxes</p>
                @endif
            </div>
        </div>
    </div>

    {{-- GA4 Purchase + FB Purchase tracking --}}
    @php
        $hasGa4 = $theme->get('google_analytics_id');
        $hasGads = $theme->get('google_ads_conversion_id', '');
        $hasFbPixel = $theme->get('facebook_pixel_id', '');
        $orderItemsArr = $order->items->map(fn($item) => [
            'item_id' => $item->sku ?? (string) $item->product_id,
            'item_name' => $item->product_name,
            'price' => (float) $item->price,
            'quantity' => $item->quantity,
        ])->values()->toArray();
        $fbContentIds = $order->items->pluck('product_id')->map('strval')->values()->toArray();
        $fbEventOpts = !empty($fbPurchaseEventId) ? ", {eventID: '" . $fbPurchaseEventId . "'}" : '';
    @endphp
    @if($hasGa4 || $hasFbPixel)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // sr_test_mode cookie suppresses all tracking for test orders
            if (document.cookie.indexOf('sr_test_mode=1') !== -1) return;

            var orderItems = {!! json_encode($orderItemsArr) !!};
            @if($hasGa4)
            gtag('event', 'purchase', {
                transaction_id: '{{ $order->order_number }}',
                value: {{ (float) $order->total }},
                tax: {{ (float) $order->tax }},
                shipping: {{ (float) $order->shipping_cost }},
                currency: '{{ \App\Models\Setting::get('currency', '') ?: config('app.currency', 'INR') }}',
                items: orderItems
            });
            @endif
            @if($hasGads)
            gtag('event', 'conversion', {
                'send_to': '{{ $hasGads }}/{!! $theme->get('google_ads_conversion_label', 'CrpaCKPLhZocENDj5ZdD') !!}',
                'value': {{ (float) $order->total }},
                'currency': '{{ \App\Models\Setting::get('currency', '') ?: config('app.currency', 'INR') }}',
                'transaction_id': '{{ $order->order_number }}'
            });
            @endif
            @if($hasFbPixel)
            fbq('track', 'Purchase', {
                content_ids: {!! json_encode($fbContentIds) !!},
                content_type: 'product',
                value: {{ (float) $order->total }},
                currency: '{{ \App\Models\Setting::get('currency', '') ?: config('app.currency', 'INR') }}',
                num_items: {{ $order->items->sum('quantity') }},
                order_id: '{{ $order->order_number }}'
            }{!! $fbEventOpts !!});
            @endif
        });
    </script>
    @endif
</x-layouts.app>
