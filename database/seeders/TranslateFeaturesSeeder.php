<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class TranslateFeaturesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            'reports' => [
                'name' => [
                    'en' => 'Professional Reports',
                    'de' => 'Professionelle Berichte',
                    'es' => 'Informes Profesionales',
                    'fr' => 'Rapports Professionnels',
                ],
                'description' => [
                    'en' => 'Automatically create individual reports for your customers',
                    'de' => 'Erstellen Sie automatisch individuelle Berichte für Ihre Kunden',
                    'es' => 'Cree automáticamente informes individuales para sus clientes',
                    'fr' => 'Créez automatiquement des rapports individuels pour vos clients',
                ],
            ],
            'crm' => [
                'name' => [
                    'en' => 'Clients (CRM)',
                    'de' => 'Klienten (CRM)',
                    'es' => 'Clientes (CRM)',
                    'fr' => 'Clients (CRM)',
                ],
                'description' => [
                    'en' => 'Work with clients and customers.',
                    'de' => 'Arbeiten Sie mit Klienten und Kunden.',
                    'es' => 'Trabaje con clientes y consumidores.',
                    'fr' => 'Travaillez avec des clients et des consommateurs.',
                ],
            ],
            'flying_stars' => [
                'name' => [
                    'en' => 'Flying Stars',
                    'de' => 'Fliegende Sterne',
                    'es' => 'Estrellas Voladoras',
                    'fr' => 'Étoiles Volantes',
                ],
                'description' => [
                    'en' => 'Calculation of flying stars, the masterpiece of FengShui',
                    'de' => 'Berechnung der fliegenden Sterne, das Meisterstück des FengShui',
                    'es' => 'Cálculo de estrellas voladoras, la obra maestra del FengShui',
                    'fr' => 'Calcul des étoiles volantes, le chef-d\'œuvre du FengShui',
                ],
            ],
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
            'family' => [
                'name' => [
                    'en' => 'Family Analysis',
                    'de' => 'Familienanalyse',
                    'es' => 'Análisis Familiar',
                    'fr' => 'Analyse Familiale',
                ],
                'description' => [
                    'en' => 'Create Bagua for the whole family (max 5 members)',
                    'de' => 'Erstellen Sie Bagua für die ganze Familie (max. 5 Mitglieder)',
                    'es' => 'Cree Bagua para toda la familia (máx. 5 miembros)',
                    'fr' => 'Créez Bagua pour toute la famille (max. 5 membres)',
                ],
            ],
        ];

        foreach ($translations as $code => $trans) {
            $feature = Feature::where('code', $code)->first();

            if ($feature) {
                $feature->setTranslations('name', $trans['name']);
                $feature->setTranslations('description', $trans['description']);
                $feature->save();

                $this->command->info("✓ Translated: {$code}");
            } else {
                $this->command->warn("✗ Feature not found: {$code}");
            }
        }
    }
}
