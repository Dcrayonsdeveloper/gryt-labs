<x-layouts.app>
    <x-slot name="title">Return Request — {{ $order->order_number }}</x-slot>

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[
                ['label' => 'Track Order', 'url' => route('track-order')],
                ['label' => $order->order_number, 'url' => route('track-order.return', $order)],
                ['label' => 'Return Request', 'url' => null],
            ]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 sm:py-12">
        <div class="max-w-2xl mx-auto">
            <div class="mb-6">
                <a href="javascript:history.back()" class="text-[13px] text-primary-600 hover:text-primary-700 font-medium inline-flex items-center gap-1.5 mb-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Order
                </a>
                <h1 class="text-lg sm:text-xl font-bold text-neutral-900">Return / Exchange Request</h1>
                <p class="text-[13px] text-neutral-600 mt-1">Order {{ $order->order_number }} &middot; Returns accepted within {{ $returnWindowDays }} days of delivery</p>
            </div>

            <form action="{{ route('track-order.return.store', $order) }}" method="POST"
                  x-data="{
                      type: '{{ old('type', 'return') }}',
                      items: {{ Js::from($order->items->map(fn($i) => [
                          'id' => $i->id,
                          'name' => $i->product->name ?? $i->product_name,
                          'variant' => $i->variant_name,
                          'quantity' => $i->quantity,
                          'price' => $i->price,
                      ])) }},
                      selectedItems: [],
                      toggleItem(itemId) {
                          const idx = this.selectedItems.findIndex(si => si.id === itemId);
                          if (idx > -1) { this.selectedItems.splice(idx, 1); }
                          else {
                              const item = this.items.find(i => i.id === itemId);
                              if (item) { this.selectedItems.push({ id: item.id, quantity: 1, reason: '', condition: 'unopened' }); }
                          }
                      },
                      isSelected(itemId) { return this.selectedItems.some(si => si.id === itemId); },
                      getSelected(itemId) { return this.selectedItems.find(si => si.id === itemId); }
                  }"
                  class="space-y-4">
                @csrf

                {{-- Return Type --}}
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-neutral-100 flex items-center gap-2">
                        <span class="w-5 h-5 bg-primary-600 text-white text-[11px] font-bold rounded-full flex items-center justify-center">1</span>
                        <h2 class="text-sm font-bold text-neutral-900">Request Type</h2>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-2 gap-3">
                            <label @click="type = 'return'"
                                   :class="type === 'return' ? 'border-primary-600/50 bg-primary-600/5 ring-1 ring-primary-600/30' : 'border-neutral-200 hover:border-neutral-300'"
                                   class="flex items-center gap-3 p-3.5 rounded-lg border cursor-pointer transition-all">
                                <input type="radio" name="type" value="return" x-model="type" class="sr-only">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                                     :class="type === 'return' ? 'bg-primary-600/10 text-primary-600' : 'bg-neutral-100 text-neutral-600'">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-neutral-900">Return</p>
                                    <p class="text-xs text-neutral-600">Get a refund</p>
                                </div>
                            </label>
                            <label @click="type = 'exchange'"
                                   :class="type === 'exchange' ? 'border-primary-600/50 bg-primary-600/5 ring-1 ring-primary-600/30' : 'border-neutral-200 hover:border-neutral-300'"
                                   class="flex items-center gap-3 p-3.5 rounded-lg border cursor-pointer transition-all">
                                <input type="radio" name="type" value="exchange" x-model="type" class="sr-only">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                                     :class="type === 'exchange' ? 'bg-primary-600/10 text-primary-600' : 'bg-neutral-100 text-neutral-600'">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-neutral-900">Exchange</p>
                                    <p class="text-xs text-neutral-600">Replace the item</p>
                                </div>
                            </label>
                        </div>
                        @error('type')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Reason --}}
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-neutral-100 flex items-center gap-2">
                        <span class="w-5 h-5 bg-primary-600 text-white text-[11px] font-bold rounded-full flex items-center justify-center">2</span>
                        <h2 class="text-sm font-bold text-neutral-900">Reason</h2>
                    </div>
                    <div class="p-5 space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-neutral-600 mb-1">Reason Category <span class="text-red-500">*</span></label>
                            <select name="reason_category" required class="w-full rounded-lg border border-neutral-200 text-sm px-3 py-2.5 focus:border-primary-600/50 focus:ring focus:ring-primary-600/15">
                                <option value="">Select a reason...</option>
                                @foreach(['Product not as described','Received damaged/defective item','Wrong item received','Changed my mind','Better price available','Other'] as $r)
                                    <option value="{{ $r }}" {{ old('reason_category') === $r ? 'selected' : '' }}>{{ $r }}</option>
                                @endforeach
                            </select>
                            @error('reason_category')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-neutral-600 mb-1">Details <span class="text-neutral-400">(optional)</span></label>
                            <textarea name="reason" rows="2" placeholder="Tell us more..."
                                      class="w-full rounded-lg border border-neutral-200 text-sm px-3 py-2.5 focus:border-primary-600/50 focus:ring focus:ring-primary-600/15 resize-none">{{ old('reason') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Items --}}
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-neutral-100 flex items-center gap-2">
                        <span class="w-5 h-5 bg-primary-600 text-white text-[11px] font-bold rounded-full flex items-center justify-center">3</span>
                        <h2 class="text-sm font-bold text-neutral-900">Select Items</h2>
                    </div>
                    <div class="p-5">
                        <div class="space-y-2">
                            <template x-for="(item, idx) in items" :key="item.id">
                                <div :class="isSelected(item.id) ? 'border-primary-600/50 bg-primary-600/5 ring-1 ring-primary-600/30' : 'border-neutral-200'"
                                     class="rounded-lg border transition-all overflow-hidden">
                                    <div @click="toggleItem(item.id)" class="flex items-center gap-3 p-3.5 cursor-pointer">
                                        <div :class="isSelected(item.id) ? 'bg-primary-600 border-primary-600' : 'border-neutral-300'"
                                             class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition-colors">
                                            <svg x-show="isSelected(item.id)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-neutral-900 truncate" x-text="item.name"></p>
                                            <p class="text-xs text-neutral-600" x-show="item.variant" x-text="item.variant"></p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="text-sm font-semibold text-neutral-900" x-text="'₹' + parseFloat(item.price).toLocaleString('en-IN')"></p>
                                            <p class="text-[11px] text-neutral-600" x-text="'Qty: ' + item.quantity"></p>
                                        </div>
                                    </div>
                                    <div x-show="isSelected(item.id)" x-collapse class="border-t border-neutral-100 bg-white p-3.5">
                                        <input type="hidden" :name="'items[' + idx + '][order_item_id]'" :value="item.id" :disabled="!isSelected(item.id)">
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-xs font-medium text-neutral-600 mb-1">Return Qty</label>
                                                <input type="number" :name="'items[' + idx + '][quantity]'" min="1" :max="item.quantity"
                                                       x-model.number="getSelected(item.id).quantity" :disabled="!isSelected(item.id)"
                                                       class="w-full rounded-lg border border-neutral-200 text-sm px-3 py-2">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-neutral-600 mb-1">Condition</label>
                                                <select :name="'items[' + idx + '][condition]'" x-model="getSelected(item.id).condition"
                                                        :disabled="!isSelected(item.id)"
                                                        class="w-full rounded-lg border border-neutral-200 text-sm px-3 py-2">
                                                    <option value="unopened">Unopened</option>
                                                    <option value="opened">Opened</option>
                                                    <option value="damaged">Damaged</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-neutral-600 mb-1">Note <span class="text-neutral-400">(optional)</span></label>
                                                <input type="text" :name="'items[' + idx + '][reason]'" x-model="getSelected(item.id).reason"
                                                       :disabled="!isSelected(item.id)" placeholder="Any specific issue?"
                                                       class="w-full rounded-lg border border-neutral-200 text-sm px-3 py-2">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        @error('items')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                            :disabled="selectedItems.length === 0"
                            :class="selectedItems.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary-700'"
                            class="inline-flex items-center gap-2 bg-primary-600 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Submit Request
                    </button>
                    <a href="javascript:history.back()" class="text-sm font-medium text-neutral-600 hover:text-neutral-700 px-3 py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
