<?php
// /resources/views/livewire/modules/bagua/editproject.blade.php
use App\Models\Project;
use Livewire\Volt\Component;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new class extends Component {
    use AuthorizesRequests;

    public Project $project;

    // Form Fields
    public $name;
    public $settled_year;
    public $facing_direction;
    public $ventilation_direction; // Optional, falls man es erfassen will

    // Modal State
    public bool $showModal = false;

    public function mount(Project $project)
    {
        $this->authorize('update', $project);
        $this->project = $project;

        $this->name = $project->name;
        $this->settled_year = $project->settled_year;
        $this->facing_direction = $project->facing_direction;
        $this->ventilation_direction = $project->ventilation_direction;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'settled_year' => 'required|integer|min:1900|max:2100',
            'facing_direction' => 'required|numeric|min:0|max:360',
            'ventilation_direction' => 'nullable|numeric|min:0|max:360',
        ]);

        // Periode berechnen (Feng Shui Logik)
        // 1984-2003: 7, 2004-2023: 8, 2024-2043: 9
        $period = $this->calculatePeriod($this->settled_year);

        $this->project->update([
            'name' => $this->name,
            'settled_year' => $this->settled_year,
            'facing_direction' => $this->facing_direction,
            'ventilation_direction' => $this->ventilation_direction,
            'period' => $period,
        ]);

        $this->showModal = false;

        // Events feuern, damit Parent Components aktualisieren
        $this->dispatch('project-updated');
    }

    private function calculatePeriod(int $year): int
    {
        if ($year >= 2044)
            return 1;
        if ($year >= 2024)
            return 9;
        if ($year >= 2004)
            return 8;
        if ($year >= 1984)
            return 7;
        if ($year >= 1964)
            return 6;
        if ($year >= 1944)
            return 5;
        if ($year >= 1924)
            return 4;
        if ($year >= 1904)
            return 3;
        if ($year >= 1884)
            return 2;
        if ($year >= 1864)
            return 1;
        if ($year >= 1844)
            return 9;
        return 8; // Fallback
    }
};
?>

<div>
    <!-- Trigger Button -->
    <flux:button icon="pencil" size="sm" variant="subtle" wire:click="$set('showModal', true)">
        {{ __('Edit Details') }}
    </flux:button>

    <!-- MODAL -->
    <flux:modal wire:model="showModal" class="min-w-[20rem] md:min-w-[32rem]">
        <div class="space-y-6">
            <div>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('Project Settings') }}</h3>
                <p class="text-sm text-zinc-500">{{ __('Define the energetic parameters for this building.') }}</p>
            </div>

            <form wire:submit="save" class="space-y-6">

                <!-- Name -->
                <flux:input wire:model="name" :label="__('Project Name')" required />

                <div class="grid grid-cols-2 gap-6">
                    <!-- Year -->
                    <flux:input wire:model="settled_year" type="number" :label="__('Move-In Year (Settled)')"
                        :placeholder="__('e.g. 2020')" required />

                    <!-- Name (verschoben für besseres Layout, oder oben lassen) -->
                </div>

                <div
                    class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-100 dark:border-zinc-700 space-y-4">
                    <h4 class="text-sm font-bold text-zinc-700 dark:text-zinc-300 flex items-center gap-2">
                        <flux:icon.globe-alt class="size-4" /> {{ __('Compass Readings') }}
                    </h4>

                    <div class="grid grid-cols-2 gap-6">
                        <!-- FACING -->
                        <div class="space-y-1">
                            <flux:input wire:model="facing_direction" type="number" step="0.01"
                                :label="__('Facing Direction (°)')" placeholder="0.00" required />
                            <p class="text-[10px] text-zinc-400">{{ __('The direction the house faces (Yang side).') }}
                            </p>
                        </div>

                        <!-- VENTILATION -->
                        <div class="space-y-1">
                            <flux:input wire:model="ventilation_direction" type="number" step="0.01"
                                :label="__('Ventilation Direction (°)')" placeholder="0.00" required />
                            <p class="text-[10px] text-zinc-400">{{ __('Main airflow/ventilation direction.') }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <flux:button wire:click="$set('showModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
