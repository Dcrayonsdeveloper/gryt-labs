<x-layouts.app>
    <x-slot name="title">BMI Calculator - {{ \App\Models\Setting::get('store_name', config('app.name')) }}</x-slot>

    @push('meta')
        <meta name="description" content="Free BMI Calculator — calculate your Body Mass Index instantly. Know your healthy weight range based on your height. Ayurveda-friendly health insights.">
        <link rel="canonical" href="{{ url('/bmi-calculator') }}">
        <meta property="og:title" content="BMI Calculator - {{ \App\Models\Setting::get('store_name', config('app.name')) }}">
        <meta property="og:description" content="Calculate your Body Mass Index instantly and get your healthy weight range.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/bmi-calculator') }}">
    @endpush

    <!-- Breadcrumb -->
    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'BMI Calculator', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 sm:py-12">
        <div class="max-w-3xl mx-auto">

            <!-- Header -->
            <div class="text-center mb-6 sm:mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-primary-600/5 text-primary-700 rounded-full text-xs font-medium mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Free Health Tool
                </div>
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-neutral-900 mb-3">BMI Calculator</h1>
                <p class="text-sm sm:text-base text-neutral-700 max-w-xl mx-auto">Calculate your Body Mass Index instantly and discover your healthy weight range.</p>
            </div>

            <div x-data="bmiCalculator()" class="space-y-6">

                <!-- Input Card -->
                <div>
                    <div class="bg-white border border-neutral-100 rounded-2xl p-5 sm:p-7 shadow-sm">
                        <h2 class="text-base font-bold text-neutral-900 mb-5">Enter Your Details</h2>

                        <!-- Unit info -->
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-neutral-100 rounded-xl mb-6">
                            <span class="text-xs font-semibold text-neutral-600">Weight in kg · Height in ft/in</span>
                        </div>

                        <div class="space-y-5">

                            <!-- Gender -->
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-2">Gender</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button type="button" @click="gender = 'male'"
                                            :class="gender === 'male' ? 'border-primary-600 bg-primary-600/5 text-primary-700' : 'border-neutral-200 text-neutral-600 hover:border-neutral-300'"
                                            class="flex items-center justify-center gap-2 py-3 border-2 rounded-xl text-sm font-semibold transition-all">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a4 4 0 100 8 4 4 0 000-8zm-7 20v-2a7 7 0 0114 0v2"/></svg>
                                        Male
                                    </button>
                                    <button type="button" @click="gender = 'female'"
                                            :class="gender === 'female' ? 'border-primary-600 bg-primary-600/5 text-primary-700' : 'border-neutral-200 text-neutral-600 hover:border-neutral-300'"
                                            class="flex items-center justify-center gap-2 py-3 border-2 rounded-xl text-sm font-semibold transition-all">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a4 4 0 100 8 4 4 0 000-8zm0 10c-3 0-5 2-5 5v2h3v3h4v-3h3v-2c0-3-2-5-5-5z"/></svg>
                                        Female
                                    </button>
                                </div>
                            </div>

                            <!-- Age + Weight side by side -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="age" class="block text-sm font-medium text-neutral-700 mb-2">
                                        Age <span class="text-neutral-500 font-normal">(years)</span>
                                    </label>
                                    <input type="number" id="age" x-model.number="age" min="2" max="120"
                                           class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:border-primary-600 transition-all"
                                           placeholder="e.g. 28">
                                </div>
                                <div>
                                    <label for="weight" class="block text-sm font-medium text-neutral-700 mb-2">
                                        Weight <span class="text-neutral-500 font-normal">(kg)</span>
                                    </label>
                                    <input type="number" id="weight" x-model.number="weight" min="1" step="0.1"
                                           class="w-full px-4 py-3 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:border-primary-600 transition-all"
                                           placeholder="e.g. 65">
                                </div>
                            </div>

                            <!-- Height (ft/in) -->
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-2">Height</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="relative">
                                        <input type="number" x-model.number="heightFt" min="1" max="8"
                                               class="w-full px-4 py-3 pr-10 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:border-primary-600 transition-all"
                                               placeholder="5">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-neutral-500 font-medium">ft</span>
                                    </div>
                                    <div class="relative">
                                        <input type="number" x-model.number="heightIn" min="0" max="11"
                                               class="w-full px-4 py-3 pr-10 bg-neutral-50 border border-neutral-200 rounded-xl text-sm text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary-600/20 focus:border-primary-600 transition-all"
                                               placeholder="7">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-neutral-500 font-medium">in</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-3 pt-2">
                                <button type="button" @click="calculate()"
                                        class="flex-1 px-6 py-3 bg-gradient-to-r from-accent-500 to-accent-600 hover:from-accent-600 hover:to-accent-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-accent-500/25 hover:shadow-accent-500/40 transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0">
                                    Calculate BMI
                                </button>
                                <button type="button" @click="reset()"
                                        class="px-5 py-3 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-sm font-semibold rounded-xl transition-colors">
                                    Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Result Card (only shown after calculation) -->
                <div x-show="bmi !== null" x-cloak
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="bg-gradient-to-br from-primary-600 to-primary-700 rounded-2xl p-6 sm:p-8 text-white shadow-xl shadow-primary-600/20">

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                            <div>
                                <p class="text-xs uppercase tracking-wider text-white/70 font-semibold mb-1">Your BMI Score</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-5xl sm:text-6xl font-bold tabular-nums" x-text="bmi !== null ? bmi.toFixed(1) : '--'"></span>
                                    <span class="text-sm text-white/70">kg/m²</span>
                                </div>
                            </div>
                            <div>
                                <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-bold"
                                      :class="categoryColor">
                                    <span class="w-2 h-2 rounded-full bg-current"></span>
                                    <span x-text="category"></span>
                                </span>
                            </div>
                        </div>

                        <!-- BMI scale -->
                        <div class="mb-6">
                            <div class="relative h-3 rounded-full overflow-hidden bg-white/10">
                                <div class="absolute inset-0 flex">
                                    <div class="bg-blue-400" style="width: 23.125%"></div>
                                    <div class="bg-green-400" style="width: 18.75%"></div>
                                    <div class="bg-yellow-400" style="width: 18.75%"></div>
                                    <div class="bg-orange-400" style="width: 12.5%"></div>
                                    <div class="bg-red-400" style="width: 26.875%"></div>
                                </div>
                                <div class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-4 h-4 rounded-full bg-white border-2 border-primary-700 shadow-lg transition-all duration-500"
                                     :style="`left: ${markerPosition}%`"></div>
                            </div>
                            <div class="flex justify-between text-[10px] text-white/70 mt-2 font-medium">
                                <span>15</span><span>18.5</span><span>25</span><span>30</span><span>35</span><span>40</span>
                            </div>
                        </div>

                        <!-- Healthy range -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-5 border-t border-white/15">
                            <div>
                                <p class="text-xs text-white/70 mb-1">Healthy weight range</p>
                                <p class="text-base font-semibold tabular-nums" x-text="healthyRange"></p>
                            </div>
                            <div>
                                <p class="text-xs text-white/70 mb-1" x-text="weightDiffLabel"></p>
                                <p class="text-base font-semibold tabular-nums" x-text="weightDiff"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BMI Categories table -->
            <div class="mt-10 bg-white border border-neutral-100 rounded-2xl p-5 sm:p-7">
                <h3 class="text-base font-bold text-neutral-900 mb-4">BMI Categories (WHO Standard)</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div class="p-4 rounded-xl border border-blue-100 bg-blue-50/50">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                            <span class="text-xs font-bold text-blue-700">Underweight</span>
                        </div>
                        <p class="text-[11px] text-neutral-600">Below 18.5</p>
                    </div>
                    <div class="p-4 rounded-xl border border-green-100 bg-green-50/50">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span class="text-xs font-bold text-green-700">Normal</span>
                        </div>
                        <p class="text-[11px] text-neutral-600">18.5 – 24.9</p>
                    </div>
                    <div class="p-4 rounded-xl border border-yellow-100 bg-yellow-50/50">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                            <span class="text-xs font-bold text-yellow-700">Overweight</span>
                        </div>
                        <p class="text-[11px] text-neutral-600">25 – 29.9</p>
                    </div>
                    <div class="p-4 rounded-xl border border-orange-100 bg-orange-50/50">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                            <span class="text-xs font-bold text-orange-700">Obese I</span>
                        </div>
                        <p class="text-[11px] text-neutral-600">30 – 34.9</p>
                    </div>
                    <div class="p-4 rounded-xl border border-red-100 bg-red-50/50">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            <span class="text-xs font-bold text-red-700">Obese II+</span>
                        </div>
                        <p class="text-[11px] text-neutral-600">35 and above</p>
                    </div>
                </div>

                <div class="mt-5 p-4 bg-amber-50/60 border border-amber-100 rounded-xl">
                    <p class="text-xs text-amber-900 leading-relaxed">
                        <strong>Note:</strong> BMI is a general guideline and does not account for muscle mass, bone density, or body composition. Consult a qualified Ayurveda practitioner or healthcare provider for personalised guidance.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function bmiCalculator() {
            return {
                gender: 'male',
                age: null,
                heightFt: null,
                heightIn: null,
                weight: null,
                bmi: null,
                category: '',
                categoryColor: '',
                markerPosition: 0,
                healthyRange: '',
                weightDiff: '',
                weightDiffLabel: '',

                getHeightMeters() {
                    const totalIn = (this.heightFt || 0) * 12 + (this.heightIn || 0);
                    return totalIn > 0 ? totalIn * 0.0254 : 0;
                },

                getWeightKg() {
                    if (!this.weight || this.weight <= 0) return 0;
                    return this.weight;
                },

                calculate() {
                    const h = this.getHeightMeters();
                    const w = this.getWeightKg();

                    if (h <= 0 || w <= 0) {
                        if (window.Alpine && Alpine.store('toast')) Alpine.store('toast').error('Please enter valid height and weight.');
                        return;
                    }
                    if (!this.age || this.age < 2 || this.age > 120) {
                        if (window.Alpine && Alpine.store('toast')) Alpine.store('toast').error('Please enter a valid age (2–120).');
                        return;
                    }

                    const bmi = w / (h * h);
                    this.bmi = bmi;

                    // Category
                    if (bmi < 18.5) {
                        this.category = 'Underweight';
                        this.categoryColor = 'bg-blue-400/20 text-blue-100';
                    } else if (bmi < 25) {
                        this.category = 'Normal Weight';
                        this.categoryColor = 'bg-green-400/20 text-green-100';
                    } else if (bmi < 30) {
                        this.category = 'Overweight';
                        this.categoryColor = 'bg-yellow-400/20 text-yellow-100';
                    } else if (bmi < 35) {
                        this.category = 'Obese Class I';
                        this.categoryColor = 'bg-orange-400/20 text-orange-100';
                    } else {
                        this.category = 'Obese Class II+';
                        this.categoryColor = 'bg-red-400/20 text-red-100';
                    }

                    // Marker position on 15–40 scale
                    const clamped = Math.max(15, Math.min(40, bmi));
                    this.markerPosition = ((clamped - 15) / 25) * 100;

                    // Healthy weight range (BMI 18.5–24.9)
                    const minKg = 18.5 * h * h;
                    const maxKg = 24.9 * h * h;
                    this.healthyRange = `${minKg.toFixed(1)} – ${maxKg.toFixed(1)} kg`;

                    // Difference from healthy range
                    let diffKg = 0;
                    let label = '';
                    if (w < minKg) {
                        diffKg = minKg - w;
                        label = 'You need to gain';
                    } else if (w > maxKg) {
                        diffKg = w - maxKg;
                        label = 'You need to lose';
                    } else {
                        label = 'Status';
                        this.weightDiff = "You're in range ✓";
                        this.weightDiffLabel = label;
                        return;
                    }
                    this.weightDiffLabel = label;
                    this.weightDiff = `${diffKg.toFixed(1)} kg`;
                },

                reset() {
                    this.age = null;
                    this.heightFt = null;
                    this.heightIn = null;
                    this.weight = null;
                    this.bmi = null;
                    this.category = '';
                    this.markerPosition = 0;
                    this.healthyRange = '';
                    this.weightDiff = '';
                    this.weightDiffLabel = '';
                },
            };
        }
    </script>
</x-layouts.app>
