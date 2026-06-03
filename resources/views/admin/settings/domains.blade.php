<x-layouts.admin>
    <x-slot name="title">Domains</x-slot>

    <x-slot name="header">
        <h1 class="text-lg font-bold text-neutral-800">Domains</h1>
    </x-slot>

    @include('admin.settings.partials.nav')

    <div class="max-w-3xl space-y-6"
         x-data="domainsPage()">

        {{-- Current Domains --}}
        <div class="bg-white rounded-xl shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-900 m-0">Your Domains</h2>
                <p class="text-xs text-gray-600 mt-1 mb-0">Customers can access your store through these domains.</p>
            </div>

            <div>
                @foreach($domains as $domain)
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 last:border-b-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900">{{ $domain->domain }}</span>
                            @if(str_ends_with($domain->domain, '.' . $baseDomain))
                                <span class="admin-badge admin-badge-neutral">Platform</span>
                            @else
                                <span class="admin-badge admin-badge-success">Custom</span>
                            @endif
                        </div>
                        @if(str_ends_with($domain->domain, '.' . $baseDomain))
                            <p class="text-[11px] text-gray-500 mt-0.5">Auto-assigned platform subdomain</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if(!str_ends_with($domain->domain, '.' . $baseDomain))
                            <button type="button"
                                    x-on:click="verifyDns('{{ $domain->domain }}')"
                                    class="admin-btn admin-btn-outline text-[11px]">Verify DNS</button>
                            <form method="POST" action="{{ route('admin.settings.domains.destroy') }}"
                                  x-on:submit="return confirm('Remove this domain?')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="domain_id" value="{{ $domain->id }}">
                                <button type="submit" class="admin-btn text-[11px] text-red-700 border border-red-100 bg-red-100 hover:bg-red-200">Remove</button>
                            </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Add Custom Domain --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-900 mt-0 mb-1">Connect a Custom Domain</h2>
            <p class="text-xs text-gray-600 mt-0 mb-4">Use your own domain name (e.g., mystore.com) for your store.</p>

            <form method="POST" action="{{ route('admin.settings.domains.store') }}">
                @csrf
                <div class="flex gap-2 mb-2">
                    <input type="text" name="domain" value="{{ old('domain') }}" placeholder="e.g., mystore.com"
                           class="admin-input flex-1">
                    <button type="submit" class="admin-btn admin-btn-primary">Add Domain</button>
                </div>
                @error('domain') <p class="text-xs text-red-700 m-0">{{ $message }}</p> @enderror
            </form>

            <div class="mt-4 p-4 bg-gray-100 rounded-lg">
                <h3 class="text-[13px] font-semibold text-gray-900 mt-0 mb-2">DNS Setup Instructions</h3>
                <p class="text-xs text-gray-600 mt-0 mb-2">After adding your domain, update your DNS settings with one of these options:</p>
                <table class="w-full text-xs border-collapse">
                    <tr class="border-b border-gray-200">
                        <td class="py-1.5 font-semibold text-gray-900 w-20">Option 1</td>
                        <td class="py-1.5 text-gray-600">
                            Add an <strong>A Record</strong> pointing to <code class="bg-gray-200 px-1.5 py-0.5 rounded text-[11px]">{{ config('tenancy.server_ip', '15.207.133.144') }}</code>
                        </td>
                    </tr>
                    <tr>
                        <td class="py-1.5 font-semibold text-gray-900">Option 2</td>
                        <td class="py-1.5 text-gray-600">
                            Add a <strong>CNAME Record</strong> pointing to <code class="bg-gray-200 px-1.5 py-0.5 rounded text-[11px]">{{ $primarySubdomain }}</code>
                        </td>
                    </tr>
                </table>
                <p class="text-[11px] text-gray-500 mt-2 mb-0">DNS changes may take up to 48 hours to propagate.</p>
            </div>
        </div>

        {{-- DNS Verification Result --}}
        <div x-show="resultVisible" x-cloak class="bg-white rounded-xl shadow-sm p-4">
            <template x-if="resultStatus === 'checking'">
                <div class="p-4">
                    <p class="text-[13px] text-gray-600 m-0" x-text="'Checking DNS for ' + resultDomain + '...'"></p>
                </div>
            </template>
            <template x-if="resultStatus === 'verified'">
                <div class="p-4 bg-green-100 rounded-xl">
                    <p class="text-[13px] font-semibold text-green-800 m-0">&#10003; <span x-text="resultMessage"></span></p>
                </div>
            </template>
            <template x-if="resultStatus === 'pending'">
                <div class="p-4 bg-amber-100 rounded-xl">
                    <p class="text-[13px] font-semibold text-amber-800 m-0">&#9888; <span x-text="resultMessage"></span></p>
                </div>
            </template>
            <template x-if="resultStatus === 'failed'">
                <div class="p-4 bg-red-100 rounded-xl">
                    <p class="text-[13px] text-red-700 m-0">Failed to check DNS.</p>
                </div>
            </template>
        </div>

    </div>

    @push('scripts')
    <script>
    function domainsPage() {
        return {
            resultVisible: false,
            resultStatus: '',
            resultDomain: '',
            resultMessage: '',
            verifyDns(domain) {
                this.resultVisible = true;
                this.resultStatus = 'checking';
                this.resultDomain = domain;
                this.resultMessage = '';

                fetch('{{ route("admin.settings.domains.verify-dns") }}?domain=' + encodeURIComponent(domain), {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                })
                .then(r => r.json())
                .then(data => {
                    this.resultStatus = data.verified ? 'verified' : 'pending';
                    this.resultMessage = data.message;
                })
                .catch(() => {
                    this.resultStatus = 'failed';
                });
            }
        };
    }
    </script>
    @endpush

</x-layouts.admin>
