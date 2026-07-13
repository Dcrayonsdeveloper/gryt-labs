<x-layouts.app>
    <x-slot name="title">FSSAI License - {{ config('app.name') }}</x-slot>

    @push('meta')
        <meta name="description" content="FSSAI License details for {{ config('app.name') }}. Licence number 10425999000390, held by Suyash Enterprise, the parent company of GRYT Health Labs.">
        <link rel="canonical" href="{{ url('/fssai-license') }}">
    @endpush

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'FSSAI License', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 sm:py-12">
        <div class="max-w-3xl mx-auto">

            {{-- Header --}}
            <div class="text-center mb-8">
                <div class="w-14 h-14 mx-auto rounded-full bg-green-50 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                    </svg>
                </div>
                <h1 class="text-lg sm:text-xl font-bold text-neutral-900">FSSAI License</h1>
                <p class="text-[13px] text-neutral-600 mt-2">Our food safety registration under the Food Safety and Standards Authority of India.</p>
            </div>

            {{-- Licence number --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4 text-center">
                <p class="text-[13px] text-neutral-600 mb-2">FSSAI License Number</p>
                <p class="text-2xl sm:text-3xl font-bold tracking-wider text-neutral-900 tabular-nums">10425999000390</p>
                <p class="text-[13px] text-neutral-600 mt-3">Held by <span class="font-semibold text-neutral-900">Suyash Enterprise</span>, the parent company of GRYT Health Labs.</p>
            </div>

            {{-- What the FSSAI licence means --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-2">What Is an FSSAI License?</h2>
                <div class="space-y-2.5 text-[13px] text-neutral-600 leading-relaxed">
                    <p>The Food Safety and Standards Authority of India (FSSAI) is the national regulator for food safety, established under the Food Safety and Standards Act, 2006. Any business that manufactures, stores, distributes or sells food products in India — including nutrition and dietary supplements — must be licensed or registered with the FSSAI.</p>
                    <p>The 14-digit licence number displayed above is our registration under that framework. It is the same number printed on our product labels, and it allows any customer or authority to trace a product back to the licensed business responsible for it.</p>
                </div>
            </div>

            {{-- What it means for you --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-3">What This Means for You</h2>
                <ul class="space-y-2">
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Our products are made and handled under a valid FSSAI licence, subject to the hygiene and safety standards the Act requires.
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Labels carry the licence number, ingredients, nutritional information and best-before date, as required by FSSAI labelling rules.
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        The licence is verifiable — you do not have to take our word for it. Anyone can look the number up on the FSSAI's public portal.
                    </li>
                    <li class="flex items-start gap-2 text-[13px] text-neutral-600">
                        <svg class="w-4 h-4 text-success-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        If a product ever falls short of these standards, the licence gives you a clear, accountable route to raise it — with us and with the regulator.
                    </li>
                </ul>
            </div>

            {{-- Verify --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Verify Our License</h2>
                <div class="space-y-2.5 text-[13px] text-neutral-600 leading-relaxed">
                    <p>You can confirm the status of licence number <span class="font-semibold text-neutral-900">10425999000390</span> yourself on the FSSAI's official compliance portal, FoSCoS, by searching for the licence number under "FBO Search".</p>
                    <p>
                        <a href="https://foscos.fssai.gov.in/" target="_blank" rel="noopener noreferrer" class="text-primary-600 font-semibold hover:underline">foscos.fssai.gov.in</a>
                        <span class="mx-1">&middot;</span>
                        <a href="https://www.fssai.gov.in/" target="_blank" rel="noopener noreferrer" class="text-primary-600 font-semibold hover:underline">fssai.gov.in</a>
                    </p>
                </div>
            </div>

            {{-- Company --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-2">The Company Behind GRYT</h2>
                <div class="space-y-2.5 text-[13px] text-neutral-600 leading-relaxed">
                    <p><span class="font-semibold text-neutral-900">Suyash Enterprise</span> is the parent company of GRYT Health Labs, and is the entity that holds our FSSAI licence. Every GRYT product is sold under that licence.</p>
                    <p>If you have a question about our licence, our labelling, or the safety of anything you have bought from us, <a href="{{ route('contact') }}" class="text-primary-600 font-semibold hover:underline">get in touch</a> — we will answer it.</p>
                </div>
            </div>

        </div>
    </div>
</x-layouts.app>
