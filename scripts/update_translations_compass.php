<?php

$newTranslations = [
    'de' => [
        "Compass Assistant" => "Kompass-Assistent",
        "Determine North & Facing Direction" => "Nord- & Blickrichtung bestimmen",
        "Instructions" => "Anleitung",
        "Ask the client which window is mainly used for ventilation." => "Fragen Sie den Klienten, welches Fenster hauptsächlich zum Lüften genutzt wird.",
        "Stand at this window looking out." => "Stellen Sie sich an dieses Fenster und schauen Sie hinaus.",
        "Hold the smartphone with the compass app active." => "Halten Sie das Smartphone mit aktiver Kompass-App.",
        "Point the TOP of the phone INTO the room (away from the window)." => "Zeigen Sie mit der OBERSEITE des Telefons IN den Raum (weg vom Fenster).",
        "Note the compass reading (0-360°)." => "Notieren Sie den Kompasswert (0-360°).",
        "Window" => "Fenster",
        "Into Room" => "In den Raum",
        "Start Measurement" => "Messung starten",
        "Select Floor Plan" => "Grundriss wählen",
        "Draw Arrow pointing INTO the house" => "Pfeil IN das Haus zeichnen",
        "Click and drag from the window INWARDS." => "Klicken und ziehen Sie vom Fenster nach INNEN.",
        "No floor plan selected" => "Kein Grundriss ausgewählt",
        "Arrow Angle: " => "Pfeilwinkel: ",
        "Line too short" => "Linie zu kurz",
        "Enter the degrees shown on the phone when pointing INTO the room." => "Geben Sie die Gradzahl ein, die das Telefon anzeigt, wenn es IN den Raum zeigt.",
        "Reading (°)" => "Messwert (°)",
        "North Deviation" => "Nord-Abweichung",
        "Map Rotation" => "Karten-Rotation",
        "Sitting (In)" => "Sitzrichtung (Rein)",
        "Energy Entry" => "Energie-Eintritt",
        "Facing (Out)" => "Blickrichtung (Raus)",
        "House Face" => "Haus-Fassade",
        "Calculation complete. Click Apply to update the project settings." => "Berechnung abgeschlossen. Klicken Sie auf Anwenden, um die Projekteinstellungen zu aktualisieren.",
        "Apply Settings" => "Einstellungen anwenden",
        "Please draw a longer arrow on the floor plan to indicate the direction clearly." => "Bitte zeichnen Sie einen längeren Pfeil auf den Grundriss, um die Richtung deutlich zu machen.",
        "Please draw an arrow on the floor plan pointing INTO the house." => "Bitte zeichnen Sie einen Pfeil auf den Grundriss, der IN das Haus zeigt.",
        "Settings updated successfully." => "Einstellungen erfolgreich aktualisiert."
    ],
    'en' => [
        "Compass Assistant" => "Compass Assistant",
        "Determine North & Facing Direction" => "Determine North & Facing Direction",
        "Instructions" => "Instructions",
        "Ask the client which window is mainly used for ventilation." => "Ask the client which window is mainly used for ventilation.",
        "Stand at this window looking out." => "Stand at this window looking out.",
        "Hold the smartphone with the compass app active." => "Hold the smartphone with the compass app active.",
        "Point the TOP of the phone INTO the room (away from the window)." => "Point the TOP of the phone INTO the room (away from the window).",
        "Note the compass reading (0-360°)." => "Note the compass reading (0-360°).",
        "Window" => "Window",
        "Into Room" => "Into Room",
        "Start Measurement" => "Start Measurement",
        "Select Floor Plan" => "Select Floor Plan",
        "Draw Arrow pointing INTO the house" => "Draw Arrow pointing INTO the house",
        "Click and drag from the window INWARDS." => "Click and drag from the window INWARDS.",
        "No floor plan selected" => "No floor plan selected",
        "Arrow Angle: " => "Arrow Angle: ",
        "Line too short" => "Line too short",
        "Enter the degrees shown on the phone when pointing INTO the room." => "Enter the degrees shown on the phone when pointing INTO the room.",
        "Reading (°)" => "Reading (°)",
        "North Deviation" => "North Deviation",
        "Map Rotation" => "Map Rotation",
        "Sitting (In)" => "Sitting (In)",
        "Energy Entry" => "Energy Entry",
        "Facing (Out)" => "Facing (Out)",
        "House Face" => "House Face",
        "Calculation complete. Click Apply to update the project settings." => "Calculation complete. Click Apply to update the project settings.",
        "Apply Settings" => "Apply Settings",
        "Please draw a longer arrow on the floor plan to indicate the direction clearly." => "Please draw a longer arrow on the floor plan to indicate the direction clearly.",
        "Please draw an arrow on the floor plan pointing INTO the house." => "Please draw an arrow on the floor plan pointing INTO the house.",
        "Settings updated successfully." => "Settings updated successfully."
    ]
];

// Replicate English for ES and FR for now (fallback)
$newTranslations['es'] = $newTranslations['en'];
$newTranslations['fr'] = $newTranslations['en'];

$langs = ['de', 'en', 'es', 'fr'];

foreach ($langs as $lang) {
    $file = __DIR__ . '/../lang/' . $lang . '.json';
    if (file_exists($file)) {
        $current = json_decode(file_get_contents($file), true);
        if (!$current) $current = [];
        
        // Merge new translations
        foreach ($newTranslations[$lang] as $key => $val) {
            $current[$key] = $val;
        }
        
        // Sort keys
        ksort($current);
        
        file_put_contents($file, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "Updated $lang.json\n";
    }
}
