<x-layouts.app>
    <x-slot name="title">Free Consultation - {{ \App\Models\Setting::get('store_name', config('app.name')) }}</x-slot>

    @push('meta')
        <meta name="description" content="Book a free consultation with our specialists at {{ \App\Models\Setting::get('store_name', config('app.name')) }}. Personalized advice for your wellness needs.">
        <link rel="canonical" href="{{ route('consultation') }}">
    @endpush

    @php
        $storeName = \App\Models\Setting::get('store_name', config('app.name'));
        $responseTime = \App\Models\Setting::get('contact_response_time', '24 hours');

        $defaultQuestions = [
            'Spirulina' => [
                'Have you heard of Spirulina before?',
                'Do you currently take any supplements or superfoods?',
                'Do you often feel low on energy during the day?',
                'Do you experience fatigue or stress that impacts your productivity?',
                'Do you prefer supplements that are vegan and chemical-free?',
            ],
            'Skin Sculpt' => [
                'Do you struggle with dull or tired-looking skin?',
                'Do you notice uneven texture or loss of firmness?',
                'Are environmental factors (pollution, sun exposure) making your skin age faster?',
                'Are you looking for a natural solution to support collagen and elasticity?',
                'If your skin felt firmer and more radiant, how would it boost your confidence?',
            ],
            'Energize' => [
                'Do you often feel drained after work or workouts?',
                'Would a natural stamina booster help you stay active longer?',
                'If you could feel energized all day, how would it improve your routine?',
                'Do you prefer Ayurvedic, chemical-free solutions for energy?',
                'Would you like sustained energy without caffeine jitters?',
            ],
            'CoQ10' => [
                'Do you experience fatigue even after adequate rest?',
                'Would you benefit from a supplement that helps with cellular energy production?',
                'Do you want to feel more energetic and active throughout the day?',
                'Would you like to improve endurance and vitality naturally?',
                'If you could boost your energy and protect your heart, how would it improve your daily life?',
            ],
            'Liver' => [
                'Do you often feel bloated or experience indigestion?',
                'Do you feel your body struggles with detox after long days or heavy meals?',
                'Do you want to improve digestion and metabolism naturally?',
                'Would you like to maintain long-term liver health and overall wellness?',
                'If your liver felt healthier and more efficient, how would it improve your daily life?',
            ],
        ];
        $rawQuestions = \App\Models\Setting::get('consultation_questions', '');
        $consultationQuestions = $rawQuestions ? json_decode($rawQuestions, true) : $defaultQuestions;
        if (!is_array($consultationQuestions) || empty($consultationQuestions)) {
            $consultationQuestions = $defaultQuestions;
        }
    @endphp

    <!-- Breadcrumb -->
    <div class="bg-white border-b border-neutral-100">
        <div class="container mx-auto px-4 py-2.5">
            <x-breadcrumb :items="[['label' => 'Free Consultation', 'url' => null]]" />
        </div>
    </div>

    <!-- Hero -->
    <div class="bg-gradient-to-br from-primary-50 via-white to-primary-50/30 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-10 sm:py-14 text-center">
            <div class="w-16 h-16 mx-auto rounded-full bg-primary-600 flex items-center justify-center mb-4 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-neutral-900 mb-3">Free Personalized Consultation</h1>
            <p class="text-sm sm:text-base text-neutral-700 max-w-2xl mx-auto">Get expert advice from our wellness specialists at {{ $storeName }}. Share your concerns and receive a personalized recommendation — completely free.</p>
            <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-6 mt-6 text-xs sm:text-sm text-neutral-700">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    100% Free
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    Confidential
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    Reply within {{ $responseTime }}
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-10 sm:py-14">
        <div class="max-w-2xl mx-auto">

            {{-- Success message --}}
            @if(session('success'))
                <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 flex items-start gap-3" role="alert">
                    <svg class="w-5 h-5 text-green-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <p class="text-sm text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            {{-- Validation errors --}}
            @if($errors->any())
                <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200" role="alert">
                    <p class="text-sm font-semibold text-red-800 mb-2">Please fix the following:</p>
                    <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm p-6 sm:p-8"
                 x-data="{
                     step: {{ $errors->any() && $errors->has('q1') ? 2 : 1 }},
                     name: @js(old('name', '')),
                     email: @js(old('email', '')),
                     phone: @js(old('phone', '')),
                     selectedProduct: @js(old('product_interest', '')),
                     answers: {},
                     freeText: @js(old('q1', '')),
                     err: '',
                     productQuestions: JSON.parse(atob('{{ base64_encode(json_encode($consultationQuestions)) }}')),
                     normalize(s) { return s.toLowerCase().replace(/[^a-z0-9]/g, ''); },
                     get matchedProduct() {
                         let p = this.normalize(this.selectedProduct);
                         if (!p) return null;
                         for (let key in this.productQuestions) {
                             if (p.includes(this.normalize(key))) return key;
                         }
                         return null;
                     },
                     get hasQuestions() {
                         return this.matchedProduct !== null && Array.isArray(this.productQuestions[this.matchedProduct]);
                     },
                     get matchedQuestionList() {
                         return this.hasQuestions ? this.productQuestions[this.matchedProduct] : [];
                     },
                     get formattedAnswers() {
                         if (!this.hasQuestions) return this.freeText;
                         return this.matchedQuestionList.map((q, i) =>
                             (this.answers[i] === 'yes' ? 'Yes' : this.answers[i] === 'no' ? 'No' : '—') + ': ' + q
                         ).join('\n');
                     },
                     get allAnswered() {
                         if (!this.hasQuestions) return this.freeText.trim().length > 0;
                         return this.matchedQuestionList.every((_, i) => this.answers[i] === 'yes' || this.answers[i] === 'no');
                     },
                     nextStep() {
                         this.err = '';
                         if (!this.name.trim()) { this.err = 'Please enter your full name.'; return; }
                         if (!this.email.trim() || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(this.email)) { this.err = 'Please enter a valid email address.'; return; }
                         if (!/^[6-9]\d{9}$/.test(this.phone)) { this.err = 'Please enter a valid 10-digit mobile number.'; return; }
                         if (!this.selectedProduct) { this.err = 'Please select a product.'; return; }
                         if (!document.getElementById('cons-date').value) { this.err = 'Please select a preferred date.'; return; }
                         if (!document.getElementById('cons-time').value) { this.err = 'Please select a preferred time.'; return; }
                         this.step = 2;
                     },
                     init() {
                         this.$watch('selectedProduct', () => { this.answers = {}; this.freeText = ''; });
                     }
                 }">
                <h2 class="text-lg sm:text-xl font-bold text-neutral-900 mb-1">Book Your Free Consultation</h2>
                <p class="text-sm text-neutral-700 mb-5">Wellness is a choice. Make it today with {{ $storeName }}</p>

                {{-- Step indicator --}}
                <div class="flex items-center gap-3 mb-6">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors"
                             :class="step >= 1 ? 'bg-primary-600 text-white' : 'border-2 border-primary-600 text-primary-600'">1</div>
                        <span class="text-xs font-semibold hidden sm:inline" :class="step >= 1 ? 'text-primary-600' : 'text-neutral-700'">Your Details</span>
                    </div>
                    <div class="flex-1 h-0.5 transition-colors" :class="step >= 2 ? 'bg-primary-600' : 'bg-neutral-200'"></div>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors"
                             :class="step >= 2 ? 'bg-primary-600 text-white' : 'border-2 border-primary-600 text-primary-600'">2</div>
                        <span class="text-xs font-semibold hidden sm:inline" :class="step >= 2 ? 'text-primary-600' : 'text-neutral-700'">Quick Questions</span>
                    </div>
                </div>

                <form action="{{ route('consultation.store') }}" method="POST" class="space-y-4">
                    @csrf

                    {{-- ============ STEP 1: Name / Email / Phone ============ --}}
                    <div x-show="step === 1" class="space-y-4">
                        {{-- Name --}}
                        <div>
                            <label for="cons-name" class="block text-sm font-semibold text-neutral-900 mb-1.5">Full Name <span class="text-red-600">*</span></label>
                            <input type="text" id="cons-name" name="name" x-model="name" maxlength="100"
                                   class="w-full px-3.5 py-2.5 text-sm border @error('name') border-red-400 @else border-neutral-300 @enderror rounded-lg focus:outline-none focus:border-primary-600 bg-white"
                                   placeholder="Your full name">
                            @error('name')<p class="text-xs text-red-700 mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label for="cons-email" class="block text-sm font-semibold text-neutral-900 mb-1.5">Email <span class="text-red-600">*</span></label>
                            <input type="email" id="cons-email" name="email" x-model="email" maxlength="255"
                                   class="w-full px-3.5 py-2.5 text-sm border @error('email') border-red-400 @else border-neutral-300 @enderror rounded-lg focus:outline-none focus:border-primary-600 bg-white"
                                   placeholder="you@example.com">
                            @error('email')<p class="text-xs text-red-700 mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Phone --}}
                        <div>
                            <label for="cons-phone" class="block text-sm font-semibold text-neutral-900 mb-1.5">Mobile Number <span class="text-red-600">*</span></label>
                            <input type="tel" id="cons-phone" name="phone" x-model="phone" pattern="[6-9][0-9]{9}" inputmode="numeric" maxlength="10"
                                   class="w-full px-3.5 py-2.5 text-sm border @error('phone') border-red-400 @else border-neutral-300 @enderror rounded-lg focus:outline-none focus:border-primary-600 bg-white"
                                   placeholder="10-digit mobile number">
                            @error('phone')<p class="text-xs text-red-700 mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Product Interest --}}
                        <div>
                            <label for="cons-product" class="block text-sm font-semibold text-neutral-900 mb-1.5">Which product are you interested in? <span class="text-red-600">*</span></label>
                            <select id="cons-product" name="product_interest" x-model="selectedProduct" required
                                    class="w-full px-3.5 py-2.5 text-sm border @error('product_interest') border-red-400 @else border-neutral-300 @enderror rounded-lg focus:outline-none focus:border-primary-600 bg-white">
                                <option value="">Select a product / category</option>
                                @php $categories = \App\Models\Category::where('is_active', true)->with(['products' => fn($q) => $q->where('is_active', true)])->orderByRaw("CASE WHEN LOWER(name) = 'combos' THEN 1 ELSE 0 END")->orderBy('name')->get(); @endphp
                                @foreach($categories as $cat)
                                    @if($cat->products->isNotEmpty())
                                        <optgroup label="{{ $cat->name }}">
                                            @foreach($cat->products as $prod)
                                                <option value="{{ $prod->name }}" @selected(old('product_interest') === $prod->name)>{{ $prod->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                                <option value="Not sure — need guidance" @selected(old('product_interest') === 'Not sure — need guidance')>Not sure — need guidance</option>
                            </select>
                            @error('product_interest')<p class="text-xs text-red-700 mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Preferred Date --}}
                        <div>
                            <label for="cons-date" class="block text-sm font-semibold text-neutral-900 mb-1.5">Preferred Date <span class="text-red-600">*</span></label>
                            <input type="date" id="cons-date" name="preferred_date" min="{{ date('Y-m-d') }}" value="{{ old('preferred_date') }}"
                                   class="w-full px-3.5 py-2.5 text-sm border @error('preferred_date') border-red-400 @else border-neutral-300 @enderror rounded-lg focus:outline-none focus:border-primary-600 bg-white">
                            @error('preferred_date')<p class="text-xs text-red-700 mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Preferred Time --}}
                        <div>
                            <label for="cons-time" class="block text-sm font-semibold text-neutral-900 mb-1.5">Preferred Time <span class="text-red-600">*</span></label>
                            <select id="cons-time" name="preferred_time"
                                    class="w-full px-3.5 py-2.5 text-sm border @error('preferred_time') border-red-400 @else border-neutral-300 @enderror rounded-lg focus:outline-none focus:border-primary-600 bg-white">
                                <option value="">Select a time slot</option>
                                <option value="10:00 AM - 11:00 AM" @selected(old('preferred_time') === '10:00 AM - 11:00 AM')>10:00 AM - 11:00 AM</option>
                                <option value="11:00 AM - 12:00 PM" @selected(old('preferred_time') === '11:00 AM - 12:00 PM')>11:00 AM - 12:00 PM</option>
                                <option value="12:00 PM - 1:00 PM" @selected(old('preferred_time') === '12:00 PM - 1:00 PM')>12:00 PM - 1:00 PM</option>
                                <option value="2:00 PM - 3:00 PM" @selected(old('preferred_time') === '2:00 PM - 3:00 PM')>2:00 PM - 3:00 PM</option>
                                <option value="3:00 PM - 4:00 PM" @selected(old('preferred_time') === '3:00 PM - 4:00 PM')>3:00 PM - 4:00 PM</option>
                                <option value="4:00 PM - 5:00 PM" @selected(old('preferred_time') === '4:00 PM - 5:00 PM')>4:00 PM - 5:00 PM</option>
                                <option value="5:00 PM - 6:00 PM" @selected(old('preferred_time') === '5:00 PM - 6:00 PM')>5:00 PM - 6:00 PM</option>
                            </select>
                            @error('preferred_time')<p class="text-xs text-red-700 mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Inline step error --}}
                        <p x-show="err" x-text="err" role="alert" class="text-xs text-red-700" x-cloak></p>

                        {{-- Next button --}}
                        <button type="button" @click="nextStep()"
                                class="w-full py-3 px-6 bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white text-sm font-bold rounded-lg shadow-sm transition-colors flex items-center justify-center gap-2">
                            Next
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>

                    {{-- ============ STEP 2: Product-specific questions ============ --}}
                    <div x-show="step === 2" x-cloak class="space-y-5">
                        {{-- Hidden fields --}}
                        <input type="hidden" name="q1_question" :value="hasQuestions ? 'Product Survey: ' + selectedProduct : 'Free text'">
                        <input type="hidden" name="q1" :value="formattedAnswers">

                        {{-- Product-specific yes/no questions --}}
                        <div x-show="hasQuestions">
                            <p class="text-sm font-semibold text-primary-700 mb-3">Quick questions about <span x-text="selectedProduct"></span></p>
                            <div class="space-y-3">
                                <template x-for="(q, idx) in matchedQuestionList" :key="idx">
                                    <div class="p-3.5 border rounded-lg transition-colors"
                                         :class="answers[idx] ? 'border-primary-200 bg-primary-50/30' : 'border-neutral-200'">
                                        <p class="text-sm text-neutral-900 mb-2.5" x-text="q"></p>
                                        <div class="flex gap-4">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" :name="'q_'+idx" value="yes" x-model="answers[idx]"
                                                       class="w-4 h-4 text-primary-600 border-neutral-300 focus:ring-primary-500">
                                                <span class="text-sm font-medium" :class="answers[idx] === 'yes' ? 'text-primary-700' : 'text-neutral-600'">Yes</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" :name="'q_'+idx" value="no" x-model="answers[idx]"
                                                       class="w-4 h-4 text-primary-600 border-neutral-300 focus:ring-primary-500">
                                                <span class="text-sm font-medium" :class="answers[idx] === 'no' ? 'text-primary-700' : 'text-neutral-600'">No</span>
                                            </label>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            @error('q1')<p class="text-xs text-red-700 mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Fallback for products without specific questions --}}
                        <div x-show="!hasQuestions">
                            <label for="cons-q1-fallback" class="block text-sm text-neutral-900 mb-1.5">What would you like to know about <span x-text="selectedProduct" class="font-semibold"></span>?</label>
                            <textarea id="cons-q1-fallback" rows="3" x-model="freeText"
                                      class="w-full px-3.5 py-2.5 text-sm border border-neutral-300 rounded-lg focus:outline-none focus:border-primary-600 bg-neutral-50"
                                      placeholder="Tell us your questions or concerns...">{{ old('q1') }}</textarea>
                            @error('q1')<p class="text-xs text-red-700 mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Previous + Send --}}
                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="step = 1"
                                    class="flex-1 py-3 px-6 bg-primary-600 hover:bg-primary-700 text-white text-sm font-bold rounded-full shadow-sm transition-colors">
                                Previous
                            </button>
                            <button type="submit" :disabled="!allAnswered"
                                    class="flex-1 py-3 px-6 text-white text-sm font-bold rounded-full shadow-sm transition-colors flex items-center justify-center gap-2"
                                    :class="allAnswered ? 'bg-primary-600 hover:bg-primary-700' : 'bg-neutral-300 cursor-not-allowed'">
                                Send
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </div>
                    </div>

                    <p class="text-xs text-neutral-700 text-center">By submitting, you agree to be contacted by {{ $storeName }} regarding your consultation.</p>
                </form>
            </div>

            {{-- Trust strip --}}
            <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="bg-white border border-neutral-100 rounded-xl p-4 text-center">
                    <div class="w-10 h-10 mx-auto bg-primary-50 rounded-full flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-neutral-900">Expert Specialists</p>
                    <p class="text-xs text-neutral-700 mt-0.5">Certified wellness experts</p>
                </div>
                <div class="bg-white border border-neutral-100 rounded-xl p-4 text-center">
                    <div class="w-10 h-10 mx-auto bg-primary-50 rounded-full flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-neutral-900">100% Confidential</p>
                    <p class="text-xs text-neutral-700 mt-0.5">Your privacy is protected</p>
                </div>
                <div class="bg-white border border-neutral-100 rounded-xl p-4 text-center">
                    <div class="w-10 h-10 mx-auto bg-primary-50 rounded-full flex items-center justify-center mb-2">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-neutral-900">Quick Response</p>
                    <p class="text-xs text-neutral-700 mt-0.5">Reply within {{ $responseTime }}</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
