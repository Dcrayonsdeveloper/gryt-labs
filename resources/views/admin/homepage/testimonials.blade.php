<x-layouts.admin>
    <x-slot name="title">Testimonials</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Testimonials</h1>
                <p class="text-sm text-neutral-600 mt-1">Manage customer testimonials displayed on the homepage</p>
            </div>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary">Back to Homepage</a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add Testimonial -->
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-neutral-900 mb-4">Add Testimonial</h2>

            <form action="{{ route('admin.homepage.testimonials.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="form-label form-label-required">Customer Name</label>
                        <input type="text" name="name" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Title/Role</label>
                        <input type="text" name="title" class="form-input" placeholder="e.g. Happy Parent">
                    </div>
                    <div>
                        <label class="form-label form-label-required">Review</label>
                        <textarea name="content" rows="4" required class="form-input"></textarea>
                    </div>
                    <div>
                        <label class="form-label form-label-required">Rating</label>
                        <select name="rating" class="form-select">
                            <option value="5">5 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="3">3 Stars</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Product Name</label>
                        <input type="text" name="product_name" class="form-input" placeholder="e.g. Velvet Matte Lipstick">
                    </div>
                    <div>
                        <label class="form-label">Avatar Photo</label>
                        <input type="file" name="avatar" accept="image/*" class="form-input">
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Add Testimonial</button>
                </div>
            </form>
        </div>

        <!-- Existing Testimonials -->
        <div class="lg:col-span-2 space-y-4">
            @forelse($testimonials as $testimonial)
                <div class="card p-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center shrink-0 overflow-hidden">
                            @if($testimonial->avatar_url)
                                <img src="{{ asset('storage/' . $testimonial->avatar_url) }}" alt="{{ $testimonial->name }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-primary-600 font-semibold text-lg">{{ substr($testimonial->name, 0, 1) }}</span>
                            @endif
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="font-semibold text-neutral-900">{{ $testimonial->name }}</h3>
                                <div class="flex items-center gap-2">
                                    <span class="badge {{ $testimonial->is_active ? 'badge-success' : 'badge-neutral' }}">
                                        {{ $testimonial->is_active ? 'Active' : 'Hidden' }}
                                    </span>
                                </div>
                            </div>
                            @if($testimonial->title)
                                <p class="text-sm text-neutral-600">{{ $testimonial->title }}</p>
                            @endif
                            <div class="flex items-center gap-1 my-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-4 h-4 {{ $i <= $testimonial->rating ? 'text-warning-400' : 'text-neutral-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                            <p class="text-sm text-neutral-600 mt-1">"{{ $testimonial->content }}"</p>
                            @if($testimonial->product_name)
                                <p class="text-xs text-neutral-600 mt-1">Product: {{ $testimonial->product_name }}</p>
                            @endif
                            <div class="flex items-center gap-2 mt-3">
                                <form action="{{ route('admin.homepage.testimonials.toggle', $testimonial) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm {{ $testimonial->is_active ? 'btn-secondary' : 'btn-success' }}">
                                        {{ $testimonial->is_active ? 'Hide' : 'Show' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.homepage.testimonials.destroy', $testimonial) }}" method="POST" class="inline" onsubmit="return confirm('Delete this testimonial?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card p-12 text-center">
                    <p class="text-neutral-600">No testimonials yet. Add your first customer review.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.admin>
