@php
    $fsService = app(\App\Services\Metaphysics\FlyingStarService::class);
    $northDirection = $project->compass_direction ?? 0;
    // The facing direction (Blickrichtung) is the active direction for Flying Stars
    $facingDirection = $project->getActiveDirection() ?? 0;
    
    $chart = $fsService->calculateChart(
        $project->period,
        $facingDirection,
        null, // Auto-calculate mountain from facingDegree (don't use stale DB override)
        (bool) $project->is_replacement_chart
    );

    // Standard Lo Shu Grid Mapping: maps Gua number to [col, row] (0-indexed)
    // Visual: S at top, N at bottom (standard Feng Shui orientation)
    $loShuGrid = [
        4 => ['col' => 0, 'row' => 0], // SE (top-left)
        9 => ['col' => 1, 'row' => 0], // S  (top-center)
        2 => ['col' => 2, 'row' => 0], // SW (top-right)
        3 => ['col' => 0, 'row' => 1], // E  (middle-left)
        5 => ['col' => 1, 'row' => 1], // C  (center)
        7 => ['col' => 2, 'row' => 1], // W  (middle-right)
        8 => ['col' => 0, 'row' => 2], // NE (bottom-left)
        1 => ['col' => 1, 'row' => 2], // N  (bottom-center)
        6 => ['col' => 2, 'row' => 2], // NW (bottom-right)
    ];

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
                <select wire:model.live="selectedFloorPlanId"
                        class="text-sm bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-700 rounded-lg px-3 py-2 shadow-sm focus:ring-brand-blue focus:border-brand-blue">
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
            <span
                class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">{{ __('Building Period') }}</span>
            <div class="text-3xl font-black text-brand-blue">{{ $project->period }}</div>
            <p class="text-xs text-zinc-500 mt-1">{{ __('Settled in') }} {{ $project->settled_year }}</p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-100 dark:border-zinc-700 shadow-sm">
            <span
                class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">{{ __('Facing Direction (°)') }}</span>
            <div class="text-3xl font-black text-brand-orange">{{ $facingDirection }}°</div>
            <p class="text-xs text-zinc-500 mt-1">{{ __('Mountain') }}:
                <strong>{{ $chart['facing_mountain'] }}</strong></p>
            <p class="text-xs text-zinc-400 mt-0.5">{{ __('Sitting') }}: {{ $chart['sitting_mountain'] }}</p>
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

            @if(!$project->is_replacement_chart && $chart['needs_replacement'])
                <div
                    class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded text-xs text-red-600 dark:text-red-400 flex items-start gap-2">
                    <flux:icon.exclamation-triangle class="size-4 shrink-0 mt-0.5"/>
                    <div>
                        <strong>{{ __('Replacement Recommended!') }}</strong><br>
                        {{ __('The facing direction is close to a boundary (Void Line). Consider activating the Replacement Chart option.') }}
                    </div>
                </div>
            @endif

            @if($project->special_chart_type)
                <p class="text-xs text-purple-600 font-bold mt-1 uppercase">{{ $project->special_chart_type }}</p>
            @endif
        </div>
    </div>

    {{-- Grundriss mit Bagua & Flying Stars Overlay --}}
    @if($currentFloorPlan && $currentFloorPlan->outer_bounds)
        <div
            class="relative bg-zinc-100 dark:bg-zinc-950 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden shadow-inner p-4">
            <div class="max-w-4xl mx-auto relative group">
                @php
                    $media = $currentFloorPlan->getFirstMedia('floor_plans');
                    $imageUrl = $media ? route('media.floor-plans', ['floorPlan' => $currentFloorPlan->id, 'media' => $media->id]) : null;
                    $bounds = $currentFloorPlan->outer_bounds;
                @endphp

                @if($imageUrl)
                    <img src="{{ $imageUrl }}" class="w-full h-auto rounded shadow-lg"
                         alt="{{ $currentFloorPlan->title }}">

                    <svg class="absolute inset-0 w-full h-full pointer-events-none z-10"
                         viewBox="0 0 {{ $bounds['image_width'] }} {{ $bounds['image_height'] }}"
                         preserveAspectRatio="xMidYMid meet">

                        {{-- Bagua Gitter mit mehr Transparenz --}}
                        <g opacity="0.4">
                            <rect x="{{ $bounds['x1'] }}" y="{{ $bounds['y1'] }}"
                                  width="{{ $bounds['x2'] - $bounds['x1'] }}"
                                  height="{{ $bounds['y2'] - $bounds['y1'] }}"
                                  fill="none" stroke="#3b82f6" stroke-width="2" vector-effect="non-scaling-stroke"/>

                            <line x1="{{ $bounds['x1'] }}"
                                  y1="{{ $bounds['y1'] + ($bounds['y2'] - $bounds['y1']) * 0.33 }}"
                                  x2="{{ $bounds['x2'] }}"
                                  y2="{{ $bounds['y1'] + ($bounds['y2'] - $bounds['y1']) * 0.33 }}"
                                  stroke="#ef4444" stroke-width="1.5" vector-effect="non-scaling-stroke"/>
                            <line x1="{{ $bounds['x1'] }}"
                                  y1="{{ $bounds['y1'] + ($bounds['y2'] - $bounds['y1']) * 0.66 }}"
                                  x2="{{ $bounds['x2'] }}"
                                  y2="{{ $bounds['y1'] + ($bounds['y2'] - $bounds['y1']) * 0.66 }}"
                                  stroke="#ef4444" stroke-width="1.5" vector-effect="non-scaling-stroke"/>

                            <line x1="{{ $bounds['x1'] + ($bounds['x2'] - $bounds['x1']) * 0.33 }}"
                                  y1="{{ $bounds['y1'] }}"
                                  x2="{{ $bounds['x1'] + ($bounds['x2'] - $bounds['x1']) * 0.33 }}"
                                  y2="{{ $bounds['y2'] }}"
                                  stroke="#ef4444" stroke-width="1.5" vector-effect="non-scaling-stroke"/>
                            <line x1="{{ $bounds['x1'] + ($bounds['x2'] - $bounds['x1']) * 0.66 }}"
                                  y1="{{ $bounds['y1'] }}"
                                  x2="{{ $bounds['x1'] + ($bounds['x2'] - $bounds['x1']) * 0.66 }}"
                                  y2="{{ $bounds['y2'] }}"
                                  stroke="#ef4444" stroke-width="1.5" vector-effect="non-scaling-stroke"/>
                        </g>

                        {{-- Kompassrose basierend auf $compass (Nordrichtung) --}}
                        {{-- Bei 0° zeigt Nord direkt nach unten, bei 342° leicht nach unten-rechts --}}
                        @php
                            $cx = ($bounds['x1'] + $bounds['x2']) / 2;
                            $cy = ($bounds['y1'] + $bounds['y2']) / 2;
                            $halfW = ($bounds['x2'] - $bounds['x1']) / 2;
                            $halfH = ($bounds['y2'] - $bounds['y1']) / 2;
                            $compassFontSize = max(14, ($bounds['x2'] - $bounds['x1']) / 28);

                            // 8 Himmelsrichtungen mit Winkel-Offset von Nord (Grad, Uhrzeigersinn)
                            $compassDirs = [
                                ['N',  0,   true],
                                ['NO', 45,  false],
                                ['O',  90,  true],
                                ['SO', 135, false],
                                ['S',  180, true],
                                ['SW', 225, false],
                                ['W',  270, true],
                                ['NW', 315, false],
                            ];
                        @endphp

                        <g class="compass-rose" opacity="0.85">
                            @foreach($compassDirs as $dir)
                                @php
                                    [$label, $offsetDeg, $isCardinal] = $dir;
                                    $totalRad = deg2rad($northDirection + $offsetDeg);

                                    // Richtungsvektor: Bei 0° zeigt Nord nach unten (+Y)
                                    $dirX = -sin($totalRad);
                                    $dirY = cos($totalRad);

                                    // Ray-Rect-Intersection: Wo trifft die Linie den Gebäuderand?
                                    $tCandidates = [];
                                    if (abs($dirX) > 0.001) {
                                        $tX = $dirX > 0
                                            ? ($bounds['x2'] - $cx) / $dirX
                                            : ($bounds['x1'] - $cx) / $dirX;
                                        if ($tX > 0) $tCandidates[] = $tX;
                                    }
                                    if (abs($dirY) > 0.001) {
                                        $tY = $dirY > 0
                                            ? ($bounds['y2'] - $cy) / $dirY
                                            : ($bounds['y1'] - $cy) / $dirY;
                                        if ($tY > 0) $tCandidates[] = $tY;
                                    }
                                    $tBorder = !empty($tCandidates) ? min($tCandidates) : $halfW;

                                    // Linie: knapp über den Gebäuderand hinaus (+5%)
                                    $lineEndX = $cx + $dirX * $tBorder * 1.05;
                                    $lineEndY = $cy + $dirY * $tBorder * 1.05;

                                    // Label: etwas weiter außerhalb (+15%)
                                    $labelPosX = $cx + $dirX * $tBorder * 1.15;
                                    $labelPosY = $cy + $dirY * $tBorder * 1.15;
                                @endphp

                                {{-- Richtungslinie --}}
                                <line x1="{{ $cx }}" y1="{{ $cy }}"
                                      x2="{{ $lineEndX }}" y2="{{ $lineEndY }}"
                                      stroke="{{ $isCardinal ? '#dc2626' : '#9ca3af' }}"
                                      stroke-width="{{ $isCardinal ? 2.5 : 1.5 }}"
                                      vector-effect="non-scaling-stroke"
                                      {!! $isCardinal ? '' : 'stroke-dasharray="6 3"' !!}/>

                                {{-- Richtungsbezeichnung --}}
                                <text x="{{ $labelPosX }}" y="{{ $labelPosY + ($compassFontSize * 0.35) }}"
                                      font-size="{{ $compassFontSize * ($isCardinal ? 1.0 : 0.8) }}"
                                      font-weight="{{ $isCardinal ? '900' : '700' }}"
                                      fill="{{ $isCardinal ? '#dc2626' : '#6b7280' }}"
                                      text-anchor="middle"
                                      paint-order="stroke"
                                      stroke="white" stroke-width="4" stroke-linejoin="round"
                                >{{ $label }}</text>
                            @endforeach

                            {{-- Herz im Zentrum --}}
                            <text x="{{ $cx }}" y="{{ $cy + $compassFontSize * 0.4 }}"
                                  font-size="{{ $compassFontSize * 1.3 }}"
                                  fill="#dc2626"
                                  text-anchor="middle"
                                  paint-order="stroke"
                                  stroke="white" stroke-width="4" stroke-linejoin="round"
                            >♥</text>
                        </g>

                        {{-- Sektor-Farben (Hintergrund) & Gua-Nummer --}}
                        @foreach($currentFloorPlan->baguaNotes as $note)
                            @php
                                $noteData = json_decode($note->content, true);
                                if (!$noteData && $note->content) $noteData = json_decode(json_decode($note->content, true), true) ?? [];
                                $bgcolor = $noteData['bg_color'] ?? '#d1d5db';
                                $color = $noteData['color'] ?? '#6b7280';
                                $gridPos = $loShuGrid[$note->gua_number] ?? ['col' => 1, 'row' => 1];
                                $sectorWidth = ($bounds['x2'] - $bounds['x1']) / 3;
                                $sectorHeight = ($bounds['y2'] - $bounds['y1']) / 3;
                                $sectorX = $bounds['x1'] + $gridPos['col'] * $sectorWidth;
                                $sectorY = $bounds['y1'] + $gridPos['row'] * $sectorHeight;
                                $fontSize = max(12, ($bounds['x2']-$bounds['x1'])/25);
                            @endphp
                            <rect x="{{ $sectorX }}" y="{{ $sectorY }}" width="{{ $sectorWidth }}"
                                  height="{{ $sectorHeight }}"
                                  fill="{{ $bgcolor }}" fill-opacity="0.2"/>

                            {{-- Gua Number Circle --}}
                            <circle cx="{{ $sectorX + 25 }}" cy="{{ $sectorY + 25 }}" r="18" fill="white"
                                    fill-opacity="0.9" stroke="{{ $color }}" stroke-width="2"/>
                            <text x="{{ $sectorX + 25 }}" y="{{ $sectorY + 31 }}" font-size="18" font-weight="black"
                                  fill="{{ $color }}" text-anchor="middle">{{ $note->gua_number }}</text>
                        @endforeach

                        {{-- Flying Stars (Kräftig) --}}
                        @foreach($currentFloorPlan->baguaNotes as $note)
                            @php
                                $gridPos = $loShuGrid[$note->gua_number] ?? ['col' => 1, 'row' => 1];
                                $sectorWidth = ($bounds['x2'] - $bounds['x1']) / 3;
                                $sectorHeight = ($bounds['y2'] - $bounds['y1']) / 3;
                                $sectorX = $bounds['x1'] + $gridPos['col'] * $sectorWidth;
                                $sectorY = $bounds['y1'] + $gridPos['row'] * $sectorHeight;
                                $fontSize = max(12, ($bounds['x2']-$bounds['x1'])/25);

                                // Use calculated chart values instead of stored DB values
                                $pos = $note->gua_number;
                                $mountain = $chart['mountain'][$pos] ?? null;
                                $water = $chart['water'][$pos] ?? null;
                                $base = $chart['base'][$pos] ?? null;
                            @endphp
                            <g class="flying-stars font-black" style="font-size: {{ $fontSize }}px">
                                @if($mountain)
                                    <text x="{{ $sectorX + $sectorWidth * 0.2 }}"
                                          y="{{ $sectorY + $sectorHeight * 0.3 }}" fill="white" stroke="white"
                                          stroke-width="3" stroke-linejoin="round"
                                          text-anchor="middle">{{ $mountain }}</text>
                                    <text x="{{ $sectorX + $sectorWidth * 0.2 }}"
                                          y="{{ $sectorY + $sectorHeight * 0.3 }}" fill="#b45309"
                                          text-anchor="middle">{{ $mountain }}</text>
                                @endif
                                @if($water)
                                    <text x="{{ $sectorX + $sectorWidth * 0.8 }}"
                                          y="{{ $sectorY + $sectorHeight * 0.3 }}" fill="white" stroke="white"
                                          stroke-width="3" stroke-linejoin="round"
                                          text-anchor="middle">{{ $water }}</text>
                                    <text x="{{ $sectorX + $sectorWidth * 0.8 }}"
                                          y="{{ $sectorY + $sectorHeight * 0.3 }}" fill="#1d4ed8"
                                          text-anchor="middle">{{ $water }}</text>
                                @endif
                                @if($base)
                                    <text x="{{ $sectorX + $sectorWidth * 0.5 }}"
                                          y="{{ $sectorY + $sectorHeight * 0.85 }}" fill="white" stroke="white"
                                          stroke-width="3" stroke-linejoin="round"
                                          text-anchor="middle">{{ $base }}</text>
                                    <text x="{{ $sectorX + $sectorWidth * 0.5 }}"
                                          y="{{ $sectorY + $sectorHeight * 0.85 }}" fill="#52525b"
                                          text-anchor="middle">{{ $base }}</text>
                                @endif
                            </g>

                            {{-- Room Type & Residents --}}
                            @if($note->room_type)
                                <text x="{{ $sectorX + $sectorWidth * 0.5 }}" y="{{ $sectorY + $sectorHeight * 0.5 }}"
                                      fill="white" stroke="white" stroke-width="2" stroke-linejoin="round"
                                      font-size="{{ $fontSize * 0.6 }}" font-weight="bold" text-anchor="middle"
                                      opacity="0.9">
                                    {{ __($roomTypeOptions[$note->room_type] ?? '') }}
                                </text>
                                <text x="{{ $sectorX + $sectorWidth * 0.5 }}" y="{{ $sectorY + $sectorHeight * 0.5 }}"
                                      fill="#71717a" font-size="{{ $fontSize * 0.6 }}" font-weight="bold"
                                      text-anchor="middle">
                                    {{ __($roomTypeOptions[$note->room_type] ?? '') }}
                                </text>
                            @endif

                            {{-- Avatars of residents --}}
                            @if($note->roomAssignments->isNotEmpty())
                                @php
                                    $avatarSize = $fontSize * 1.5;
                                    $totalResidents = $note->roomAssignments->count();
                                    $spacing = $avatarSize * 1.1;
                                    $startX = $sectorX + ($sectorWidth / 2) - (($totalResidents - 1) * $spacing / 2);
                                @endphp
                                @foreach($note->roomAssignments as $index => $assignment)
                                    <foreignObject x="{{ $startX + ($index * $spacing) - ($avatarSize / 2) }}"
                                                   y="{{ $sectorY + $sectorHeight * 0.55 }}"
                                                   width="{{ $avatarSize }}" height="{{ $avatarSize }}">
                                        <div xmlns="http://www.w3.org/1999/xhtml">
                                            <img
                                                src="https://ui-avatars.com/api/?name={{ urlencode($assignment->person->name) }}&background=random"
                                                style="width: {{ $avatarSize }}px; height: {{ $avatarSize }}px; border-radius: 9999px; border: 1px solid white; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                        </div>
                                    </foreignObject>
                                @endforeach
                            @endif
                        @endforeach

                        {{-- Center Palace (Gua 5) – Flying Stars --}}
                        @php
                            $centerGridPos = $loShuGrid[5];
                            $cSectorWidth = ($bounds['x2'] - $bounds['x1']) / 3;
                            $cSectorHeight = ($bounds['y2'] - $bounds['y1']) / 3;
                            $cSectorX = $bounds['x1'] + $centerGridPos['col'] * $cSectorWidth;
                            $cSectorY = $bounds['y1'] + $centerGridPos['row'] * $cSectorHeight;
                            $cFontSize = max(12, ($bounds['x2']-$bounds['x1'])/25);
                            $cMountain = $chart['mountain'][5] ?? null;
                            $cWater = $chart['water'][5] ?? null;
                            $cBase = $chart['base'][5] ?? null;
                        @endphp
                        <g class="flying-stars center-palace font-black" style="font-size: {{ $cFontSize }}px">
                            @if($cMountain)
                                <text x="{{ $cSectorX + $cSectorWidth * 0.2 }}"
                                      y="{{ $cSectorY + $cSectorHeight * 0.3 }}" fill="white" stroke="white"
                                      stroke-width="3" stroke-linejoin="round"
                                      text-anchor="middle">{{ $chart['mountain_flight_direction'] }}{{ $cMountain }}</text>
                                <text x="{{ $cSectorX + $cSectorWidth * 0.2 }}"
                                      y="{{ $cSectorY + $cSectorHeight * 0.3 }}" fill="#b45309"
                                      text-anchor="middle">{{ $chart['mountain_flight_direction'] }}{{ $cMountain }}</text>
                            @endif
                            @if($cWater)
                                <text x="{{ $cSectorX + $cSectorWidth * 0.8 }}"
                                      y="{{ $cSectorY + $cSectorHeight * 0.3 }}" fill="white" stroke="white"
                                      stroke-width="3" stroke-linejoin="round"
                                      text-anchor="middle">{{ $chart['water_flight_direction'] }}{{ $cWater }}</text>
                                <text x="{{ $cSectorX + $cSectorWidth * 0.8 }}"
                                      y="{{ $cSectorY + $cSectorHeight * 0.3 }}" fill="#1d4ed8"
                                      text-anchor="middle">{{ $chart['water_flight_direction'] }}{{ $cWater }}</text>
                            @endif
                            @if($cBase)
                                <text x="{{ $cSectorX + $cSectorWidth * 0.5 }}"
                                      y="{{ $cSectorY + $cSectorHeight * 0.85 }}" fill="white" stroke="white"
                                      stroke-width="3" stroke-linejoin="round"
                                      text-anchor="middle">{{ $cBase }}</text>
                                <text x="{{ $cSectorX + $cSectorWidth * 0.5 }}"
                                      y="{{ $cSectorY + $cSectorHeight * 0.85 }}" fill="#52525b"
                                      text-anchor="middle">{{ $cBase }}</text>
                            @endif
                        </g>

                    </svg>
                @endif
            </div>
        </div>
    @else
        <div
            class="p-12 text-center bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700">
            <flux:icon.map class="size-12 mx-auto text-zinc-300 mb-4"/>
            <h3 class="text-lg font-medium">{{ __('No Grid Calculated') }}</h3>
            <p class="text-zinc-500">{{ __('Please open the editor and calculate the Bagua grid first.') }}</p>
            <flux:button class="mt-4"
                         :href="route('modules.bagua.editor', $selectedFloorPlanId ?: $floorPlans->first()->id)">{{ __('Open Editor') }}</flux:button>
        </div>
    @endif

    <div class="border-t border-zinc-200 dark:border-zinc-800 pt-8">
        <h4 class="text-xl font-bold text-zinc-800 dark:text-zinc-200 mb-6 flex items-center gap-2">
            <flux:icon.document-text class="size-5 text-brand-blue"/>
            {{ __('Sector Breakdown & Interpretation') }}
        </h4>

        {{-- Zweispaltige Sektor-Details (Nur die 8 Richtungen) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach([9,1,3,7,2,8,4,6] as $pos)
                @php
                    $note = $notes->get($pos);
                @endphp
                @if($note)
                    @php
                        $noteData = json_decode($note->content, true);
                        if (!$noteData && $note->content) $noteData = json_decode(json_decode($note->content, true), true) ?? [];
                        $accentColor = $noteData['color'] ?? '#6b7280';
                        $accentBg = $noteData['bg_color'] ?? '#f3f4f6';
                    @endphp
                    <div
                        class="p-6 bg-white dark:bg-zinc-900 rounded-xl border-2 shadow-sm space-y-4 hover:border-brand-blue/30 transition-colors"
                        style="border-color: {{ $accentColor }}20;">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                {{-- Gua Badge --}}
                                <div
                                    class="size-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                                    style="background-color: {{ $accentBg }}; color: {{ $accentColor }}; border: 1px solid {{ $accentColor }}40;">
                                    {{ $pos }}
                                </div>
                                <div>
                                    <div
                                        class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">{{ $gridLabels[$pos] }}</div>
                                    <div class="text-2xl font-black text-zinc-800 dark:text-zinc-200 mt-1">
                                        <span class="text-amber-700">{{ $chart['mountain'][$pos] }}</span>
                                        <span class="text-zinc-300 mx-2">·</span>
                                        <span class="text-blue-700">{{ $chart['water'][$pos] }}</span>
                                        <span class="text-zinc-300 mx-2">·</span>
                                        <span class="text-zinc-500">{{ $chart['base'][$pos] }}</span>
                                    </div>
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
                                        <option
                                            value="{{ $value }}" {{ $note->room_type === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Bewohner & Kompatibilität --}}
                        @if($note->roomAssignments->isNotEmpty())
                            <div class="space-y-2">
                                <label
                                    class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">{{ __('Assigned Residents') }}</label>
                                <div class="grid grid-cols-1 gap-2">
                                    @foreach($note->roomAssignments as $assignment)
                                        @php
                                            $person = $assignment->person;
                                            $fsComp = $person->life_gua ? $calculator->analyzeFlyingStarCompatibility($person->life_gua, $chart['mountain'][$pos], $chart['water'][$pos]) : null;
                                        @endphp
                                        <div
                                            class="flex items-start gap-3 p-2 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-700">
                                            <div class="relative shrink-0">
                                                <img
                                                    src="https://ui-avatars.com/api/?name={{ urlencode($person->name) }}&background=random"
                                                    class="size-8 rounded-full border border-white shadow-sm">
                                                @if($fsComp)
                                                    <div
                                                        class="absolute -top-1 -right-1 size-3 rounded-full border border-white {{ $fsComp['mountain']['quality'] === 'Excellent' ? 'bg-green-500' : ($fsComp['mountain']['quality'] === 'Challenging' ? 'bg-red-500' : 'bg-zinc-400') }}"></div>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-xs font-bold truncate">{{ $person->name }}</div>
                                                @if($fsComp)
                                                    <div class="text-[10px] text-zinc-500 leading-tight mt-0.5">
                                                        {{ __($fsComp['mountain']['label']) }} {{ __('Mountain Star') }}
                                                        ({{ __($fsComp['mountain']['element']) }})
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Interpretation --}}
                        <div
                            class="p-3 rounded-lg bg-brand-blue/5 border border-brand-blue/10 text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                            @php
                                $m = $chart['mountain'][$pos];
                                $w = $chart['water'][$pos];
                                $period = $project->period;

                                // Timely/Prosperous stars for Period 9 (Current, Next, Previous)
                                $isTimelyM = ($m == 9 || $m == 1 || $m == 8);
                                $isTimelyW = ($w == 9 || $w == 1 || $w == 8);

                                // Very Inauspicious stars
                                $isBadM = ($m == 2 || $m == 5);
                                $isBadW = ($w == 2 || $w == 5);
                            @endphp

                            <span class="font-bold text-brand-blue">{{ __('Combination') }} {{ $m }}-{{ $w }}:</span>

                            @if($m == $w)
                                {{ __('Double Star formation. Energy is highly concentrated here.') }}
                            @elseif($m == 5 && $w == 2 || $m == 2 && $w == 5)
                                <span
                                    class="text-red-600 font-bold">{{ __('Highly challenging combination (Sickness & Misfortune). Requires strong Metal remedies.') }}</span>
                            @elseif($isTimelyM && $isTimelyW)
                                {{ __('Prosperous combination. Excellent for both health and wealth in this period.') }}
                            @elseif($isTimelyW)
                                {{ __('Wealth-focused combination. The Water Star supports prosperity.') }}
                            @elseif($isTimelyM)
                                {{ __('Health-focused combination. The Mountain Star supports relationships and well-being.') }}
                            @elseif($isBadM || $isBadW)
                                {{ __('Challenging combination. May bring instability or health issues. Elemental balancing recommended.') }}
                            @else
                                {{ __('Neutral or complex combination. The influence depends on the specific room usage and layout.') }}
                            @endif
                        </div>

                        {{-- Beraternotizen --}}
                        <div class="space-y-1">
                            <label
                                class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">{{ __('Consultant Notes') }}</label>
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
