<?php
namespace Database\Seeders;

use App\Models\EarthlyBranch;
use App\Models\HeavenlyStem;
use App\Models\JiaZi;
use Illuminate\Database\Seeder;

class MetaphysicsCoreSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Stems
        $stems = [
            ['name_en' => 'Jia', 'name_zh' => '甲', 'yin_yang' => 'yang', 'element' => 'wood'],
            ['name_en' => 'Yi',  'name_zh' => '乙', 'yin_yang' => 'yin',  'element' => 'wood'],
            ['name_en' => 'Bing','name_zh' => '丙', 'yin_yang' => 'yang', 'element' => 'fire'],
            ['name_en' => 'Ding','name_zh' => '丁', 'yin_yang' => 'yin',  'element' => 'fire'],
            ['name_en' => 'Wu',  'name_zh' => '戊', 'yin_yang' => 'yang', 'element' => 'earth'],
            ['name_en' => 'Ji',  'name_zh' => '己', 'yin_yang' => 'yin',  'element' => 'earth'],
            ['name_en' => 'Geng','name_zh' => '庚', 'yin_yang' => 'yang', 'element' => 'metal'],
            ['name_en' => 'Xin', 'name_zh' => '辛', 'yin_yang' => 'yin',  'element' => 'metal'],
            ['name_en' => 'Ren', 'name_zh' => '壬', 'yin_yang' => 'yang', 'element' => 'water'],
            ['name_en' => 'Gui', 'name_zh' => '癸', 'yin_yang' => 'yin',  'element' => 'water'],
        ];

        foreach ($stems as $stem) {
            HeavenlyStem::create($stem);
        }

        // 2. Branches
        $branches = [
            ['name_en' => 'Zi',   'name_zh' => '子', 'animal_en' => 'Rat',     'yin_yang' => 'yang', 'element' => 'water', 'reference_hour_start' => 23],
            ['name_en' => 'Chou', 'name_zh' => '丑', 'animal_en' => 'Ox',      'yin_yang' => 'yin',  'element' => 'earth', 'reference_hour_start' => 1],
            ['name_en' => 'Yin',  'name_zh' => '寅', 'animal_en' => 'Tiger',   'yin_yang' => 'yang', 'element' => 'wood',  'reference_hour_start' => 3],
            ['name_en' => 'Mao',  'name_zh' => '卯', 'animal_en' => 'Rabbit',  'yin_yang' => 'yin',  'element' => 'wood',  'reference_hour_start' => 5],
            ['name_en' => 'Chen', 'name_zh' => '辰', 'animal_en' => 'Dragon',  'yin_yang' => 'yang', 'element' => 'earth', 'reference_hour_start' => 7],
            ['name_en' => 'Si',   'name_zh' => '巳', 'animal_en' => 'Snake',   'yin_yang' => 'yin',  'element' => 'fire',  'reference_hour_start' => 9],
            ['name_en' => 'Wu',   'name_zh' => '午', 'animal_en' => 'Horse',   'yin_yang' => 'yang', 'element' => 'fire',  'reference_hour_start' => 11],
            ['name_en' => 'Wei',  'name_zh' => '未', 'animal_en' => 'Goat',    'yin_yang' => 'yin',  'element' => 'earth', 'reference_hour_start' => 13],
            ['name_en' => 'Shen', 'name_zh' => '申', 'animal_en' => 'Monkey',  'yin_yang' => 'yang', 'element' => 'metal', 'reference_hour_start' => 15],
            ['name_en' => 'You',  'name_zh' => '酉', 'animal_en' => 'Rooster', 'yin_yang' => 'yin',  'element' => 'metal', 'reference_hour_start' => 17],
            ['name_en' => 'Xu',   'name_zh' => '戌', 'animal_en' => 'Dog',     'yin_yang' => 'yang', 'element' => 'earth', 'reference_hour_start' => 19],
            ['name_en' => 'Hai',  'name_zh' => '亥', 'animal_en' => 'Pig',     'yin_yang' => 'yin',  'element' => 'water', 'reference_hour_start' => 21],
        ];

        foreach ($branches as $branch) {
            EarthlyBranch::create($branch);
        }

        // 3. Jia Zi (The 60 Pillars)
        // Mapping für Na Yin Elements (verkürzt für Übersichtlichkeit, hier vollständige Liste empfohlen)
        // Da die Liste lang ist, generieren wir hier die Basis-Verknüpfung.
        // Das Na Yin Element kann man auch algorithmisch berechnen, aber ein Lookup ist einfacher.

        $naYinLookup = [
            1 => 'Sea Gold', 2 => 'Sea Gold', 3 => 'Furnace Fire', 4 => 'Furnace Fire',
            5 => 'Forest Wood', 6 => 'Forest Wood', 7 => 'Road Earth', 8 => 'Road Earth',
            9 => 'Sword Metal', 10 => 'Sword Metal', 11 => 'Mountain Fire', 12 => 'Mountain Fire',
            13 => 'Cave Water', 14 => 'Cave Water', 15 => 'City Wall Earth', 16 => 'City Wall Earth',
            17 => 'White Wax Metal', 18 => 'White Wax Metal', 19 => 'Willow Wood', 20 => 'Willow Wood',
            21 => 'Spring Water', 22 => 'Spring Water', 23 => 'Roof Earth', 24 => 'Roof Earth',
            25 => 'Thunder Fire', 26 => 'Thunder Fire', 27 => 'Pine Wood', 28 => 'Pine Wood',
            29 => 'Stream Water', 30 => 'Stream Water', 31 => 'Sand Metal', 32 => 'Sand Metal',
            33 => 'Mountain Fire', 34 => 'Mountain Fire', 35 => 'Flatland Wood', 36 => 'Flatland Wood',
            37 => 'Wall Earth', 38 => 'Wall Earth', 39 => 'Foil Metal', 40 => 'Foil Metal',
            41 => 'Lamp Fire', 42 => 'Lamp Fire', 43 => 'Sky River Water', 44 => 'Sky River Water',
            45 => 'Highway Earth', 46 => 'Highway Earth', 47 => 'Jewelry Metal', 48 => 'Jewelry Metal',
            49 => 'Mountain Wood', 50 => 'Mountain Wood', 51 => 'Stream Water', 52 => 'Stream Water',
            53 => 'Sand Earth', 54 => 'Sand Earth', 55 => 'Sky Fire', 56 => 'Sky Fire',
            57 => 'Pomegranate Wood', 58 => 'Pomegranate Wood', 59 => 'Ocean Water', 60 => 'Ocean Water'
        ];
        // Hinweis: Die englischen Namen variieren je nach Übersetzung (Gold vs Metal). Ich nutze Standard.

        for ($i = 1; $i <= 60; $i++) {
            // Berechne IDs (Zyklisch)
            $stemId = ($i - 1) % 10 + 1;
            $branchId = ($i - 1) % 12 + 1;

            // Namen holen (wir laden sie frisch aus der DB oder nutzen Arrays wenn performance wichtig)
            $stem = HeavenlyStem::find($stemId);
            $branch = EarthlyBranch::find($branchId);

            JiaZi::create([
                'id' => $i, // Wichtig: ID erzwingen, damit 1=JiaZi bleibt
                'stem_id' => $stemId,
                'branch_id' => $branchId,
                'name_en' => $stem->name_en . ' ' . $branch->name_en,
                'name_zh' => $stem->name_zh . $branch->name_zh,
                'na_yin_element' => $naYinLookup[$i] ?? 'Unknown',
            ]);
        }
    }
}
