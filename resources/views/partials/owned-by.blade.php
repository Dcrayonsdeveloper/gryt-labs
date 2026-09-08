{{-- Ownership disclosure: the brand customers see vs the legal entity behind it.
     Values come from settings so the entity can change without touching views. --}}
@php
    $__brand = $theme->get('site_name', config('app.name')) ?: config('app.name');
    $__owner = $theme->get('legal_name', '') ?: 'Suyash Enterprise';
@endphp
<div class="my-6 rounded-xl border border-primary-200 bg-primary-50/60 px-4 py-3.5 sm:px-5 flex items-start gap-3">
    <svg class="w-5 h-5 text-primary-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <p class="text-[13px] leading-relaxed text-neutral-800">
        <strong class="font-bold text-neutral-900">{{ $__brand }}</strong>
        is a brand owned and operated by
        <strong class="font-bold text-primary-700">{{ $__owner }}</strong>.
        All orders, invoices and payments are processed by {{ $__owner }}.
    </p>
</div>
