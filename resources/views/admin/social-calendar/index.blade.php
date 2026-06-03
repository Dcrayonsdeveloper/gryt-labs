<x-layouts.admin>
    <x-slot name="title">Social Calendar</x-slot>

    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold text-gray-900">Social Calendar</h1>
        <a href="{{ route('admin.social-calendar.create') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            + New Post
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">{{ session('error') }}</div>
    @endif

    {{-- Calendar View --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 p-4"
         x-data="socialCalendar()" x-init="init()">
        <div class="flex items-center justify-between mb-4">
            <button @click="prevMonth()" class="px-3 py-1 text-sm border rounded hover:bg-gray-50">&larr;</button>
            <h2 class="text-lg font-semibold" x-text="monthLabel"></h2>
            <button @click="nextMonth()" class="px-3 py-1 text-sm border rounded hover:bg-gray-50">&rarr;</button>
        </div>

        <div class="grid grid-cols-7 gap-px bg-gray-200 rounded-lg overflow-hidden">
            <template x-for="day in ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']">
                <div class="bg-gray-50 text-center text-xs font-medium text-gray-500 py-2" x-text="day"></div>
            </template>
            <template x-for="cell in calendarCells" :key="cell.date">
                <div class="bg-white min-h-[80px] p-1 text-xs"
                     :class="cell.isCurrentMonth ? '' : 'opacity-40'">
                    <div class="font-medium text-gray-700 mb-1" x-text="cell.day"></div>
                    <template x-for="event in cell.events" :key="event.id">
                        <a :href="event.url"
                           class="block text-[10px] px-1 py-0.5 rounded mb-0.5 truncate text-white"
                           :style="'background:' + event.color"
                           :title="event.title"
                           x-text="event.title"></a>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- List View --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="flex items-center gap-0 px-4" style="border-bottom:1px solid #e1e1e1">
            @php $tabs = ['all' => "All ({$counts['all']})", 'scheduled' => "Scheduled ({$counts['scheduled']})", 'published' => "Published ({$counts['published']})", 'draft' => "Drafts ({$counts['draft']})", 'failed' => "Failed ({$counts['failed']})"]; @endphp
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.social-calendar.index', $key === 'all' ? [] : ['status' => $key]) }}"
                   class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ ($status ?? null) === ($key === 'all' ? null : $key) || (!$status && $key === 'all') ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Post</th>
                        <th class="px-4 py-3 text-left">Platforms</th>
                        <th class="px-4 py-3 text-left">Scheduled</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($posts as $post)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($post->media_urls && count($post->media_urls))
                                    <img src="{{ $post->media_urls[0] }}" class="w-10 h-10 rounded object-cover" alt="">
                                @else
                                    <div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center text-gray-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5"/></svg>
                                    </div>
                                @endif
                                <div>
                                    <div class="font-medium text-gray-900">{{ $post->title }}</div>
                                    <div class="text-gray-500 text-xs truncate max-w-[200px]" title="{{ $post->caption }}">{{ Str::limit($post->caption, 60) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-1 flex-wrap">
                                @foreach($post->platforms as $p)
                                    <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded
                                        {{ str_starts_with($p, 'ig_') ? 'bg-pink-100 text-pink-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ \App\Models\SocialMediaPost::PLATFORMS[$p] ?? $p }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ $post->scheduled_at?->format('M d, Y h:i A') ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full
                                {{ match($post->status) {
                                    'published' => 'bg-green-100 text-green-700',
                                    'scheduled' => 'bg-blue-100 text-blue-700',
                                    'draft' => 'bg-gray-100 text-gray-600',
                                    'failed','partially_failed' => 'bg-red-100 text-red-700',
                                    'publishing' => 'bg-yellow-100 text-yellow-700',
                                    default => 'bg-gray-100 text-gray-600',
                                } }}">
                                {{ ucfirst($post->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if(in_array($post->status, ['draft', 'scheduled', 'failed', 'partially_failed']))
                                    <a href="{{ route('admin.social-calendar.edit', $post) }}" class="text-gray-500 hover:text-gray-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                    </a>
                                @endif
                                @if(in_array($post->status, ['draft', 'scheduled']))
                                    <form action="{{ route('admin.social-calendar.publish-now', $post) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-800" title="Publish Now">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                                        </button>
                                    </form>
                                @endif
                                @if(in_array($post->status, ['failed', 'partially_failed']))
                                    <form action="{{ route('admin.social-calendar.retry', $post) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-orange-600 hover:text-orange-800" title="Retry">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.social-calendar.destroy', $post) }}" method="POST" class="inline" onsubmit="return confirm('Delete this post?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">No posts yet. Create your first post!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($posts->hasPages())
            <div class="px-4 py-3 border-t">{{ $posts->links() }}</div>
        @endif
    </div>

    @push('scripts')
    <script>
    function socialCalendar() {
        return {
            currentDate: new Date(),
            events: [],
            calendarCells: [],
            get monthLabel() {
                return this.currentDate.toLocaleString('en-US', { month: 'long', year: 'numeric' });
            },
            async init() {
                await this.fetchEvents();
                this.buildCalendar();
            },
            async fetchEvents() {
                const start = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), 1);
                const end = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 0);
                const res = await fetch(`{{ route('admin.social-calendar.calendar-data') }}?start=${start.toISOString().split('T')[0]}&end=${end.toISOString().split('T')[0]}`);
                this.events = await res.json();
                this.buildCalendar();
            },
            buildCalendar() {
                const year = this.currentDate.getFullYear();
                const month = this.currentDate.getMonth();
                const firstDay = new Date(year, month, 1);
                const lastDay = new Date(year, month + 1, 0);
                let startDay = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;

                const cells = [];
                // Previous month padding
                for (let i = startDay - 1; i >= 0; i--) {
                    const d = new Date(year, month, -i);
                    cells.push({ date: d.toISOString().split('T')[0], day: d.getDate(), isCurrentMonth: false, events: [] });
                }
                // Current month
                for (let d = 1; d <= lastDay.getDate(); d++) {
                    const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                    const dayEvents = this.events.filter(e => e.start && e.start.startsWith(dateStr));
                    cells.push({ date: dateStr, day: d, isCurrentMonth: true, events: dayEvents });
                }
                // Next month padding
                while (cells.length % 7 !== 0) {
                    const d = new Date(year, month + 1, cells.length - startDay - lastDay.getDate() + 1);
                    cells.push({ date: d.toISOString().split('T')[0], day: d.getDate(), isCurrentMonth: false, events: [] });
                }
                this.calendarCells = cells;
            },
            prevMonth() { this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1); this.fetchEvents(); },
            nextMonth() { this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1); this.fetchEvents(); },
        };
    }
    </script>
    @endpush
</x-layouts.admin>
