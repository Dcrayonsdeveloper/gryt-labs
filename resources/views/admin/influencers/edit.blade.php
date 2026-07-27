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
        </div>
    </div>
</x-layouts.admin>
