# Anleitung zur Verwendung des Flying Stars Moduls

Dieses Dokument beschreibt die Funktionen und den Arbeitsablauf für das Flying Stars Modul (Xuan Kong Fei Xing) innerhalb der Feng Shui App.

## 1. Einleitung
Das Flying Stars Modul ermöglicht die detaillierte energetische Analyse von Gebäuden basierend auf ihrer zeitlichen Entstehung (Periode) und ihrer exakten Ausrichtung im Raum (Blickrichtung). Die App berechnet automatisch das 9-Felder-Raster mit Berg-, Wasser- und Zeitsternen.

## 2. Voraussetzungen
* Aktives Feature-Paket `flying_stars` oder Administrator-Rechte.
* Ein angelegtes Projekt mit einem Kunden.
* Mindestens ein hochgeladener Grundriss.

## 3. Schritt-für-Schritt Arbeitsablauf

### Schritt 1: Projekt-Parameter festlegen
Bevor Sterne berechnet werden können, müssen die Basisdaten des Gebäudes im Projekt definiert werden:
1. Öffnen Sie die Analyse des Kunden.
2. Gehen Sie zum Tab **"Floor Plans & Map"**.
3. Klicken Sie auf **"Edit Details"** beim aktiven Projekt.
4. Geben Sie das **Einzugsjahr (Settled Year)** an. Die Periode wird automatisch berechnet (z.B. 2004-2023 = Periode 8).
5. Geben Sie die **Facing Direction (Blickrichtung)** in Grad (0-360°) ein.

#### Manuelle Overrides (Für Fortgeschrittene)
Das System berechnet den **Facing Mountain** (einer der 24 Berge) und den Status als **Replacement Chart** (Ersatzsterne) automatisch. Sie können diese jedoch manuell überschreiben, falls Messungenauigkeiten oder Sonderfälle vorliegen.
* **Special Chart Type:** Hier können Sie Sonderformationen wie "Dual Star at Facing" oder "Sum of Ten" markieren.

### Schritt 2: Bagua-Gitter legen & Berechnen
1. Öffnen Sie den **Editor** für einen Grundriss.
2. Richten Sie das Bagua-Raster an den Außenwänden des Hauses aus.
3. Geben Sie (optional) die Lüftungsrichtung (**Ventilation Direction**) an. 
   - *Hinweis:* Das Bagua-Gitter orientiert sich an dieser Richtung (z.B. Haupt-Lüftungsstrom), während das Flying Star Chart immer auf der globalen **Blickrichtung (Facing)** des Gebäudes basiert. Das System mappt die Sterne automatisch korrekt auf die Himmelsrichtungen des Gitters.
4. Klicken Sie auf den Button **"Bagua"**.
   - Das System legt nun das klassische Bagua-Gitter an.
   - Wenn das Flying Stars Feature aktiv ist, werden automatisch die Sterne für jeden Sektor berechnet und im Gitter angezeigt.
   - **Darstellung:** Bergstern (oben links, bernstein), Wasserstern (oben rechts, blau), Zeitstern (unten mittig, grau).

### Schritt 3: Raumnutzung und Bewohner zuweisen
Im Editor können Sie nun die reale Nutzung der Räume definieren:
1. Ziehen Sie Bewohner (Kunde/Familie) per **Drag & Drop** in die jeweiligen Sektoren.
2. Das System berechnet sofort die persönliche Kompatibilität (Gua-Zahl vs. Sektor-Richtung).

### Schritt 4: Flying Stars Analyse & Report
Wechseln Sie in der Hauptansicht zum Tab **"Flying Stars Analysis"**. Hier finden Sie eine professionelle Aufbereitung der Daten:
* **Chart-Übersicht:** Das gesamte 9-Felder-Raster des Projekts auf einen Blick.
* **Sektor-Details:** Eine Liste aller Sektoren mit:
    - **Raumtyp:** Wählen Sie hier die Funktion des Raums (z.B. Schlafzimmer).
    - **Timely Badge:** Zeigt an, ob der Berg- oder Wasserstern in der aktuellen Periode besonders kraftvoll (zeitgemäß) ist.
    - **Bewohner-Kompatibilität:** Das System analysiert, wie die fliegenden Sterne auf die dort zugewiesenen Personen wirken (basierend auf den 5-Elemente-Zyklen).
    - **Beraternotizen:** Speichern Sie Ihre individuellen Empfehlungen und Heilmittel (Remedies) direkt pro Sektor.

## 4. Fachliche Interpretation der Sterne
* **Bergstern (Mountain Star):** Steht für Gesundheit, Beziehungen und menschliche Harmonie. Wirkt besonders stark in Räumen mit wenig Aktivität (Schlafzimmer).
* **Wasserstern (Water Star):** Steht für Wohlstand, Karriere und Geldfluss. Wirkt besonders stark in aktiven Bereichen (Eingang, Wohnzimmer).
* **Zeitstern (Base Star):** Definiert die zeitliche Grundqualität des Sektors in der gewählten Periode.

---
*Stand: Februar 2026*
