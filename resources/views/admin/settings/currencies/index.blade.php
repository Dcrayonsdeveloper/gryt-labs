<x-layouts.admin>
    <x-slot name="title">Currencies</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Currencies</h1>
            <p class="text-neutral-600">Manage store currencies</p>
        </div>
        <a href="{{ route('admin.settings.currencies.create') }}" class="btn btn-primary">Add Currency</a>
    </div>
    <div class="card overflow-hidden">
        @if($currencies->total() > 0)
            <div class="px-5 py-3 border-b border-neutral-200">
                {{ $currencies->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Symbol</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Exchange Rate</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($currencies as $currency)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 font-medium text-neutral-900">
                                {{ $currency->code }}
                                @if($currency->is_default)
                                    <span class="badge badge-primary ml-1">Default</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $currency->name }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ $currency->symbol }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ $currency->exchange_rate }}</td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $currency->is_active ? 'badge-success' : 'badge-neutral' }}">
                                    {{ $currency->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.settings.currencies.edit', $currency) }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">Edit</a>
                                    @unless($currency->is_default)
                                        <form action="{{ route('admin.settings.currencies.destroy', $currency) }}" method="POST" onsubmit="return confirm('Delete this currency?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-error-600 hover:text-error-700 text-sm font-medium">Delete</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-neutral-600">
                                No currencies configured.
                                <a href="{{ route('admin.settings.currencies.create') }}" class="text-primary-600 font-medium ml-1">Add one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($currencies->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">{{ $currencies->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
