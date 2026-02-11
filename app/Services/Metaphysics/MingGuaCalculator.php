<?php

namespace App\Services\Metaphysics;

use App\Models\BaguaNote;
use App\Models\FloorPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class MingGuaCalculator
{
    // === CLASSICAL BAGUA TRIGRAMS (NEW) ===
    public const CLASSICAL_TRIGRAMS = [
        1 => [
            'name' => 'KAN',
            'symbol' => '☵',
            'direction' => 'N',
            'element' => 'Water',
            'color' => '#3b82f6',
            'bg_color' => '#dbeafe',
        ],
        2 => [
            'name' => 'KUN',
            'symbol' => '☷',
            'direction' => 'SW',
            'element' => 'Earth',
            'color' => '#d97706',
            'bg_color' => '#fef3c7',
        ],
        3 => [
            'name' => 'CHEN',
            'symbol' => '☳',
            'direction' => 'E',
            'element' => 'Wood',
            'color' => '#059669',
            'bg_color' => '#d1fae5',
        ],
        4 => [
            'name' => 'SUN',
            'symbol' => '☴',
            'direction' => 'SE',
            'element' => 'Wood',
            'color' => '#10b981',
            'bg_color' => '#bbf7d0',
        ],
        6 => [
            'name' => 'CHIEN',
            'symbol' => '☰',
            'direction' => 'NW',
            'element' => 'Metal',
            'color' => '#6b7280',
            'bg_color' => '#f3f4f6',
        ],
        7 => [
            'name' => 'TUI',
            'symbol' => '☱',
            'direction' => 'W',
            'element' => 'Metal',
            'color' => '#9ca3af',
            'bg_color' => '#f9fafb',
        ],
        8 => [
            'name' => 'KEN',
            'symbol' => '☶',
            'direction' => 'NE',
            'element' => 'Earth',
            'color' => '#b45309',
            'bg_color' => '#fde68a',
        ],
        9 => [
            'name' => 'LI',
            'symbol' => '☲',
            'direction' => 'S',
            'element' => 'Fire',
            'color' => '#dc2626',
            'bg_color' => '#fef2f2',
        ],
        5 => [
            'name' => 'Tai Qi',
            'symbol' => '☯',
            'direction' => 'Center',
            'element' => 'Earth',
            'color' => '#ca8a04',
            'bg_color' => '#fef9c3',
        ],
    ];

    // === LIFE ASPIRATIONS (Lebensbereiche) ===
    // Mapping der Trigramme zu ihren Lebensbereichen im Bagua
    public const LIFE_ASPIRATIONS = [
        1 => 'Karriere',           // Kan (N) - Career
        2 => 'Partnerschaft',      // Kun (SW) - Love & Relationships
        3 => 'Familie & Gesundheit', // Chen (E) - Family & Health
        4 => 'Reichtum',           // Sun (SE) - Wealth & Prosperity
        5 => 'Gesundheit',         // Tai Qi (Center) - Health & Unity
        6 => 'Hilfreiche Freunde', // Chien (NW) - Helpful People & Travel
        7 => 'Kinder & Kreativität', // Tui (W) - Children & Creativity
        8 => 'Wissen',             // Ken (NE) - Knowledge & Self-Cultivation
        9 => 'Ruhm',               // Li (S) - Fame & Recognition
    ];

    /**
     * Get the life aspiration (Lebensbereich) for a given Gua number.
     */
    public function getLifeAspiration(int $gua): string
    {
        return self::LIFE_ASPIRATIONS[$gua] ?? __('Unknown');
    }

    /**
     * Berechnet das Ming Gua (Life Kua) basierend auf Jahr und Geschlecht.
     * Achtung: Das "chinesische Jahr" beginnt meist am 4. Feb.
     * Für diese Basis-Funktion nehmen wir das gregorianische Jahr an,
     * eine exakte Solar-Term-Prüfung kommt später in der Advanced-Klasse.
     */
    public function calculate(int $year, string $gender): int
    {
        if (!in_array($gender, ['m', 'f'])) {
            throw new InvalidArgumentException("Gender must be 'm' or 'f'.");
        }

        // Quersumme 1 berechnen
        $sum = $this->crossSum($year);

        if ($gender === 'm') {
            $gua = 11 - $this->crossSumRecursive($sum);
        } else { // Female
            $gua = $this->crossSumRecursive($sum + 4);
        }

        // Ergebnis kann negativ oder zweistellig sein -> Normieren
        // (z.B. Mann 2009: QS=9. 9-9=0. Gua 0 gibt es nicht -> Gua 9)
        // (z.B. Mann 1999: QS=18->9. 10-9=1. Gua 1)

        // Gua muss im Bereich 1-9 sein.
        // Wenn < 1: addiere 9
        // Wenn > 9: subtrahiere 9 (oder Quersumme bilden)

        while ($gua < 1)
            $gua += 9;
        while ($gua > 9)
            $gua -= 9;

        // Spezialfall Gua 5
        if ($gua === 5) {
            return ($gender === 'm') ? 2 : 8;
        }

        return $gua;
    }

    private function crossSum(int $zahl): int
    {
        // Konvertiere die Zahl in einen String
        $zahlAlsString = (string)$zahl;

        // Initialisiere die Quersumme
        $quersumme = 0;

        // Durchlaufe jede Ziffer (jedes Zeichen im String)
        for ($i = 0; $i < strlen($zahlAlsString); $i++) {
            // Holen Sie sich das Zeichen an der aktuellen Position
            $zifferAlsChar = $zahlAlsString[$i];

            // Konvertieren Sie das Zeichen zurück in eine Zahl und addieren Sie es
            $quersumme += (int)$zifferAlsChar;
        }

        return $quersumme;
    }

    private function crossSumRecursive(int $n): int
    {
        // 1986 -> 86 -> 8+6=14 -> 1+4=5
        while ($n > 9) {
            $n = array_sum(str_split((string)$n));
        }
        return $n;
    }

    /**
     * Determines the element of the birth year (Heavenly Stem).
     */


    /**
     * Maps degrees to a cardinal direction code (N, NE, E, etc.).
     */
    public function getDirectionFromDegrees(float $degrees): string
    {
        // Normalize degrees
        $degrees = fmod($degrees, 360);
        if ($degrees < 0) $degrees += 360;

        if ($degrees >= 337.5 || $degrees < 22.5) return 'N';
        if ($degrees >= 22.5 && $degrees < 67.5) return 'NE';
        if ($degrees >= 67.5 && $degrees < 112.5) return 'E';
        if ($degrees >= 112.5 && $degrees < 157.5) return 'SE';
        if ($degrees >= 157.5 && $degrees < 202.5) return 'S';
        if ($degrees >= 202.5 && $degrees < 247.5) return 'SW';
        if ($degrees >= 247.5 && $degrees < 292.5) return 'W';
        if ($degrees >= 292.5 && $degrees < 337.5) return 'NW';

        return 'N'; // Fallback
    }

    /**
     * Maps degrees to the corresponding Trigram / Gua number (1-9).
     * Based on Standard 24 Mountains or 8 Directions.
     */
    public function getTrigramFromDegrees(float $degrees): int
    {
        // Normalize degrees
        $degrees = fmod($degrees, 360);
        if ($degrees < 0) $degrees += 360;

        if ($degrees >= 337.5 || $degrees < 22.5) return 1; // Kan (N)
        if ($degrees >= 22.5 && $degrees < 67.5) return 8; // Gen (NE)
        if ($degrees >= 67.5 && $degrees < 112.5) return 3; // Zhen (E)
        if ($degrees >= 112.5 && $degrees < 157.5) return 4; // Xun (SE)
        if ($degrees >= 157.5 && $degrees < 202.5) return 9; // Li (S)
        if ($degrees >= 202.5 && $degrees < 247.5) return 2; // Kun (SW)
        if ($degrees >= 247.5 && $degrees < 292.5) return 7; // Dui (W)
        if ($degrees >= 292.5 && $degrees < 337.5) return 6; // Qian (NW)

        return 5; // Should not happen
    }

    /**
     * Analysiert die Kompatibilität mit präzisem Rating (AA, A1-A3, D1-D4).
     */
    public function analyzeCompatibility(int $personGua, int $sectorGua): array
    {
        $sectorDirections = [
            1 => 'N',
            2 => 'SW',
            3 => 'E',
            4 => 'SE',
            5 => 'Center',
            6 => 'NW',
            7 => 'W',
            8 => 'NE',
            9 => 'S'
        ];

        if (!isset($sectorDirections[$sectorGua])) {
            return ['rating' => '?', 'quality' => __('Unknown'), 'is_compatible' => false, 'class' => 'text-zinc-400'];
        }

        $direction = $sectorDirections[$sectorGua];

        if ($direction === 'Center') {
            return [
                'rating' => 'Neu', // Neutral / Zentrum
                'quality' => __('Tai Qi (Center)'),
                'is_compatible' => true,
                'class' => 'text-amber-600 bg-amber-50 border-amber-200',
                'description' => __('Energetic Center')
            ];
        }

        $personAttr = $this->getAttributes($personGua);

        // --- PRÜFUNG DER GUTEN RICHTUNGEN (A-Ratings) ---
        if (isset($personAttr['good_directions'][$direction])) {
            $rawLabel = $personAttr['good_directions'][$direction]; // z.B. "Sheng Qi (Success)"

            // Mapping basierend auf dem Namen im String
            if (str_contains($rawLabel, 'Sheng Qi')) {
                $rating = 'A+'; // Oder 'AA' - Sheng Qi ist das Beste (Life Generating)
                $desc = __('Best Direction (Success, Wealth)');
            } elseif (str_contains($rawLabel, 'Tian Yi')) {
                $rating = 'A1'; // Heavenly Doctor (Health)
                $desc = __('Great for Health & Healing');
            } elseif (str_contains($rawLabel, 'Yan Nian')) {
                $rating = 'A2'; // Longevity (Relationships)
                $desc = __('Good for Relationships & Harmony');
            } else { // Fu Wei
                $rating = 'A3'; // Stability (Personal Growth)
                $desc = __('Good for Study & Stability');
            }

            return [
                'rating' => $rating,
                'quality' => $rawLabel,
                'is_compatible' => true,
                'description' => $desc,
                'class' => 'text-green-700 bg-green-50 border-green-200 font-bold', // A-Klasse Styling
                'icon' => 'check-circle'
            ];
        }

        // --- PRÜFUNG DER SCHLECHTEN RICHTUNGEN (D-Ratings) ---
        if (isset($personAttr['bad_directions'][$direction])) {
            $rawLabel = $personAttr['bad_directions'][$direction];

            // Die Reihenfolge der "Schlechtigkeit" variiert je nach Schule.
            // Klassisch oft: Huo Hai (Mishaps) < Wu Gui (5 Ghosts) < Liu Sha (6 Killings) < Jue Ming (Total Loss)

            if (str_contains($rawLabel, 'Huo Hai')) {
                $rating = 'D1'; // Least Bad (Mishaps)
                $desc = __('Minor annoyances');
            } elseif (str_contains($rawLabel, 'Wu Gui')) {
                $rating = 'D2'; // Five Ghosts
                $desc = __('Fire, Theft, Quarrels');
            } elseif (str_contains($rawLabel, 'Liu Sha')) {
                $rating = 'D3'; // Six Killings
                $desc = __('Legal issues, Illness');
            } else { // Jue Ming
                $rating = 'D4'; // Total Loss - Worst
                $desc = __('Avoid for sleeping/working');
            }

            return [
                'rating' => $rating,
                'quality' => $rawLabel,
                'is_compatible' => false,
                'description' => $desc,
                'class' => 'text-red-700 bg-red-50 border-red-200 font-bold', // D-Klasse Styling
                'icon' => 'x-circle'
            ];
        }

        return ['rating' => '-', 'quality' => __('Neutral'), 'is_compatible' => false];
    }

    /**
     * Returns the type of a direction (e.g. sheng_qi, jue_ming) for a given Gua.
     */
    public function getDirectionType(int $gua, string $direction): ?string
    {
        $map = [
            1 => ['SE' => 'sheng_qi', 'E' => 'tian_yi', 'S' => 'yan_nian', 'N' => 'fu_wei', 'SW' => 'jue_ming', 'NE' => 'wu_gui', 'NW' => 'liu_sha', 'W' => 'huo_hai'],
            2 => ['NE' => 'sheng_qi', 'W' => 'tian_yi', 'NW' => 'yan_nian', 'SW' => 'fu_wei', 'N' => 'jue_ming', 'SE' => 'wu_gui', 'S' => 'liu_sha', 'E' => 'huo_hai'],
            3 => ['S' => 'sheng_qi', 'N' => 'tian_yi', 'SE' => 'yan_nian', 'E' => 'fu_wei', 'W' => 'jue_ming', 'NW' => 'wu_gui', 'NE' => 'liu_sha', 'SW' => 'huo_hai'],
            4 => ['N' => 'sheng_qi', 'S' => 'tian_yi', 'E' => 'yan_nian', 'SE' => 'fu_wei', 'NE' => 'jue_ming', 'SW' => 'wu_gui', 'W' => 'liu_sha', 'NW' => 'huo_hai'],
            6 => ['W' => 'sheng_qi', 'NE' => 'tian_yi', 'SW' => 'yan_nian', 'NW' => 'fu_wei', 'S' => 'jue_ming', 'E' => 'wu_gui', 'SE' => 'liu_sha', 'N' => 'huo_hai'],
            7 => ['NW' => 'sheng_qi', 'SW' => 'tian_yi', 'NE' => 'yan_nian', 'W' => 'fu_wei', 'E' => 'jue_ming', 'S' => 'wu_gui', 'N' => 'liu_sha', 'SE' => 'huo_hai'],
            8 => ['SW' => 'sheng_qi', 'NW' => 'tian_yi', 'W' => 'yan_nian', 'NE' => 'fu_wei', 'SE' => 'jue_ming', 'N' => 'wu_gui', 'E' => 'liu_sha', 'S' => 'huo_hai'],
            9 => ['E' => 'sheng_qi', 'SE' => 'tian_yi', 'N' => 'yan_nian', 'S' => 'fu_wei', 'NW' => 'jue_ming', 'W' => 'wu_gui', 'SW' => 'liu_sha', 'NE' => 'huo_hai'],
            5 => [] // Special case, handled as 2 (male) or 8 (female) usually, but raw Gua 5 is not standard in this context without gender
        ];

        return $map[$gua][$direction] ?? null;
    }

    /**
     * Returns attributes (element, group, directions) for a given Gua number.
     */
    public function getAttributes(int $gua): array
    {
        // Mapping of the 8 Trigrams (Gua)
        // Format: [Element, Group (East/West), Best Direction (Sheng Qi), ... ]

        // Definition of Directions for readability
        // Good: Sheng Qi (Success), Tian Yi (Health), Yan Nian (Relationships), Fu Wei (Stability)
        // Bad: Jue Ming (Total Loss), Wu Gui (Five Ghosts), Liu Sha (Six Killings), Huo Hai (Mishaps)

        $data = [
            1 => [
                'name' => __('Kan (Water)'),
                'element' => 'Water',
                'group' => 'East',
                'colors' => ['text-blue-600', 'bg-blue-100'],
                'good_directions' => ['SE' => __('Sheng Qi (Success)'), 'E' => __('Tian Yi (Health)'), 'S' => __('Yan Nian (Love)'), 'N' => __('Fu Wei (Stability)')],
                'bad_directions' => ['SW' => __('Jue Ming'), 'NE' => __('Wu Gui'), 'NW' => __('Liu Sha'), 'W' => __('Huo Hai')]
            ],
            2 => [
                'name' => __('Kun (Earth)'),
                'element' => 'Earth',
                'group' => 'West',
                'colors' => ['text-amber-700', 'bg-amber-100'],
                'good_directions' => ['NE' => __('Sheng Qi (Success)'), 'W' => __('Tian Yi (Health)'), 'NW' => __('Yan Nian (Love)'), 'SW' => __('Fu Wei (Stability)')],
                'bad_directions' => ['N' => __('Jue Ming'), 'SE' => __('Wu Gui'), 'S' => __('Liu Sha'), 'E' => __('Huo Hai')]
            ],
            3 => [
                'name' => __('Zhen (Wood)'),
                'element' => 'Wood',
                'group' => 'East',
                'colors' => ['text-green-600', 'bg-green-100'],
                'good_directions' => ['S' => __('Sheng Qi (Success)'), 'N' => __('Tian Yi (Health)'), 'SE' => __('Yan Nian (Love)'), 'E' => __('Fu Wei (Stability)')],
                'bad_directions' => ['W' => __('Jue Ming'), 'NW' => __('Wu Gui'), 'NE' => __('Liu Sha'), 'SW' => __('Huo Hai')]
            ],
            4 => [
                'name' => __('Xun (Wood)'),
                'element' => 'Wood',
                'group' => 'East',
                'colors' => ['text-emerald-600', 'bg-emerald-100'],
                'good_directions' => ['N' => __('Sheng Qi (Success)'), 'S' => __('Tian Yi (Health)'), 'E' => __('Yan Nian (Love)'), 'SE' => __('Fu Wei (Stability)')],
                'bad_directions' => ['NE' => __('Jue Ming'), 'SW' => __('Wu Gui'), 'W' => __('Liu Sha'), 'NW' => __('Huo Hai')]
            ],
            // Gua 5 doesn't exist for people (converted to 2 or 8), fallback to 2
            5 => [ /* Same as Gua 2 */
                'name' => __('Kun (Earth)'),
                'element' => 'Earth',
                'group' => 'West',
                'colors' => ['text-amber-700', 'bg-amber-100'],
                'good_directions' => ['NE' => __('Sheng Qi (Success)'), 'W' => __('Tian Yi (Health)'), 'NW' => __('Yan Nian (Love)'), 'SW' => __('Fu Wei (Stability)')],
                'bad_directions' => ['N' => __('Jue Ming'), 'SE' => __('Wu Gui'), 'S' => __('Liu Sha'), 'E' => __('Huo Hai')]
            ],
            6 => [
                'name' => __('Qian (Metal)'),
                'element' => 'Metal',
                'group' => 'West',
                'colors' => ['text-zinc-500', 'bg-zinc-100'],
                'good_directions' => ['W' => __('Sheng Qi (Success)'), 'NE' => __('Tian Yi (Health)'), 'SW' => __('Yan Nian (Love)'), 'NW' => __('Fu Wei (Stability)')],
                'bad_directions' => ['S' => __('Jue Ming'), 'E' => __('Wu Gui'), 'SE' => __('Liu Sha'), 'N' => __('Huo Hai')]
            ],
            7 => [
                'name' => __('Dui (Metal)'),
                'element' => 'Metal',
                'group' => 'West',
                'colors' => ['text-zinc-500', 'bg-zinc-100'],
                'good_directions' => ['NW' => __('Sheng Qi (Success)'), 'SW' => __('Tian Yi (Health)'), 'NE' => __('Yan Nian (Love)'), 'W' => __('Fu Wei (Stability)')],
                'bad_directions' => ['E' => __('Jue Ming'), 'S' => __('Wu Gui'), 'N' => __('Liu Sha'), 'SE' => __('Huo Hai')]
            ],
            8 => [
                'name' => __('Gen (Earth)'),
                'element' => 'Earth',
                'group' => 'West',
                'colors' => ['text-amber-700', 'bg-amber-100'],
                'good_directions' => ['SW' => __('Sheng Qi (Success)'), 'NW' => __('Tian Yi (Health)'), 'W' => __('Yan Nian (Love)'), 'NE' => __('Fu Wei (Stability)')],
                'bad_directions' => ['SE' => __('Jue Ming'), 'N' => __('Wu Gui'), 'E' => __('Liu Sha'), 'S' => __('Huo Hai')]
            ],
            9 => [
                'name' => __('Li (Fire)'),
                'element' => 'Fire',
                'group' => 'East',
                'colors' => ['text-red-600', 'bg-red-100'],
                'good_directions' => ['E' => __('Sheng Qi (Success)'), 'SE' => __('Tian Yi (Health)'), 'N' => __('Yan Nian (Love)'), 'S' => __('Fu Wei (Stability)')],
                'bad_directions' => ['NW' => __('Jue Ming'), 'W' => __('Wu Gui'), 'SW' => __('Liu Sha'), 'NE' => __('Huo Hai')]
            ],
        ];

        return $data[$gua] ?? $data[2];
    }

    /**
     * Analysiert die Kompatibilität von fliegenden Sternen mit dem Life Gua einer Person.
     */
    public function analyzeFlyingStarCompatibility(int $lifeGua, int $mountainStar, int $waterStar): array
    {
        $lifeAttr = $this->getAttributes($lifeGua);
        $lifeElement = $lifeAttr['element'];

        // Sterne als Elemente
        // 1: Water, 2: Earth, 3: Wood, 4: Wood, 5: Earth, 6: Metal, 7: Metal, 8: Earth, 9: Fire
        $starElements = [
            1 => 'Water',
            2 => 'Earth',
            3 => 'Wood',
            4 => 'Wood',
            5 => 'Earth',
            6 => 'Metal',
            7 => 'Metal',
            8 => 'Earth',
            9 => 'Fire'
        ];

        $mountainElement = $starElements[$mountainStar] ?? __('Unknown');
        $waterElement = $starElements[$waterStar] ?? __('Unknown');

        // Bergstern (Gesundheit/Beziehungen) vs Life Gua
        $mountainRel = $this->analyzeElementRelationship($lifeElement, $mountainElement);
        // Wasserstern (Wohlstand/Karriere) vs Life Gua
        $waterRel = $this->analyzeElementRelationship($lifeElement, $waterElement);

        return [
            'mountain' => [
                'star' => $mountainStar,
                'element' => $mountainElement,
                'quality' => $mountainRel['quality'] ?? 'Neutral',
                'description' => $mountainRel['desc'] ?? '',
                'label' => $mountainRel['label'] ?? ''
            ],
            'water' => [
                'star' => $waterStar,
                'element' => $waterElement,
                'quality' => $waterRel['quality'] ?? 'Neutral',
                'description' => $waterRel['desc'] ?? '',
                'label' => $waterRel['label'] ?? ''
            ]
        ];
    }

    /**
     * Vergleicht zwei Elemente (Gua vs. Year) und liefert die Beziehung.
     * Wir betrachten das Gua als "Selbst" und das Jahr als "Basis/Wurzel".
     * Frage: Wie wirkt das Jahr auf das Gua?
     */
    public function analyzeElementRelationship(string $guaElement, string $yearElement): array
    {
        // Zyklus: Wood -> Fire -> Earth -> Metal -> Water -> Wood
        $cycle = [__('Wood'), __('Fire'), __('Earth'), __('Metal'), __('Water')];

        $g = __(ucfirst($guaElement));
        $y = __(ucfirst($yearElement));

        if ($g === $y) {
            return [
                'type' => __('Same'),
                'quality' => 'Good',
                'label' => __('Harmonious'),
                'desc' => __('Your birth year supports your Life Gua directly. You have a strong energetic foundation.'),
                'color' => 'text-blue-600 bg-blue-50'
            ];
        }

        // Find index
        $gIndex = array_search($g, $cycle);
        $yIndex = array_search($y, $cycle);

        // Distanz im Zyklus berechnen
        // 1 Schritt vorwärts = Erzeugend (Mutter -> Kind)
        // 2 Schritte vorwärts = Kontrollierend

        // Wir prüfen: Was macht Y (Jahr) mit G (Gua)?

        // Case 1: Jahr nährt Gua (Y -> G)
        // Wenn G der Nachfolger von Y ist.
        if (($yIndex + 1) % 5 === $gIndex) {
            return [
                'type' => __('Resource'),
                'quality' => 'Excellent',
                'label' => __('Nourishing'),
                'desc' => __('Your birth year nourishes your Life Gua (Resource). This indicates natural support and good vitality.'),
                'color' => 'text-green-600 bg-green-50'
            ];
        }

        // Case 2: Gua nährt Jahr (G -> Y)
        // Das Jahr "saugt" am Gua (Output).
        if (($gIndex + 1) % 5 === $yIndex) {
            return [
                'type' => __('Output'),
                'quality' => 'Neutral',
                'label' => __('Draining'),
                'desc' => __('Your Life Gua feeds your birth year. You are generous, but may tire easily. Watch your energy levels.'),
                'color' => 'text-amber-600 bg-amber-50'
            ];
        }

        // Case 3: Jahr kontrolliert Gua (Y -x-> G)
        // Wenn Y zwei Schritte vor G ist (oder G drei Schritte nach Y).
        if (($yIndex + 2) % 5 === $gIndex) {
            return [
                'type' => __('Control'),
                'quality' => 'Challenging',
                'label' => __('Controlling'),
                'desc' => __('Your birth year controls your Life Gua. You may face internal pressure or high expectations. Discipline helps you.'),
                'color' => 'text-red-600 bg-red-50'
            ];
        }

        // Case 4: Gua kontrolliert Jahr (G -x-> Y)
        // Wenn G zwei Schritte vor Y ist.
        if (($gIndex + 2) % 5 === $yIndex) {
            return [
                'type' => __('Wealth'),
                'quality' => 'Mixed',
                'label' => __('Dominating'),
                'desc' => __('Your Life Gua controls your birth year. You strive for control and achievement, but this requires effort.'),
                'color' => 'text-purple-600 bg-purple-50'
            ];
        }

        return []; // Should not happen
    }

    /**
     * Berechnet das Element des Geburtsjahres (Heavenly Stem).
     * 0=Metal, 1=Metal, 2=Water, 3=Water, 4=Wood, 5=Wood, 6=Fire, 7=Fire, 8=Earth, 9=Earth
     * Basierend auf der letzten Ziffer des Jahres.
     */
    public function getYearElement(int $year): string
    {
        // Die letzte Ziffer bestimmt den Himmelsstamm und damit das Element.
        // 0: Yang Metal (Geng), 1: Yin Metal (Xin)
        // 2: Yang Water (Ren), 3: Yin Water (Gui)
        // 4: Yang Wood (Jia), 5: Yin Wood (Yi)
        // 6: Yang Fire (Bing), 7: Yin Fire (Ding)
        // 8: Yang Earth (Wu), 9: Yin Earth (Ji)

        $lastDigit = $year % 10;

        return match ($lastDigit) {
            0, 1 => 'Metal',
            2, 3 => 'Water',
            4, 5 => 'Wood',
            6, 7 => 'Fire',
            8, 9 => 'Earth',
        };
    }

    /**
     * Berechnet das Tierzeichen (Earthly Branch).
     */
    public function getZodiac(int $year): string
    {
        // 1900 = Ratte. (1900 - 4) % 12 ... Standard Algorithmus.
        // Rat, Ox, Tiger, Rabbit, Dragon, Snake, Horse, Goat, Monkey, Rooster, Dog, Pig
        $animals = [
            'Monkey',
            'Rooster',
            'Dog',
            'Pig',
            'Rat',
            'Ox',
            'Tiger',
            'Rabbit',
            'Dragon',
            'Snake',
            'Horse',
            'Goat'
        ];

        return $animals[$year % 12];
    }

    /**
     * Liefert Tailwind Farb-Klassen für ein Element.
     * [Text-Color, Bg-Color, Border-Color]
     */
    public function getElementColors(string $element): array
    {
        return match (strtolower($element)) {
            'wood' => ['text-emerald-600 dark:text-emerald-400', 'bg-emerald-100 dark:bg-emerald-900/30', 'border-emerald-200 dark:border-emerald-800'],
            'fire' => ['text-rose-600 dark:text-rose-400', 'bg-rose-100 dark:bg-rose-900/30', 'border-rose-200 dark:border-rose-800'],
            'earth' => ['text-amber-700 dark:text-amber-400', 'bg-amber-100 dark:bg-amber-900/30', 'border-amber-200 dark:border-amber-800'],
            'metal' => ['text-zinc-500 dark:text-zinc-400', 'bg-zinc-100 dark:bg-zinc-800', 'border-zinc-200 dark:border-zinc-700'],
            'water' => ['text-blue-600 dark:text-blue-400', 'bg-blue-100 dark:bg-blue-900/30', 'border-blue-200 dark:border-blue-800'],
            default => ['text-zinc-500', 'bg-zinc-100', 'border-zinc-200'],
        };
    }

    /**
     * Ermittelt das relevante Jahr für Feng Shui Berechnungen (Solar Year).
     * Wechsel ist am Li Chun (ca. 4. Februar).
     */
    public function getSolarYear(Carbon $date): int
    {
        // Wenn Datum vor dem 4. Februar ist, gehört es zum Vorjahr.
        // Beispiel: 25.01.1984 -> Gehört energetisch zu 1983.

        $cutoffMonth = 2;
        $cutoffDay = 4;

        if ($date->month < $cutoffMonth || ($date->month == $cutoffMonth && $date->day < $cutoffDay)) {
            return $date->year - 1;
        }

        return $date->year;
    }

    /**
     * Analysiert die Kompatibilität zwischen zwei GUA-Zahlen für Partner.
     */
    public function analyzePartnerCompatibility(int $gua1, int $gua2): array
    {
        $attr1 = $this->getAttributes($gua1);
        $attr2 = $this->getAttributes($gua2);

        $sameGroup = ($attr1['group'] === $attr2['group']);

        $cycle = ['Wood', 'Fire', 'Earth', 'Metal', 'Water'];
        $e1 = $attr1['element'];
        $e2 = $attr2['element'];

        $idx1 = array_search($e1, $cycle);
        $idx2 = array_search($e2, $cycle);

        if ($e1 === $e2) {
            $result = [
                'quality' => 'Good',
                'label' => __('Harmonious'),
                'desc' => __('Both share the same element. You have a similar approach to life and understand each other well.'),
                'color' => 'text-blue-600 bg-blue-50',
                'same_group' => $sameGroup
            ];
        } elseif (($idx1 + 1) % 5 === $idx2 || ($idx2 + 1) % 5 === $idx1) {
            $result = [
                'quality' => 'Excellent',
                'label' => __('Supportive'),
                'desc' => __('Your elements nourish each other. This is a very supportive and growth-oriented relationship.'),
                'color' => 'text-green-600 bg-green-50',
                'same_group' => $sameGroup
            ];
        } elseif (($idx1 + 2) % 5 === $idx2 || ($idx2 + 2) % 5 === $idx1) {
            $result = [
                'quality' => 'Challenging',
                'label' => __('Challenging'),
                'desc' => __('Your elements are in a controlling relationship. This can lead to tension but also offers great potential for personal growth if handled with respect.'),
                'color' => 'text-red-600 bg-red-50',
                'same_group' => $sameGroup
            ];
        } else {
            $result = [
                'quality' => 'Neutral',
                'label' => __('Balanced'),
                'desc' => __('Your energetic structures are different but can complement each other well through conscious interaction.'),
                'color' => 'text-zinc-600 bg-zinc-50',
                'same_group' => $sameGroup
            ];
        }

        // Adjust quality if in different group (West vs East)
        if (!$sameGroup && $result['quality'] !== 'Challenging') {
            $result['desc'] .= ' ' . __('Note: You belong to different GUA groups (East vs West), which can bring different fundamental needs into the relationship.');
        }

        return $result;
    }

    public function calculateClassicalBaguaForFloorPlan(FloorPlan $floorPlan, ?float $compassDirection = null, ?float $sittingDirection = null): array
    {
        if (!$floorPlan->project || !$floorPlan->project->hasValidDirection()) {
            return ['error' => 'No valid project direction'];
        }

        $project = $floorPlan->project;

        // Determine Directions
        $compass = $compassDirection ?? $project->compass_direction;
        $sitting = $sittingDirection ?? $project->sitting_direction;

        // Fallback for Sitting if not provided
        if ($sitting === null && $compass !== null) {
            $sitting = fmod($compass + 180, 360);
        }

        // 1. Calculate Trigrams (House Group) based on Sitting
        $sitzGua = $this->degreesToTrigram($sitting);
        $houseGroup = $this->calculateHouseGroup($sitzGua);

        // 2. Rotate Grid based on Compass (Facing Logic)
        $facingTrigramForGrid = $this->degreesToTrigram($compass);
        $baguaGrid = $this->rotateBaguaGrid($facingTrigramForGrid);

        // Flying Stars Logic
        $flyingStars = null;
        $user = Auth::user();
        if ($user && $user->hasFeature('flying_stars')) {
            $fsService = app(FlyingStarService::class);
            $flyingStars = $fsService->calculateChart(
                $project->period,
                $project->compass_direction, // Uses Compass
                $project->facing_mountain,
                (bool)$project->is_replacement_chart
            );

            if (!$project->facing_mountain) {
                if ($flyingStars['needs_replacement'] && !$project->is_replacement_chart) {
                    $flyingStars = $fsService->calculateChart(
                        $project->period,
                        $project->compass_direction,
                        $project->facing_mountain,
                        true
                    );
                }
                $project->update([
                    'facing_mountain' => $flyingStars['facing_mountain'],
                    'is_replacement_chart' => $flyingStars['needs_replacement']
                ]);
            }
        }

        // Delete old notes
        $floorPlan->baguaNotes()->delete();

        $results = [];

        foreach ($baguaGrid as $position => $gua) {
            if ($gua !== null) {
                // $gua is the actual Trigram (e.g. 1=Kan) at this Grid Position (e.g. 1=Front)
                $trigramData = self::CLASSICAL_TRIGRAMS[$gua];

                $data = [
                    'floor_plan_id' => $floorPlan->id,
                    'gua_number' => $position, // The Grid Position (1-9)
                ];

                if ($flyingStars) {
                    $data['mountain_star'] = $flyingStars['mountain'][$gua] ?? null;
                    $data['water_star'] = $flyingStars['water'][$gua] ?? null;
                    $data['base_star'] = $flyingStars['base'][$gua] ?? null;
                }

                $data['content'] = json_encode([
                    'gua_number' => $position,
                    'trigram_gua' => $gua,
                    'name' => $trigramData['name'],
                    'symbol' => $trigramData['symbol'],
                    'direction' => $trigramData['direction'],
                    'element' => $trigramData['element'],
                    'color' => $trigramData['color'],
                    'bg_color' => $trigramData['bg_color'],
                    'ventilation_degrees' => $compass, // Actually compass degrees now
                    'sitz_gua' => $sitzGua,
                    'direction_type' => $project->getDirectionType(),
                ]);

                $baguaNote = BaguaNote::create($data);
                $results[$position] = $baguaNote->content;
            }
        }

        return [
            'grid' => $baguaGrid,
            'sitz_gua' => $sitzGua,
            'facing_gua' => $facingTrigramForGrid,
            'house_group' => $houseGroup, // 'East'/'West'
            'mountain' => $this->calculateMountain($sitting), // e.g. "Ost, 1/3"
            'facing_mountain' => $this->calculateMountain($compass), // e.g. "Nord, 3/3"
            'compass_degrees' => $compass,
            'notes_count' => count($results),
            'flying_stars' => (bool)$flyingStars,
        ];
    }

    public function degreesToTrigram(float $degrees): int
    {
        $ranges = [
            [337.5, 22.5, 1],   // N = Kan (1)
            [22.5, 67.5, 8],    // NE = Gen (8)
            [67.5, 112.5, 3],   // E = Zhen (3)
            [112.5, 157.5, 4],  // SE = Xun (4)
            [157.5, 202.5, 9],  // S = Li (9)
            [202.5, 247.5, 2],  // SW = Kun (2)
            [247.5, 292.5, 7],  // W = Dui (7)
            [292.5, 337.5, 6],  // NW = Qian (6)
        ];

        $normalized = fmod($degrees + 360, 360);

        foreach ($ranges as [$min, $max, $gua]) {
            if ($min > $max) {
                if ($normalized >= $min || $normalized < $max) return $gua;
            } else {
                if ($normalized >= $min && $normalized < $max) return $gua;
            }
        }
        return 1;
    }

    public function calculateHouseGroup(int $sitzGua): string
    {
        return in_array($sitzGua, [1, 3, 4, 9]) ? 'East' : 'West';
    }

    public function calculateMountain(float $degrees): string
    {
        // 24 Mountains: Each direction (N, NE, E...) has 3 sub-sectors (1, 2, 3)
        // N spans 337.5 to 22.5. 
        // N1: 337.5 - 352.5 (North, 1/3) -> Ren & Zi & Gui mappings... but simplified:
        // N1 (Ren), N2 (Zi), N3 (Gui)

        // Logical Index 0..23 starting near N2 essentially?
        // Let's stick to simple degree ranges to ensure "1/3" is correct.

        // Define centers of Directions:
        // N: 0 (360), NE: 45, E: 90, SE: 135, S: 180, SW: 225, W: 270, NW: 315
        // Each sector is +/- 22.5 deg.
        // Within sector: 
        // 1/3: [Center - 22.5, Center - 7.5)
        // 2/3: [Center - 7.5, Center + 7.5)
        // 3/3: [Center + 7.5, Center + 22.5)

        $normalized = fmod($degrees + 360, 360);

        $directions = [
            0 => __('North'),
            45 => __('Northeast'),
            90 => __('East'),
            135 => __('Southeast'),
            180 => __('South'),
            225 => __('Southwest'),
            270 => __('West'),
            315 => __('Northwest')
        ];

        foreach ($directions as $center => $label) {
            // Check if degrees fall within this 45-degree sector
            // Handle North wrap-around carefully
            $min = fmod($center - 22.5 + 360, 360);
            $max = fmod($center + 22.5 + 360, 360);

            $inRange = false;
            if ($min > $max) { // Wrap around 0/360
                if ($normalized >= $min || $normalized < $max) $inRange = true;
            } else {
                if ($normalized >= $min && $normalized < $max) $inRange = true;
            }

            if ($inRange) {
                // Determine Third
                // Calculate delta from sector start ($min)
                // Distance from min:
                $dist = $normalized - $min;
                if ($dist < 0) $dist += 360; // Handle wrap

                if ($dist < 15) return "$label, 1/3";
                if ($dist < 30) return "$label, 2/3";
                return "$label, 3/3";
            }
        }

        return 'Unknown';
    }

    private function rotateBaguaGrid(int $facingTrigram): array
    {
        // Grid Path (CW from Bottom): N(1) -> NE(8) -> E(3) -> SE(4) -> S(9) -> SW(2) -> W(7) -> NW(6)
        $gridPath = [1, 8, 3, 4, 9, 2, 7, 6];
        // Trigram Sequence (N->NE->E...): 1 -> 8 -> 3 -> 4 -> 9 -> 2 -> 7 -> 6
        $trigramSeq = [1, 8, 3, 4, 9, 2, 7, 6];

        $startIndex = array_search($facingTrigram, $trigramSeq);
        if ($startIndex === false) $startIndex = 0;

        $grid = [];
        $count = count($trigramSeq);
        for ($i = 0; $i < $count; $i++) {
            $grid[$gridPath[$i]] = $trigramSeq[($startIndex + $i) % $count];
        }
        $grid[5] = 5;
        return $grid;
    }

    public function getBestDirection(int $gua): array
    {
        $attr = $this->getAttributes($gua);
        $bestDir = null;
        $bestLabel = null;

        foreach ($attr['good_directions'] as $dir => $label) {
            if (str_contains($label, 'Sheng Qi')) {
                $bestDir = $dir;
                $bestLabel = $label;
                break;
            }
        }

        if (!$bestDir) {
            // Fallback to first if not found (should not happen)
            $bestDir = array_key_first($attr['good_directions']);
            $bestLabel = $attr['good_directions'][$bestDir];
        }

        return [
            'direction' => $bestDir,
            'label' => $bestLabel,
            'degrees' => $this->getDirectionDegrees($bestDir)
        ];
    }

    public function getDirectionDegrees(string $direction): string
    {
        $ranges = [
            'N' => '337.5° - 22.5°',
            'NE' => '22.5° - 67.5°',
            'E' => '67.5° - 112.5°',
            'SE' => '112.5° - 157.5°',
            'S' => '157.5° - 202.5°',
            'SW' => '202.5° - 247.5°',
            'W' => '247.5° - 292.5°',
            'NW' => '292.5° - 337.5°',
        ];

        return $ranges[$direction] ?? '';
    }
}
