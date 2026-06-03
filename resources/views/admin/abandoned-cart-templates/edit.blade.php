<x-layouts.admin>
    <x-slot name="title">Abandoned Cart Templates</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold text-gray-900 leading-tight">Abandoned Cart Email Templates</h1>
            </div>
            <button type="submit" form="abandoned-cart-templates-form"
                    class="bg-gray-900 hover:bg-gray-700 border border-gray-900 text-white text-[13px] font-medium px-3.5 py-1.5 rounded-lg cursor-pointer">
                Save all templates
            </button>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="px-3.5 py-2.5 bg-green-100 text-green-800 rounded-lg text-[13px] mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="px-3.5 py-2.5 bg-red-100 text-red-800 rounded-lg text-[13px] mb-4">
            <ul class="m-0 pl-4 list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div x-data="abandonedCartTemplatesEditor({{ \Illuminate\Support\Js::from($values) }})" class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Left: tabs + form (spans 2 cols on desktop) --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">

            {{-- Tab bar --}}
            <div class="flex border-b border-gray-200 bg-gray-50">
                @foreach($reminders as $slot => $cfg)
                    <button type="button"
                            @click="activeTab = '{{ $slot }}'"
                            :class="activeTab === '{{ $slot }}'
                                ? 'bg-white text-gray-900 border-b-2 border-gray-900'
                                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 border-b-2 border-transparent'"
                            class="px-4 py-2.5 text-[13px] font-medium transition-colors cursor-pointer focus:outline-none">
                        {{ $cfg['label'] }}
                    </button>
                @endforeach
            </div>

            <form id="abandoned-cart-templates-form" method="POST" action="{{ route('admin.abandoned-cart-templates.update') }}">
                @csrf
                @method('PUT')

                @foreach($reminders as $slot => $cfg)
                    <div x-show="activeTab === '{{ $slot }}'" x-cloak class="p-5 space-y-4">

                        <div>
                            <label class="block text-[12px] font-medium text-gray-700 mb-1" for="{{ $slot }}_subject">Subject line</label>
                            <input type="text"
                                   id="{{ $slot }}_subject"
                                   name="{{ $slot }}[subject]"
                                   x-model="fields.{{ $slot }}.subject"
                                   value="{{ old($slot.'.subject', $values[$slot]['subject']) }}"
                                   maxlength="200"
                                   required
                                   class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-md outline-none text-gray-900 bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                            <p class="text-[11px] text-gray-500 mt-1">Shown in the inbox. Keep under 60 chars for mobile.</p>
                        </div>

                        <div>
                            <label class="block text-[12px] font-medium text-gray-700 mb-1" for="{{ $slot }}_preheader">Preheader (preview text)</label>
                            <input type="text"
                                   id="{{ $slot }}_preheader"
                                   name="{{ $slot }}[preheader]"
                                   x-model="fields.{{ $slot }}.preheader"
                                   value="{{ old($slot.'.preheader', $values[$slot]['preheader']) }}"
                                   maxlength="200"
                                   class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-md outline-none text-gray-900 bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                            <p class="text-[11px] text-gray-500 mt-1">Grey text shown next to the subject in Gmail / Apple Mail.</p>
                        </div>

                        <div>
                            <label class="block text-[12px] font-medium text-gray-700 mb-1" for="{{ $slot }}_heading">Heading (H1)</label>
                            <input type="text"
                                   id="{{ $slot }}_heading"
                                   name="{{ $slot }}[heading]"
                                   x-model="fields.{{ $slot }}.heading"
                                   value="{{ old($slot.'.heading', $values[$slot]['heading']) }}"
                                   maxlength="200"
                                   required
                                   class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-md outline-none text-gray-900 bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                        </div>

                        <div>
                            <label class="block text-[12px] font-medium text-gray-700 mb-1" for="{{ $slot }}_body">Body (plain text / markdown)</label>
                            <textarea id="{{ $slot }}_body"
                                      name="{{ $slot }}[body]"
                                      x-model="fields.{{ $slot }}.body"
                                      rows="10"
                                      maxlength="5000"
                                      required
                                      class="w-full px-2.5 py-2 text-[13px] border border-gray-300 rounded-md outline-none text-gray-900 bg-white font-mono focus:border-blue-600 focus:ring-1 focus:ring-blue-600">{{ old($slot.'.body', $values[$slot]['body']) }}</textarea>
                            <p class="text-[11px] text-gray-500 mt-1">
                                Available tokens: <code class="bg-gray-100 px-1 rounded">{name}</code>,
                                <code class="bg-gray-100 px-1 rounded">{discount}</code>,
                                <code class="bg-gray-100 px-1 rounded">{code}</code>.
                                Blank lines become paragraph breaks.
                            </p>
                        </div>

                        <div>
                            <label class="block text-[12px] font-medium text-gray-700 mb-1" for="{{ $slot }}_cta">CTA button label</label>
                            <input type="text"
                                   id="{{ $slot }}_cta"
                                   name="{{ $slot }}[cta]"
                                   x-model="fields.{{ $slot }}.cta"
                                   value="{{ old($slot.'.cta', $values[$slot]['cta']) }}"
                                   maxlength="80"
                                   required
                                   class="w-full px-2.5 py-1.5 text-[13px] border border-gray-300 rounded-md outline-none text-gray-900 bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                        </div>

                    </div>
                @endforeach

                <div class="flex items-center justify-between px-5 py-3 border-t border-gray-200 bg-gray-50">
                    <a href="{{ route('admin.marketing.hub') }}"
                       class="inline-flex items-center px-3.5 py-1.5 text-[13px] font-medium bg-white hover:bg-gray-50 border border-gray-500 text-gray-800 rounded-lg no-underline">
                        Cancel
                    </a>
                    <button type="submit"
                            class="bg-gray-900 hover:bg-gray-700 border border-gray-900 text-white text-[13px] font-medium px-3.5 py-1.5 rounded-lg cursor-pointer">
                        Save all templates
                    </button>
                </div>
            </form>
        </div>

        {{-- Right: live preview --}}
        <aside class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden sticky top-4">
                <div class="px-4 py-2.5 border-b border-gray-200 bg-gray-50">
                    <p class="text-[12px] font-medium text-gray-700 m-0">Live preview</p>
                    <p class="text-[11px] text-gray-500 m-0 mt-0.5">Static mock of the rendered email.</p>
                </div>

                <div class="p-4 bg-gray-100">
                    <div class="bg-white rounded-lg overflow-hidden border border-gray-200 shadow-sm">
                        {{-- Inbox subject/preheader preview --}}
                        <div class="px-3 py-2 bg-gray-50 border-b border-gray-200">
                            <p class="text-[12px] font-semibold text-gray-900 m-0 truncate" x-text="currentFields.subject || '(subject)'"></p>
                            <p class="text-[11px] text-gray-500 m-0 mt-0.5 truncate" x-text="currentFields.preheader || ''"></p>
                        </div>

                        {{-- Hero band --}}
                        <div class="bg-gray-900 text-white px-4 py-5 text-center">
                            <h2 class="text-[16px] font-semibold m-0" x-text="currentFields.heading || '(heading)'"></h2>
                        </div>

                        {{-- Body --}}
                        <div class="px-4 py-4">
                            <template x-for="(para, i) in bodyParagraphs" :key="i">
                                <p class="text-[13px] text-gray-700 m-0 mb-3 whitespace-pre-wrap" x-text="para"></p>
                            </template>

                            {{-- CTA mockup --}}
                            <div class="text-center mt-4 mb-2">
                                <span class="inline-block bg-gray-900 text-white text-[13px] font-semibold px-5 py-2.5 rounded-md"
                                      x-text="currentFields.cta || '(CTA label)'"></span>
                            </div>

                            <p class="text-[11px] text-gray-400 text-center mt-3 mb-0">
                                This is a static preview. Actual email pulls cart items, totals and tokens at send time.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

    </div>

    @push('scripts')
        <script>
            function abandonedCartTemplatesEditor(initialValues) {
                return {
                    activeTab: 'r1',
                    fields: initialValues,
                    get currentFields() {
                        return this.fields[this.activeTab] || {};
                    },
                    get bodyParagraphs() {
                        const body = this.currentFields.body || '';
                        return body.split(/\n{2,}/).filter(p => p.trim().length > 0);
                    },
                };
            }
        </script>
    @endpush
</x-layouts.admin>
