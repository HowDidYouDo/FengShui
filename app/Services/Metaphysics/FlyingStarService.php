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
     * Usually +/- 1.5 or 3 degrees from a boundary.
     */
    public function needsReplacementChart(float $degree): bool
    {
        $degree = fmod($degree + 360, 360);

        foreach (self::MOUNTAINS as $m) {
            $distToMin = abs($degree - $m['min']);
            if ($distToMin > 180) $distToMin = 360 - $distToMin;

            $distToMax = abs($degree - $m['max']);
            if ($distToMax > 180) $distToMax = 360 - $distToMax;

            if ($distToMin <= 1.5 || $distToMax <= 1.5) {
                return true;
            }
        }
        return false;
    }

    /**
     * Calculates the full Flying Star Chart.
     */
    public function calculateChart(int $period, float $facingDegree): array
    {
        $mountainInfo = $this->getMountain($facingDegree);
        $facingMountainGua = $mountainInfo['gua'];

        // Sitz-Richtung ist 180° gegenüber
        $sittingDegree = fmod($facingDegree + 180, 360);
        $sittingMountainInfo = $this->getMountain($sittingDegree);
        $sittingMountainGua = $sittingMountainInfo['gua'];

        // 1. Zeitstern-Chart (Base Stars)
        $baseStars = $this->fly($period);

        // 2. Bergstern (Sitzstern) im Zentrum
        $centerBaseStar = $baseStars[5]; // In Gua 5
        $mountainStarInCenter = $baseStars[$sittingMountainGua];

        // 3. Wasserstern (Blickstern) im Zentrum
        $waterStarInCenter = $baseStars[$facingMountainGua];

        // Flugrichtung Bergstern bestimmen
        $mountainDirection = $this->getFlightDirection($mountainStarInCenter, $sittingMountainInfo);
        $mountainStars = $this->fly($mountainStarInCenter, $mountainDirection);

        // Flugrichtung Wasserstern bestimmen
        $waterDirection = $this->getFlightDirection($waterStarInCenter, $mountainInfo);
        $waterStars = $this->fly($waterStarInCenter, $waterDirection);

        return [
            'base' => $baseStars,
            'mountain' => $mountainStars,
            'water' => $waterStars,
            'facing_mountain' => $mountainInfo['name'],
            'sitting_mountain' => $sittingMountainInfo['name'],
            'needs_replacement' => $this->needsReplacementChart($facingDegree),
        ];
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

    /**
     * Determines if a star flies forward (+) or backward (-)
     * $star: The star number landing in center (1-9)
     * $originalMountainInfo: The mountain of the house (e.g. N1, S2, etc.)
     */
    private function getFlightDirection(int $star, array $originalMountainInfo): bool
    {
        // 1. Determine which sub-mountain it is (1, 2, or 3)
        // N1 is 1, N2 is 2, N3 is 3
        $subMountain = (int) substr($originalMountainInfo['name'], 1, 1);

        // 2. Find the palace (gua) of the star that landed in the center
        // If Star 1 lands in center, its palace is 1 (North).
        // If Star 5 lands in center, it's special.
        $targetPalace = $star;
        if ($targetPalace === 5) {
            // Star 5 takes the direction of the sitting/facing mountain itself?
            // Actually it takes the polarity of the base period star in that palace.
            // Simplified: use original mountain's polarity.
            return $originalMountainInfo['yin_yang'] === '+';
        }

        // 3. Find the mountain in THAT palace with the same sub-number
        // E.g. if we are N2, and star 3 lands in center, we look at E2.
        foreach (self::MOUNTAINS as $m) {
            if ($m['gua'] === $targetPalace && str_ends_with($m['name'], (string)$subMountain . ')')) {
                return $m['yin_yang'] === '+';
            }
        }

        return true;
    }
}
