<x-layouts.admin title="Add Influencer">
    <div class="max-w-3xl">
        <div class="mb-4">
            <a href="{{ route('admin.influencers.index') }}" class="text-sm text-neutral-500 hover:underline">&larr; Back to Influencers</a>
        </div>
        <div class="card p-6">
            <h1 class="text-lg font-semibold text-neutral-900 mb-5">New Influencer</h1>
            <form method="POST" action="{{ route('admin.influencers.store') }}">
                @csrf
                @include('admin.influencers._form')
                <div class="mt-6 flex items-center gap-3">
                    <button type="submit" class="btn btn-primary">Create Influencer</button>
                    <a href="{{ route('admin.influencers.index') }}" class="text-sm text-neutral-500 hover:underline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
