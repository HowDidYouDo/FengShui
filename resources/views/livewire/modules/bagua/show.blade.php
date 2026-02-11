<?php
// /resources/views/livewire/modules/bagua/show.blade.php
use App\Models\Customer;
use App\Models\Project;
use App\Services\Metaphysics\MingGuaCalculator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new #[Layout('components.layouts.app')]
class extends Component {
    use AuthorizesRequests;

    public Customer $customer;

    // State für Tabs
    #[Url(keep: true)]
    public string $tab = 'analysis'; // 'analysis' | 'map' | 'family' | 'flying_stars'

    public Collection $floorPlans;
    public string $selectedFloorPlanId = '';
    
    // Toggle für Bagua-Anzeige: Asiatische Namen vs. Deutsche Lebensbereiche
    public bool $showAspirations = false;

    public function mount(Customer $customer, ?string $tab = null): void
    {
        $this->authorize('view', $customer);
        $this->customer = $customer;

        $this->floorPlans = $this->project ? $this->project->floorPlans : collect();
        if ($this->floorPlans->isNotEmpty()) {
            $this->selectedFloorPlanId = (string)$this->floorPlans->first()->id;
        }

        if ($tab && in_array($tab, ['analysis', 'map', 'family', 'flying_stars'])) {
            $this->tab = $tab;
        }
        
        // Load user preference for Bagua display
        $user = auth()->user();
        $this->showAspirations = $user->settings['show_bagua_aspirations'] ?? false;
    }
    
    public function updatedShowAspirations($value): void
    {
        // Save to user settings
        $user = auth()->user();
        $settings = $user->settings ?? [];
        $settings['show_bagua_aspirations'] = $value;
        $user->update(['settings' => $settings]);
    }

    protected $listeners = ['project-updated' => '$refresh'];

    // Computed Property für das Projekt (spart uns das manuelle Laden)
    public function getProjectProperty(): ?Project
    {
        return $this->customer->projects()->latest()->first();
    }

    public function getCalculatorProperty(): MingGuaCalculator
    {
        return new MingGuaCalculator();
    }

    public function getRoomTypeOptionsProperty(): array
    {
        return [
            'living_room' => __('Living Room'),
            'bedroom' => __('Bedroom'),
            'kitchen' => __('Kitchen'),
            'dining_room' => __('Dining Room'),
            'family_room' => __('Family Room'),
            'study_room' => __('Study / Office'),
            'nursery' => __('Nursery / Kids Room'),
            'guest_room' => __('Guest Room'),
            'entrance' => __('Entrance / Foyer'),
            'balcony' => __('Balcony / Terrace'),
            'garden' => __('Garden / Patio'),
            'hallway' => __('Hallway / Corridor'),
            'bathroom' => __('Bathroom'),
            'storage' => __('Storage / Closet'),
            'garage' => __('Garage'),
            'utility' => __('Utility Room'),
            'other' => __('Other'),
        ];
    }

    public function updateRoomType(int $noteId, string $type): void
    {
        $note = \App\Models\BaguaNote::find($noteId);
        if ($note && $note->floorPlan->project->customer_id === $this->customer->id) {
            $note->update(['room_type' => $type]);
            $this->dispatch('notify', message: __('Room type updated.'));
        }
    }

    public function updateStarsAnalysis(int $noteId, string $analysis): void
    {
        $note = \App\Models\BaguaNote::find($noteId);
        if ($note && $note->floorPlan->project->customer_id === $this->customer->id) {
            $note->update(['stars_analysis' => $analysis]);
            $this->dispatch('notify', message: __('Analysis updated.'));
        }
    }

    public function getArrowRotationsProperty(): array
    {
        return [
            'N' => 'rotate-0',
            'NE' => 'rotate-45',
            'E' => 'rotate-90',
            'SE' => 'rotate-135',
            'S' => 'rotate-180',
            'SW' => 'rotate-225',
            'W' => 'rotate-270',
            'NW' => 'rotate-315',
        ];
    }

    // Computed Property für die Analysen (Gua)
    public function getAnalysesProperty(): Collection
    {
        $calculator = $this->getCalculatorProperty();
        $results = collect();
        $user = auth()->user();

        $mainGua = null;

        // 1. Haupt-Person (Kunde)
        if ($this->customer->life_gua) {
            $mainGua = $this->customer->life_gua;
            $year = $calculator->getSolarYear($this->customer->birth_date);
            $yearElement = $calculator->getYearElement($year);
            $elementColors = $calculator->getElementColors($yearElement);
            $zodiac = $calculator->getZodiac($year);
            $relationship = $calculator->analyzeElementRelationship(
                $calculator->getAttributes($mainGua)['element'], // Gua Element
                $yearElement // Jahr Element
            );

            $results->push([
                'type' => 'Main',
                'person' => $this->customer,
                'gua' => $mainGua,
                'attributes' => $calculator->getAttributes($mainGua),
                'best_direction' => $calculator->getBestDirection($mainGua),
                'year_element' => $yearElement,
                'year_element_colors' => $elementColors,
                'zodiac' => $zodiac,
                'relationship' => $relationship,
                'partner_compatibility' => null,
            ]);
        }

        // 2. Familie (wenn Feature aktiv)
        if ($user->hasFeature('family')) {
            foreach ($this->customer->familyMembers as $member) {
                if ($member->life_gua) {
                    $gua = $member->life_gua;
                    $year = $calculator->getSolarYear($member->birth_date);
                    $yearElement = $calculator->getYearElement($year);
                    $elementColors = $calculator->getElementColors($yearElement);
                    $zodiac = $calculator->getZodiac($year);
                    $relationship = $calculator->analyzeElementRelationship(
                        $calculator->getAttributes($gua)['element'],
                        $yearElement
                    );

                    $partnerCompatibility = null;
                    if ($mainGua && in_array($member->relationship, [\App\Models\FamilyMember::RELATIONSHIP_PRIMARY_PARTNER, \App\Models\FamilyMember::RELATIONSHIP_SECONDARY_PARTNER])) {
                        $partnerCompatibility = $calculator->analyzePartnerCompatibility($mainGua, $gua);
                    }

                    $results->push([
                        'type' => 'Family',
                        'person' => $member,
                        'gua' => $gua,
                        'attributes' => $calculator->getAttributes($gua),
                        'best_direction' => $calculator->getBestDirection($gua),
                        'year_element' => $yearElement,
                        'year_element_colors' => $elementColors,
                        'zodiac' => $zodiac,
                        'relationship' => $relationship,
                        'partner_compatibility' => $partnerCompatibility,
                    ]);
                }
            }
        }

        return $results;
    }

    // Action: Neues Projekt anlegen (direkt hier, ohne extra Route!)
    public function createDefaultProject(): void
    {
        // Wir erstellen ein Standard-Projekt, damit der User sofort loslegen kann
        Project::create([
            'customer_id' => $this->customer->id,
            'name' => $this->customer->name . __("'s Home"),
        ]);

        // Durch Reactive State wird $this->project automatisch neu geladen
        $this->dispatch('project-created');
    }
};
?>

@php
    $arrowRotations = [
        'N' => 'rotate-0', 'NE' => 'rotate-45', 'E' => 'rotate-90', 'SE' => 'rotate-[135deg]',
        'S' => 'rotate-180', 'SW' => 'rotate-[225deg]', 'W' => '-rotate-90', 'NW' => '-rotate-45',
    ];
@endphp

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-heading font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
                {{ __('Bagua Analysis') }}: <span class="text-brand-blue">{{ $customer->name }}</span>
            </h2>

            <!-- Back Link Logic -->
            @php
                $hasClientsFeature = auth()->user()->hasFeature('crm');
            @endphp
            <flux:button icon="arrow-left" size="sm" variant="subtle"
                         :href="$hasClientsFeature ? route('modules.bagua') : route('dashboard')">
                {{ $hasClientsFeature ? __('Back to Clients') : __('Back to Dashboard') }}
            </flux:button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- TABS NAVIGATION (Livewire Driven) -->
            <div class="border-b border-zinc-200 dark:border-zinc-700">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button
                        wire:click="$set('tab', 'analysis')"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2 {{ $tab === 'analysis' ? 'border-brand-blue text-brand-blue' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}"
                    >
                        <flux:icon.sparkles class="size-4"/>
                        {{ __('Gua Analysis') }}
                    </button>

                    @php
                        $hasFamilyFeature = auth()->user()->hasFeature('family');
                    @endphp
                    @if($hasFamilyFeature)
                        <button
                            wire:click="$set('tab', 'family')"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2 {{ $tab === 'family' ? 'border-brand-blue text-brand-blue' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}"
                        >
                            <flux:icon.users class="size-4"/>
                            {{ __('Family Tree') }}
                        </button>
                    @endif

                    <button
                        wire:click="$set('tab', 'map')"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2 {{ $tab === 'map' ? 'border-brand-blue text-brand-blue' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}"
                    >
                        <flux:icon.map class="size-4"/>
                        {{ __('Floor Plans & Map') }}
                    </button>

                    @if(auth()->user()->hasFeature('flying_stars'))
                        <button
                            wire:click="$set('tab', 'flying_stars')"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2 {{ $tab === 'flying_stars' ? 'border-brand-blue text-brand-blue' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300' }}"
                        >
                            <flux:icon.sparkles class="size-4"/>
                            {{ __('Flying Stars Analysis') }}
                        </button>
                    @endif
                </nav>
            </div>

            <!-- TAB 1: ANALYSIS -->
            @if($tab === 'analysis')
                <div class="space-y-12">
                    @if($this->analyses->isEmpty())
                        <div
                            class="p-12 text-center bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                            <flux:icon.user class="size-12 mx-auto text-zinc-300 mb-4"/>
                            <h3 class="text-lg font-medium text-zinc-900 dark:text-white">{{ __('No Analysis Data') }}</h3>
                            <p class="text-zinc-500">{{ __('Please complete the profile (Birth Date & Gender) to see the analysis.') }}</p>
                            <flux:button class="mt-4"
                                         :href="route('profile.edit')">{{ __('Edit Profile') }}</flux:button>
                        </div>
                    @endif

                    @foreach($this->analyses as $analysis)
                        <div class="space-y-6">
                            <!-- Section Header (if multiple people) -->
                            @if($this->analyses->count() > 1)
                                <div class="flex items-center gap-3 pb-2 border-b border-zinc-200 dark:border-zinc-800">
                                    <div class="p-1.5 rounded bg-zinc-100 dark:bg-zinc-800">
                                        @if($analysis['type'] === 'Main')
                                            <flux:icon.user class="size-5 text-zinc-600"/>
                                        @else
                                            <flux:icon.users class="size-5 text-zinc-600"/>
                                        @endif
                                    </div>
                                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white">
                                        {{ $analysis['person']->name }}
                                        <span class="text-sm font-normal text-zinc-500">({{ $analysis['type'] === 'Main' ? __('Client') : $analysis['person']->getRelationshipLabel() }})</span>
                                    </h3>
                                </div>
                            @endif

                            <!-- HERO CARD (Gua Number) -->
                            <div
                                class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-xl border border-zinc-200 dark:border-zinc-800">
                                <div class="p-8 flex flex-col md:flex-row items-center gap-8">
                                    @php
                                        $calculator = $this->calculator;
                                        $arrowRotations = $this->arrowRotations;
                                    @endphp
                                    <div class="relative shrink-0">
                                        <div
                                            class="w-24 h-24 md:w-32 md:h-32 rounded-full flex items-center justify-center text-5xl font-bold border-4 shadow-inner {{ $calculator->getElementColors($analysis['attributes']['element'])[0] }} {{ $calculator->getElementColors($analysis['attributes']['element'])[1] }} {{ $calculator->getElementColors($analysis['attributes']['element'])[2] }}">
                                            {{ $analysis['gua'] }}
                                        </div>
                                        <div
                                            class="absolute -bottom-2 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-zinc-800 text-white text-xs font-bold uppercase tracking-wider border-2 border-white dark:border-zinc-900">
                                            {{ __($analysis['attributes']['element']) }}
                                        </div>
                                    </div>

                                    <div class="text-center md:text-left flex-1">
                                        <h4 class="text-2xl font-heading font-bold text-zinc-900 dark:text-white">
                                            Life Gua <span
                                                class="{{ $analysis['attributes']['colors'][0] }}">{{ $analysis['attributes']['name'] }}</span>
                                        </h4>

                                        <div class="flex items-center gap-2 mt-2 mb-2">
                                            @php
                                                $colors = $analysis['year_element_colors'];
                                            @endphp

                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold border {{ $colors[0] }} {{ $colors[1] }} {{ $colors[2] }}">
                                                <!-- Element Icon (Optional: Passendes Icon je Element) -->
                                                @if($analysis['year_element'] === 'Fire')
                                                    <flux:icon.fire class="size-3.5"/>
                                                @elseif($analysis['year_element'] === 'Water')
                                                    <flux:icon.beaker class="size-3.5"/>
                                                @elseif($analysis['year_element'] === 'Wood')
                                                    <flux:icon.sparkles class="size-3.5"/>
                                                    <!-- Tree gibt es nicht in Flux Standard, Sparkles ist ok -->
                                                @elseif($analysis['year_element'] === 'Earth')
                                                    <flux:icon.globe-americas class="size-3.5"/>
                                                @else
                                                    <flux:icon.cube class="size-3.5"/> <!-- Metal -->
                                                @endif

                                                {{ __($analysis['year_element']) }} {{ __($analysis['zodiac']) }}
                                            </span>

                                            <span class="text-xs text-zinc-400">({{ __('Birth Year') }})</span>
                                        </div>

                                        <p class="text-zinc-600 dark:text-zinc-400 text-sm">
                                            {{ __('Belongs to the') }} <strong
                                                class="text-zinc-900 dark:text-zinc-200">{{ __($analysis['attributes']['group']) }} {{ __('Group') }}</strong>.
                                        </p>

                                        @if(isset($analysis['best_direction']))
                                            <p class="text-zinc-600 dark:text-zinc-400 text-sm mt-1 flex items-center gap-1">
                                                <flux:icon.star class="size-3 text-amber-500 inline mr-1" />
                                                {{ __('Best Compass Direction') }}: 
                                                <strong class="text-zinc-900 dark:text-zinc-200">
                                                    {{ $analysis['best_direction']['direction'] }} ({{ $analysis['best_direction']['degrees'] }})
                                                </strong>
                                            </p>
                                        @endif

                                        <!-- Flying Stars Summary (if applicable and authorized) -->
                                        @if($analysis['type'] === 'Main' && auth()->user()->hasFeature('flying_stars') && $this->project?->facing_mountain)
                                            <div class="mt-4 flex flex-wrap gap-2">
                                                <span
                                                    class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-bold border border-brand-orange/20 bg-brand-orange/5 text-brand-orange uppercase">
                                                    <flux:icon.sparkles class="size-3"/>
                                                    {{ __('Flying Stars') }}: {{ $this->project->facing_mountain }}
                                                    @if($this->project->is_replacement_chart)
                                                        ({{ __('Replacement') }})
                                                    @endif
                                                </span>
                                                @if($this->project->special_chart_type)
                                                    <span
                                                        class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-bold border border-purple-500/20 bg-purple-500/5 text-purple-600 uppercase">
                                                        {{ $this->project->special_chart_type }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif

                                        <!-- Relationship Infos -->
                                        <div
                                            class="mt-4 grid grid-cols-1 {{ $analysis['partner_compatibility'] ? 'sm:grid-cols-2' : '' }} gap-4">
                                            <!-- Element Relationship Info -->
                                            <div
                                                class="p-3 rounded-lg border border-zinc-100 dark:border-zinc-700 {{ $analysis['relationship']['color'] }} dark:bg-opacity-10">
                                                <div class="flex items-start gap-3">
                                                    <div class="mt-0.5">
                                                        @if($analysis['relationship']['quality'] === 'Excellent')
                                                            <flux:icon.check-circle class="size-5"/>
                                                        @elseif($analysis['relationship']['quality'] === 'Challenging')
                                                            <flux:icon.exclamation-circle class="size-5"/>
                                                        @else
                                                            <flux:icon.information-circle class="size-5"/>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <h5 class="text-sm font-bold">
                                                            {{ $analysis['relationship']['label'] }} {{ __('Relationship') }}
                                                        </h5>
                                                        <p class="text-xs opacity-90 mt-0.5 leading-relaxed">
                                                            {{ $analysis['relationship']['desc'] }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Partner Compatibility Info -->
                                            @if($analysis['partner_compatibility'])
                                                <div
                                                    class="p-3 rounded-lg border border-zinc-100 dark:border-zinc-700 {{ $analysis['partner_compatibility']['color'] }} dark:bg-opacity-10">
                                                    <div class="flex items-start gap-3">
                                                        <div class="mt-0.5">
                                                            @if($analysis['partner_compatibility']['quality'] === 'Excellent')
                                                                <flux:icon.heart class="size-5 fill-current"/>
                                                            @elseif($analysis['partner_compatibility']['quality'] === 'Challenging')
                                                                <flux:icon.bolt class="size-5 fill-current"/>
                                                            @else
                                                                <flux:icon.star class="size-5 fill-current"/>
                                                            @endif
                                                        </div>
                                                        <div>
                                                            <h5 class="text-sm font-bold">
                                                                {{ __('Partner Compatibility') }}
                                                                : {{ $analysis['partner_compatibility']['label'] }}
                                                            </h5>
                                                            <p class="text-xs opacity-90 mt-0.5 leading-relaxed">
                                                                {{ $analysis['partner_compatibility']['desc'] }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <!-- DIRECTIONS GRID (Compact) -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Good Directions -->
                                <div
                                    class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800">
                                    <h5 class="text-sm font-bold text-green-600 uppercase tracking-wider mb-4 flex items-center gap-2">
                                        <flux:icon.hand-thumb-up class="size-4"/> {{ __('Auspicious') }}
                                    </h5>
                                    <div class="grid grid-cols-2 gap-3">
                                        @foreach($analysis['attributes']['good_directions'] as $dir => $quality)
                                            <div
                                                class="flex items-center gap-2 p-2 rounded bg-zinc-50 dark:bg-zinc-800/50">
                                                <flux:icon.arrow-up
                                                    class="size-4 text-zinc-400 {{ $arrowRotations[$dir] ?? 'rotate-0' }}"/>
                                                <div>
                                                    <div
                                                        class="text-xs font-bold text-zinc-700 dark:text-zinc-300">{{ $quality }}</div>
                                                    <div class="text-[10px] text-zinc-400">{{ $dir }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Bad Directions -->
                                <div
                                    class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800">
                                    <h5 class="text-sm font-bold text-red-600 uppercase tracking-wider mb-4 flex items-center gap-2">
                                        <flux:icon.hand-thumb-down class="size-4"/> {{ __('Inauspicious') }}
                                    </h5>
                                    <div class="grid grid-cols-2 gap-3">
                                        @foreach($analysis['attributes']['bad_directions'] as $dir => $quality)
                                            <div
                                                class="flex items-center gap-2 p-2 rounded bg-zinc-50 dark:bg-zinc-800/50 opacity-75">
                                                <flux:icon.arrow-up
                                                    class="size-4 text-zinc-400 {{ $arrowRotations[$dir] ?? 'rotate-0' }}"/>
                                                <div>
                                                    <div
                                                        class="text-xs font-bold text-zinc-600 dark:text-zinc-400">{{ $quality }}</div>
                                                    <div class="text-[10px] text-zinc-400">{{ $dir }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    
                    @include('livewire.modules.bagua.partials.residents-summary', ['project' => $this->project, 'showAspirations' => $showAspirations])    
                </div>
            @endif

            <!-- TAB 3: FAMILY -->
            @if($tab === 'family' && auth()->user()->hasFeature('family'))
                <div class="space-y-6">
                    <livewire:modules.bagua.managefamilymembers
                        :customer="$customer"
                        :key="'family-'.$customer->id"
                    />
                </div>
            @endif

            <!-- TAB 2: MAP -->
            @if($tab === 'map')
                <div class="space-y-8">

                    @if($this->project)
                        <!-- Projekt Info -->
                        <div
                            class="flex items-center justify-between bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                            <div>
                                <span
                                    class="text-xs font-bold text-zinc-400 uppercase tracking-wider">{{ __('Active Project') }}</span>
                                <h4 class="font-bold text-lg text-brand-blue">{{ $this->project->name }}</h4>
                                @if($this->project->compass_direction)
                                    <div class="text-sm text-zinc-500 mt-1 flex items-center flex-wrap gap-x-2">
                                        @php
                                            $currCalc = $this->calculator;
                                            $currCompass = $this->project->compass_direction;
                                            $currSitting = $this->project->sitting_direction;
                                            
                                            if ($currSitting === null) {
                                                $currSitting = fmod($currCompass + 180, 360);
                                            }

                                            $currSitzGua = $currCalc->degreesToTrigram($currSitting);
                                            $currGroup = $currCalc->calculateHouseGroup($currSitzGua);
                                            
                                            $facingMtn = $currCalc->calculateMountain($currCompass);
                                            $sitzMtn = $currCalc->calculateMountain($currSitting);
                                        @endphp
                                        <span>
                                            {{ __('Facing') }}: <strong>{{ $currCompass }}° ({{ $facingMtn }})</strong>
                                        </span>
                                        <span class="text-zinc-300">|</span>
                                        <span>
                                            {{ __('Sitting') }}: <strong>{{ $currSitting }}° ({{ $sitzMtn }})</strong>
                                        </span>
                                        <span class="text-zinc-300">|</span>
                                        <span>
                                            {{ __('Group') }}: <strong>{{ $currGroup }}</strong>
                                        </span>
                                        <span class="text-zinc-300">|</span>
                                        <span>
                                            {{ __('Year') }}: <strong>{{ $this->project->settled_year }}</strong>
                                        </span>
                                        <span class="text-zinc-300">|</span>
                                        <span>
                                            {{ __('Period') }}: <span
                                            class="inline-flex items-center justify-center size-5 rounded-full bg-zinc-100 dark:bg-zinc-700 text-xs font-bold">{{ $this->project->period ?? '-' }}</span>
                                        </span>
                                    </div>
                                @else
                                    <p class="text-sm text-amber-600 mt-1 flex items-center gap-1">
                                        <flux:icon.exclamation-triangle
                                            class="size-4"/> {{ __('Missing Compass Direction') }}
                                    </p>
                                @endif
                            </div>

                            <!-- EDIT BUTTON (COMPONENT) -->
                            <livewire:modules.bagua.editproject :project="$this->project"
                                                                :key="'edit-'.$this->project->id"/>
                        </div>

                        <!-- HIER BINDEN WIR DIE UPLOAD KOMPONENTE EIN -->
                        <!-- Mit :key sorgen wir dafür, dass sie neu rendert, wenn sich das Projekt ändert -->
                        <livewire:modules.bagua.managefloorplans
                            :customer="$customer"
                            :project="$this->project"
                            :key="'manager-'.$this->project->id"
                        />

                    @else
                        <!-- Kein Projekt -> Create Button -->
                        <div
                            class="text-center py-12 border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl">
                            <flux:icon.home class="size-12 mx-auto text-zinc-300 mb-4"/>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('No Project Found') }}</h3>
                            <p class="text-zinc-500 mb-6">{{ __('Create a project to start uploading floor plans.') }}</p>

                            <flux:button wire:click="createDefaultProject" variant="primary">
                                {{ __('Create New Project') }}
                            </flux:button>
                        </div>
                    @endif

                </div>
            @endif

            <!-- TAB 4: FLYING STARS -->
            @if($tab === 'flying_stars' && auth()->user()->hasFeature('flying_stars'))
                <div class="space-y-6">
                    @if($this->project)
                        <div
                            class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-xl border border-zinc-200 dark:border-zinc-800">
                            <div class="p-8">
                                @include('livewire.modules.bagua.partials.flying-stars-report', [
                                    'project' => $this->project,
                                    'floorPlans' => $this->floorPlans,
                                    'selectedFloorPlanId' => $this->selectedFloorPlanId,
                                    'roomTypeOptions' => $this->roomTypeOptions,
                                    'calculator' => $this->calculator
                                ])
                            </div>
                        </div>
                    @else
                        <div
                            class="text-center py-12 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                            <flux:icon.home class="size-12 mx-auto text-zinc-300 mb-4"/>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('No Project Found') }}</h3>
                            <p class="text-zinc-500">{{ __('Please create a project first.') }}</p>
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</div>
