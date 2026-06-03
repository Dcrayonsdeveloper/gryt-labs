<x-super-admin.layouts.app title="Create Tenant">
    <div class="px-6 py-6 max-w-2xl">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Create New Tenant</h1>

        <form method="POST" action="{{ route('super-admin.tenants.store') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tenant ID *</label>
                    <input type="text" name="id" value="{{ old('id') }}" required placeholder="e.g. mystore"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <p class="text-[10px] text-gray-400 mt-1">Unique slug. Used for database name (tenant_mystore)</p>
                    @error('id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Store Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="My Store"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Domain *</label>
                    <input type="text" name="domain" value="{{ old('domain') }}" required placeholder="mystore.in"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('domain') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Plan *</label>
                    <select name="plan" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="free">Free</option>
                        <option value="standard" selected>Standard</option>
                        <option value="premium">Premium</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>
            </div>

            <hr class="border-gray-200">
            <h3 class="text-sm font-bold text-gray-700">Store Configuration</h3>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand Name</label>
                    <input type="text" name="brand_name" value="{{ old('brand_name') }}" placeholder="Display name"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Support Email</label>
                    <input type="email" name="support_email" value="{{ old('support_email') }}" placeholder="support@mystore.in"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Support Phone</label>
                <input type="text" name="support_phone" value="{{ old('support_phone') }}" placeholder="+91 9876543210"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <hr class="border-gray-200">
            <h3 class="text-sm font-bold text-gray-700">Payment & Shipping (optional — can configure later)</h3>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Razorpay Key ID</label>
                    <input type="text" name="razorpay_key_id" value="{{ old('razorpay_key_id') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Razorpay Key Secret</label>
                    <input type="password" name="razorpay_key_secret" value="{{ old('razorpay_key_secret') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Delhivery API Token</label>
                <input type="text" name="delhivery_api_token" value="{{ old('delhivery_api_token') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-lg text-sm hover:bg-indigo-700">
                    Create Tenant
                </button>
                <a href="{{ route('super-admin.tenants.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-lg text-sm hover:bg-gray-200">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</x-super-admin.layouts.app>
