<x-layouts.admin>
    <x-slot name="title">Add Delivery Partner</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.delivery-partners.index') }}" class="hover:text-primary-600">Delivery Partners</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-neutral-900">Add Partner</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Add Delivery Partner</h1>
    </div>

    <form action="{{ route('admin.delivery-partners.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                {{-- Personal Details --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Personal Details</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="first_name" class="form-label">First Name <span class="text-error-500">*</span></label>
                                <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required class="form-input w-full">
                                @error('first_name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="last_name" class="form-label">Last Name <span class="text-error-500">*</span></label>
                                <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required class="form-input w-full">
                                @error('last_name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="email" class="form-label">Email <span class="text-error-500">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required class="form-input w-full">
                                @error('email') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="phone" class="form-label">Phone <span class="text-error-500">*</span></label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required class="form-input w-full">
                                @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="form-label">Password <span class="text-error-500">*</span></label>
                                <input type="password" name="password" id="password" required class="form-input w-full">
                                @error('password') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="form-label">Confirm Password <span class="text-error-500">*</span></label>
                                <input type="password" name="password_confirmation" id="password_confirmation" required class="form-input w-full">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Vehicle Details --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Vehicle Details</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="vehicle_type" class="form-label">Vehicle Type <span class="text-error-500">*</span></label>
                                <select name="vehicle_type" id="vehicle_type" required class="form-select w-full">
                                    <option value="bike" @selected(old('vehicle_type') === 'bike')>Bike</option>
                                    <option value="scooter" @selected(old('vehicle_type') === 'scooter')>Scooter</option>
                                    <option value="van" @selected(old('vehicle_type') === 'van')>Van</option>
                                    <option value="truck" @selected(old('vehicle_type') === 'truck')>Truck</option>
                                    <option value="other" @selected(old('vehicle_type') === 'other')>Other</option>
                                </select>
                                @error('vehicle_type') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="vehicle_number" class="form-label">Vehicle Number</label>
                                <input type="text" name="vehicle_number" id="vehicle_number" value="{{ old('vehicle_number') }}" class="form-input w-full" placeholder="e.g. MH 02 AB 1234">
                                @error('vehicle_number') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="license_number" class="form-label">License Number</label>
                                <input type="text" name="license_number" id="license_number" value="{{ old('license_number') }}" class="form-input w-full">
                                @error('license_number') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" class="form-input w-full" placeholder="Optional">
                                @error('company_name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="space-y-6">
                {{-- Status --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Status</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                            <div>
                                <span class="text-sm font-medium text-neutral-900">Active</span>
                                <p class="text-xs text-neutral-600">Partner can receive and deliver orders</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col gap-3">
                    <button type="submit" class="btn btn-primary w-full justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Create Partner
                    </button>
                    <a href="{{ route('admin.delivery-partners.index') }}" class="btn btn-secondary w-full text-center">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
