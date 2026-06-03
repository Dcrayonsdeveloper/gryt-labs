<x-layouts.admin>
    <x-slot name="title">Reviews</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-900 leading-tight">Reviews</h1>
        </div>
    </x-slot>

    @php
        $currentStatus = request('status', '');
        $pageIds = $reviews->pluck('id')->toArray();
    @endphp

    <div class="bg-white rounded-xl shadow-sm overflow-hidden"
         x-data="{
             selected: [],
             toggleAll(checked) {
                 this.selected = checked ? {{ json_encode($pageIds) }} : [];
             },
             toggle(id) {
                 const idx = this.selected.indexOf(id);
                 idx === -1 ? this.selected.push(id) : this.selected.splice(idx, 1);
             },
             get allChecked() {
                 return this.selected.length === {{ count($pageIds) }} && {{ count($pageIds) }} > 0;
             }
         }">

        {{-- Tab row --}}
        <div class="flex items-center justify-between border-b border-gray-200 px-3">
            <div class="flex items-center gap-0">
                @php
                    $tabs = [
                        '' => 'All',
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ];
                @endphp
                @foreach($tabs as $statusKey => $label)
                    <a href="{{ route('admin.reviews.index', array_merge(request()->except('status', 'page'), $statusKey ? ['status' => $statusKey] : [])) }}"
                       class="relative px-3 py-2.5 text-[13px] font-medium no-underline {{ $currentStatus === $statusKey ? 'text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
                        {{ $label }}
                        @if($currentStatus === $statusKey)
                            <span class="absolute -bottom-px left-3 right-3 h-0.5 bg-gray-900 rounded-sm"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Search row --}}
        <div class="flex items-center gap-2 p-3 border-b border-gray-200">
            <form action="{{ route('admin.reviews.index') }}" method="GET" class="flex items-center gap-2 flex-1" x-data x-on:change="$el.submit()">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="flex-1 relative">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="#8a8a8a" class="absolute left-2.5 top-1/2 -translate-y-1/2"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Filter reviews"
                           class="w-full py-1.5 pl-8 pr-2.5 text-[13px] border border-gray-300 rounded-lg outline-none text-gray-900 bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                           x-on:change.stop>
                </div>
                <select name="rating"
                        class="py-1.5 px-2 text-[13px] border border-gray-300 rounded-lg outline-none text-gray-900 bg-white focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                    <option value="">All ratings</option>
                    @for($r = 5; $r >= 1; $r--)
                        <option value="{{ $r }}" @selected((string) request('rating') === (string) $r)>{{ $r }} star{{ $r === 1 ? '' : 's' }}</option>
                    @endfor
                </select>
                @if(request()->hasAny(['search', 'rating']))
                    <a href="{{ route('admin.reviews.index', request('status') ? ['status' => request('status')] : []) }}"
                       class="px-3 py-1.5 text-[13px] text-gray-800 no-underline whitespace-nowrap">
                        Clear filters
                    </a>
                @endif
            </form>
        </div>

        {{-- Bulk actions --}}
        @include('admin.partials.bulk-action-bar', [
            'route' => route('admin.reviews.bulk-action'),
            'actions' => ['approve' => 'Approve', 'reject' => 'Reject', 'delete' => 'Delete'],
        ])

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="w-9 px-2 py-2 pl-3 text-center">
                            <input type="checkbox"
                                   class="w-4 h-4 cursor-pointer accent-gray-900"
                                   @change="toggleAll($event.target.checked)"
                                   :checked="allChecked">
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Product</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Customer</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Rating</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Date</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $review)
                        <tr class="border-b border-gray-100 cursor-pointer hover:bg-gray-50"
                            x-data="reviewRow({ id: {{ $review->id }}, status: '{{ $review->status }}' })"
                            x-on:click="if(!$event.target.closest('input[type=checkbox],button,a')) window.location.href='{{ route('admin.reviews.show', $review) }}'">
                            <td class="w-9 px-2 py-2 pl-3 text-center" x-on:click.stop>
                                <input type="checkbox"
                                       class="w-4 h-4 cursor-pointer accent-gray-900"
                                       :checked="selected.includes({{ $review->id }})"
                                       @change="toggle({{ $review->id }})">
                            </td>
                            <td class="px-3 py-2">
                                <span class="block text-[13px] font-medium text-gray-900 whitespace-nowrap overflow-hidden text-ellipsis max-w-[220px]" title="{{ $review->product->name ?? 'Deleted Product' }}">
                                    {{ $review->product->name ?? 'Deleted Product' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-[13px] text-gray-800">
                                {{ $review->user ? $review->user->first_name . ' ' . $review->user->last_name : 'Guest' }}
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-0.5">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg width="14" height="14" viewBox="0 0 20 20" fill="{{ $i <= $review->rating ? '#f59e0b' : '#e1e1e1' }}"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    @endfor
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <template x-if="status === 'approved'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-800 inline-block"></span>
                                        Approved
                                    </span>
                                </template>
                                <template x-if="status === 'rejected'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-800 inline-block"></span>
                                        Rejected
                                    </span>
                                </template>
                                <template x-if="status !== 'approved' && status !== 'rejected'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block"></span>
                                        Pending
                                    </span>
                                </template>
                            </td>
                            <td class="px-3 py-2 text-[13px] text-gray-800">
                                {{ $review->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-3 py-2 text-right" x-on:click.stop>
                                <template x-if="status === 'pending'">
                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                        <button type="button"
                                                x-on:click="act('{{ route('admin.reviews.approve', $review) }}', 'approved')"
                                                x-bind:disabled="loading"
                                                class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium bg-green-50 text-green-700 hover:bg-green-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            <span x-text="loading && pending === 'approved' ? 'Approving…' : 'Approve'"></span>
                                        </button>
                                        <button type="button"
                                                x-on:click="act('{{ route('admin.reviews.reject', $review) }}', 'rejected')"
                                                x-bind:disabled="loading"
                                                class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium bg-red-50 text-red-700 hover:bg-red-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                            <span x-text="loading && pending === 'rejected' ? 'Rejecting…' : 'Reject'"></span>
                                        </button>
                                    </div>
                                </template>
                                <template x-if="status === 'approved'">
                                    <span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium bg-green-50 text-green-700">
                                        <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        Approved
                                    </span>
                                </template>
                                <template x-if="status === 'rejected'">
                                    <span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium bg-red-50 text-red-700">
                                        <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                        Rejected
                                    </span>
                                </template>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#c9c9c9" stroke-width="1.5" class="mb-3"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                    <p class="text-sm font-medium text-gray-900 mb-1">No reviews found</p>
                                    <p class="text-[13px] text-gray-600 mb-4">
                                        @if(request()->hasAny(['search', 'status']))
                                            Try changing the filters or search term.
                                        @else
                                            Reviews will appear here when customers submit them.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($reviews->total() > 0)
            <div class="flex items-center justify-center gap-4 p-3 border-t border-gray-200">
                <div class="flex items-center gap-2">
                    @if($reviews->onFirstPage())
                        <span class="p-1.5 text-gray-300 cursor-not-allowed">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </span>
                    @else
                        <a href="{{ $reviews->previousPageUrl() }}" class="inline-flex p-1.5 text-gray-600 hover:bg-gray-100 rounded-md no-underline">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </a>
                    @endif

                    <span class="text-[13px] text-gray-800">
                        {{ $reviews->firstItem() }}-{{ $reviews->lastItem() }} of {{ $reviews->total() }}
                    </span>

                    @if($reviews->hasMorePages())
                        <a href="{{ $reviews->nextPageUrl() }}" class="inline-flex p-1.5 text-gray-600 hover:bg-gray-100 rounded-md no-underline">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                        </a>
                    @else
                        <span class="p-1.5 text-gray-300 cursor-not-allowed">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function reviewRow(opts) {
            return {
                id: opts.id,
                status: opts.status,
                loading: false,
                pending: null,
                async act(url, nextStatus) {
                    if (this.loading) return;
                    const previous = this.status;
                    this.loading = true;
                    this.pending = nextStatus;
                    // optimistic
                    this.status = nextStatus;
                    try {
                        const form = new FormData();
                        form.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                        const res = await fetch(url, {
                            method: 'POST',
                            body: form,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.message || 'Failed');
                        this.status = data.status || nextStatus;
                    } catch (e) {
                        this.status = previous;
                        alert('Could not update review. Please try again.');
                    } finally {
                        this.loading = false;
                        this.pending = null;
                    }
                },
            };
        }
    </script>
    @endpush
</x-layouts.admin>
