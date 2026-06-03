<x-layouts.admin>
    <x-slot name="title">Integrations</x-slot>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-neutral-900">Settings</h1>
        <p class="text-neutral-600">Manage your store configuration</p>
    </div>

    @include('admin.settings.partials.nav', ['active' => 'integrations'])

    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 px-4 py-3 bg-success-50 border border-success-200 rounded-xl text-sm text-success-700">
            <svg class="w-5 h-5 text-success-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.integrations.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="space-y-4">

            {{-- Google OAuth --}}
            <div class="card" x-data="{ enabled: {{ ($settings['google_login_enabled'] ?? '0') === '1' ? 'true' : 'false' }} }">
                <div class="px-5 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 bg-white border border-neutral-200">
                            <svg class="w-5 h-5" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-neutral-900 flex items-center gap-2">
                                Google Sign-In
                                @if(!empty($settings['google_client_id']))
                                    <span class="text-[10px] font-medium text-success-600 bg-success-50 px-1.5 py-0.5 rounded">Connected</span>
                                @endif
                            </h3>
                            <p class="text-xs text-neutral-600">Allow customers to sign in with their Google account</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="google_login_enabled" value="1" x-model="enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                    </label>
                </div>
                <div class="px-5 pb-5 space-y-4 border-t border-neutral-100" x-show="enabled" x-collapse>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
                        <div>
                            <label class="form-label">Client ID *</label>
                            <input type="text" name="google_client_id" value="{{ old('google_client_id', $settings['google_client_id'] ?? '') }}" placeholder="xxxxxxxxxx.apps.googleusercontent.com" class="form-input">
                            <p class="text-xs text-neutral-500 mt-1">From Google Cloud Console > APIs & Services > Credentials</p>
                        </div>
                        <div>
                            <label class="form-label">Client Secret *</label>
                            <input type="password" name="google_client_secret" value="" placeholder="{{ !empty($settings['google_client_secret']) ? '••••••••••••' : 'Enter client secret' }}" class="form-input">
                            @if(!empty($settings['google_client_secret']))
                                <p class="text-xs text-neutral-500 mt-1">Secret is saved. Leave blank to keep current.</p>
                            @endif
                        </div>
                    </div>
                    <div class="p-3 bg-neutral-50 rounded-lg">
                        <p class="text-xs text-neutral-600 font-medium mb-1">Setup Instructions:</p>
                        <ol class="text-xs text-neutral-600 list-decimal list-inside space-y-1">
                            <li>Go to <strong>Google Cloud Console</strong> > APIs & Services > Credentials</li>
                            <li>Create OAuth 2.0 Client ID (Web application)</li>
                            <li>Add Authorized redirect URI: <code class="bg-white px-1.5 py-0.5 rounded text-[11px] border">{{ url('/auth/google/callback') }}</code></li>
                            <li>Copy Client ID and Client Secret here</li>
                        </ol>
                    </div>
                </div>
            </div>

            {{-- Facebook Login --}}
            <div class="card" x-data="{ enabled: {{ ($settings['facebook_login_enabled'] ?? '0') === '1' ? 'true' : 'false' }} }">
                <div class="px-5 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background:#1877F2;">
                            <svg class="w-5 h-5" fill="white" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-neutral-900 flex items-center gap-2">
                                Facebook Login
                                @if(!empty($settings['facebook_client_id']))
                                    <span class="text-[10px] font-medium text-success-600 bg-success-50 px-1.5 py-0.5 rounded">Connected</span>
                                @endif
                            </h3>
                            <p class="text-xs text-neutral-600">Allow customers to sign in with their Facebook account</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="facebook_login_enabled" value="1" x-model="enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                    </label>
                </div>
                <div class="px-5 pb-5 space-y-4 border-t border-neutral-100" x-show="enabled" x-collapse>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
                        <div>
                            <label class="form-label">App ID *</label>
                            <input type="text" name="facebook_client_id" value="{{ old('facebook_client_id', $settings['facebook_client_id'] ?? '') }}" placeholder="123456789012345" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">App Secret *</label>
                            <input type="password" name="facebook_client_secret" value="" placeholder="{{ !empty($settings['facebook_client_secret']) ? '••••••••••••' : 'Enter app secret' }}" class="form-input">
                            @if(!empty($settings['facebook_client_secret']))
                                <p class="text-xs text-neutral-500 mt-1">Secret is saved. Leave blank to keep current.</p>
                            @endif
                        </div>
                    </div>
                    <div class="p-3 bg-neutral-50 rounded-lg">
                        <p class="text-xs text-neutral-600 font-medium mb-1">Setup Instructions:</p>
                        <ol class="text-xs text-neutral-600 list-decimal list-inside space-y-1">
                            <li>Go to <strong>Meta for Developers</strong> > Your App > Facebook Login > Settings</li>
                            <li>Add Valid OAuth Redirect URI: <code class="bg-white px-1.5 py-0.5 rounded text-[11px] border">{{ url('/auth/facebook/callback') }}</code></li>
                            <li>Copy App ID and App Secret here</li>
                        </ol>
                    </div>
                </div>
            </div>

        </div>

        <div class="mt-6">
            <button type="submit" class="btn btn-primary">Save Integrations</button>
        </div>
    </form>
</x-layouts.admin>
