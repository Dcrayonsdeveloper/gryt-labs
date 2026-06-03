@php $settings = $settings ?? []; @endphp
@php
    $heading = $settings['heading'] ?? 'Sale Ends Soon!';
    $endDate = $settings['end_date'] ?? now()->addDays(1)->toIso8601String();
    $bgColor = $settings['bg_color'] ?? 'bg-accent-500';
    $textColor = $settings['text_color'] ?? 'text-white';
    $ctaText = $settings['cta_text'] ?? '';
    $ctaLink = $settings['cta_link'] ?? '';
@endphp

<section class="{{ $bgColor }} {{ $textColor }} py-8 sm:py-12"
         x-data="{
            end: new Date('{{ $endDate }}').getTime(),
            days: 0, hours: 0, minutes: 0, seconds: 0, expired: false,
            init() { this.tick(); setInterval(() => this.tick(), 1000); },
            tick() {
                const diff = this.end - Date.now();
                if (diff <= 0) { this.expired = true; return; }
                this.days = Math.floor(diff / 86400000);
                this.hours = Math.floor((diff % 86400000) / 3600000);
                this.minutes = Math.floor((diff % 3600000) / 60000);
                this.seconds = Math.floor((diff % 60000) / 1000);
            }
         }">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-xl sm:text-2xl font-bold mb-6">{{ $heading }}</h2>

        <div x-show="!expired" class="flex justify-center gap-3 sm:gap-5 mb-6">
            @foreach(['days' => 'Days', 'hours' => 'Hrs', 'minutes' => 'Min', 'seconds' => 'Sec'] as $var => $label)
            <div class="flex flex-col items-center">
                <span x-text="{{ $var }}" class="text-3xl sm:text-5xl font-bold tabular-nums bg-white/20 rounded-lg w-16 sm:w-20 py-2 block text-center"></span>
                <span class="text-xs sm:text-sm mt-1 opacity-80">{{ $label }}</span>
            </div>
            @endforeach
        </div>

        <p x-show="expired" x-cloak class="text-lg font-semibold opacity-90 mb-4">This sale has ended.</p>

        @if($ctaText && $ctaLink)
        <a href="{{ $ctaLink }}" x-show="!expired"
           class="inline-block bg-white text-gray-900 font-semibold px-6 py-3 rounded-lg hover:bg-gray-100 transition">
            {{ $ctaText }}
        </a>
        @endif
    </div>
</section>
