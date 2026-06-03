<x-layouts.admin>
    <x-slot name="title">Staff</x-slot>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold text-gray-900">Staff</h1>
        <a href="{{ route('admin.staff.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            Add staff
        </a>
    </div>

    {{-- Main card --}}
    <div class="bg-white rounded-xl shadow-sm" x-data="{ checkAll: false }">

        {{-- Tabs --}}
        <div class="flex gap-0 px-4" style="border-bottom:1px solid #e1e1e1">
            <a href="{{ route('admin.staff.index') }}"
               class="relative px-4 py-3 text-sm font-medium text-gray-900">
                All
                <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-gray-900 rounded-t"></span>
            </a>
        </div>

        {{-- Search row --}}
        <div class="flex items-center gap-2 p-4" style="border-bottom:1px solid #e1e1e1">
            <form action="{{ route('admin.staff.index') }}" method="GET" class="flex items-center gap-2 flex-1">
                <div class="relative flex-1 max-w-md">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search staff"
                           class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                </div>
                <button type="submit" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Search
                </button>
                @if(request('search'))
                    <a href="{{ route('admin.staff.index') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50" style="border-bottom:1px solid #e1e1e1">
                        <th class="w-10 px-4 py-3 text-left">
                            <input type="checkbox" class="rounded border-gray-300" x-model="checkAll">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last login</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staff as $member)
                        <tr class="hover:bg-gray-50 cursor-pointer" style="border-bottom:1px solid #e1e1e1"
                            onclick="window.location='{{ route('admin.staff.edit', $member) }}'">
                            <td class="px-4 py-3" onclick="event.stopPropagation()">
                                <input type="checkbox" class="rounded border-gray-300" :checked="checkAll">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center shrink-0">
                                        <span class="text-xs font-medium text-gray-600">{{ strtoupper(substr($member->user->first_name ?? '', 0, 1) . substr($member->user->last_name ?? '', 0, 1)) }}</span>
                                    </div>
                                    <span class="font-medium text-gray-900">{{ $member->user->full_name ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $member->user->email ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ ucfirst(str_replace('_', ' ', $member->role ?? 'staff')) }}</td>
                            <td class="px-4 py-3">
                                @if($member->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-green-50 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                @if($member->user->last_login_at ?? null)
                                    {{ $member->user->last_login_at->format('M d, Y') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-500">No staff members found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($staff->hasPages())
            <div class="px-4 py-3 flex items-center justify-between" style="border-top:1px solid #e1e1e1">
                <p class="text-sm text-gray-500">
                    Showing {{ $staff->firstItem() }}–{{ $staff->lastItem() }} of {{ $staff->total() }}
                </p>
                <div class="flex items-center gap-1">
                    @if($staff->onFirstPage())
                        <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg">&lsaquo; Previous</span>
                    @else
                        <a href="{{ $staff->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">&lsaquo; Previous</a>
                    @endif
                    @if($staff->hasMorePages())
                        <a href="{{ $staff->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Next &rsaquo;</a>
                    @else
                        <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg">Next &rsaquo;</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
