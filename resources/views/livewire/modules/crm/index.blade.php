{{-- resources/views/livewire/modules/crm/index.blade.php --}}
<div>
    <flux:heading size="xl">{{ __('Clients') }}</flux:heading>
    <flux:subheading class="mb-6 flex items-center">
        {{ __('Manage your client profiles.') }}
        @php
            $quota = auth()->user()->getFeatureQuota('crm');
            $usage = auth()->user()->customers()->count();
        @endphp
        @if($quota !== null)
            <span
                class="ml-2 text-xs font-medium px-2 py-0.5 rounded-full {{ $usage >= $quota ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' }}">
                {{ __('Quota:') }} {{ $usage }} / {{ $quota }}
            </span>
        @endif
    </flux:subheading>

    {{-- Header Actions --}}
    <div class="flex justify-between items-center mb-6">
        <div class="flex gap-4 items-center flex-1">
            {{-- Search Input --}}
            <div class="flex-1 max-w-md">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="{{ __('Search by name, email or birthplace...') }}"
                    icon="magnifying-glass"
                />
            </div>

            {{-- Gender Filter --}}
            <flux:select wire:model.live="genderFilter" class="w-48">
                <option value="">{{ __('All Genders') }}</option>
                <option value="m">{{ __('Male') }}</option>
                <option value="f">{{ __('Female') }}</option>
            </flux:select>

            {{-- Reset Filters --}}
            @if($search || $genderFilter)
                <flux:button wire:click="resetFilters" variant="ghost" size="sm">
                    {{ __('Reset Filters') }}
                </flux:button>
            @endif
        </div>

        {{-- Create Button --}}
        <flux:button wire:click="$set('showCreateModal', true)" icon="plus">
            {{ __('Add New Client') }}
        </flux:button>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="text-left py-3 px-4">
                        <flux:button
                            wire:click="sortBy('name')"
                            variant="ghost"
                            size="sm"
                            class="font-semibold"
                        >
                            {{ __('Name') }}
                            @if($sortField === 'name')
                                @if($sortDirection === 'asc')
                                    <flux:icon.chevron-up class="ml-1 w-4 h-4"/>
                                @else
                                    <flux:icon.chevron-down class="ml-1 w-4 h-4"/>
                                @endif
                            @endif
                        </flux:button>
                    </th>
                    <th class="text-left py-3 px-4">{{ __('Email') }}</th>
                    <th class="text-left py-3 px-4">
                        <flux:button
                            wire:click="sortBy('birth_date')"
                            variant="ghost"
                            size="sm"
                            class="font-semibold"
                        >
                            {{ __('Birth Date') }}
                            @if($sortField === 'birth_date')
                                @if($sortDirection === 'asc')
                                    <flux:icon.chevron-up class="ml-1 w-4 h-4"/>
                                @else
                                    <flux:icon.chevron-down class="ml-1 w-4 h-4"/>
                                @endif
                            @endif
                        </flux:button>
                    </th>
                    <th class="text-left py-3 px-4">{{ __('Birth Place') }}</th>
                    <th class="text-left py-3 px-4">{{ __('Gender') }}</th>
                    <th class="text-left py-3 px-4">{{ __('Self') }}</th>
                    <th class="text-right py-3 px-4">{{ __('Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($customers as $customer)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="py-3 px-4">
                            <a
                                href="{{ route('modules.bagua.show', $customer) }}"
                                class="font-semibold text-zinc-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                {{ $customer->name }}
                            </a>
                        </td>
                        <td class="py-3 px-4">
                            @if($customer->email)
                                <div class="flex items-center gap-2">
                                    <flux:icon.envelope class="w-4 h-4 text-zinc-400"/>
                                    <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $customer->email }}</span>
                                </div>
                            @else
                                <span class="text-sm text-zinc-400">-</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $customer->birth_date ? $customer->birth_date->format(__('date_format')) : '-' }}
                        </td>
                        <td class="py-3 px-4 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $customer->birth_place ?? '-' }}
                        </td>
                        <td class="py-3 px-4">
                            @if($customer->gender)
                                <flux:badge
                                    :color="$customer->gender === 'm' ? 'blue' : 'pink'"
                                    size="sm"
                                >
                                    {{ $customer->gender === 'm' ? __('Male') : __('Female') }}
                                </flux:badge>
                            @else
                                <span class="text-sm text-zinc-400">-</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($customer->is_self_profile)
                                <flux:icon.check-circle class="w-5 h-5 text-green-600"/>
                            @else
                                <span class="text-zinc-300 dark:text-zinc-700">-</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <flux:button.group>
                                <flux:button
                                    wire:click="editCustomer({{ $customer->id }})"
                                    variant="ghost"
                                    size="sm"
                                    icon="pencil"
                                >
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button
                                    wire:click="deleteCustomer({{ $customer->id }})"
                                    wire:confirm="{{ __('Are you sure you want to delete this client?') }}"
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    class="text-red-600 hover:text-red-700"
                                >
                                    {{ __('Delete') }}
                                </flux:button>
                            </flux:button.group>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center">
                            <flux:icon.user-group class="w-12 h-12 mx-auto text-zinc-300 dark:text-zinc-700 mb-3"/>
                            <flux:heading size="lg" class="mb-2">{{ __('No clients yet') }}</flux:heading>
                            <flux:text>{{ __('Add your first client to start your analysis.') }}</flux:text>
                            <flux:button
                                wire:click="$set('showCreateModal', true)"
                                class="mt-4"
                                icon="plus"
                            >
                                {{ __('Add New Client') }}
                            </flux:button>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($customers->hasPages())
            <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-3">
                {{ $customers->links() }}
            </div>
        @endif

        {{-- Results Count --}}
        @if($customers->total() > 0)
            <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-3">
                <flux:text size="sm" class="text-zinc-500">
                    {{ __('Showing :from to :to of :total results', [
                        'from' => $customers->firstItem(),
                        'to' => $customers->lastItem(),
                        'total' => $customers->total()
                    ]) }}
                </flux:text>
            </div>
        @endif
    </div>

    {{-- Create Modal einbinden --}}
    @include('livewire.modules.crm.partials.create-modal')

    {{-- Edit Modal einbinden --}}
    @include('livewire.modules.crm.partials.edit-modal')
</div>
