<x-layouts.admin>
    <x-slot name="title">Gift Card {{ $giftCard->code }}</x-slot>

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-neutral-600 mb-1">
            <a href="{{ route('admin.gift-cards.index') }}" class="hover:text-primary-600">Gift Cards</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-neutral-900">{{ $giftCard->code }}</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">Gift Card Details</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Card Info --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-neutral-900">Gift Card Information</h2>
                </div>
                <div class="p-4">
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Code</dt>
                            <dd class="font-mono font-medium text-gray-900 mt-1">{{ $giftCard->code }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Status</dt>
                            <dd class="mt-1">
                                @php
                                    $statusColors = [
                                        'active' => 'bg-green-100 text-green-700',
                                        'depleted' => 'bg-gray-100 text-gray-600',
                                        'disabled' => 'bg-red-100 text-red-700',
                                    ];
                                @endphp
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $statusColors[$giftCard->status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($giftCard->status) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Initial Balance</dt>
                            <dd class="font-medium text-gray-900 mt-1">{{ $giftCard->currency }} {{ number_format($giftCard->initial_balance, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Current Balance</dt>
                            <dd class="font-medium text-gray-900 mt-1">{{ $giftCard->currency }} {{ number_format($giftCard->current_balance, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Purchased</dt>
                            <dd class="text-gray-900 mt-1">{{ $giftCard->purchased_at?->format('M d, Y h:i A') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Expires</dt>
                            <dd class="text-gray-900 mt-1">{{ $giftCard->expires_at?->format('M d, Y') ?? 'Never' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Last Used</dt>
                            <dd class="text-gray-900 mt-1">{{ $giftCard->last_used_at?->format('M d, Y h:i A') ?? 'Never' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Recipient Info --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-neutral-900">Recipient</h2>
                </div>
                <div class="p-4">
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Name</dt>
                            <dd class="text-gray-900 mt-1">{{ $giftCard->recipient_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Email</dt>
                            <dd class="text-gray-900 mt-1">{{ $giftCard->recipient_email }}</dd>
                        </div>
                        @if($giftCard->purchaser_email)
                            <div>
                                <dt class="text-gray-500">Purchaser Email</dt>
                                <dd class="text-gray-900 mt-1">{{ $giftCard->purchaser_email }}</dd>
                            </div>
                        @endif
                        @if($giftCard->message)
                            <div class="col-span-2">
                                <dt class="text-gray-500">Message</dt>
                                <dd class="text-gray-900 mt-1">{{ $giftCard->message }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Usage History --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="font-semibold text-neutral-900">Usage History</h2>
                </div>
                @if($giftCard->usages->isEmpty())
                    <div class="p-4 text-sm text-gray-500">No usage yet.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-medium text-gray-500 uppercase" style="border-bottom:1px solid #e1e1e1">
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Order</th>
                                    <th class="px-4 py-3">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($giftCard->usages as $usage)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-600">{{ $usage->created_at->format('M d, Y h:i A') }}</td>
                                        <td class="px-4 py-3">
                                            @if($usage->order)
                                                <a href="{{ route('admin.orders.show', $usage->order) }}" class="text-blue-600 hover:underline">
                                                    #{{ $usage->order->order_number }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ number_format($usage->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="card">
                <div class="p-4 space-y-3">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-gray-900">{{ $giftCard->currency }} {{ number_format($giftCard->current_balance, 2) }}</p>
                        <p class="text-sm text-gray-500 mt-1">Remaining Balance</p>
                    </div>

                    @if($giftCard->initial_balance > 0)
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: {{ min(100, ($giftCard->current_balance / $giftCard->initial_balance) * 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 text-center">
                            {{ number_format(($giftCard->current_balance / $giftCard->initial_balance) * 100, 0) }}% remaining
                        </p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="p-4">
                    <form action="{{ route('admin.gift-cards.destroy', $giftCard) }}" method="POST"
                          onsubmit="return confirm('Are you sure you want to delete this gift card?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full text-center text-sm text-red-600 hover:text-red-800">
                            Delete Gift Card
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
