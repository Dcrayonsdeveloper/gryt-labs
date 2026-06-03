<x-layouts.app>
    <x-slot name="title">Wholesale - {{ config('app.name') }}</x-slot>

    <div class="bg-[#F7F8FA] min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-2xl mx-auto text-center">
                <h1 class="text-2xl font-bold text-[#0F1111] mb-4">Wholesale Enquiry</h1>
                <p class="text-sm text-[#3a3a3a] mb-6">Interested in buying in bulk? We offer competitive wholesale pricing for businesses and resellers.</p>

                <div class="bg-white rounded-lg border border-[#E3E6E6] p-6 text-left">
                    <h2 class="text-base font-semibold text-[#0F1111] mb-3">Get in touch</h2>
                    <p class="text-sm text-[#3a3a3a] mb-4">Contact us for wholesale pricing, minimum order quantities, and custom packaging options.</p>

                    <div class="space-y-3 text-sm">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            @php $wsEmail = \App\Models\Setting::get('contact_email', ''); @endphp
                            <a href="mailto:{{ $wsEmail }}" class="text-link hover:text-link-hover">{{ $wsEmail }}</a>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            @php $wsPhone = \App\Models\Setting::get('contact_phone', ''); $wsWa = \App\Models\Setting::get('contact_whatsapp', preg_replace('/[^0-9]/', '', $wsPhone)); @endphp
                            <a href="https://wa.me/{{ $wsWa }}" class="text-link hover:text-link-hover">WhatsApp: {{ $wsPhone }}</a>
                        </div>
                    </div>

                    <a href="{{ route('contact') }}" class="mt-5 inline-block px-5 py-2.5 bg-primary-600 hover:bg-primary-600/90 text-white text-sm font-semibold rounded transition-colors">
                        Send Enquiry
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
