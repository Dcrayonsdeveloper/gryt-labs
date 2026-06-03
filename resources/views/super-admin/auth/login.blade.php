<x-super-admin.layouts.app title="Login">
    <div class="flex items-center justify-center min-h-screen bg-[#0F172A]">
        <div class="w-full max-w-sm">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-white">{{ config('app.name', 'Store') }} Platform</h1>
                <p class="text-gray-400 text-sm mt-1">Super Admin Access</p>
            </div>

            <form method="POST" action="{{ route('super-admin.login.submit') }}" class="bg-white rounded-xl p-8 shadow-xl">
                @csrf
                <h2 class="text-lg font-bold text-gray-900 mb-6">Sign in</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-600">Remember me</span>
                    </label>
                </div>

                <button type="submit" class="w-full mt-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg text-sm transition-colors">
                    Sign in
                </button>
            </form>
        </div>
    </div>
</x-super-admin.layouts.app>
