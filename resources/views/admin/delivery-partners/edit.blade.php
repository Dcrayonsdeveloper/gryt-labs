<x-layouts.admin>
    <x-slot name="title">Edit Delivery Partner</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.delivery-partners.index') }}" class="hover:text-primary-600">Delivery Partners</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-neutral-900">Edit {{ $deliveryPartner->user->full_name }}</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Edit Delivery Partner</h1>
    </div>

    <form action="{{ route('admin.delivery-partners.update', $deliveryPartner) }}" method="POST">
        @csrf
        @method('PUT')

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
                                <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $deliveryPartner->user->first_name) }}" required class="form-input w-full">
                                @error('first_name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="last_name" class="form-label">Last Name <span class="text-error-500">*</span></label>
                                <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $deliveryPartner->user->last_name) }}" required class="form-input w-full">
                                @error('last_name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="email" class="form-label">Email <span class="text-error-500">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email', $deliveryPartner->user->email) }}" required class="form-input w-full">
                                @error('email') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="phone" class="form-label">Phone <span class="text-error-500">*</span></label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $deliveryPartner->phone) }}" required class="form-input w-full">
                                @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" name="password" id="password" class="form-input w-full" placeholder="Leave blank to keep current">
                                @error('password') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-input w-full">
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
                                    <option value="bike" @selected(old('vehicle_type', $deliveryPartner->vehicle_type) === 'bike')>Bike</option>
                                    <option value="scooter" @selected(old('vehicle_type', $deliveryPartner->vehicle_type) === 'scooter')>Scooter</option>
                                    <option value="van" @selected(old('vehicle_type', $deliveryPartner->vehicle_type) === 'van')>Van</option>
                                    <option value="truck" @selected(old('vehicle_type', $deliveryPartner->vehicle_type) === 'truck')>Truck</option>
                                    <option value="other" @selected(old('vehicle_type', $deliveryPartner->vehicle_type) === 'other')>Other</option>
                                </select>
                                @error('vehicle_type') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="vehicle_number" class="form-label">Vehicle Number</label>
                                <input type="text" name="vehicle_number" id="vehicle_number" value="{{ old('vehicle_number', $deliveryPartner->vehicle_number) }}" class="form-input w-full">
                                @error('vehicle_number') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="license_number" class="form-label">License Number</label>
                                <input type="text" name="license_number" id="license_number" value="{{ old('license_number', $deliveryPartner->license_number) }}" class="form-input w-full">
                                @error('license_number') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $deliveryPartner->company_name) }}" class="form-input w-full">
                                @error('company_name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                {{-- Info --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Partner Info</h2>
                    </div>
                    <div class="p-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-neutral-600">Partner ID</span>
                            <span class="font-mono font-medium text-neutral-900">{{ $deliveryPartner->partner_id }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-neutral-600">Joined</span>
                            <span class="text-neutral-700">{{ $deliveryPartner->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between text-sm items-center">
                            <span class="text-neutral-600">Verification</span>
                            @php
                                $vBadge = match($deliveryPartner->verification_status) {
                                    'verified' => 'badge-success',
                                    'rejected' => 'badge-error',
                                    default => 'badge-warning',
                                };
                            @endphp
                            <span class="badge {{ $vBadge }}">{{ ucfirst($deliveryPartner->verification_status) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Verification --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Verification</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <div>
                            <label class="form-label">Verification Status</label>
                            <select name="verification_status" class="form-select w-full">
                                <option value="pending" @selected(old('verification_status', $deliveryPartner->verification_status) === 'pending')>Pending</option>
                                <option value="verified" @selected(old('verification_status', $deliveryPartner->verification_status) === 'verified')>Verified</option>
                                <option value="rejected" @selected(old('verification_status', $deliveryPartner->verification_status) === 'rejected')>Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Verification Note</label>
                            <textarea name="verification_note" rows="2" class="form-textarea w-full" placeholder="Reason for approval/rejection...">{{ old('verification_note', $deliveryPartner->verification_note) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Documents (uploaded by partner) --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Documents</h2>
                        <p class="text-xs text-neutral-600">Uploaded by the delivery partner</p>
                    </div>
                    <div class="p-4 space-y-3">
                        <div class="flex justify-between text-sm items-center">
                            <span class="text-neutral-600">ID Proof (Aadhaar/PAN)</span>
                            @if($deliveryPartner->id_proof)
                                <a href="{{ asset('storage/' . $deliveryPartner->id_proof) }}" target="_blank" class="text-primary-600 hover:text-primary-700 font-medium text-xs">View Document</a>
                            @else
                                <span class="badge badge-error">Not Uploaded</span>
                            @endif
                        </div>
                        <div class="flex justify-between text-sm items-center">
                            <span class="text-neutral-600">Driving License</span>
                            @if($deliveryPartner->license_document)
                                <a href="{{ asset('storage/' . $deliveryPartner->license_document) }}" target="_blank" class="text-primary-600 hover:text-primary-700 font-medium text-xs">View Document</a>
                            @else
                                <span class="badge badge-error">Not Uploaded</span>
                            @endif
                        </div>
                        <div class="flex justify-between text-sm items-center">
                            <span class="text-neutral-600">Address Proof</span>
                            @if($deliveryPartner->address_proof)
                                <a href="{{ asset('storage/' . $deliveryPartner->address_proof) }}" target="_blank" class="text-primary-600 hover:text-primary-700 font-medium text-xs">View Document</a>
                            @else
                                <span class="text-xs text-neutral-600">Not uploaded</span>
                            @endif
                        </div>
                        @if(!$deliveryPartner->hasDocuments())
                            <div class="mt-2 p-2 bg-warning-50 rounded-lg">
                                <p class="text-xs text-warning-700">Partner has not uploaded mandatory documents yet.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Status --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="font-semibold text-neutral-900">Status</h2>
                    </div>
                    <div class="p-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $deliveryPartner->is_active)) class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update Partner
                    </button>
                    <a href="{{ route('admin.delivery-partners.index') }}" class="btn btn-secondary w-full text-center">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
