<?php

$de = json_decode(file_get_contents(__DIR__ . '/../lang/de.json'), true);
$en = json_decode(file_get_contents(__DIR__ . '/../lang/en.json'), true);
$es = json_decode(file_get_contents(__DIR__ . '/../lang/es.json'), true);
$fr = json_decode(file_get_contents(__DIR__ . '/../lang/fr.json'), true);

$allKeys = array_unique(array_merge(array_keys($de), array_keys($en), array_keys($es), array_keys($fr)));
sort($allKeys);

$missing = [
    'de' => array_diff($allKeys, array_keys($de)),
    'en' => array_diff($allKeys, array_keys($en)),
    'es' => array_diff($allKeys, array_keys($es)),
    'fr' => array_diff($allKeys, array_keys($fr)),
];

echo "Total unique keys: " . count($allKeys) . PHP_EOL;
echo "DE keys: " . count($de) . PHP_EOL;
echo "EN keys: " . count($en) . PHP_EOL;
echo "ES keys: " . count($es) . PHP_EOL;
echo "FR keys: " . count($fr) . PHP_EOL;
echo PHP_EOL;
echo "Missing in DE: " . count($missing['de']) . PHP_EOL;
echo "Missing in EN: " . count($missing['en']) . PHP_EOL;
echo "Missing in ES: " . count($missing['es']) . PHP_EOL;
echo "Missing in FR: " . count($missing['fr']) . PHP_EOL;
echo PHP_EOL;

if (count($missing['de']) > 0) {
    echo "Missing DE keys:" . PHP_EOL;
    foreach ($missing['de'] as $key) {
        echo "  - " . $key . PHP_EOL;
    }
    echo PHP_EOL;
}
if (count($missing['en']) > 0) {
    echo "Missing EN keys:" . PHP_EOL;
    foreach ($missing['en'] as $key) {
        echo "  - " . $key . PHP_EOL;
    }
    echo PHP_EOL;
}
if (count($missing['es']) > 0) {
    echo "Missing ES keys:" . PHP_EOL;
    foreach ($missing['es'] as $key) {
        echo "  - " . $key . PHP_EOL;
    }
    echo PHP_EOL;
}
if (count($missing['fr']) > 0) {
    echo "Missing FR keys:" . PHP_EOL;
    foreach ($missing['fr'] as $key) {
        echo "  - " . $key . PHP_EOL;
    }
    echo PHP_EOL;
}

// Output missing translations for each language
$output = [
    'de' => [],
    'en' => [],
    'es' => [],
    'fr' => [],
];

foreach ($missing['de'] as $key) {
    $output['de'][$key] = $en[$key] ?? $key;
}
foreach ($missing['en'] as $key) {
    $output['en'][$key] = $key;
}
foreach ($missing['es'] as $key) {
    $output['es'][$key] = $en[$key] ?? $key;
}
foreach ($missing['fr'] as $key) {
    $output['fr'][$key] = $en[$key] ?? $key;
}

file_put_contents(__DIR__ . '/missing_de.json', json_encode($output['de'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents(__DIR__ . '/missing_en.json', json_encode($output['en'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents(__DIR__ . '/missing_es.json', json_encode($output['es'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents(__DIR__ . '/missing_fr.json', json_encode($output['fr'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Missing translations exported to scripts/missing_*.json files" . PHP_EOL;
