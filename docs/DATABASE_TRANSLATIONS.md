# Datenbank-Übersetzungen / Database Translations

## Übersicht

Für Datenbankinhalte, die in mehreren Sprachen angezeigt werden sollen, verwenden wir das **Spatie Laravel Translatable** Package.

## Verwendung

### Modell konfigurieren

```php
use Spatie\Translatable\HasTranslations;

class Feature extends Model
{
    use HasTranslations;
    
    // Definiere übersetzbare Felder
    public $translatable = ['name', 'description'];
}
```

### Übersetzungen setzen

```php
// Einzelne Übersetzung setzen
$feature->setTranslation('name', 'de', 'Professionelle Berichte');
$feature->setTranslation('name', 'en', 'Professional Reports');

// Alle Übersetzungen auf einmal setzen
$feature->setTranslations('name', [
    'en' => 'Professional Reports',
    'de' => 'Professionelle Berichte',
    'es' => 'Informes Profesionales',
    'fr' => 'Rapports Professionnels',
]);

$feature->save();
```

### Übersetzungen abrufen

```php
// Automatisch in aktueller Sprache
$feature->name; // "Professionelle Berichte" (wenn app()->getLocale() === 'de')

// Spezifische Sprache
$feature->getTranslation('name', 'en'); // "Professional Reports"

// Alle Übersetzungen
$feature->getTranslations('name'); 
// ['en' => 'Professional Reports', 'de' => 'Professionelle Berichte', ...]

// Fallback auf andere Sprache
$feature->name; // Nutzt automatisch Fallback-Sprache wenn aktuelle nicht verfügbar
```

### In Blade-Views

```blade
<!-- Automatisch in aktueller Sprache -->
<h1>{{ $feature->name }}</h1>

<!-- Spezifische Sprache -->
<h1>{{ $feature->getTranslation('name', 'de') }}</h1>
```

## Migration erstellen

### Neue Tabelle mit übersetzten Feldern

```php
Schema::create('features', function (Blueprint $table) {
    $table->id();
    $table->string('code')->unique();
    $table->json('name');           // Übersetzbar
    $table->json('description');    // Übersetzbar
    $table->timestamps();
});
```

### Bestehende Tabelle konvertieren

```php
public function up(): void
{
    // 1. Bestehende Daten sichern
    $features = DB::table('features')->get();
    
    // 2. Spalten zu JSON ändern
    Schema::table('features', function (Blueprint $table) {
        $table->json('name')->change();
        $table->json('description')->nullable()->change();
    });
    
    // 3. Daten konvertieren
    foreach ($features as $feature) {
        DB::table('features')
            ->where('id', $feature->id)
            ->update([
                'name' => json_encode([
                    'en' => $feature->name,
                    'de' => $feature->name, // Später übersetzen
                ]),
                'description' => $feature->description ? json_encode([
                    'en' => $feature->description,
                    'de' => $feature->description,
                ]) : null,
            ]);
    }
}
```

## Seeder für Übersetzungen

```php
class TranslateFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        $translations = [
            'bagua' => [
                'name' => [
                    'en' => 'Bagua Map',
                    'de' => 'Bagua Karte',
                    'es' => 'Mapa Bagua',
                    'fr' => 'Carte Bagua',
                ],
                'description' => [
                    'en' => 'BaguaMap is the base for your home calculation.',
                    'de' => 'BaguaMap ist die Grundlage für Ihre Hausberechnung.',
                    'es' => 'BaguaMap es la base para el cálculo de su hogar.',
                    'fr' => 'BaguaMap est la base du calcul de votre maison.',
                ],
            ],
        ];

        foreach ($translations as $code => $trans) {
            $feature = Feature::where('code', $code)->first();
            
            if ($feature) {
                $feature->setTranslations('name', $trans['name']);
                $feature->setTranslations('description', $trans['description']);
                $feature->save();
            }
        }
    }
}
```

Ausführen:
```bash
php artisan db:seed --class=TranslateFeaturesSeeder
```

## Filament Integration

### In Filament Forms

```php
use Filament\Forms\Components\TextInput;

TextInput::make('name')
    ->label('Name')
    ->required()
    ->translatable() // Aktiviert Sprachauswahl
```

### In Filament Tables

```php
use Filament\Tables\Columns\TextColumn;

TextColumn::make('name')
    ->label('Name')
    ->searchable()
    ->sortable()
```

Die Übersetzung erfolgt automatisch basierend auf `app()->getLocale()`.

## Best Practices

### ✅ Wann Datenbank-Übersetzungen verwenden

- **Benutzergenerierte Inhalte**: Produktnamen, Beschreibungen
- **CMS-Inhalte**: Seiten, Artikel, Kategorien
- **Konfigurierbare Texte**: Feature-Namen, Optionen
- **Dynamische Daten**: Alles was im Admin-Panel bearbeitet wird

### ❌ Wann NICHT verwenden

- **UI-Texte**: Buttons, Labels → Nutze `lang/*.json`
- **Statische Texte**: Überschriften, Menüs → Nutze `__('...')`
- **Validierungsmeldungen**: → Nutze Laravel's Validation
- **E-Mail-Templates**: → Nutze Laravel's Notifications

### Fallback-Strategie

```php
// In config/translatable.php
return [
    'fallback_locale' => 'en',
    'fallback_any_locale' => true, // Nutzt irgendeine verfügbare Sprache
];
```

### Performance

```php
// Eager Loading für bessere Performance
$features = Feature::with('translations')->get();

// Oder spezifische Sprachen laden
$features = Feature::withTranslations(['de', 'en'])->get();
```

## Troubleshooting

### Übersetzung wird nicht angezeigt

1. **Prüfe Spaltentyp**
   ```sql
   SHOW COLUMNS FROM features WHERE Field = 'name';
   -- Type sollte 'json' sein
   ```

2. **Prüfe JSON-Format**
   ```php
   dd($feature->getTranslations('name'));
   // Sollte Array sein: ['en' => '...', 'de' => '...']
   ```

3. **Prüfe Trait**
   ```php
   // Model muss HasTranslations trait haben
   use Spatie\Translatable\HasTranslations;
   ```

### Migration schlägt fehl

```bash
# Doctrine DBAL für column changes
composer require doctrine/dbal

# Dann Migration erneut ausführen
php artisan migrate
```

### Alte Daten wiederherstellen

```php
// In Migration down()
$nameData = json_decode($feature->name, true);
$name = $nameData['en'] ?? $feature->name;
```

## Beispiel: Vollständiger Workflow

### 1. Model erstellen

```php
php artisan make:model Product -m
```

### 2. Migration

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('sku')->unique();
    $table->json('name');
    $table->json('description');
    $table->decimal('price', 10, 2);
    $table->timestamps();
});
```

### 3. Model konfigurieren

```php
class Product extends Model
{
    use HasTranslations;
    
    protected $fillable = ['sku', 'name', 'description', 'price'];
    public $translatable = ['name', 'description'];
}
```

### 4. Produkt erstellen

```php
Product::create([
    'sku' => 'PROD-001',
    'name' => [
        'en' => 'Premium Widget',
        'de' => 'Premium Widget',
        'es' => 'Widget Premium',
    ],
    'description' => [
        'en' => 'The best widget on the market',
        'de' => 'Das beste Widget auf dem Markt',
        'es' => 'El mejor widget del mercado',
    ],
    'price' => 99.99,
]);
```

### 5. In View anzeigen

```blade
<h1>{{ $product->name }}</h1>
<p>{{ $product->description }}</p>
<p>{{ number_format($product->price, 2) }} €</p>
```

## Weitere Ressourcen

- [Spatie Translatable Dokumentation](https://github.com/spatie/laravel-translatable)
- [Laravel Localization](https://laravel.com/docs/localization)
- [Filament Translatable](https://filamentphp.com/docs/forms/fields#translatable)
