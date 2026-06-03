<x-layouts.admin>
    <x-slot name="title">Email Settings</x-slot>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-neutral-900">Settings</h1>
        <p class="text-neutral-600">Manage your store configuration</p>
    </div>

    <!-- Settings Navigation -->
    @include('admin.settings.partials.nav', ['active' => 'email'])

    <form action="{{ route('admin.settings.email.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- SMTP Configuration -->
            <div class="card">
                <div class="px-5 py-3.5 border-b border-neutral-200">
                    <h2 class="text-sm font-semibold text-neutral-900">Mail Configuration</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="form-label form-label-required">Mail Driver</label>
                        <select name="mail_driver" required class="form-select">
                            <option value="smtp" @selected(($settings['mail_driver'] ?? 'smtp') === 'smtp')>SMTP</option>
                            <option value="sendmail" @selected(($settings['mail_driver'] ?? '') === 'sendmail')>Sendmail</option>
                            <option value="mailgun" @selected(($settings['mail_driver'] ?? '') === 'mailgun')>Mailgun</option>
                            <option value="ses" @selected(($settings['mail_driver'] ?? '') === 'ses')>Amazon SES</option>
                            <option value="postmark" @selected(($settings['mail_driver'] ?? '') === 'postmark')>Postmark</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="mail_host" value="{{ old('mail_host', $settings['mail_host'] ?? '') }}" placeholder="smtp.example.com" class="form-input">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Port</label>
                            <input type="number" name="mail_port" value="{{ old('mail_port', $settings['mail_port'] ?? '587') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Encryption</label>
                            <select name="mail_encryption" class="form-select">
                                <option value="">None</option>
                                <option value="tls" @selected(($settings['mail_encryption'] ?? 'tls') === 'tls')>TLS</option>
                                <option value="ssl" @selected(($settings['mail_encryption'] ?? '') === 'ssl')>SSL</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Username</label>
                        <input type="text" name="mail_username" value="{{ old('mail_username', $settings['mail_username'] ?? '') }}" class="form-input">
                    </div>

                    <div>
                        <label class="form-label">Password</label>
                        <input type="password" name="mail_password" value="" placeholder="{{ !empty($settings['mail_password']) ? '••••••••••••' : 'Enter password' }}" class="form-input">
                        @if(!empty($settings['mail_password']))
                            <p class="text-xs text-neutral-600 mt-1">Password is saved. Leave blank to keep current value.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- From Address -->
            <div class="card h-fit">
                <div class="px-5 py-3.5 border-b border-neutral-200">
                    <h2 class="text-sm font-semibold text-neutral-900">Sender Details</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="form-label form-label-required">From Email</label>
                        <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address'] ?? '') }}" required placeholder="noreply@example.com" class="form-input">
                    </div>

                    <div>
                        <label class="form-label form-label-required">From Name</label>
                        <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name'] ?? '') }}" required placeholder="Your Store Name" class="form-input">
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" class="btn btn-secondary" onclick="testEmail()">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Send Test Email
            </button>
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </form>

    <script>
        function testEmail() {
            alert('Test email functionality would send a test email to the admin.');
        }
    </script>
</x-layouts.admin>
