<x-layouts.admin>
    <x-slot name="title">Create Gift Card</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.gift-cards.index') }}" class="hover:text-primary-600">Gift Cards</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">Create Gift Card</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Create Gift Card</h1>
    </div>

    <form action="{{ route('admin.gift-cards.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                {{-- Gift Card Details --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Gift Card Details</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label for="initial_balance" class="form-label">Amount (INR) <span class="text-error-500">*</span></label>
                            <input type="number" name="initial_balance" id="initial_balance" value="{{ old('initial_balance') }}" required
                                   step="0.01" min="1" max="100000"
                                   class="form-input w-full" placeholder="e.g. 500">
                            @error('initial_balance')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="expires_at" class="form-label">Expiry Date</label>
                            <input type="date" name="expires_at" id="expires_at" value="{{ old('expires_at') }}"
                                   min="{{ now()->addDay()->format('Y-m-d') }}"
                                   class="form-input w-full">
                            <p class="text-xs text-gray-500 mt-1">Leave blank for no expiry.</p>
                            @error('expires_at')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Recipient --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Recipient</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="recipient_name" class="form-label">Recipient Name <span class="text-error-500">*</span></label>
                                <input type="text" name="recipient_name" id="recipient_name" value="{{ old('recipient_name') }}" required
                                       class="form-input w-full" placeholder="John Doe">
                                @error('recipient_name')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="recipient_email" class="form-label">Recipient Email <span class="text-error-500">*</span></label>
                                <input type="email" name="recipient_email" id="recipient_email" value="{{ old('recipient_email') }}" required
                                       class="form-input w-full" placeholder="john@example.com">
                                @error('recipient_email')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="purchaser_email" class="form-label">Purchaser Email</label>
                            <input type="email" name="purchaser_email" id="purchaser_email" value="{{ old('purchaser_email') }}"
                                   class="form-input w-full" placeholder="buyer@example.com">
                            @error('purchaser_email')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="message" class="form-label">Personal Message</label>
                            <textarea name="message" id="message" rows="3"
                                      class="form-input w-full" placeholder="A personal message for the recipient...">{{ old('message') }}</textarea>
                            @error('message')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <div class="card">
                    <div class="p-4 space-y-4">
                        <p class="text-sm text-gray-500">A unique 16-character code will be auto-generated for this gift card.</p>
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                            Create Gift Card
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
