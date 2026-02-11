@php
    $fsService = app(\App\Services\Metaphysics\FlyingStarService::class);
    $mingGuaService = app(\App\Services\Metaphysics\MingGuaCalculator::class);
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

    $posToDirection = [
        1 => 'N',
        2 => 'SW',
        3 => 'E',
        4 => 'SE',
        6 => 'NW',
        7 => 'W',
        8 => 'NE',
        9 => 'S',
        5 => 'Center'
    ];

    $gridLabels = [
        2 => __('Southwest (KUN)'),
        3 => __('East (ZHEN)'),
        4 => __('Southeast (XUN)'),
        6 => __('Northwest (QIAN)'),
        7 => __('West (DUI)'),
        8 => __('Northeast (GEN)'),
        9 => __('South (LI)'),
        1 => __('North (KAN)'),
        5 => __('Center (Tai Qi)')
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

    {{-- Grundriss mit Enhanced Compass Rose & Flying Stars --}}
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

                        {{-- Building outline (read-only boundary) --}}
                        <rect x="{{ $bounds['x1'] }}" y="{{ $bounds['y1'] }}"
                              width="{{ $bounds['x2'] - $bounds['x1'] }}"
                              height="{{ $bounds['y2'] - $bounds['y1'] }}"
                              fill="none" stroke="#3b82f6" stroke-width="2" stroke-opacity="0.4"
                              vector-effect="non-scaling-stroke"/>

                        @php
                            $cx = ($bounds['x1'] + $bounds['x2']) / 2;
                            $cy = ($bounds['y1'] + $bounds['y2']) / 2;
                            $halfW = ($bounds['x2'] - $bounds['x1']) / 2;
                            $halfH = ($bounds['y2'] - $bounds['y1']) / 2;
                            $compassFontSize = max(14, ($bounds['x2'] - $bounds['x1']) / 28);
                            $starFontSize = max(11, ($bounds['x2'] - $bounds['x1']) / 38);

                            // Mapping: direction label => Gua number for star lookup
                            $dirToGua = [
                                'N'  => 1,
                                'NO' => 8,  // NE
                                'O'  => 3,  // E
                                'SO' => 4,  // SE
                                'S'  => 9,
                                'SW' => 2,
                                'W'  => 7,
                                'NW' => 6,
                            ];

                            // 8 directions: [label, offsetDeg from North (CW), isCardinal]
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

                            // Helper: compute where a ray from center at given angle hits the building rect
                            // Returns [endX, endY, t] where t is the ray parameter
                            $rayHitBuilding = function($angleDeg) use ($cx, $cy, $bounds, $northDirection, $halfW) {
                                $totalRad = deg2rad($northDirection + $angleDeg);
                                $dX = -sin($totalRad);
                                $dY = cos($totalRad);

                                $tCandidates = [];
                                if (abs($dX) > 0.001) {
                                    $tX = $dX > 0
                                        ? ($bounds['x2'] - $cx) / $dX
                                        : ($bounds['x1'] - $cx) / $dX;
                                    if ($tX > 0) $tCandidates[] = $tX;
                                }
                                if (abs($dY) > 0.001) {
                                    $tY = $dY > 0
                                        ? ($bounds['y2'] - $cy) / $dY
                                        : ($bounds['y1'] - $cy) / $dY;
                                    if ($tY > 0) $tCandidates[] = $tY;
                                }
                                $t = !empty($tCandidates) ? min($tCandidates) : $halfW;

                                return [
                                    'x' => $cx + $dX * $t,
                                    'y' => $cy + $dY * $t,
                                    't' => $t,
                                    'dx' => $dX,
                                    'dy' => $dY,
                                ];
                            };
                        @endphp

                        <defs>
                            <clipPath id="building-clip">
                                <rect x="{{ $bounds['x1'] }}" y="{{ $bounds['y1'] }}"
                                      width="{{ $bounds['x2'] - $bounds['x1'] }}"
                                      height="{{ $bounds['y2'] - $bounds['y1'] }}"/>
                            </clipPath>
                        </defs>

                        {{-- Cardinal direction wedges (45° light red transparent fields) --}}
                        {{-- Each cardinal direction (N, O, S, W) gets a ±22.5° wedge clipped to building --}}
                        <g clip-path="url(#building-clip)">
                            @foreach($compassDirs as $dir)
                                @php
                                    [$label, $offsetDeg, $isCardinal] = $dir;
                                    if (!$isCardinal) continue;

                                    // Build a wedge from center: ±22.5° around the cardinal direction
                                    // We create a polygon: center -> edge at -22.5° -> edge at +22.5°
                                    // Use enough intermediate points to approximate the arc along the building edge
                                    $wedgePoints = ["{$cx},{$cy}"];
                                    $steps = 12;
                                    for ($i = 0; $i <= $steps; $i++) {
                                        $a = $offsetDeg - 22.5 + ($i * 45.0 / $steps);
                                        $hit = $rayHitBuilding($a);
                                        $wedgePoints[] = round($hit['x'], 1) . ',' . round($hit['y'], 1);
                                    }
                                    $wedgePointsStr = implode(' ', $wedgePoints);
                                @endphp
                                <polygon points="{{ $wedgePointsStr }}"
                                         fill="#dc2626" fill-opacity="0.15"/>
                            @endforeach
                        </g>

                        {{-- Compass Rose: Direction lines & labels with stars --}}
                        <g class="compass-rose" opacity="0.9">
                            @foreach($compassDirs as $dir)
                                @php
                                    [$label, $offsetDeg, $isCardinal] = $dir;
                                    $hit = $rayHitBuilding($offsetDeg);
                                    $tBorder = $hit['t'];
                                    $dX = $hit['dx'];
                                    $dY = $hit['dy'];

                                    // Line extends slightly beyond building edge (+5%)
                                    $lineEndX = $cx + $dX * $tBorder * 1.05;
                                    $lineEndY = $cy + $dY * $tBorder * 1.05;

                                    // Direction label position (outside building)
                                    $labelPosX = $cx + $dX * $tBorder * 1.15;
                                    $labelPosY = $cy + $dY * $tBorder * 1.15;

                                    // Get stars for this direction
                                    $guaNum = $dirToGua[$label];
                                    $dirMountain = $chart['mountain'][$guaNum] ?? null;
                                    $dirWater = $chart['water'][$guaNum] ?? null;
                                    $dirBase = $chart['base'][$guaNum] ?? null;

                                    // Fixed screen-space offsets for star layout around label:
                                    // M  W    (Mountain upper-left, Water upper-right)
                                    //  D      (Direction label center)
                                    //  B      (Base below)
                                    $hSpread = $starFontSize * 1.1;  // horizontal offset for M/W
                                    $vUp = $starFontSize * 1.3;      // vertical offset upward for M/W
                                    $vDown = $starFontSize * 1.3;    // vertical offset downward for B

                                    // Background panel dimensions for the star group
                                    $bgPadX = $starFontSize * 0.6;
                                    $bgPadY = $starFontSize * 0.4;
                                    $bgW = ($hSpread * 2) + ($starFontSize * 1.2) + ($bgPadX * 2);
                                    $bgH = $vUp + $vDown + $compassFontSize + ($bgPadY * 2);
                                    $bgX = $labelPosX - $bgW / 2;
                                    $bgY = $labelPosY + ($compassFontSize * 0.35) - $vUp - $bgPadY - $starFontSize * 0.7;
                                @endphp

                                {{-- Direction line --}}
                                <line x1="{{ $cx }}" y1="{{ $cy }}"
                                      x2="{{ $lineEndX }}" y2="{{ $lineEndY }}"
                                      stroke="{{ $isCardinal ? '#dc2626' : '#9ca3af' }}"
                                      stroke-width="{{ $isCardinal ? 2.5 : 1.5 }}"
                                      vector-effect="non-scaling-stroke"
                                      {!! $isCardinal ? '' : 'stroke-dasharray="6 3"' !!}/>

                                {{-- Light gray background panel for star group --}}
                                <rect x="{{ $bgX }}" y="{{ $bgY }}"
                                      width="{{ $bgW }}" height="{{ $bgH }}"
                                      rx="{{ $starFontSize * 0.5 }}" ry="{{ $starFontSize * 0.5 }}"
                                      fill="#f3f4f6" fill-opacity="0.85"
                                      stroke="#d1d5db" stroke-width="1" stroke-opacity="0.5"/>

                                {{-- Direction label (outside building) --}}
                                <text x="{{ $labelPosX }}" y="{{ $labelPosY + ($compassFontSize * 0.35) }}"
                                      font-size="{{ $compassFontSize * ($isCardinal ? 1.0 : 0.8) }}"
                                      font-weight="{{ $isCardinal ? '900' : '700' }}"
                                      fill="{{ $isCardinal ? '#dc2626' : '#6b7280' }}"
                                      text-anchor="middle"
                                >{{ $label }}</text>

                                {{-- Flying Stars in fixed layout around direction label --}}
                                {{-- M  W  (upper-left / upper-right) --}}
                                {{--  D    (direction label - already rendered above) --}}
                                {{--  B    (base below) --}}
                                @if($dirMountain)
                                    <text x="{{ $labelPosX - $hSpread }}"
                                          y="{{ $labelPosY + ($compassFontSize * 0.35) - $vUp }}"
                                          font-size="{{ $starFontSize }}" font-weight="900"
                                          fill="white" stroke="white" stroke-width="3" stroke-linejoin="round"
                                          text-anchor="middle" paint-order="stroke">{{ $dirMountain }}</text>
                                    <text x="{{ $labelPosX - $hSpread }}"
                                          y="{{ $labelPosY + ($compassFontSize * 0.35) - $vUp }}"
                                          font-size="{{ $starFontSize }}" font-weight="900"
                                          fill="#b45309" text-anchor="middle">{{ $dirMountain }}</text>
                                @endif
                                @if($dirWater)
                                    <text x="{{ $labelPosX + $hSpread }}"
                                          y="{{ $labelPosY + ($compassFontSize * 0.35) - $vUp }}"
                                          font-size="{{ $starFontSize }}" font-weight="900"
                                          fill="white" stroke="white" stroke-width="3" stroke-linejoin="round"
                                          text-anchor="middle" paint-order="stroke">{{ $dirWater }}</text>
                                    <text x="{{ $labelPosX + $hSpread }}"
                                          y="{{ $labelPosY + ($compassFontSize * 0.35) - $vUp }}"
                                          font-size="{{ $starFontSize }}" font-weight="900"
                                          fill="#1d4ed8" text-anchor="middle">{{ $dirWater }}</text>
                                @endif
                                @if($dirBase)
                                    <text x="{{ $labelPosX }}"
                                          y="{{ $labelPosY + ($compassFontSize * 0.35) + $vDown }}"
                                          font-size="{{ $starFontSize * 0.85 }}" font-weight="800"
                                          fill="white" stroke="white" stroke-width="3" stroke-linejoin="round"
                                          text-anchor="middle" paint-order="stroke">{{ $dirBase }}</text>
                                    <text x="{{ $labelPosX }}"
                                          y="{{ $labelPosY + ($compassFontSize * 0.35) + $vDown }}"
                                          font-size="{{ $starFontSize * 0.85 }}" font-weight="800"
                                          fill="#52525b" text-anchor="middle">{{ $dirBase }}</text>
                                @endif
                            @endforeach

                            {{-- Center Palace: Heart + center stars around it --}}
                            @php
                                $cMountain = $chart['mountain'][5] ?? null;
                                $cWater = $chart['water'][5] ?? null;
                                $cBase = $chart['base'][5] ?? null;
                                $centerStarOffset = $compassFontSize * 1.2;

                                // Center background panel
                                $cBgW = $centerStarOffset * 2 + $starFontSize * 2.5;
                                $cBgH = $centerStarOffset * 1.8 + $compassFontSize * 1.5;
                                $cBgX = $cx - $cBgW / 2;
                                $cBgY = $cy - $centerStarOffset * 0.8 - $starFontSize;
                            @endphp

                            {{-- Light gray background for center palace --}}
                            <rect x="{{ $cBgX }}" y="{{ $cBgY }}"
                                  width="{{ $cBgW }}" height="{{ $cBgH }}"
                                  rx="{{ $starFontSize * 0.6 }}" ry="{{ $starFontSize * 0.6 }}"
                                  fill="#f3f4f6" fill-opacity="0.85"
                                  stroke="#d1d5db" stroke-width="1" stroke-opacity="0.5"/>

                            {{-- Heart in center --}}
                            <text x="{{ $cx }}" y="{{ $cy + $compassFontSize * 0.4 }}"
                                  font-size="{{ $compassFontSize * 1.3 }}"
                                  fill="#dc2626"
                                  text-anchor="middle"
                            >♥</text>

                            {{-- Mountain star (upper-left of heart) --}}
                            @if($cMountain)
                                <text x="{{ $cx - $centerStarOffset }}"
                                      y="{{ $cy - $centerStarOffset * 0.4 }}"
                                      font-size="{{ $starFontSize * 1.1 }}" font-weight="900"
                                      fill="white" stroke="white" stroke-width="3" stroke-linejoin="round"
                                      text-anchor="middle" paint-order="stroke"
                                >{{ $chart['mountain_flight_direction'] }}{{ $cMountain }}</text>
                                <text x="{{ $cx - $centerStarOffset }}"
                                      y="{{ $cy - $centerStarOffset * 0.4 }}"
                                      font-size="{{ $starFontSize * 1.1 }}" font-weight="900"
                                      fill="#b45309" text-anchor="middle"
                                >{{ $chart['mountain_flight_direction'] }}{{ $cMountain }}</text>
                            @endif

                            {{-- Water star (upper-right of heart) --}}
                            @if($cWater)
                                <text x="{{ $cx + $centerStarOffset }}"
                                      y="{{ $cy - $centerStarOffset * 0.4 }}"
                                      font-size="{{ $starFontSize * 1.1 }}" font-weight="900"
                                      fill="white" stroke="white" stroke-width="3" stroke-linejoin="round"
                                      text-anchor="middle" paint-order="stroke"
                                >{{ $chart['water_flight_direction'] }}{{ $cWater }}</text>
                                <text x="{{ $cx + $centerStarOffset }}"
                                      y="{{ $cy - $centerStarOffset * 0.4 }}"
                                      font-size="{{ $starFontSize * 1.1 }}" font-weight="900"
                                      fill="#1d4ed8" text-anchor="middle"
                                >{{ $chart['water_flight_direction'] }}{{ $cWater }}</text>
                            @endif

                            {{-- Base star (below heart) --}}
                            @if($cBase)
                                <text x="{{ $cx }}"
                                      y="{{ $cy + $compassFontSize * 0.4 + $centerStarOffset }}"
                                      font-size="{{ $starFontSize * 1.0 }}" font-weight="800"
                                      fill="white" stroke="white" stroke-width="3" stroke-linejoin="round"
                                      text-anchor="middle" paint-order="stroke"
                                >{{ $cBase }}</text>
                                <text x="{{ $cx }}"
                                      y="{{ $cy + $compassFontSize * 0.4 + $centerStarOffset }}"
                                      font-size="{{ $starFontSize * 1.0 }}" font-weight="800"
                                      fill="#52525b" text-anchor="middle"
                                >{{ $cBase }}</text>
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
            {{-- Order: Center, then Clockwise starting from North --}}
            @foreach([5, 1, 8, 3, 4, 9, 2, 7, 6] as $pos)
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
                                                    @php
                                                        $qualityMap = [
                                                            'Excellent' => __('Supports your vitality'),
                                                            'Good' => __('Harmonious energy'),
                                                            'Neutral' => __('Drains energy slightly'),
                                                            'Challenging' => __('Stressful energy'),
                                                            'Mixed' => __('Requires effort'),
                                                        ];
                                                        $friendlyQuality = $qualityMap[$fsComp['mountain']['quality']] ?? $fsComp['mountain']['label'];
                                                        $qualityColor = match($fsComp['mountain']['quality']) {
                                                            'Excellent', 'Good' => 'text-green-600',
                                                            'Challenging', 'Mixed' => 'text-red-600',
                                                            default => 'text-zinc-500'
                                                        };
                                                    @endphp
                                                    <div class="text-[10px] {{ $qualityColor }} leading-tight mt-0.5">
                                                        {{ __('Gua') }}: {{ $friendlyQuality }}
                                                    </div>
                                                @endif
                                                @php
                                                    $dirCode = $posToDirection[$pos] ?? null;
                                                    $directionInfo = null;
                                                    
                                                    if ($dirCode && $dirCode !== 'Center' && $person && $person->life_gua) {
                                                        $dirType = $mingGuaService->getDirectionType($person->life_gua, $dirCode);
                                                        
                                                        if ($dirType) {
                                                            // Good Directions
                                                            if (in_array($dirType, ['sheng_qi', 'tian_yi', 'yan_nian', 'fu_wei'])) {
                                                                $friendlyLabel = match($dirType) {
                                                                    'sheng_qi' => __('Ideal for Success & Wealth'),
                                                                    'tian_yi' => __('Best for Health & Healing'),
                                                                    'yan_nian' => __('Supports Relationships & Harmony'),
                                                                    'fu_wei' => __('Good for Mental Clarity & Stability'),
                                                                    default => ''
                                                                };
                                                                
                                                                $directionInfo = [
                                                                    'label' => $friendlyLabel,
                                                                    'color' => 'text-green-600',
                                                                    'icon' => 'check-circle'
                                                                ];
                                                            } 
                                                            // Bad Directions
                                                            else {
                                                                $friendlyLabel = match($dirType) {
                                                                    'jue_ming' => __('Avoid this area (Major Challenges)'),
                                                                    'wu_gui' => __('Risk of Conflict & Theft'),
                                                                    'liu_sha' => __('Risk of Illness & Legal Issues'),
                                                                    'huo_hai' => __('Prone to Accidents & Obstacles'),
                                                                    default => ''
                                                                };
                                                                
                                                                $directionInfo = [
                                                                     'label' => $friendlyLabel,
                                                                     'color' => 'text-red-600',
                                                                     'icon' => 'exclamation-circle'
                                                                ];
                                                            }
                                                        }
                                                    }
                                                @endphp

                                                @if($directionInfo)
                                                    <div class="text-[10px] {{ $directionInfo['color'] }} leading-tight mt-0.5 flex items-start gap-1">
                                                        @if($directionInfo['icon'] === 'check-circle')
                                                            <flux:icon.check-circle class="size-3 shrink-0 mt-0.5" />
                                                        @else
                                                            <flux:icon.exclamation-circle class="size-3 shrink-0 mt-0.5" />
                                                        @endif
                                                        <span>{{ __('Direction') }}: {{ $directionInfo['label'] }}</span>
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


