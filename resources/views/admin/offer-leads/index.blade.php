<x-layouts.admin>
    <x-slot name="title">Offer popup leads</x-slot>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Offer popup leads</h1>
            <p class="text-sm text-gray-500 mt-0.5">Mobile numbers captured by the storefront popup and verified over WhatsApp.</p>
        </div>
        <a href="{{ route('admin.offer-leads.export', request()->only('status', 'from', 'to')) }}"
           class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Export CSV
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">{{ session('error') }}</div>
    @endif

    {{-- Setup warnings --}}
    @unless($whatsappOk)
        <div class="mb-4 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg">
            WhatsApp is not configured, so OTPs cannot be delivered and the popup will not be able to verify numbers.
            Add the WhatsApp API credentials under <a href="{{ route('admin.settings.integrations') }}" class="underline font-medium">Settings &rsaquo; Integrations</a>.
        </div>
    @endunless

    @if($stats['failed'] > 0)
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg">
            <span class="font-semibold">{{ number_format($stats['failed']) }} shopper(s) never received their code.</span>
            The code is only sent on WhatsApp, so a failed send means they got nothing. Once the
            <span class="font-mono text-xs">{{ $settings['whatsapp_offer_template'] ?: $settings['template_placeholder'] }}</span>
            template is approved, tick those rows and use <span class="font-medium">Resend code</span> to send the backlog.
        </div>
    @endif

    @unless($couponExists)
        <div class="mb-4 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg">
            No active discount called <span class="font-semibold">{{ $couponCode }}</span> exists yet, so the code handed out by the popup will be rejected at checkout.
            <a href="{{ route('admin.coupons.create') }}" class="underline font-medium">Create it under Discounts</a>.
        </div>
    @endunless

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
        @php
            $cards = [
                ['label' => 'Numbers captured', 'value' => number_format($stats['total']), 'tone' => 'text-gray-900'],
                ['label' => 'Verified on WhatsApp', 'value' => number_format($stats['verified']), 'tone' => 'text-green-600'],
                ['label' => 'Verified in last 7 days', 'value' => number_format($stats['last_7']), 'tone' => 'text-gray-900'],
                ['label' => 'Code delivered on WhatsApp', 'value' => number_format($stats['delivered']), 'tone' => $stats['failed'] > 0 ? 'text-amber-600' : 'text-gray-900'],
            ];
        @endphp
        @foreach($cards as $card)
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-4">
                <p class="text-2xl font-bold {{ $card['tone'] }}">{{ $card['value'] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Popup settings --}}
    <div class="bg-white rounded-xl border border-gray-200 mb-5" x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">
        <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-5 py-4 text-left">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Popup settings</h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    Currently
                    <span class="font-medium {{ $settings['offer_popup_enabled'] === '1' ? 'text-green-600' : 'text-gray-500' }}">
                        {{ $settings['offer_popup_enabled'] === '1' ? 'live' : 'off' }}
                    </span>
                    &middot; opens after {{ $settings['offer_popup_delay'] }}s &middot; code {{ $couponCode }}
                </p>
            </div>
            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="open" x-cloak class="px-5 pb-5" style="border-top:1px solid #e1e1e1">
            <form method="POST" action="{{ route('admin.offer-leads.settings') }}" class="pt-4 space-y-4">
                @csrf
                @method('PUT')

                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="offer_popup_enabled" value="1"
                               {{ $settings['offer_popup_enabled'] === '1' ? 'checked' : '' }}
                               class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                        <span class="text-sm text-gray-700">Show the popup on the storefront</span>
                    </label>

                    <label class="flex items-center gap-2">
                        <span class="text-sm text-gray-700">Open after</span>
                        <input type="number" name="offer_popup_delay" min="0" max="300"
                               value="{{ old('offer_popup_delay', $settings['offer_popup_delay']) }}"
                               class="w-20 px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                        <span class="text-sm text-gray-700">seconds</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Headline</label>
                        <input type="text" name="offer_popup_title" value="{{ old('offer_popup_title', $settings['offer_popup_title']) }}"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Button text</label>
                        <input type="text" name="offer_popup_button_text" value="{{ old('offer_popup_button_text', $settings['offer_popup_button_text']) }}"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Sub-headline</label>
                    <input type="text" name="offer_popup_subtitle" value="{{ old('offer_popup_subtitle', $settings['offer_popup_subtitle']) }}"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Consent checkbox text</label>
                    <input type="text" name="offer_popup_consent_text" value="{{ old('offer_popup_consent_text', $settings['offer_popup_consent_text']) }}"
                           placeholder="{{ $settings['consent_placeholder'] }}"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                    <p class="text-xs text-gray-400 mt-1">Shown next to the checkbox. The exact wording is stored with every lead as proof of consent.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Coupon code</label>
                        <input type="text" name="offer_popup_coupon_code" value="{{ old('offer_popup_coupon_code', $settings['offer_popup_coupon_code']) }}"
                               class="w-full px-3 py-2 text-sm font-mono uppercase border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Offer wording</label>
                        <input type="text" name="offer_popup_offer_text" value="{{ old('offer_popup_offer_text', $settings['offer_popup_offer_text']) }}"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                        <p class="text-xs text-gray-400 mt-1">e.g. "15% off your first order".</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">WhatsApp template name</label>
                        <input type="text" name="whatsapp_offer_template" value="{{ old('whatsapp_offer_template', $settings['whatsapp_offer_template']) }}"
                               placeholder="{{ $settings['template_placeholder'] }}"
                               class="w-full px-3 py-2 text-sm font-mono border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                        <p class="text-xs text-gray-400 mt-1">Approved Meta template with 3 body variables: name, code, offer.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Fine print</label>
                    <input type="text" name="offer_popup_footer_text" value="{{ old('offer_popup_footer_text', $settings['offer_popup_footer_text']) }}"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                </div>

                @if($errors->any())
                    <ul class="text-xs text-red-600 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                        Save settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Leads --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200"
         x-data="{
             selected: [],
             allIds: {{ $leads->pluck('id') }},
             toggleAll(checked) { this.selected = checked ? [...this.allIds] : []; },
             isAllSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; }
         }">

        {{-- Bulk bar --}}
        <div x-show="selected.length > 0" x-cloak
             class="px-4 py-3 bg-gray-50 flex items-center justify-between gap-3" style="border-bottom:1px solid #e1e1e1">
            <span class="text-sm font-medium text-gray-700"><span x-text="selected.length"></span> selected</span>
            <form method="POST" action="{{ route('admin.offer-leads.bulk-action') }}"
                  @submit.prevent="$el.querySelector('input[name=ids]').value = JSON.stringify(selected); $el.submit()">
                @csrf
                <input type="hidden" name="ids" value="">
                <div class="flex items-center gap-4">
                    <button type="submit" name="action" value="resend"
                            onclick="return confirm('Send the coupon code to the selected numbers on WhatsApp?')"
                            class="text-sm text-gray-700 hover:text-gray-900 font-medium">Resend code</button>
                    <button type="submit" name="action" value="delete"
                            onclick="return confirm('Delete the selected leads?')"
                            class="text-sm text-red-600 hover:text-red-800 font-medium">Delete</button>
                </div>
            </form>
        </div>

        {{-- Tabs --}}
        @php $currentStatus = request('status', 'all'); @endphp
        <div class="flex items-center gap-0 px-4" style="border-bottom:1px solid #e1e1e1">
            @foreach(['all' => 'All', 'verified' => 'Verified', 'pending' => 'Not verified'] as $key => $label)
                <a href="{{ route('admin.offer-leads.index', array_merge(request()->except('status', 'page'), $key === 'all' ? [] : ['status' => $key])) }}"
                   class="relative px-4 py-3 text-sm font-medium transition-colors {{ $currentStatus === $key ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                    <span class="ml-1 text-xs text-gray-400">
                        {{ $key === 'all' ? $stats['total'] : ($key === 'verified' ? $stats['verified'] : $stats['pending']) }}
                    </span>
                    @if($currentStatus === $key)
                        <span class="absolute bottom-0 left-4 right-4 h-0.5 bg-gray-900 rounded-full"></span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Filters --}}
        <div class="px-4 py-3" style="border-bottom:1px solid #e1e1e1">
            <form action="{{ route('admin.offer-leads.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative flex-1 min-w-50 max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search phone or name"
                           class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                </div>
                <input type="date" name="from" value="{{ request('from') }}"
                       class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                <input type="date" name="to" value="{{ request('to') }}"
                       class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">Filter</button>
                @if(request()->hasAny(['search', 'from', 'to']))
                    <a href="{{ route('admin.offer-leads.index', request()->only('status')) }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="border-bottom:1px solid #e1e1e1">
                        <th class="pl-4 pr-0 py-3 w-10">
                            <input type="checkbox" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500"
                                   @change="toggleAll($event.target.checked)" :checked="isAllSelected()">
                        </th>
                        <th class="px-4 py-3">Mobile number</th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Coupon</th>
                        <th class="px-4 py-3">WhatsApp</th>
                        <th class="px-4 py-3">Captured</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                        <tr class="hover:bg-gray-50" style="border-bottom:1px solid #e1e1e1">
                            <td class="pl-4 pr-0 py-3 w-10">
                                <input type="checkbox" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500"
                                       value="{{ $lead->id }}"
                                       :checked="selected.includes({{ $lead->id }})"
                                       @change="selected.includes({{ $lead->id }}) ? selected.splice(selected.indexOf({{ $lead->id }}), 1) : selected.push({{ $lead->id }})">
                            </td>
                            <td class="px-4 py-3">
                                <a href="https://wa.me/{{ $lead->msisdn() }}" target="_blank" rel="noopener"
                                   class="font-medium text-gray-900 hover:text-green-600">+{{ $lead->country_code }} {{ $lead->phone }}</a>
                                @if($lead->user_id)
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700">Customer</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $lead->name ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if($lead->isVerified())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Verified</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Not verified</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $lead->coupon_code ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if($lead->whatsapp_status === 'sent')
                                    <span class="text-xs text-green-600">Delivered</span>
                                @elseif($lead->whatsapp_status === 'failed')
                                    <span class="text-xs text-red-600">Failed</span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                {{ $lead->created_at->format('d M Y, g:i a') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    @if($lead->isVerified())
                                        <form method="POST" action="{{ route('admin.offer-leads.resend', $lead) }}">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-gray-700 hover:text-gray-900">Resend code</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.offer-leads.destroy', $lead) }}"
                                          onsubmit="return confirm('Delete this lead?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <p class="text-gray-500 text-sm">
                                    @if(request()->hasAny(['search', 'status', 'from', 'to']))
                                        No leads match your filters
                                    @else
                                        No numbers captured yet. They appear here as soon as shoppers submit the popup.
                                    @endif
                                </p>
                                @if(request()->hasAny(['search', 'status', 'from', 'to']))
                                    <a href="{{ route('admin.offer-leads.index') }}" class="inline-block mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">Clear filters</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($leads->hasPages())
            <div class="px-4 py-3 flex items-center justify-between" style="border-top:1px solid #e1e1e1">
                <div class="text-sm text-gray-600">
                    Showing {{ $leads->firstItem() }}-{{ $leads->lastItem() }} of {{ $leads->total() }}
                </div>
                <div class="flex items-center gap-1">
                    @if($leads->onFirstPage())
                        <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded-lg cursor-not-allowed">&lsaquo;</span>
                    @else
                        <a href="{{ $leads->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">&lsaquo;</a>
                    @endif
                    @if($leads->hasMorePages())
                        <a href="{{ $leads->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">&rsaquo;</a>
                    @else
                        <span class="px-3 py-1.5 text-sm text-gray-400 border border-gray-200 rounded-lg cursor-not-allowed">&rsaquo;</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
