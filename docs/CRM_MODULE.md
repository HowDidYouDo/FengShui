# CRM Module - Klientenverwaltung

## Übersicht

Das CRM-Modul bietet eine vollständige CRUD-Funktionalität für die Verwaltung von Klienten (Customers) in der FengShui-Anwendung. Es ist als Filament Resource implementiert und in ein separates **CRM Panel** integriert, das für Benutzer (Consultants) zugänglich ist.

## Features

### ✅ Vollständiges CRUD
- **Create**: Neue Klienten anlegen mit allen erforderlichen Daten
- **Read**: Klientenliste mit Filterung und Suche
- **Update**: Klientendaten bearbeiten
- **Delete**: Klienten löschen (mit Schutz für Self-Profiles)

### 🔒 Sicherheit & Policies
- Benutzer können nur ihre eigenen Klienten sehen und verwalten
- Self-Profiles können nicht gelöscht werden
- Automatische Zuweisung des `user_id` beim Erstellen
- **Feature-Check**: Zugriff auf das CRM-Panel und die Klientenliste erfordert das Feature `clients`.

### 📋 Datenfelder

#### Grundinformationen
- Name (Pflichtfeld)
- E-Mail (optional)
- Notizen

#### Geburtsdaten
- Geburtsdatum (Pflichtfeld)
- Geburtszeit (Pflichtfeld)
- Geburtsort (Pflichtfeld)
- Geschlecht (m/f, Pflichtfeld)

#### Rechnungsadresse
- Straße & Hausnummer
- PLZ
- Stadt
- Land

#### System
- Is Self Profile (automatisch gesetzt)
- User ID (automatisch gesetzt)

## Zugriff

### Dashboard
Das CRM-Modul ist über das Haupt-Dashboard (`/dashboard`) erreichbar. Wenn der Benutzer das Feature `clients` besitzt, erscheint dort eine Karte "Client Management" (oder ähnlich), die zum CRM-Panel verlinkt.

**URL**: `/crm`

### Integration mit Bagua-Modul
Das Bagua-Modul zeigt weiterhin die Klientenliste an, aber die Verwaltung (Anlegen, Bearbeiten, Löschen) erfolgt jetzt ausschließlich über das CRM-Modul.

## Technische Details

### Dateien
- **Panel Provider**: `app/Providers/Filament/CrmPanelProvider.php`
- **Resource**: `app/Filament/Crm/Resources/Customers/CustomerResource.php`
- **Form**: `app/Filament/Crm/Resources/Customers/Schemas/CustomerForm.php`
- **Table**: `app/Filament/Crm/Resources/Customers/Tables/CustomersTable.php`
- **Infolist**: `app/Filament/Crm/Resources/Customers/Schemas/CustomerInfolist.php`
- **Pages**:
  - `ListCustomers.php`
  - `CreateCustomer.php`
  - `EditCustomer.php`
  - `ViewCustomer.php`

### Model
- **Model**: `app/Models/Customer.php`
- **Policy**: `app/Policies/CustomerPolicy.php`
- **Migration**: `database/migrations/2025_11_26_093744_create_crm_tables.php`

### Features
- Automatisches Setzen von `user_id` via Model Boot
- Scoping der Queries auf den aktuellen Benutzer
- Übersetzungen in Deutsch (de.json)
- Responsive Tabellen mit Sortierung und Filterung

## Übersetzungen

Alle UI-Strings sind übersetzt. Neue Übersetzungen wurden in `lang/de.json` hinzugefügt.
