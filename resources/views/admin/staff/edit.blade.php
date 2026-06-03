<x-layouts.admin>
    <x-slot name="title">Edit Staff</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.staff.index') }}" class="hover:text-primary-600">Staff</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">Edit: {{ $staff->user->full_name ?? 'Staff' }}</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Edit Staff: {{ $staff->user->full_name ?? 'Staff' }}</h1>
    </div>

    <form action="{{ route('admin.staff.update', $staff) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Personal Details</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">First Name <span class="text-danger-500">*</span></label>
                                <input type="text" name="first_name" value="{{ old('first_name', $staff->user->first_name) }}" required
                                       class="form-input w-full">
                                @error('first_name')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">Last Name <span class="text-danger-500">*</span></label>
                                <input type="text" name="last_name" value="{{ old('last_name', $staff->user->last_name) }}" required
                                       class="form-input w-full">
                                @error('last_name')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Email <span class="text-danger-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $staff->user->email) }}" required
                                   class="form-input w-full">
                            @error('email')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">New Password</label>
                                <input type="password" name="password" class="form-input w-full" placeholder="Leave blank to keep current">
                                @error('password')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-input w-full">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Role & Status</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Role <span class="text-danger-500">*</span></label>
                            <select name="role" class="form-select w-full" required>
                                <option value="manager" @selected(old('role', $staff->role) === 'manager')>Manager</option>
                                <option value="cashier" @selected(old('role', $staff->role) === 'cashier')>Cashier</option>
                                <option value="support" @selected(old('role', $staff->role) === 'support')>Support</option>
                                <option value="warehouse" @selected(old('role', $staff->role) === 'warehouse')>Warehouse</option>
                            </select>
                            @error('role')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active"
                                   class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                                   @checked(old('is_active', $staff->is_active))>
                            <label for="is_active" class="text-sm font-medium text-neutral-700">Active</label>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Permissions</h2>
                    </div>
                    <div class="p-4 space-y-2">
                        <p class="text-xs text-neutral-600 mb-3">Override default role permissions. Leave all unchecked to use role defaults.</p>
                        @php
                            $sections = [
                                'dashboard' => 'Dashboard',
                                'orders' => 'Orders & Returns',
                                'catalog' => 'Catalog & Inventory',
                                'customers' => 'Customers',
                                'sellers' => 'Sellers',
                                'staff' => 'Staff Management',
                                'marketing' => 'Marketing',
                                'storefront' => 'Storefront',
                                'content' => 'Content & Reviews',
                                'reports' => 'Reports',
                                'settings' => 'Settings',
                            ];
                            $currentPerms = old('permissions', $staff->permissions ?? []);
                        @endphp
                        @foreach($sections as $key => $label)
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="permissions[]" value="{{ $key }}"
                                       class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                                       @checked(is_array($currentPerms) && in_array($key, $currentPerms))>
                                <span class="text-sm text-neutral-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Info</h2>
                    </div>
                    <div class="p-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Created</span>
                            <span class="font-medium">{{ $staff->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Updated</span>
                            <span class="font-medium">{{ $staff->updated_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Actions</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <button type="submit" class="btn btn-primary w-full">Update Staff</button>
                        <a href="{{ route('admin.staff.index') }}" class="btn btn-secondary w-full text-center">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
