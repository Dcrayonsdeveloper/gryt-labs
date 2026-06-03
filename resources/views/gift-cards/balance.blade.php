<x-layouts.app>
    <x-slot name="title">Check Gift Card Balance</x-slot>

    <div class="max-w-lg mx-auto px-4 py-12">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Check Gift Card Balance</h1>
        <p class="text-gray-600 mb-8">Enter your gift card code to view the remaining balance.</p>

        <form action="{{ route('gift-cards.check-balance') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Gift Card Code</label>
                <input type="text" name="code" id="code"
                       value="{{ $code ?? old('code') }}"
                       required maxlength="20"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg text-lg font-mono uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-gray-900"
                       placeholder="XXXXXXXXXXXX">
                @error('code')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="w-full px-4 py-3 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors">
                Check Balance
            </button>
        </form>

        @if(!empty($searched))
            <div class="mt-8">
                @if(isset($giftCard) && $giftCard)
                    <div class="bg-white border border-gray-200 rounded-xl p-6 text-center">
                        <p class="text-sm text-gray-500 mb-1">Gift Card Balance</p>
                        <p class="text-4xl font-bold text-gray-900">
                            {{ $giftCard->currency }} {{ number_format($giftCard->current_balance, 2) }}
                        </p>
                        <p class="text-sm text-gray-500 mt-2">
                            @if($giftCard->status === 'active')
                                <span class="text-green-600 font-medium">Active</span>
                            @elseif($giftCard->status === 'depleted')
                                <span class="text-gray-500 font-medium">Depleted</span>
                            @else
                                <span class="text-red-600 font-medium">{{ ucfirst($giftCard->status) }}</span>
                            @endif
                            @if($giftCard->expires_at)
                                &middot; Expires {{ $giftCard->expires_at->format('M d, Y') }}
                            @endif
                        </p>
                    </div>
                @else
                    <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                        <p class="text-red-700">No gift card found with that code. Please check and try again.</p>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-layouts.app>
