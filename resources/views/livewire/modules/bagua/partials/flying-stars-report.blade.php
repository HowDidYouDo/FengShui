@php
    $fsService = app(\App\Services\Metaphysics\FlyingStarService::class);
    $chart = $fsService->calculateChart(
        $project->period,
        $project->facing_direction,
        $project->facing_mountain
    );

    $gridLabels = [
        2 => 'Southwest (KUN)',
        3 => 'East (ZHEN)',
        4 => 'Southeast (XUN)',
        6 => 'Northwest (QIAN)',
        7 => 'West (DUI)',
        8 => 'Northeast (GEN)',
        9 => 'South (LI)',
        1 => 'North (KAN)',
        5 => 'Center (Tai Qi)'
    ];

    $currentFloorPlan = $floorPlans->find($selectedFloorPlanId) ?? $floorPlans->first();
    $notes = $currentFloorPlan ? $currentFloorPlan->baguaNotes->keyBy('gua_number') : collect();
@endphp

<div class="space-y-8">
    {{-- Header mit Projekt-Info --}}
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-2xl font-heading font-bold text-zinc-900 dark:text-white">{{ __('Flying Stars Report') }}</h3>
            <p class="text-zinc-500 italic">{{ __('Detailed energetic analysis for') }} {{ $project->name }}</p>
        </div>
        <div class="flex items-center gap-4">
            @if($floorPlans->count() > 1)
                <select wire:model.live="selectedFloorPlanId" class="text-sm bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-700 rounded-lg px-3 py-2 shadow-sm focus:ring-brand-blue focus:border-brand-blue">
                    @foreach($floorPlans as $fp)
                        <option value="{{ $fp->id }}">{{ $fp->title }}</option>
                    @endforeach
                </select>
            @endif
            <livewire:modules.bagua.editproject :project="$project" :key="'edit-fs-'.$project->id"/>
        </div>
    </div>

    {{-- Projekt Metadaten --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-100 dark:border-zinc-700 shadow-sm">
            <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">{{ __('Building Period') }}</span>
            <div class="text-3xl font-black text-brand-blue">{{ $project->period }}</div>
            <p class="text-xs text-zinc-500 mt-1">{{ __('Settled in') }} {{ $project->settled_year }}</p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-100 dark:border-zinc-700 shadow-sm">
            <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">{{ __('Facing Direction') }}</span>
            <div class="text-3xl font-black text-brand-orange">{{ $project->facing_direction }}°</div>
            <p class="text-xs text-zinc-500 mt-1">{{ __('Mountain') }}: <strong>{{ $project->facing_mountain ?: 'Auto' }}</strong></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-100 dark:border-zinc-700 shadow-sm">
            <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">{{ __('Chart Type') }}</span>
            <div class="text-xl font-bold text-zinc-800 dark:text-zinc-200 mt-1">
                @if($project->is_replacement_chart)
                    <span class="text-amber-600">Replacement Chart (Kong Wang)</span>
                @else
                    <span class="text-green-600">Standard Chart</span>
                @endif
            </div>
            @if($project->special_chart_type)
                <p class="text-xs text-purple-600 font-bold mt-1 uppercase">{{ $project->special_chart_type }}</p>
            @endif
        </div>
    </div>

    {{-- Grundriss mit Bagua & Flying Stars Overlay --}}
    @if($currentFloorPlan && $currentFloorPlan->outer_bounds)
        <div class="relative bg-zinc-100 dark:bg-zinc-950 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden shadow-inner p-4">
            <div class="max-w-4xl mx-auto relative group">
                @php
                    $media = $currentFloorPlan->getFirstMedia('floor_plans');
                    $imageUrl = $media ? route('media.floor-plans', ['floorPlan' => $currentFloorPlan->id, 'media' => $media->id]) : null;
                    $bounds = $currentFloorPlan->outer_bounds;
                @endphp

                @if($imageUrl)
                    <img src="{{ $imageUrl }}" class="w-full h-auto rounded shadow-lg" alt="{{ $currentFloorPlan->title }}">

                    <svg class="absolute inset-0 w-full h-full pointer-events-none z-10"
                         viewBox="0 0 {{ $bounds['image_width'] }} {{ $bounds['image_height'] }}"
                         preserveAspectRatio="xMidYMid meet">

                        {{-- Bagua Gitter mit mehr Transparenz --}}
                        <g opacity="0.4">
                            <rect x="{{ $bounds['x1'] }}" y="{{ $bounds['y1'] }}"
                                  width="{{ $bounds['x2'] - $bounds['x1'] }}" height="{{ $bounds['y2'] - $bounds['y1'] }}"
                                  fill="none" stroke="#3b82f6" stroke-width="2" vector-effect="non-scaling-stroke" />

                            <line x1="{{ $bounds['x1'] }}" y1="{{ $bounds['y1'] + ($bounds['y2'] - $bounds['y1']) * 0.33 }}"
                                  x2="{{ $bounds['x2'] }}" y2="{{ $bounds['y1'] + ($bounds['y2'] - $bounds['y1']) * 0.33 }}"
                                  stroke="#ef4444" stroke-width="1.5" vector-effect="non-scaling-stroke" />
                            <line x1="{{ $bounds['x1'] }}" y1="{{ $bounds['y1'] + ($bounds['y2'] - $bounds['y1']) * 0.66 }}"
                                  x2="{{ $bounds['x2'] }}" y2="{{ $bounds['y1'] + ($bounds['y2'] - $bounds['y1']) * 0.66 }}"
                                  stroke="#ef4444" stroke-width="1.5" vector-effect="non-scaling-stroke" />

                            <line x1="{{ $bounds['x1'] + ($bounds['x2'] - $bounds['x1']) * 0.33 }}" y1="{{ $bounds['y1'] }}"
                                  x2="{{ $bounds['x1'] + ($bounds['x2'] - $bounds['x1']) * 0.33 }}" y2="{{ $bounds['y2'] }}"
                                  stroke="#ef4444" stroke-width="1.5" vector-effect="non-scaling-stroke" />
                            <line x1="{{ $bounds['x1'] + ($bounds['x2'] - $bounds['x1']) * 0.66 }}" y1="{{ $bounds['y1'] }}"
                                  x2="{{ $bounds['x1'] + ($bounds['x2'] - $bounds['x1']) * 0.66 }}" y2="{{ $bounds['y2'] }}"
                                  stroke="#ef4444" stroke-width="1.5" vector-effect="non-scaling-stroke" />
                        </g>

                        {{-- Sektor-Farben (Hintergrund) --}}
                        @foreach($currentFloorPlan->baguaNotes as $note)
                            @php
                                $noteData = json_decode($note->content, true);
                                if (!$noteData && $note->content) $noteData = json_decode(json_decode($note->content, true), true) ?? [];
                                $bgcolor = $noteData['bg_color'] ?? '#d1d5db';
                                $row = 2 - floor(($note->gua_number - 1) / 3);
                                $col = 2 - (($note->gua_number - 1) % 3);
                                $sectorWidth = ($bounds['x2'] - $bounds['x1']) / 3;
                                $sectorHeight = ($bounds['y2'] - $bounds['y1']) / 3;
                                $sectorX = $bounds['x1'] + $col * $sectorWidth;
                                $sectorY = $bounds['y1'] + $row * $sectorHeight;
                            @endphp
                            <rect x="{{ $sectorX }}" y="{{ $sectorY }}" width="{{ $sectorWidth }}" height="{{ $sectorHeight }}"
                                  fill="{{ $bgcolor }}" fill-opacity="0.2" />
                        @endforeach

                        {{-- Flying Stars (Kräftig) --}}
                        @foreach($currentFloorPlan->baguaNotes as $note)
                            @php
                                $row = 2 - floor(($note->gua_number - 1) / 3);
                                $col = 2 - (($note->gua_number - 1) % 3);
                                $sectorWidth = ($bounds['x2'] - $bounds['x1']) / 3;
                                $sectorHeight = ($bounds['y2'] - $bounds['y1']) / 3;
                                $sectorX = $bounds['x1'] + $col * $sectorWidth;
                                $sectorY = $bounds['y1'] + $row * $sectorHeight;
                                $fontSize = max(12, ($bounds['x2']-$bounds['x1'])/25);
                            @endphp
                            <g class="flying-stars font-black" style="font-size: {{ $fontSize }}px">
                                @if($note->mountain_star)
                                    <text x="{{ $sectorX + $sectorWidth * 0.2 }}" y="{{ $sectorY + $sectorHeight * 0.3 }}" fill="white" stroke="white" stroke-width="3" stroke-linejoin="round" text-anchor="middle">{{ $note->mountain_star }}</text>
                                    <text x="{{ $sectorX + $sectorWidth * 0.2 }}" y="{{ $sectorY + $sectorHeight * 0.3 }}" fill="#b45309" text-anchor="middle">{{ $note->mountain_star }}</text>
                                @endif
                                @if($note->water_star)
                                    <text x="{{ $sectorX + $sectorWidth * 0.8 }}" y="{{ $sectorY + $sectorHeight * 0.3 }}" fill="white" stroke="white" stroke-width="3" stroke-linejoin="round" text-anchor="middle">{{ $note->water_star }}</text>
                                    <text x="{{ $sectorX + $sectorWidth * 0.8 }}" y="{{ $sectorY + $sectorHeight * 0.3 }}" fill="#1d4ed8" text-anchor="middle">{{ $note->water_star }}</text>
                                @endif
                                @if($note->base_star)
                                    <text x="{{ $sectorX + $sectorWidth * 0.5 }}" y="{{ $sectorY + $sectorHeight * 0.85 }}" fill="white" stroke="white" stroke-width="3" stroke-linejoin="round" text-anchor="middle">{{ $note->base_star }}</text>
                                    <text x="{{ $sectorX + $sectorWidth * 0.5 }}" y="{{ $sectorY + $sectorHeight * 0.85 }}" fill="#52525b" text-anchor="middle">{{ $note->base_star }}</text>
                                @endif
                            </g>
                        @endforeach
                    </svg>
                @endif
            </div>
        </div>
    @else
        <div class="p-12 text-center bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700">
            <flux:icon.map class="size-12 mx-auto text-zinc-300 mb-4"/>
            <h3 class="text-lg font-medium">{{ __('No Grid Calculated') }}</h3>
            <p class="text-zinc-500">{{ __('Please open the editor and calculate the Bagua grid first.') }}</p>
            <flux:button class="mt-4" :href="route('modules.bagua.editor', $selectedFloorPlanId ?: $floorPlans->first()->id)">{{ __('Open Editor') }}</flux:button>
        </div>
    @endif

    <div class="border-t border-zinc-200 dark:border-zinc-800 pt-8">
        <h4 class="text-xl font-bold text-zinc-800 dark:text-zinc-200 mb-6 flex items-center gap-2">
            <flux:icon.document-text class="size-5 text-brand-blue" />
            {{ __('Sector Breakdown & Interpretation') }}
        </h4>

        {{-- Zweispaltige Sektor-Details (Nur die 8 Richtungen) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach([9,1,3,7,2,8,4,6] as $pos)
                @php
                    $note = $notes->get($pos);
                @endphp
                @if($note)
                    <div class="p-6 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm space-y-4 hover:border-brand-blue/30 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">{{ $gridLabels[$pos] }}</div>
                                <div class="text-2xl font-black text-zinc-800 dark:text-zinc-200 mt-1">
                                    <span class="text-amber-700">{{ $chart['mountain'][$pos] }}</span>
                                    <span class="text-zinc-300 mx-2">·</span>
                                    <span class="text-blue-700">{{ $chart['water'][$pos] }}</span>
                                    <span class="text-zinc-300 mx-2">·</span>
                                    <span class="text-zinc-500">{{ $chart['base'][$pos] }}</span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                @if($chart['mountain'][$pos] == $project->period || $chart['water'][$pos] == $project->period)
                                     <flux:badge color="green" size="sm" variant="pill">{{ __('Timely') }}</flux:badge>
                                @endif

                                <select
                                    wire:change="updateRoomType({{ $note->id }}, $event.target.value)"
                                    class="text-[10px] uppercase font-bold bg-zinc-50 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 rounded px-2 py-1"
                                >
                                    <option value="">{{ __('Select Room Type') }}</option>
                                    @foreach($roomTypeOptions as $value => $label)
                                        <option value="{{ $value }}" {{ $note->room_type === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Bewohner & Kompatibilität --}}
                        @if($note->roomAssignments->isNotEmpty())
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">{{ __('Assigned Residents') }}</label>
                                <div class="grid grid-cols-1 gap-2">
                                    @foreach($note->roomAssignments as $assignment)
                                        @php
                                            $person = $assignment->person;
                                            $fsComp = $person->life_gua ? $calculator->analyzeFlyingStarCompatibility($person->life_gua, $chart['mountain'][$pos], $chart['water'][$pos]) : null;
                                        @endphp
                                        <div class="flex items-start gap-3 p-2 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-700">
                                            <div class="relative shrink-0">
                                                <img src="https://ui-avatars.com/api/?name={{ urlencode($person->name) }}&background=random" class="size-8 rounded-full border border-white shadow-sm">
                                                @if($fsComp)
                                                    <div class="absolute -top-1 -right-1 size-3 rounded-full border border-white {{ $fsComp['mountain']['quality'] === 'Excellent' ? 'bg-green-500' : ($fsComp['mountain']['quality'] === 'Challenging' ? 'bg-red-500' : 'bg-zinc-400') }}"></div>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-xs font-bold truncate">{{ $person->name }}</div>
                                                @if($fsComp)
                                                    <div class="text-[10px] text-zinc-500 leading-tight mt-0.5">
                                                        {{ __($fsComp['mountain']['label']) }} {{ __('Mountain Star') }} ({{ __($fsComp['mountain']['element']) }})
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Interpretation --}}
                        <div class="p-3 rounded-lg bg-brand-blue/5 border border-brand-blue/10 text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                            @php
                                $m = $chart['mountain'][$pos];
                                $w = $chart['water'][$pos];
                                $isGood = ($m == 8 || $m == 9 || $m == 1) && ($w == 8 || $w == 9 || $w == 1);
                            @endphp
                            <span class="font-bold text-brand-blue">{{ __('Combination') }} {{ $m }}-{{ $w }}:</span>
                            @if($m == $w)
                                {{ __('Double Star formation. Energy is highly concentrated here.') }}
                            @elseif($isGood)
                                {{ __('Prosperous combination. Good for health and wealth.') }}
                            @else
                                {{ __('Neutral or complex combination. Requires careful balance of elements.') }}
                            @endif
                        </div>

                        {{-- Beraternotizen --}}
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">{{ __('Consultant Notes') }}</label>
                            <textarea
                                wire:blur="updateStarsAnalysis({{ $note->id }}, $event.target.value)"
                                class="w-full text-xs bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-700 rounded-lg p-3 focus:ring-brand-blue focus:border-brand-blue h-24 shadow-sm"
                                placeholder="{{ __('Enter your professional analysis for this sector...') }}"
                            >{{ $note->stars_analysis }}</textarea>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
