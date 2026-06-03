<div class="flex items-center justify-between p-2.5 {{ $indent ? 'bg-white ml-6 border-l-2 border-neutral-200' : 'bg-neutral-50' }} rounded-lg group">
    <div class="flex items-center gap-3 flex-1 min-w-0">
        <span class="w-1.5 h-1.5 rounded-full {{ $item->is_active ? 'bg-success-500' : 'bg-neutral-300' }} shrink-0"></span>
        <div class="min-w-0">
            <span class="text-sm font-medium {{ $item->is_active ? 'text-neutral-900' : 'text-neutral-400 line-through' }}">{{ $item->label }}</span>
            <span class="text-xs text-neutral-500 ml-2">{{ $item->url }}</span>
            @if($item->open_in_new_tab)<span class="text-[10px] text-neutral-400 ml-1">↗</span>@endif
            @if($item->children && $item->children->count())<span class="text-[10px] text-primary-600 ml-1">({{ $item->children->count() }} sub)</span>@endif
        </div>
    </div>
    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
        {{-- Toggle active --}}
        <form action="{{ route('admin.homepage.navigation.update', $item) }}" method="POST" class="inline">
            @csrf @method('PUT')
            <input type="hidden" name="label" value="{{ $item->label }}">
            <input type="hidden" name="url" value="{{ $item->url }}">
            <input type="hidden" name="location" value="{{ $item->location }}">
            <input type="hidden" name="is_active" value="{{ $item->is_active ? '0' : '1' }}">
            <button type="submit" class="px-1.5 py-0.5 text-[10px] font-medium rounded {{ $item->is_active ? 'bg-success-50 text-success-700 hover:bg-warning-50 hover:text-warning-700' : 'bg-neutral-100 text-neutral-500 hover:bg-success-50 hover:text-success-700' }}" title="{{ $item->is_active ? 'Deactivate' : 'Activate' }}">
                {{ $item->is_active ? 'ON' : 'OFF' }}
            </button>
        </form>
        {{-- Edit --}}
        <button type="button"
                @click="edit({{ $item->toJson() }})"
                class="p-1.5 text-neutral-400 hover:text-primary-600 rounded hover:bg-primary-50" title="Edit">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        </button>
        {{-- Delete --}}
        <form action="{{ route('admin.homepage.navigation.destroy', $item) }}" method="POST" onsubmit="return confirm('Remove {{ addslashes($item->label) }}?')" class="inline">
            @csrf @method('DELETE')
            <button class="p-1.5 text-neutral-400 hover:text-error-600 rounded hover:bg-error-50" title="Remove">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </form>
    </div>
</div>
