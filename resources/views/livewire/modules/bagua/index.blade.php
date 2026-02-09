<?php
// /resources/views/livewire/modules/bagua/index.blade.php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public ?string $target = null;

    public function mount()
    {
        $user = Auth::user();
        $this->target = request()->query('target');

        // Logik-Check: Hat User das 'clients' Feature?
        if (!$user->hasFeature('crm')) {
            // KEIN Client-Feature -> Redirect zum eigenen Profil
            $selfProfile = $user->customers()->where('is_self_profile', true)->first();

            if ($selfProfile) {
                return redirect()->route('modules.bagua.show', [
                    'customer' => $selfProfile,
                    'tab' => $this->target ?? 'analysis'
                ]);
            } else {
                // Fallback (sollte nicht passieren)
                return redirect()->route('profile.edit');
            }
        }
    }

    public function with()
    {
        $user = Auth::user();

        return [
            'customers' => $user->customers()
                ->orderBy('is_self_profile', 'desc')
                ->orderBy('name')
                ->paginate(12), // 12 passt gut zu Grid (2, 3 oder 4 Spalten)
        ];
    }
};
?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-heading font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
                {{ __('Select Client') }}
            </h2>
            <flux:button icon="arrow-left" size="sm" variant="subtle" :href="route('dashboard')">
                {{ __('Back to Dashboard') }}
            </flux:button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Simple Card List -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- Create New Client Button (Links to CRM) -->
                <a href="{{ route('modules.crm') }}"
                    class="group flex flex-col items-center justify-center min-h-[150px] gap-3 p-6 rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 hover:border-brand-blue hover:bg-brand-blue/5 transition-all text-zinc-400 hover:text-brand-blue">
                    <flux:icon.plus class="size-8" />
                    <span class="font-medium">{{ __('Add New Client') }}</span>
                </a>

                @foreach($customers as $customer)
                    <a href="{{ route('modules.bagua.show', ['customer' => $customer, 'tab' => $target ?? 'analysis']) }}" wire:navigate
                        class="group relative flex flex-col p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl hover:shadow-md hover:border-brand-blue/30 transition-all">

                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <!-- Initials Avatar -->
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center bg-brand-blue/10 text-brand-blue font-bold text-lg shrink-0">
                                    {{ substr($customer->name, 0, 1) }}
                                </div>
                                <div class="overflow-hidden">
                                    <h3
                                        class="font-bold text-zinc-900 dark:text-white group-hover:text-brand-blue transition-colors truncate">
                                        {{ $customer->name }}
                                    </h3>
                                    @if($customer->is_self_profile)
                                        <span
                                            class="inline-block text-[10px] font-bold px-1.5 py-0.5 rounded bg-brand-orange/10 text-brand-orange uppercase tracking-wide">
                                            {{ __('Me') }}
                                        </span>
                                    @else
                                        <span class="text-xs text-zinc-500">{{ __('Client') }}</span>
                                    @endif
                                </div>
                            </div>
                            <flux:icon.chevron-right
                                class="size-5 text-zinc-300 group-hover:text-brand-blue transition-colors" />
                        </div>

                        <div
                            class="mt-auto pt-4 border-t border-zinc-100 dark:border-zinc-800/50 text-sm text-zinc-500 space-y-1">
                            @if($customer->birth_date)
                                <div class="flex items-center gap-2">
                                    <flux:icon.calendar class="size-4 text-zinc-400" />
                                    <span>{{ $customer->birth_date->format('d.m.Y') }}</span>
                                </div>
                            @else
                                <div class="flex items-center gap-2 text-amber-600 font-medium">
                                    <flux:icon.exclamation-triangle class="size-4" />
                                    <span>{{ __('Missing Birth Data') }}</span>
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach

            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $customers->links() }}
            </div>

        </div>
    </div>
</div>
