@php $settings = $settings ?? []; @endphp
@php
    $badges = $settings['badges'] ?? [
        ['icon' => 'truck', 'title' => 'Free Shipping', 'text' => 'On orders over ' . currency_symbol() . \App\Models\Setting::get('free_shipping_threshold', 499)],
        ['icon' => 'shield', 'title' => 'Secure Payments', 'text' => '100% protected checkout'],
        ['icon' => 'refresh', 'title' => 'Easy Returns', 'text' => \App\Models\Setting::get('return_policy_days', 7) . '-day return policy'],
        ['icon' => 'cash', 'title' => 'COD Available', 'text' => 'Cash on delivery'],
    ];
    $icons = [
        'truck' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0H3m10 0a2 2 0 104 0m-4 0a2 2 0 114 0m6-6v6m0 0a2 2 0 104 0m-4 0a2 2 0 114 0M9 6h6l3 4v4"/>',
        'shield' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
        'refresh' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>',
        'cash' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
    ];
@endphp

<section class="py-6 sm:py-10 bg-gray-50 border-y border-gray-100">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            @foreach($badges as $badge)
            <div class="flex items-center gap-3 sm:gap-4">
                <div class="shrink-0 w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center rounded-full bg-primary-100 text-primary-600">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {!! $icons[$badge['icon'] ?? 'shield'] ?? $icons['shield'] !!}
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-sm sm:text-base text-gray-900">{{ $badge['title'] ?? '' }}</p>
                    <p class="text-xs sm:text-sm text-gray-500">{{ $badge['text'] ?? '' }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
