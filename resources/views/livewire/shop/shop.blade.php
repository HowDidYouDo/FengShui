<div>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
            {{ __('Shop') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(!empty($cart))
                <div class="mb-6 px-4 sm:px-0 flex justify-end">
                    <flux:button :href="route('checkout')" wire:navigate variant="primary"
                                 class="!bg-brand-blue hover:!bg-brand-orange !text-white border-0 transition-colors">
                        {{ __('Go to Checkout') }} ({{ count($cart) }})
                    </flux:button>
                </div>
            @endif

            <div class="mb-8 px-4 sm:px-0">
                <h3 class="text-2xl font-bold font-heading text-brand-blue dark:text-brand-blue-light">
                    {{ __('Expand Your Possibilities') }}
                </h3>
                <p class="text-zinc-600 dark:text-zinc-400 mt-1">
                    {{ __('Choose from the modules below to enhance your experience.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 px-4 sm:px-0">

                @forelse($features as $feature)
                    <div
                        class="relative group flex flex-col bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-300">
                        <div class="p-6 flex flex-col h-full">
                            <div
                                class="w-12 h-12 rounded-lg mb-4 flex items-center justify-center text-lg font-bold bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                {{ substr($feature->name, 0, 1) }}
                            </div>

                            <h4 class="text-lg font-bold font-heading text-zinc-900 dark:text-white mb-2">
                                {{ $feature->name }}
                            </h4>

                            <p class="text-sm text-zinc-600 dark:text-zinc-400 flex-grow">
                                {{ $feature->description ?? __('Unlock this module for more features.') }}
                            </p>

                            <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 text-xs text-zinc-500">
                                @if($feature->purchase_type === 'subscription')
                                    <span>{{ Number::currency($feature->price_netto / 100, in: $feature->currency) }} / {{ __($feature->renewal_period) }}</span>
                                @else
                                    <span>{{ Number::currency($feature->price_netto / 100, in: $feature->currency) }}</span>
                                @endif
                            </div>

                            <div class="mt-6">
                                @if(in_array($feature->id, $cart))
                                    <flux:button disabled class="w-full cursor-default">
                                        {{ __('Added to Cart') }}
                                    </flux:button>
                                @else
                                    <flux:button
                                        wire:click="buy({{ $feature->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="buy({{ $feature->id }})"
                                        variant="primary"
                                        class="w-full !bg-brand-orange hover:!bg-brand-blue !text-white border-0 transition-colors">
                                        <span wire:loading.remove
                                              wire:target="buy({{ $feature->id }})">{{ __('Buy Now') }}</span>
                                        <span wire:loading
                                              wire:target="buy({{ $feature->id }})">{{ __('Adding...') }}</span>
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12 text-zinc-500">
                        {{ __('You already own all available modules.') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
