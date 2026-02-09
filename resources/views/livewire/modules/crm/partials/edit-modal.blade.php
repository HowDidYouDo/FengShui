{{-- resources/views/livewire/modules/crm/partials/edit-modal.blade.php --}}
@if($showEditModal)
    <div
        x-data="{ show: @entangle('showEditModal') }"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        @keydown.escape.window="show = false"
    >
        {{-- Backdrop --}}
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/50"
            @click="show = false"
        ></div>

        {{-- Modal Container --}}
        <div class="flex min-h-screen items-center justify-center p-4">
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white dark:bg-zinc-900 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
                @click.stop
            >
                <form wire:submit="updateCustomer">
                    {{-- Header --}}
                    <div class="border-b border-zinc-200 dark:border-zinc-800 px-6 py-4">
                        <flux:heading size="lg">{{ __('Edit Client') }}</flux:heading>
                        <flux:subheading>
                            {{ __('Update the client profile details.') }}
                        </flux:subheading>
                    </div>

                    {{-- Body --}}
                    <div class="px-6 py-4 space-y-6">
                        {{-- Basic Information --}}
                        <flux:separator text="{{ __('Basic Information') }}" />

                        <flux:input
                            wire:model="name"
                            label="{{ __('Full Name') }}"
                            placeholder="{{ __('e.g. Simon Benedikt') }}"
                            required
                        />

                        <flux:input
                            wire:model="email"
                            type="email"
                            label="{{ __('Email Address') }}"
                            placeholder="{{ __('e.g. simon@example.com') }}"
                        />

                        <flux:textarea
                            wire:model="notes"
                            label="{{ __('Notes') }}"
                            placeholder="{{ __('Optional notes about this client...') }}"
                            rows="3"
                        />

                        {{-- Birth Data --}}
                        <flux:separator text="{{ __('Birth Data') }}" class="mt-8" />

                        <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400 -mt-4">
                            {{ __('Required for calculation of energetic cycles.') }}
                        </flux:text>

                        <div class="grid grid-cols-2 gap-6">
                            <flux:input
                                wire:model="birth_date"
                                type="date"
                                label="{{ __('Birth Date') }}"
                                required
                            />

                            <flux:input
                                wire:model="birth_time"
                                type="time"
                                label="{{ __('Birth Time') }}"
                                required
                            />

                            <flux:input
                                wire:model="birth_place"
                                label="{{ __('Birth Place') }}"
                                placeholder="{{ __('e.g. Hamburg') }}"
                                required
                            />

                            <div>
                                <flux:field>
                                    <flux:label>{{ __('Biological Sex') }}</flux:label>
                                    <flux:radio.group wire:model="gender" inline>
                                        <flux:radio value="m" label="{{ __('Male') }}" />
                                        <flux:radio value="f" label="{{ __('Female') }}" />
                                    </flux:radio.group>
                                </flux:field>
                            </div>
                        </div>

                        {{-- Billing Address (Collapsible) --}}
                        <flux:separator text="{{ __('Billing Address') }}" class="mt-8" />

                        <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400 -mt-4">
                            {{ __('Optional') }}
                        </flux:text>

                        <div x-data="{ showBilling: {{ $billing_street || $billing_city || $billing_zip || $billing_country ? 'true' : 'false' }} }">
                            <flux:button
                                type="button"
                                variant="ghost"
                                size="sm"
                                x-on:click="showBilling = !showBilling"
                                class="mb-4"
                            >
                                <span x-show="!showBilling">{{ __('Show billing address fields') }}</span>
                                <span x-show="showBilling">{{ __('Hide billing address fields') }}</span>
                            </flux:button>

                            <div x-show="showBilling" x-collapse class="space-y-6">
                                <flux:input
                                    wire:model="billing_street"
                                    label="{{ __('Street & Number') }}"
                                    placeholder="{{ __('e.g. Main Street 123') }}"
                                />

                                <div class="grid grid-cols-2 gap-6">
                                    <flux:input
                                        wire:model="billing_zip"
                                        label="{{ __('ZIP Code') }}"
                                        placeholder="12345"
                                    />

                                    <flux:input
                                        wire:model="billing_city"
                                        label="{{ __('City') }}"
                                        placeholder="{{ __('e.g. Hamburg') }}"
                                    />
                                </div>

                                <flux:input
                                    wire:model="billing_country"
                                    label="{{ __('Country') }}"
                                    placeholder="{{ __('e.g. Germany') }}"
                                />
                            </div>
                        </div>

                        @php
                            $hasOtherSelf = auth()->user()->customers()
                                ->where('is_self_profile', true)
                                ->where('id', '!=', $editingCustomerId)
                                ->exists();
                        @endphp

                        @if(!$hasOtherSelf)
                            {{-- System --}}
                            <flux:separator text="{{ __('System') }}" class="mt-8" />

                            <flux:checkbox
                                wire:model="is_self_profile"
                                label="{{ __('Is Self Profile') }}"
                                description="{{ __('Mark this if this customer represents the consultant themselves.') }}"
                            />
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="border-t border-zinc-200 dark:border-zinc-800 px-6 py-4 flex justify-end gap-3">
                        <flux:button type="button" variant="ghost" wire:click="closeEditModal">
                            {{ __('Cancel') }}
                        </flux:button>
                        <flux:button type="submit" variant="primary">
                            {{ __('Save Changes') }}
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
