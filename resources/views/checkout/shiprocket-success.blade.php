<x-layouts.app>
    <x-slot name="title">Order Confirmed - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="robots" content="noindex, nofollow">
    @endpush

    <div class="min-h-[60vh] flex items-center justify-center px-4 py-16">
        <div class="max-w-lg w-full text-center">
            <div class="mb-6">
                <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ __('Order Confirmed!') }}</h1>
            <p class="text-gray-600 mb-6">{{ __('Thank you for your purchase. Your order has been placed successfully.') }}</p>

            @if(isset($order) && $order)
                <p class="text-sm text-gray-500 mb-2">{{ __('Order Number') }}: <span class="font-mono font-semibold text-gray-900">{{ $order->order_number }}</span></p>
                <p class="text-sm text-gray-500 mb-4">{{ __('Total') }}: <span class="font-semibold text-gray-900">{{ config('app.currency_symbol', '₹') }}{{ number_format($order->total, 0) }}</span></p>
            @elseif($shiprocketOrderId)
                <p class="text-sm text-gray-500 mb-4">{{ __('Order Reference') }}: <span class="font-mono font-medium">{{ $shiprocketOrderId }}</span></p>
            @endif

            <p class="text-sm text-gray-500 mb-8">{{ __('You will receive a confirmation email shortly with your order details and tracking information.') }}</p>

            @if(isset($order) && $order)
            {{-- Silently save any customer data captured from Shiprocket iframe postMessage events --}}
            <script>
            (function() {
                try {
                    var raw = sessionStorage.getItem('sr_checkout_customer');
                    if (!raw) return;
                    var d = JSON.parse(raw);
                    sessionStorage.removeItem('sr_checkout_customer');
                    var name = d.customer_name || d.billing_customer_name || d.name || '';
                    var phone = d.customer_phone || d.billing_phone || d.phone || '';
                    if (!name && !phone) return;
                    fetch('{{ url("/checkout/shiprocket-details") }}', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                        body: JSON.stringify({order_id:{{ $order->id }},name:name,phone:phone,email:d.customer_email||d.billing_email||d.email||'',address:d.billing_address||d.address||'',city:d.billing_city||d.city||'',state:d.billing_state||d.state||'',pincode:d.billing_pincode||d.pincode||''})
                    });
                } catch(e) {}
            })();
            </script>
            @endif

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-black hover:bg-gray-800 transition">
                    {{ __('Continue Shopping') }}
                </a>
            </div>
        </div>
    </div>

    {{-- GA4 Purchase + Google Ads Conversion + FB Purchase tracking --}}
    {{-- Skipped entirely when Fastrr handles checkout (all tracking done by Fastrr) --}}
    {{-- Also skipped in test mode (cookie sr_test_mode=1) --}}
    {{-- Read the flag here: no controller ever passed $fastrHandlesPixel, so the
         guard below silently never fired — turning the setting on would have
         double-counted every Purchase (ours + Shiprocket's). --}}
    @php $fastrHandlesPixel = (bool) \App\Models\Setting::get('fastrr_handles_purchase_pixel', false); @endphp
    @if(isset($order) && $order && empty($isTestMode) && empty($fastrHandlesPixel))
    @php
        $hasGa4 = \App\Models\Setting::get('google_analytics_id');
        $hasGads = \App\Models\Setting::get('google_ads_conversion_id', '');
        $hasFbPixel = \App\Models\Setting::get('facebook_pixel_id', '');
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
            // Double-check cookie client-side (server already checks, this catches edge cases)
            if (document.cookie.indexOf('sr_test_mode=1') !== -1) return;

            var orderItems = {!! json_encode($orderItemsArr) !!};
            @if($hasGa4)
            if (typeof gtag === 'function') {
                gtag('event', 'purchase', {
                    transaction_id: '{{ $order->order_number }}',
                    value: {{ (float) $order->total }},
                    tax: {{ (float) $order->tax }},
                    shipping: {{ (float) $order->shipping_cost }},
                    currency: '{{ \App\Models\Setting::get('currency', '') ?: config('app.currency', 'INR') }}',
                    items: orderItems
                });
            }
            @endif
            @if($hasGads)
            if (typeof gtag === 'function') {
                gtag('event', 'conversion', {
                    'send_to': '{{ $hasGads }}/{!! \App\Models\Setting::get('google_ads_conversion_label', '') !!}',
                    'value': {{ (float) $order->total }},
                    'currency': '{{ \App\Models\Setting::get('currency', '') ?: config('app.currency', 'INR') }}',
                    'transaction_id': '{{ $order->order_number }}'
                });
            }
            @endif
            @if($hasFbPixel && empty($fastrHandlesPixel))
            if (typeof fbq === 'function') {
                fbq('track', 'Purchase', {
                    content_ids: {!! json_encode($fbContentIds) !!},
                    content_type: 'product',
                    value: {{ (float) $order->total }},
                    currency: '{{ \App\Models\Setting::get('currency', '') ?: config('app.currency', 'INR') }}',
                    num_items: {{ $order->items->sum('quantity') }},
                    order_id: '{{ $order->order_number }}'
                }{!! $fbEventOpts !!});
            }
            @endif
        });
    </script>
    @endif
    @endif
</x-layouts.app>
