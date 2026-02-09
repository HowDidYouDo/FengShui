<?php

namespace App\Services\Metaphysics;

use App\Models\BaguaNote;
use App\Models\FloorPlan;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class MingGuaCalculator
{
    // === CLASSICAL BAGUA TRIGRAMS (NEW) ===
    public const CLASSICAL_TRIGRAMS = [
        1 => [
            'name' => 'KAN', 'symbol' => '☵', 'direction' => 'N', 'element' => 'Water',
            'color' => '#3b82f6', 'bg_color' => '#dbeafe',
        ],
        2 => [
            'name' => 'KUN', 'symbol' => '☷', 'direction' => 'SW', 'element' => 'Earth',
            'color' => '#d97706', 'bg_color' => '#fef3c7',
        ],
        3 => [
            'name' => 'CHEN', 'symbol' => '☳', 'direction' => 'E', 'element' => 'Wood',
            'color' => '#059669', 'bg_color' => '#d1fae5',
        ],
        4 => [
            'name' => 'SUN', 'symbol' => '☴', 'direction' => 'SE', 'element' => 'Wood',
            'color' => '#10b981', 'bg_color' => '#bbf7d0',
        ],
        6 => [
            'name' => 'CHIEN', 'symbol' => '☰', 'direction' => 'NW', 'element' => 'Metal',
            'color' => '#6b7280', 'bg_color' => '#f3f4f6',
        ],
        7 => [
            'name' => 'TUI', 'symbol' => '☱', 'direction' => 'W', 'element' => 'Metal',
            'color' => '#9ca3af', 'bg_color' => '#f9fafb',
        ],
        8 => [
            'name' => 'KEN', 'symbol' => '☶', 'direction' => 'NE', 'element' => 'Earth',
            'color' => '#b45309', 'bg_color' => '#fde68a',
        ],
        9 => [
            'name' => 'LI', 'symbol' => '☲', 'direction' => 'S', 'element' => 'Fire',
            'color' => '#dc2626', 'bg_color' => '#fef2f2',
        ],
    ];

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

    private function crossSumRecursive(int $n): int
    {
        // 1986 -> 86 -> 8+6=14 -> 1+4=5
        while ($n > 9) {
            $n = array_sum(str_split((string)$n));
        }
        return $n;
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
     * Analysiert die Kompatibilität von fliegenden Sternen mit dem Life Gua einer Person.
     */
    public function analyzeFlyingStarCompatibility(int $lifeGua, int $mountainStar, int $waterStar): array
    {
        $lifeAttr = $this->getAttributes($lifeGua);
        $lifeElement = $lifeAttr['element'];

        // Sterne als Elemente
        // 1: Water, 2: Earth, 3: Wood, 4: Wood, 5: Earth, 6: Metal, 7: Metal, 8: Earth, 9: Fire
        $starElements = [
            1 => 'Water', 2 => 'Earth', 3 => 'Wood', 4 => 'Wood',
            5 => 'Earth', 6 => 'Metal', 7 => 'Metal', 8 => 'Earth', 9 => 'Fire'
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
                'quality' => __('Good'),
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
                'quality' => __('Excellent'),
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
                'quality' => __('Neutral'),
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
                'quality' => __('Challenging'),
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
                'quality' => __('Mixed'),
                'label' => __('Dominating'),
                'desc' => __('Your Life Gua controls your birth year. You strive for control and achievement, but this requires effort.'),
                'color' => 'text-purple-600 bg-purple-50'
            ];
        }

        return []; // Should not happen
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

    public function ventilationToSitzGua(float $ventilationDegrees): int
    {
        $ventilationRanges = [
            [337.5, 22.5, 9],   // N=LI
            [22.5, 67.5, 2],    // NE=KUN
            [67.5, 112.5, 7],   // E=TUI
            [112.5, 157.5, 6],  // SE=CHIEN
            [157.5, 202.5, 1],  // S=KAN
            [202.5, 247.5, 8],  // SW=KEN
            [247.5, 292.5, 3],  // W=CHEN
            [292.5, 337.5, 4],  // NW=SUN
        ];

        $normalized = fmod($ventilationDegrees + 360, 360);

        foreach ($ventilationRanges as [$min, $max, $sitzGua]) {
            if ($normalized >= $min && $normalized < $max) {
                return $sitzGua;
            }
        }
        return 9; // Default LI
    }

    private function rotateBaguaGrid(int $sitzGua): array
    {
        $positions = [2, 3, 6, 9, 8, 7, 4, 1]; // Clockwise skip 5
        $grid = [];
        $currentGua = $sitzGua;

        foreach ($positions as $position) {
            $grid[$position] = $currentGua;
            $currentGua = $this->getNextTrigram($currentGua);
        }

        $grid[5] = null; // Center empty
        return $grid;
    }

    private function getNextTrigram(int $currentGua): int
    {
        $sequence = [1, 8, 3, 4, 9, 2, 7, 6]; // KAN→KEN→CHEN→SUN→LI→KUN→TUI→CHIEN
        $index = array_search($currentGua, $sequence);
        return $sequence[($index + 1) % count($sequence)];
    }

    // === MAIN BAGUA METHOD ===
    public function calculateClassicalBaguaForFloorPlan(FloorPlan $floorPlan, ?float $direction = null): array
    {
        if (!$floorPlan->project || !$floorPlan->project->hasValidDirection()) {
            return ['error' => 'No valid project direction'];
        }

        $project = $floorPlan->project;
        $direction = $direction ?? $project->getActiveDirection();
        $sitzGua = $this->ventilationToSitzGua($direction);
        $baguaGrid = $this->rotateBaguaGrid($sitzGua);

        // Flying Stars Logic
        $flyingStars = null;
        $user = Auth::user();
        if ($user && $user->hasFeature('flying_stars')) {
            $fsService = app(FlyingStarService::class);
            $flyingStars = $fsService->calculateChart(
                $project->period,
                $project->facing_direction,
                $project->facing_mountain, // Pass the override if set
                (bool) $project->is_replacement_chart
            );

            // Update Project with FS info if not yet set (and not overridden)
            if (!$project->facing_mountain) {
                // If we detected replacement needed but didn't calculate with it, do it now
                if ($flyingStars['needs_replacement'] && !$project->is_replacement_chart) {
                    $flyingStars = $fsService->calculateChart(
                        $project->period,
                        $project->facing_direction,
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
            if ($position != 5 && $gua !== null) {
                $trigramData = self::CLASSICAL_TRIGRAMS[$gua];

                $data = [
                    'floor_plan_id' => $floorPlan->id,
                    'gua_number' => $position,
                ];

                // Add Flying Stars to Note if available
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
                    'ventilation_degrees' => $direction,
                    'sitz_gua' => $sitzGua,
                    'direction_type' => $project->getDirectionType(),
                    'mountain_flight_direction' => $flyingStars['mountain_flight_direction'] ?? null,
                    'water_flight_direction' => $flyingStars['water_flight_direction'] ?? null,
                ]);

                $baguaNote = BaguaNote::create($data);
                $results[$position] = $baguaNote->content;
            }
        }

        return [
            'grid' => $baguaGrid,
            'sitz_gua' => $sitzGua,
            'ventilation_degrees' => $direction,
            'notes_count' => count($results),
            'flying_stars' => (bool)$flyingStars,
        ];
    }

}
