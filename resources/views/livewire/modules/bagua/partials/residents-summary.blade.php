@use('App\Services\Metaphysics\MingGuaCalculator')

@php
    if (!$project) {
        return;
    }

    $mingGuaService = new MingGuaCalculator();

    // 1. Determine House Details
    // compass_direction = Nordabweichung / Facing direction on floor plan
    $houseFacingDegree = (float) ($project->compass_direction ?? 0.0);

    // House Gua is determined from the SITTING direction (opposite of facing), not the facing direction
    $houseSittingDegree = (float) ($project->sitting_direction ?? fmod($houseFacingDegree + 180, 360));
    $houseGua = $mingGuaService->getTrigramFromDegrees($houseSittingDegree);
    $houseAttr = $mingGuaService->getAttributes($houseGua);
    $houseGroup = ($houseAttr['group'] === 'East') ? __('East') : __('West');
    $houseElement = __($houseAttr['element']);
    $houseTrigramName = explode(' ', $houseAttr['name'])[0];
    $buildingYear = $project->settled_year ?? 2024; // Fallback

    // Prepare Residents List (Owner + FamilyMembers)
    $residents = collect();

    // Owner
    if ($project->customer) {
        $residents->push($project->customer);
    }
    // Family Members
    if ($project->customer && $project->customer->familyMembers) {
        foreach ($project->customer->familyMembers as $fm) {
            $residents->push($fm);
        }
    }

    // Helper to get Best Compass Direction name
    $getBestDirName = function ($gua) use ($mingGuaService) {
        $attr = $mingGuaService->getAttributes($gua);
        // Best is usually Sheng Qi, which is the first in 'good_directions' if ordered? 
        // Or find key where label contains 'Sheng Qi'.
        foreach ($attr['good_directions'] as $dir => $label) {
            if (str_contains($label, 'Sheng Qi')) {
                // Map dir code to full name if needed? Or just use code (W, SW, etc.)
                return $dir;
            }
        }
        return '-';
    };

    // Mapping for Direction Codes to German/Localized Names if needed, 
    // but the task image shows "West", "Südwest", etc.
    // We can rely on standard Laravel localization for 'N', 'NE', etc. if available, 
    // or create a simple map. 
    // The existing code seems to use abbreviations or English keys. 
    // Let's assume we output the Code (W, SW) or look up a translation.

    $dirNames = [
        'N' => __('North'),
        'NE' => __('Northeast'),
        'E' => __('East'),
        'SE' => __('Southeast'),
        'S' => __('South'),
        'SW' => __('Southwest'),
        'W' => __('West'),
        'NW' => __('Northwest'),
    ];

@endphp

<div class="mt-8">
    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">{{ __('Overview Apartment and Residents') }}
    </h3>

    @php
        $effectiveShowAspirations = false;
    @endphp

    {{-- 3. Detailed Table --}}
    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead>
                <tr
                    class="bg-zinc-50 dark:bg-zinc-800 text-left text-xs font-bold text-zinc-500 uppercase tracking-wider">
                    <th class="px-3 py-2 border-b">{{ __('Name') }}</th>
                    <th class="px-3 py-2 border-b">{{ __('Birth Year') }}</th>
                    <th class="px-3 py-2 border-b">{{ __('Gua') }}</th>
                    <th class="px-3 py-2 border-b">{{ $effectiveShowAspirations ? __('Life Area') : __('Trigram') }}
                    </th>
                    <th class="px-3 py-2 border-b">{{ __('Element') }}</th>
                    <th class="px-3 py-2 border-b">{{ __('Birth Element') }}</th>
                    <th class="px-3 py-2 border-b">{{ __('OH/WH') }}</th> {{-- East/West Group --}}
                    <th class="px-3 py-2 border-b">{{ __('Relationship') }}</th> {{-- To House --}}
                    <th class="px-3 py-2 border-b">{{ __('Best Direction') }}</th>
                </tr>
            </thead>
            <tbody>
                {{-- House Row --}}
                <tr class="bg-white dark:bg-zinc-900 border-b dark:border-zinc-800">
                    <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">{{ __('Apartment') }}</td>
                    <td class="px-3 py-2">{{ $buildingYear }}</td>
                    <td class="px-3 py-2">{{ $houseGua }}</td>
                    <td class="px-3 py-2">
                        @if($effectiveShowAspirations)
                            {{ $mingGuaService->getLifeAspiration($houseGua) }}
                        @else
                            {{ $houseTrigramName }}
                        @endif
                    </td>
                    <td class="px-3 py-2">{{ $houseElement }}</td>
                    <td class="px-3 py-2">-</td>
                    <td class="px-3 py-2">{{ ($houseAttr['group'] === 'East') ? 'OH' : 'WH' }}</td>
                    <td class="px-3 py-2">-</td>
                    <td class="px-3 py-2">-</td>
                </tr>

                {{-- Residents Rows --}}
                @foreach($residents as $person)
                    @if($person->life_gua)
                        @php
                            $pAttr = $mingGuaService->getAttributes($person->life_gua);
                            $pTrigramName = explode(' ', $pAttr['name'])[0];
                            $pGroup = ($pAttr['group'] === 'East') ? 'OH' : 'WH';
                            $pBirthYear = $person->birth_date ? $mingGuaService->getSolarYear($person->birth_date) : '-';

                            // Elements
                            $pTrigramElement = __($pAttr['element']);
                            $pBirthElement = ($pBirthYear !== '-') ? __($mingGuaService->getYearElement($pBirthYear)) : '-';

                            // Relationship to House (Analyze Person vs House Gua)
                            $compatibility = $mingGuaService->analyzeCompatibility($person->life_gua, $houseGua);
                            $relationCode = $compatibility['rating'] ?? '-';

                            // Best Dir
                            $bestDirCode = $getBestDirName($person->life_gua);
                        @endphp
                        <tr class="bg-white dark:bg-zinc-900 border-b dark:border-zinc-800">
                            <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">{{ $person->name }}</td>
                            <td class="px-3 py-2">{{ $pBirthYear }}</td>
                            <td class="px-3 py-2">{{ $person->life_gua }}</td>
                            <td class="px-3 py-2">
                                @if($effectiveShowAspirations)
                                    {{ $mingGuaService->getLifeAspiration($person->life_gua) }}
                                @else
                                    {{ $pTrigramName }}
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $pTrigramElement }}</td>
                            <td class="px-3 py-2">{{ $pBirthElement }}</td>
                            <td class="px-3 py-2">{{ $pGroup }}</td>
                            <td class="px-3 py-2">{{ $relationCode }}</td>
                            <td class="px-3 py-2">{{ $bestDirCode }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>