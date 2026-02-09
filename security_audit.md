# Sicherheitsaudit - FengShui App

## 1. Zusammenfassung
Die Anwendung weist eine solide Grundstruktur auf, insbesondere durch die Verwendung moderner Frameworks wie Laravel und Filament, die bereits viele Sicherheitsmechanismen (CSRF-Schutz, SQL-Injection-Prävention, etc.) mitbringen. Es wurden jedoch einige kritische und mittelschwere Schwachstellen identifiziert, insbesondere im Bereich der Datenhaltung und der administrativen Schnittstellen.

## 2. Kritische Schwachstellen

### 2.1 Exponierte 2FA-Geheimnisse im Admin-Panel
**Datei:** `app/Filament/Resources/Users/Schemas/UserForm.php`
* **Problem:** Die Felder `two_factor_secret` und `two_factor_recovery_codes` sind im Benutzer-Formular des Admin-Panels als editierbare Textareas sichtbar.
* **Risiko:** Administratoren können die 2FA-Geheimnisse von Benutzern sehen oder manipulieren, was den Schutz durch Zwei-Faktor-Authentifizierung komplett aushebelt.
* **Lösung:** Diese Felder sollten aus dem Formular entfernt werden. Die Verwaltung von 2FA sollte ausschließlich durch den Benutzer selbst über die Fortify-Schnittstellen erfolgen.

### 2.2 API-Tokens im Klartext in der Datenbank
**Datei:** `app/Models/InvoiceNinjaConfig.php` / `app/Services/InvoiceNinjaService.php`
* **Problem:** API-Tokens für Invoice Ninja werden unverschlüsselt in der Tabelle `invoice_ninja_configs` gespeichert.
* **Risiko:** Bei einer Kompromittierung der Datenbank (z.B. durch ein SQL-Injection-Leck oder ein unsicheres Backup) sind alle API-Tokens sofort im Klartext verfügbar.
* **Lösung:** Nutzung von Laravels Eloquent-Verschlüsselung (`encrypted` casting).

## 3. Mittelschwere Schwachstellen

### 3.1 Ungesicherte interne API-Kommunikation
**Datei:** `app/Jobs/AnalyzeFloorPlanJob.php`
* **Problem:** Die Kommunikation mit dem Python-Service (FastAPI) erfolgt über ungesichertes HTTP ohne Authentifizierungstoken.
* **Risiko:** Wenn der Python-Service (auch nur kurzzeitig oder durch Fehlkonfiguration) von außen erreichbar ist, können Angreifer beliebige Daten zur Analyse senden oder den Service überlasten.
* **Lösung:** Implementierung eines einfachen Bearer-Tokens oder eines Shared-Secrets zwischen Laravel und dem Python-Service.

### 3.2 Unverschlüsselte Speicherung personenbezogener Daten (PII)
**Datei:** `app/Models/Customer.php`, `app/Models/FamilyMember.php`
* **Problem:** Sensible Daten wie Geburtsdatum, Geburtsort und Adressen werden im Klartext gespeichert.
* **Risiko:** Datenschutzverstoß (DSGVO) bei Datenverlust. Diese Daten sind besonders schützenswert.
* **Lösung:** Verschlüsselung der Felder `birth_date`, `birth_place`, `billing_street` etc. mittels Eloquent-Casting.

## 4. Niedrige Schwachstellen & Best Practices

### 4.1 Direkte Verwendung von `env()` im Code
**Datei:** `app/Filament/Pages/ManageInvoiceNinja.php`
* **Problem:** Aufruf von `env('INVOICE_NINJA_API_SECRET_TOKEN')` direkt in einer Komponente.
* **Risiko:** Wenn der Konfigurations-Cache (`php artisan config:cache`) aktiv ist, gibt `env()` oft `null` zurück, was zu Anwendungsfehlern führt.
* **Lösung:** Definition eines Konfigurationswertes in `config/services.php` und Zugriff via `config()`.

### 4.2 Passwort-Handling im Admin-Panel
**Datei:** `app/Filament/Resources/Users/Schemas/UserForm.php`
* **Problem:** Das Passwort-Feld ist als `required()` markiert.
* **Risiko:** Bei jeder Änderung eines Benutzers muss ein neues Passwort vergeben werden, oder das bestehende wird (je nach Implementierung) überschrieben/ungültig.
* **Lösung:** Passwort-Feld nur beim Erstellen als Pflichtfeld markieren und `dehydrated(fn($state) => filled($state))` nutzen.

### 4.3 Hartkodierte Service-URLs
**Datei:** `app/Jobs/AnalyzeFloorPlanJob.php`
* **Problem:** Die URL `http://127.0.0.1:8090` ist hartkodiert.
* **Risiko:** Erschwert Deployment in verschiedenen Umgebungen (Staging, Produktion).
* **Lösung:** In die `.env` und Konfigurationsdateien auslagern.

## 5. Empfohlene Maßnahmen (Code-Beispiele)

### Zu 2.2 (Tokens verschlüsseln)
```php
// app/Models/InvoiceNinjaConfig.php
protected $casts = [
    'value' => 'encrypted',
];
```

### Zu 3.2 (PII verschlüsseln)
```php
// app/Models/Customer.php
protected $casts = [
    'birth_date' => 'encrypted:date',
    'birth_place' => 'encrypted',
    'billing_street' => 'encrypted',
    // ...
];
```

### Zu 4.2 (Passwort-Handling)
```php
// app/Filament/Resources/Users/Schemas/UserForm.php
TextInput::make('password')
    ->password()
    ->required(fn (string $context): bool => $context === 'create')
    ->dehydrated(fn ($state) => filled($state))
    ->label(__('Password')),
```
