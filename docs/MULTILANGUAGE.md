# Mehrsprachigkeit / Multi-Language Support

## Übersicht

Das System unterstützt jetzt automatische Spracherkennung und -verwaltung. Alle verfügbaren Sprachen werden automatisch
aus den JSON-Dateien im `lang/` Verzeichnis erkannt.

## Funktionen

### ✅ Automatische Spracherkennung

- Alle `.json` Dateien im `lang/` Verzeichnis werden automatisch erkannt
- Keine hardcodierten Sprachlisten mehr
- Einfaches Hinzufügen neuer Sprachen durch Erstellen einer neuen JSON-Datei

### ✅ Systemweite Sprachunterstützung

- **Frontend**: Alle Blade-Views nutzen die gewählte Sprache
- **Filament Admin**: Das Admin-Panel nutzt ebenfalls die Benutzersprache
- **Persistenz**: Spracheinstellung wird in der Datenbank gespeichert

### ✅ Moderne UI

- **Flaggen-Emojis**: Visuelle Darstellung der Sprachen
- **Native Namen**: Sprachen werden in ihrer Muttersprache angezeigt
- **Aktive Markierung**: Die aktuelle Sprache wird hervorgehoben
- **Responsive Design**: Funktioniert auf allen Geräten

## Verfügbare Sprachen

Aktuell unterstützte Sprachen:

- 🇩🇪 Deutsch (de)
- 🇬🇧 English (en)
- 🇪🇸 Español (es)
- 🇫🇷 Français (fr)

## Neue Sprache hinzufügen

1. **JSON-Datei erstellen**
   ```bash
   # Beispiel: Italienisch hinzufügen
   cp lang/en.json lang/it.json
   ```

2. **Übersetzungen anpassen**
    - Öffne die neue `it.json` Datei
    - Übersetze alle Werte (nicht die Keys!)

3. **Fertig!**
    - Die neue Sprache erscheint automatisch in allen Sprachauswahlen
    - Keine Code-Änderungen erforderlich

## Technische Details

### LanguageService

Der `App\Services\LanguageService` bietet folgende Methoden:

```php
// Alle verfügbaren Sprachen abrufen
LanguageService::getAvailableLanguages();
// Returns: ['de' => 'Deutsch', 'en' => 'English', ...]

// Sprachname abrufen
LanguageService::getLanguageName('de');
// Returns: 'Deutsch'

// Flaggen-Emoji abrufen
LanguageService::getLanguageFlag('de');
// Returns: '🇩🇪'

// Sprache validieren
LanguageService::isValidLocale('de');
// Returns: true/false

// Aktuelle Sprache abrufen
LanguageService::getCurrentLocale();
// Returns: 'de'
```

### Middleware

Die `SetLocale` Middleware setzt die Sprache in folgender Priorität:

1. **User-Präferenz** (aus Datenbank, wenn eingeloggt)
2. **Session** (für Gäste)
3. **App-Default** (aus `config/app.php`)

### Sprachauswahl-Komponenten

**Sidebar** (`resources/views/components/layouts/app/sidebar.blade.php`)

- Dropdown mit allen verfügbaren Sprachen
- Zeigt Flagge und nativen Namen
- Markiert aktive Sprache

**Einstellungen** (`resources/views/livewire/settings/appearance.blade.php`)

- Grid-Layout mit großen Karten
- Responsive (1-3 Spalten je nach Bildschirmgröße)
- Hover-Effekte und Animationen

## Best Practices

### Übersetzungskeys

✅ **Gut:**

```php
{{ __('Welcome back') }}
{{ __('Save Changes') }}
{{ __('e.g. Hamburg') }}
```

❌ **Schlecht:**

```php
{{ 'Welcome back' }}  // Nicht übersetzt
Welcome back          // Hardcoded
```

### Neue Übersetzungen hinzufügen

1. **Englisch als Basis** (`lang/en.json`)
   ```json
   {
     "New Feature": "New Feature"
   }
   ```

2. **Alle anderen Sprachen aktualisieren**
   ```json
   // lang/de.json
   {
     "New Feature": "Neue Funktion"
   }
   
   // lang/es.json
   {
     "New Feature": "Nueva Función"
   }
   ```

### Konsistenz

- Verwende immer `__()` für Übersetzungen
- Halte Keys in Englisch
- Verwende die gleichen Keys in allen Sprachdateien
- Sortiere Keys alphabetisch für bessere Übersicht

## Troubleshooting

### Sprache wird nicht gewechselt

```bash
# Cache leeren
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

### Neue Sprache erscheint nicht

1. Prüfe, ob die JSON-Datei im `lang/` Verzeichnis liegt
2. Prüfe, ob die Datei gültiges JSON ist
3. Leere den Cache (siehe oben)

### Übersetzungen fehlen

1. Prüfe, ob der Key in allen Sprachdateien vorhanden ist
2. Prüfe die JSON-Syntax (Kommas, Anführungszeichen)
3. Verwende einen JSON-Validator

## Migration von alten Übersetzungen

Falls du von Laravel's PHP-basierten Übersetzungen migrierst:

```bash
# Alte PHP-Übersetzungen zu JSON konvertieren
php artisan lang:publish
```

Dann manuell die Übersetzungen in die JSON-Dateien übertragen.
