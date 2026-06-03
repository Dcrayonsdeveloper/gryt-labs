@php $settings = $settings ?? []; @endphp
@php
    $heading = $settings['heading'] ?? 'Stay in the Loop';
    $description = $settings['description'] ?? 'Subscribe to get exclusive deals, new arrivals and more.';
    $buttonText = $settings['button_text'] ?? 'Subscribe';
    $bgColor = $settings['bg_color'] ?? 'bg-primary-600';
@endphp

<section class="{{ $bgColor }} py-10 sm:py-14"
         x-data="{ email: '', submitted: false, error: '', loading: false }">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-xl sm:text-2xl font-bold text-white mb-2">{{ $heading }}</h2>
        <p class="text-white/80 text-sm sm:text-base mb-6 max-w-md mx-auto">{{ $description }}</p>

        <form @submit.prevent="
            loading = true; error = '';
            fetch('{{ route('newsletter.subscribe') }}', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                body: JSON.stringify({ email })
            })
            .then(r => r.json())
            .then(d => { loading = false; if (d.success) submitted = true; else error = d.message || 'Something went wrong.'; })
            .catch(() => { loading = false; error = 'Something went wrong.'; })
        " class="max-w-md mx-auto">
            <div x-show="!submitted" class="flex gap-2">
                <input type="email" x-model="email" required placeholder="Enter your email"
                       class="flex-1 px-4 py-3 rounded-lg text-sm border-0 focus:ring-2 focus:ring-white/50">
                <button type="submit" :disabled="loading"
                        class="bg-gray-900 hover:bg-gray-800 text-white font-semibold px-5 py-3 rounded-lg text-sm transition whitespace-nowrap disabled:opacity-50">
                    <span x-show="!loading">{{ $buttonText }}</span>
                    <span x-show="loading" x-cloak>...</span>
                </button>
            </div>
            <p x-show="error" x-text="error" class="text-red-200 text-sm mt-2"></p>
            <p x-show="submitted" x-cloak class="text-white font-medium">Thank you for subscribing!</p>
        </form>
    </div>
</section>
