<x-layouts.admin title="Edit Influencer">
    <div class="max-w-3xl">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('admin.influencers.index') }}" class="text-sm text-neutral-500 hover:underline">&larr; Back to Influencers</a>
            <a href="{{ route('admin.influencers.analytics', $influencer) }}" class="text-sm text-primary-600 hover:underline">View Analytics &rarr;</a>
        </div>
        <div class="card p-6">
            <div class="flex items-center justify-between mb-5">
                <h1 class="text-lg font-semibold text-neutral-900">Edit {{ $influencer->full_name }}</h1>
                <span class="text-xs text-neutral-400">Login: <span class="font-mono">/influencer/login</span></span>
            </div>
            <form method="POST" action="{{ route('admin.influencers.update', $influencer) }}">
                @csrf
                @method('PUT')
                @include('admin.influencers._form')
                <div class="mt-6 flex items-center gap-3">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('admin.influencers.index') }}" class="text-sm text-neutral-500 hover:underline">Cancel</a>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-neutral-200 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-neutral-900">Delete influencer</p>
                    <p class="text-xs text-neutral-500">Removes the login. Coupon <span class="font-mono">{{ $influencer->coupon_code }}</span> is deactivated but kept for historical orders.</p>
                </div>
                <form method="POST" action="{{ route('admin.influencers.destroy', $influencer) }}"
                      onsubmit="return confirm('Delete influencer {{ $influencer->full_name }}? Their coupon stays for historical orders but is deactivated.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Influencer</button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>
