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
    public $compass_direction;
    public $sitting_direction;
    public $ventilation_direction;
    public $facing_mountain;
    public $is_replacement_chart;
    public $special_chart_type;

    public $facingMountainOptions = [];

    // Modal State
    public bool $showModal = false;

    public function mount(Project $project)
    {
        $this->authorize('update', $project);
        $this->project = $project;

        $this->name = $project->name;
        $this->settled_year = $project->settled_year;
        $this->compass_direction = $project->compass_direction;
        $this->sitting_direction = $project->sitting_direction;
        $this->ventilation_direction = $project->ventilation_direction;
        $this->facing_mountain = $project->facing_mountain;
        $this->is_replacement_chart = $project->is_replacement_chart;
        $this->special_chart_type = $project->special_chart_type;

        $this->facingMountainOptions = collect(\App\Services\Metaphysics\FlyingStarService::MOUNTAINS)
            ->mapWithKeys(fn($m) => [$m['name'] => $m['name']])
            ->toArray();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'settled_year' => 'required|integer|min:1900|max:2100',
            'compass_direction' => 'required|numeric|min:0|max:360',
            'sitting_direction' => 'nullable|numeric|min:0|max:360',
            'ventilation_direction' => 'nullable|numeric|min:0|max:360',
            'facing_mountain' => 'nullable|string',
            'is_replacement_chart' => 'boolean',
            'special_chart_type' => 'nullable|string|max:255',
        ]);

        $period = $this->calculatePeriod($this->settled_year);

        $this->project->update([
            'name' => $this->name,
            'settled_year' => $this->settled_year,
            'compass_direction' => $this->compass_direction,
            'sitting_direction' => $this->sitting_direction,
            'ventilation_direction' => $this->ventilation_direction,
            'period' => $period,
            'facing_mountain' => $this->facing_mountain,
            'is_replacement_chart' => $this->is_replacement_chart,
            'special_chart_type' => $this->special_chart_type,
        ]);

        $this->showModal = false;
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
                <flux:input wire:model="name" :label="__('Project Name')" required/>

                <div class="grid grid-cols-2 gap-6">
                    <!-- Year -->
                    <flux:input wire:model="settled_year" type="number" :label="__('Move-In Year (Settled)')"
                                :placeholder="__('e.g. 2020')" required/>

                    <!-- Name (verschoben für besseres Layout, oder oben lassen) -->
                </div>

                <div
                    class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-100 dark:border-zinc-700 space-y-4">
                    <h4 class="text-sm font-bold text-zinc-700 dark:text-zinc-300 flex items-center gap-2">
                        <flux:icon.globe-alt class="size-4"/> {{ __('Compass Readings') }}
                    </h4>

                    <div class="grid grid-cols-2 gap-6">
                        <!-- COMPASS (FACING) -->
                        <div class="space-y-1">
                            <flux:input wire:model="compass_direction" type="number" step="0.01"
                                        :label="__('North Deviation (Compass / Facing) (°)')" placeholder="0.00" required/>
                            <p class="text-[10px] text-zinc-400">{{ __('Compass reading causing the North deviation.') }}
                            </p>
                        </div>

                        <!-- SITTING -->
                        <div class="space-y-1">
                            <flux:input wire:model="sitting_direction" type="number" step="0.01"
                                        :label="__('Sitting Direction (°)')" placeholder="0.00"/>
                            <p class="text-[10px] text-zinc-400">{{ __('Defines the house energy (Trigram). Leave empty to derive from Compass (opposite).') }}</p>
                        </div>

                        <!-- VENTILATION -->
                        <div class="space-y-1">
                            <flux:input wire:model="ventilation_direction" type="number" step="0.01"
                                        :label="__('Ventilation Direction (°)')" placeholder="0.00"/>
                            <p class="text-[10px] text-zinc-400">{{ __('Main airflow/ventilation direction.') }}</p>
                        </div>
                    </div>

                    @if(auth()->user()->hasFeature('flying_stars'))
                        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700 space-y-4">
                            <h4 class="text-sm font-bold text-zinc-700 dark:text-zinc-300 flex items-center gap-2">
                                <flux:icon.sparkles class="size-4"/> {{ __('Flying Stars Overrides') }}
                            </h4>

                            <div class="grid grid-cols-2 gap-6">
                                <flux:select wire:model="facing_mountain" :label="__('Facing Mountain')">
                                    <flux:select.option value="">{{ __('Auto-Calculate') }}</flux:select.option>
                                    @foreach($facingMountainOptions as $val => $label)
                                        <flux:select.option :value="$val">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:select wire:model="special_chart_type" :label="__('Special Chart Type')">
                                    <flux:select.option value="">{{ __('None') }}</flux:select.option>
                                    <flux:select.option
                                        value="Dual Star at Facing">{{ __('Dual Star at Facing') }}</flux:select.option>
                                    <flux:select.option
                                        value="Dual Star at Sitting">{{ __('Dual Star at Sitting') }}</flux:select.option>
                                    <flux:select.option
                                        value="Twin Stars at Door">{{ __('Twin Stars at Door') }}</flux:select.option>
                                    <flux:select.option
                                        value="Reverse Merit Chart">{{ __('Reverse Merit Chart') }}</flux:select.option>
                                    <flux:select.option value="Sum of Ten">{{ __('Sum of Ten') }}</flux:select.option>
                                    <flux:select.option
                                        value="String of Pearls">{{ __('String of Pearls') }}</flux:select.option>
                                    <flux:select.option
                                        value="Parent String">{{ __('Parent String') }}</flux:select.option>
                                </flux:select>
                            </div>

                            <div class="flex items-center gap-2">
                                <flux:checkbox wire:model="is_replacement_chart"
                                               :label="__('Is Replacement Chart (Kong Wang)')"/>
                            </div>
                            <p class="text-[10px] text-zinc-500 italic">
                                {{ __('Note: Facing Mountain and Replacement status are normally auto-calculated from the Facing Direction. Only change these if you want to force a specific chart.') }}
                            </p>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <flux:button wire:click="$set('showModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
