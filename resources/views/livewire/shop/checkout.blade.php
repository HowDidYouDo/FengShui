<div>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
            {{ __('Checkout') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div
                class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                <div class="p-6">
                    <h3 class="text-xl font-bold font-heading text-zinc-900 dark:text-white mb-4">
                        {{ __('Order Summary') }}
                    </h3>

                    @if($items->isNotEmpty())
                        <div class="space-y-4">
                            <!-- Cart Items -->
                            <div class="space-y-2">
                                @foreach($items as $item)
                                    <div wire:key="{{ $item->id }}"
                                         class="flex justify-between items-center p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                                        <div>
                                            <h4 class="font-semibold">{{ $item->name }}</h4>
                                            <p class="text-sm text-zinc-500">{{ $item->purchase_type === 'subscription' ? __('Subscription') : __('Lifetime') }}</p>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <div class="text-lg font-semibold">
                                                {{ Number::currency($item->price_netto / 100, in: $item->currency) }}
                                            </div>
                                            <button wire:click="remove({{ $item->id }})"
                                                    class="text-red-500 hover:text-red-700">
                                                &times;
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Total -->
                            <div
                                class="flex justify-end items-center pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                <span class="text-lg font-semibold text-zinc-900 dark:text-white">
                                    {{ __('Total:') }} {{ Number::currency($total / 100, in: $items->first()->currency ?? 'EUR') }}
                                </span>
                            </div>

                            <!-- Action Button -->
                            <div class="pt-4 flex justify-end">
                                <flux:button
                                    wire:click="processPurchase"
                                    wire:loading.attr="disabled"
                                    variant="primary"
                                    class="!bg-brand-blue hover:!bg-brand-orange !text-white border-0 transition-colors">
                                    <span wire:loading.remove>{{ __('Proceed to Payment') }}</span>
                                    <span wire:loading>{{ __('Processing...') }}</span>
                                </flux:button>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <p class="text-zinc-500 mb-4">{{ __('Your cart is empty.') }}</p>
                            <flux:button :href="route('shop')" wire:navigate variant="outline">
                                {{ __('Continue Shopping') }}
                            </flux:button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
