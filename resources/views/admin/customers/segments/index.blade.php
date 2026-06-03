<x-layouts.admin>
    <x-slot name="title">Customer Segments</x-slot>

    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold text-gray-900">Customer Segments</h1>
        <a href="{{ route('admin.customer-segments.create') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            Create segment
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($segments as $segment)
            <a href="{{ route('admin.customer-segments.show', $segment) }}"
               class="block bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $segment->name }}</h3>
                        @if($segment->description)
                            <p class="text-sm text-gray-500 mt-1">{{ $segment->description }}</p>
                        @endif
                    </div>
                    @if($segment->is_auto)
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Auto</span>
                    @endif
                </div>
                <div class="mt-4 flex items-center gap-4">
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($segment->customer_count) }}</p>
                        <p class="text-xs text-gray-500">customers</p>
                    </div>
                </div>
                @if($segment->conditions)
                    <div class="mt-3 flex flex-wrap gap-1">
                        @foreach($segment->conditions as $key => $value)
                            @if($key !== 'use_or')
                                <span class="inline-flex px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-600">
                                    {{ str_replace('_', ' ', $key) }}: {{ $value }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                @endif
            </a>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-500">
                No segments yet. Create your first customer segment.
            </div>
        @endforelse
    </div>
</x-layouts.admin>
