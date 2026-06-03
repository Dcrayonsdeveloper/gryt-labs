<x-layouts.app>
    <x-slot name="title">Return Request Submitted</x-slot>

    <div class="container mx-auto px-4 py-12 sm:py-16">
        <div class="max-w-md mx-auto text-center">
            <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 flex items-center justify-center mb-5">
                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <h1 class="text-xl font-bold text-neutral-900 mb-2">Return Request Submitted</h1>
            <p class="text-[14px] text-neutral-600 leading-relaxed mb-6">
                We've received your return request for order <span class="font-semibold text-neutral-900">{{ $order->order_number }}</span>.
                Our team will review it and contact you within 24–48 hours with next steps.
            </p>

            <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-4 text-left mb-6 space-y-2">
                <p class="text-[13px] text-neutral-700"><span class="font-medium">Order:</span> {{ $order->order_number }}</p>
                @if($order->guest_phone || $order->guest_email)
                    <p class="text-[13px] text-neutral-700">
                        <span class="font-medium">We'll contact you at:</span>
                        {{ $order->guest_phone ?? $order->guest_email }}
                    </p>
                @endif
            </div>

            <a href="{{ route('track-order') }}"
               class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Order Tracking
            </a>
        </div>
    </div>
</x-layouts.app>
