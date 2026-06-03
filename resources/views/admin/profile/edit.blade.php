<x-layouts.admin>
    <x-slot name="title">Profile Settings</x-slot>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-neutral-900">Profile Settings</h1>
        <p class="text-neutral-600">Manage your admin account</p>
    </div>
    <form action="{{ route('admin.profile.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Personal Information</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">First Name <span class="text-danger-500">*</span></label>
                                <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required
                                       class="form-input w-full">
                                @error('first_name')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">Last Name <span class="text-danger-500">*</span></label>
                                <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required
                                       class="form-input w-full">
                                @error('last_name')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Email <span class="text-danger-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="form-input w-full">
                            @error('email')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Change Password</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <p class="text-sm text-neutral-600">Leave blank to keep your current password.</p>

                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-1">Current Password</label>
                            <input type="password" name="current_password" class="form-input w-full">
                            @error('current_password')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">New Password</label>
                                <input type="password" name="password" class="form-input w-full">
                                @error('password')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-1">Confirm New Password</label>
                                <input type="password" name="password_confirmation" class="form-input w-full">
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>

            <div class="space-y-6">
                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Account Info</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center">
                                <span class="text-2xl font-bold text-primary-600">
                                    {{ strtoupper(substr($user->first_name, 0, 1)) }}{{ strtoupper(substr($user->last_name, 0, 1)) }}
                                </span>
                            </div>
                            <div>
                                <p class="font-semibold text-neutral-900">{{ $user->first_name }} {{ $user->last_name }}</p>
                                <p class="text-sm text-neutral-600">{{ $user->email }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-4 border-b border-neutral-200">
                        <h2 class="font-semibold text-neutral-900">Role</h2>
                    </div>
                    <div class="p-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Role</span>
                            <span class="badge badge-info">{{ ucwords(str_replace('_', ' ', $admin->role)) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Status</span>
                            @if($admin->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-error">Inactive</span>
                            @endif
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Member Since</span>
                            <span class="font-medium">{{ $admin->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
