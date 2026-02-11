<?php

namespace App\Services\Metaphysics;

class FlyingStarService
{
    public const MOUNTAINS = [
        ['name' => 'N1 (Ren)', 'min' => 337.5, 'max' => 352.5, 'gua' => 1, 'yin_yang' => '+'],
        ['name' => 'N2 (Zi)', 'min' => 352.5, 'max' => 7.5, 'gua' => 1, 'yin_yang' => '-'],
        ['name' => 'N3 (Gui)', 'min' => 7.5, 'max' => 22.5, 'gua' => 1, 'yin_yang' => '-'],
        ['name' => 'NE1 (Chou)', 'min' => 22.5, 'max' => 37.5, 'gua' => 8, 'yin_yang' => '-'],
        ['name' => 'NE2 (Gen)', 'min' => 37.5, 'max' => 52.5, 'gua' => 8, 'yin_yang' => '+'],
        ['name' => 'NE3 (Yin)', 'min' => 52.5, 'max' => 67.5, 'gua' => 8, 'yin_yang' => '+'],
        ['name' => 'E1 (Jia)', 'min' => 67.5, 'max' => 82.5, 'gua' => 3, 'yin_yang' => '+'],
        ['name' => 'E2 (Mao)', 'min' => 82.5, 'max' => 97.5, 'gua' => 3, 'yin_yang' => '-'],
        ['name' => 'E3 (Yi)', 'min' => 97.5, 'max' => 112.5, 'gua' => 3, 'yin_yang' => '-'],
        ['name' => 'SE1 (Chen)', 'min' => 112.5, 'max' => 127.5, 'gua' => 4, 'yin_yang' => '-'],
        ['name' => 'SE2 (Xun)', 'min' => 127.5, 'max' => 142.5, 'gua' => 4, 'yin_yang' => '+'],
        ['name' => 'SE3 (Si)', 'min' => 142.5, 'max' => 157.5, 'gua' => 4, 'yin_yang' => '+'],
        ['name' => 'S1 (Bing)', 'min' => 157.5, 'max' => 172.5, 'gua' => 9, 'yin_yang' => '+'],
        ['name' => 'S2 (Wu)', 'min' => 172.5, 'max' => 187.5, 'gua' => 9, 'yin_yang' => '-'],
        ['name' => 'S3 (Ding)', 'min' => 187.5, 'max' => 202.5, 'gua' => 9, 'yin_yang' => '-'],
        ['name' => 'SW1 (Wei)', 'min' => 202.5, 'max' => 217.5, 'gua' => 2, 'yin_yang' => '-'],
        ['name' => 'SW2 (Kun)', 'min' => 217.5, 'max' => 232.5, 'gua' => 2, 'yin_yang' => '+'],
        ['name' => 'SW3 (Shen)', 'min' => 232.5, 'max' => 247.5, 'gua' => 2, 'yin_yang' => '+'],
        ['name' => 'W1 (Geng)', 'min' => 247.5, 'max' => 262.5, 'gua' => 7, 'yin_yang' => '+'],
        ['name' => 'W2 (You)', 'min' => 262.5, 'max' => 277.5, 'gua' => 7, 'yin_yang' => '-'],
        ['name' => 'W3 (Xin)', 'min' => 277.5, 'max' => 292.5, 'gua' => 7, 'yin_yang' => '-'],
        ['name' => 'NW1 (Xu)', 'min' => 292.5, 'max' => 307.5, 'gua' => 6, 'yin_yang' => '-'],
        ['name' => 'NW2 (Qian)', 'min' => 307.5, 'max' => 322.5, 'gua' => 6, 'yin_yang' => '+'],
        ['name' => 'NW3 (Hai)', 'min' => 322.5, 'max' => 337.5, 'gua' => 6, 'yin_yang' => '+'],
    ];

    // Replacement Star Map (Shen's Rules)
    // Key: Original Star Number
    // Value: [Star for Sub-Mountain 1, Star for Sub-Mountain 2, Star for Sub-Mountain 3]
    private const REPLACEMENT_MAP = [
        1 => [1, 1, 2], // Kan: Ren(1)->1, Zi(2)->1, Gui(3)->2
        2 => [2, 2, 1], // Kun: Wei(1)->2, Kun(2)->2, Shen(3)->1
        3 => [3, 3, 4], // Zhen: Jia(1)->3, Mao(2)->3, Yi(3)->4
        4 => [4, 4, 6], // Xun: Chen(1)->4, Xun(2)->4, Si(3)->6
        // 5 => Special handling (uses the map of the palace it resides in)
        6 => [6, 6, 7], // Qian: Xu(1)->6, Qian(2)->6, Hai(3)->7
        7 => [7, 7, 8], // Dui: Geng(1)->7, You(2)->7, Xin(3)->8
        8 => [8, 8, 9], // Gen: Chou(1)->8, Gen(2)->8, Yin(3)->9
        9 => [9, 9, 1], // Li: Bing(1)->9, Wu(2)->9, Ding(3)->1
    ];

    /**
     * Calculates the full Flying Star Chart.
     */
    public function calculateChart(int $period, float $facingDegree, ?string $mountainOverride = null, bool $forceReplacement = false): array
    {
        if ($mountainOverride) {
            $mountainInfo = collect(self::MOUNTAINS)->firstWhere('name', $mountainOverride);
            if (!$mountainInfo) {
                $mountainInfo = $this->getMountain($facingDegree);
            }
        } else {
            $mountainInfo = $this->getMountain($facingDegree);
        }

        // Detect if we technically need replacement
        $needsReplacement = $this->needsReplacementChart($facingDegree);

        // Use replacement if forced (by user) OR if strictly adhering to needs (optional, usually manual override is preferred)
        // Here we assume: if $forceReplacement is true, we do it. The user decides.
        $isReplacement = $forceReplacement;

        $facingMountainGua = $mountainInfo['gua'];

        // Sitting Direction is 180° opposite
        if ($mountainOverride) {
            $midDegree = ($mountainInfo['min'] + $mountainInfo['max']) / 2;
            if ($mountainInfo['min'] > $mountainInfo['max']) { // North wrap
                $midDegree = ($mountainInfo['min'] + $mountainInfo['max'] + 360) / 2;
            }
            $sittingDegree = fmod($midDegree + 180, 360);
        } else {
            $sittingDegree = fmod($facingDegree + 180, 360);
        }

        $sittingMountainInfo = $this->getMountain($sittingDegree);
        $sittingMountainGua = $sittingMountainInfo['gua'];

        // 1. Base Stars (Period)
        $baseStars = $this->fly($period);

        // 2. Identify Center Stars
        $mountainStarInCenter = $baseStars[$sittingMountainGua];
        $waterStarInCenter = $baseStars[$facingMountainGua];

        // 3. Apply Replacement Logic if active
        // Determine Sub-Mountain Index (1, 2, or 3) for Sitting and Facing
        preg_match('/([1-3])/', $sittingMountainInfo['name'], $sMatches);
        $sittingSubIndex = (int)($sMatches[0] ?? 1);

        preg_match('/([1-3])/', $mountainInfo['name'], $fMatches);
        $facingSubIndex = (int)($fMatches[0] ?? 1);

        if ($isReplacement) { // Use variable we set earlier
            $mountainStarInCenter = $this->getReplacementStar($mountainStarInCenter, $sittingSubIndex, $sittingMountainGua);
            $waterStarInCenter = $this->getReplacementStar($waterStarInCenter, $facingSubIndex, $facingMountainGua);
        }

        // 4. Fly the Stars
        // Flight direction depends on the Yin/Yang of the STAR's home palace sub-mountain.
        // IMPORTANT: Mountain star and Water star have OPPOSITE flight rules:
        //   Water star:   Yang → forward (+), Yin → backward (-)
        //   Mountain star: Yang → backward (-), Yin → forward (+)  [INVERTED!]
        $mountainYangForward = $this->getFlightDirection($mountainStarInCenter, $sittingMountainInfo);
        $mountainDirection = $mountainYangForward; // Yang -> forward, Yin -> backward
        $mountainStars = $this->fly($mountainStarInCenter, $mountainDirection);

        $waterDirection = $this->getFlightDirection($waterStarInCenter, $mountainInfo);
        $waterStars = $this->fly($waterStarInCenter, $waterDirection);

        return [
            'base' => $baseStars,
            'mountain' => $mountainStars,
            'water' => $waterStars,
            'facing_mountain' => $mountainInfo['name'],
            'sitting_mountain' => $sittingMountainInfo['name'],
            'needs_replacement' => $needsReplacement,
            'mountain_flight_direction' => $mountainDirection ? '+' : '-',
            'mountain_yin_yang' => $mountainYangForward ? '+' : '-',
            'water_flight_direction' => $waterDirection ? '+' : '-',
        ];
    }

    /**
     * Finds the mountain for a given degree.
     */
    public function getMountain(float $degree): array
    {
        $degree = fmod($degree + 360, 360);

        foreach (self::MOUNTAINS as $m) {
            if ($m['min'] > $m['max']) { // North wrap around
                if ($degree >= $m['min'] || $degree < $m['max']) {
                    return $m;
                }
            } else {
                if ($degree >= $m['min'] && $degree < $m['max']) {
                    return $m;
                }
            }
        }

        return self::MOUNTAINS[0]; // Fallback
    }

    /**
     * Determines if a replacement chart (Kong Wang) is needed.
     * Usually +/- 3 degrees from a boundary (Void Line).
     */
    public function needsReplacementChart(float $degree): bool
    {
        $degree = fmod($degree + 360, 360);

        foreach (self::MOUNTAINS as $m) {
            // Check closeness to boundaries of the 24 mountains
            // But Void Lines are specifically between the 8 Trigrams, or between Earth/Heaven/Man?
            // Shen's strictly says: 3 degrees left/right of the magnetic needle's line?
            // Simplified: If within 3 degrees of the Sector Boundary.

            $distToMin = abs($degree - $m['min']);
            if ($distToMin > 180) $distToMin = 360 - $distToMin;

            $distToMax = abs($degree - $m['max']);
            if ($distToMax > 180) $distToMax = 360 - $distToMax;

            if ($distToMin <= 3.0 || $distToMax <= 3.0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Flying logic: 5 -> 6 -> 7 -> 8 -> 9 -> 1 -> 2 -> 3 -> 4
     */
    public function fly(int $startStar, bool $forward = true): array
    {
        $path = [5, 6, 7, 8, 9, 1, 2, 3, 4];
        $chart = [];

        for ($i = 0; $i < 9; $i++) {
            $gua = $path[$i];
            if ($forward) {
                $star = ($startStar + $i - 1) % 9 + 1;
            } else {
                $star = ($startStar - $i + 8) % 9 + 1;
            }
            $chart[$gua] = $star;
        }

        return $chart;
    }

    private function getReplacementStar(int $star, int $subIndex, int $palaceGua): int
    {
        // Special Case: Star 5
        // If 5 is the star to be replaced, it adopts the Replacement Map of the Palace it resides in.
        // But here $palaceGua IS the palace the star resides in (e.g. the Sitting Sector is North (1), so Star 5 in North uses Map 1).
        $lookupStar = $star;
        if ($star === 5) {
            $lookupStar = $palaceGua;
        }

        // Map 1-index based to 0-index for array if needed, or just use 1,2,3 keys.
        // Our map uses array w/ 0-index [0,1,2]. So subIndex 1->0, 2->1, 3->2.
        $map = self::REPLACEMENT_MAP[$lookupStar] ?? null;

        if (!$map) return $star; // Should not happen

        return $map[$subIndex - 1] ?? $star;
    }

    /**
     * Determines if a star flies forward (+) or backward (-)
     * $star: The star number LANDING in center (The one that is about to fly)
     * $originalMountainInfo: The original mountain of the house (e.g. N1, S2, etc.)
     */
    private function getFlightDirection(int $star, array $originalMountainInfo): bool
    {
        // 1. Determine which sub-mountain index it is (1, 2, or 3)
        preg_match('/([1-3])/', $originalMountainInfo['name'], $matches);
        $subMountain = (int)($matches[0] ?? 1);

        // 2. Find the palace (gua) of the star that landed in the center
        $targetPalace = $star;

        // Special Case: Star 5
        // If Star 5 is the one flying, it also takes the polarity of the Palace where it came from?
        // Actually, if we ALREADY replaced 5 with something else (in getReplacementStar), $star is NOT 5 anymore.
        // If $star IS 5 (e.g. no replacement, or replacement result is 5), it behaves as the base palace.
        if ($targetPalace === 5) {
            return $originalMountainInfo['yin_yang'] === '+';
        }

        // 3. Find the mountain in THAT palace with the same sub-number
        foreach (self::MOUNTAINS as $m) {
            if ($m['gua'] === $targetPalace) {
                preg_match('/([1-3])/', $m['name'], $targetMatches);
                $targetSub = (int)($targetMatches[0] ?? 0);

                if ($targetSub === $subMountain) {
                    return $m['yin_yang'] === '+';
                }
            }
        }

        return true;
    }
}
