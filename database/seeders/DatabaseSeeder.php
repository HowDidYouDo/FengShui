<?php
namespace Database\Seeders;

use App\Models\Feature;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Rollen erstellen (Spatie)
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $roleAdmin = Role::create(['name' => 'admin']);
        $roleUser = Role::create(['name' => 'user']);

        // 2. Features (Katalog) anlegen
        // Wir legen die Features an, von denen wir wissen, dass sie kommen
        $featReports = Feature::create([
            'code' => 'reports',
            'name' => 'Professional Reports',
            'description' => 'Creation of professional Reports for clients',
            'price_netto' => 29000, // 290.00 EUR
        ]);

        $featClients = Feature::create([
            'code' => 'crm',
            'name' => 'multiple Customers (CRM)',
            'description' => 'Verwaltung von Klienten und deren Daten',
            'price_netto' => 19000, // 190.00 EUR
        ]);

        $featFlyingStars = Feature::create([
            'code' => 'flying_stars',
            'name' => 'Flying Stars Modul',
            'description' => 'Berechnung der fliegenden Sterne',
            'price_netto' => 49000, // 490.00 EUR
        ]);

        $featBagua = Feature::create([
           'code' => 'bagua',
           'name' => 'Bagua Map',
            'description' => 'The Bagua Map is a Chinese symbol representing the five elements and the eight directions. It is used to understand and control life energy.',          'price_netto' => 0,
           'is_default' => true,
        ]);

        $featFamily = Feature::create([
           'code' => 'family',
           'name' => 'Family Tree',
           'description' => 'Create Bagua for the whole family (max 5 members)',
           'price_netto' => 2900,
        ]);

        // 3. Dein Admin-User erstellen
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'eike@stehr.email', // <--- HIER ÄNDERN
            'password' => Hash::make('Suessemaus1'), // <--- HIER ÄNDERN
            'email_verified_at' => now(),
        ]);

        // Rolle zuweisen
        $admin->assignRole($roleAdmin);

        // Dem Admin testweise Features geben (damit du alles testen kannst)
        // Z.B. Reports unbegrenzt, Clients mit Limit 100
        $admin->features()->create([
            'feature_id' => $featReports->id,
            'quota' => null, // unbegrenzt
            'active' => true,
        ]);

        $admin->features()->create([
            'feature_id' => $featClients->id,
            'quota' => 100, // Limit zum Testen
            'active' => true,
        ]);

        // 4. Einen Test-User erstellen (zum Ausprobieren des Frontends später)
        $testUser = User::create([
            'name' => 'Sandra Stehr',
            'email' => 'sandra@stehr.email',
            'password' => Hash::make('Hochzeit@2012'),
        ]);
        $testUser->assignRole($roleUser);
        // Kunde hat vorerst keine Features gekauft
    }
}
