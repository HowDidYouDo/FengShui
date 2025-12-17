<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Schritt 1: Bestehende Daten sichern
        $features = DB::table('features')->get();

        // Schritt 2: Spalten zu JSON ändern
        Schema::table('features', function (Blueprint $table) {
            $table->json('name')->change();
            $table->json('description')->nullable()->change();
        });

        // Schritt 3: Bestehende Daten in JSON-Format konvertieren
        foreach ($features as $feature) {
            DB::table('features')
                ->where('id', $feature->id)
                ->update([
                        'name' => json_encode([
                            'en' => $feature->name,
                            'de' => $feature->name, // Wird später übersetzt
                        ]),
                        'description' => $feature->description ? json_encode([
                            'en' => $feature->description,
                            'de' => $feature->description, // Wird später übersetzt
                        ]) : null,
                    ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schritt 1: Daten zurück zu String konvertieren (nur EN)
        $features = DB::table('features')->get();

        Schema::table('features', function (Blueprint $table) {
            $table->string('name')->change();
            $table->text('description')->nullable()->change();
        });

        foreach ($features as $feature) {
            $nameData = json_decode($feature->name, true);
            $descData = json_decode($feature->description, true);

            DB::table('features')
                ->where('id', $feature->id)
                ->update([
                        'name' => $nameData['en'] ?? $feature->name,
                        'description' => $descData ? ($descData['en'] ?? null) : null,
                    ]);
        }
    }
};
