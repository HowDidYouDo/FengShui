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
        Schema::create('features', function (Blueprint $table) {
            $table->id();

            // Der interne Code für den Programmierer (z.B. 'flying_stars')
            // unique, damit wir danach suchen können
            $table->string('code')->unique();

            // Der Name für den Kunden/Admin (z.B. "Flying Stars Profi Report")
            $table->string('name');

            // Beschreibung (für späteres Shop-Frontend)
            $table->text('description')->nullable();

            // Preisvorbereitung (in Cent speichern, um Rundungsfehler zu vermeiden!)
            // unsignedInteger reicht bis ~42 Mio. Einheiten. BigInteger ist sicherer.
            $table->unsignedBigInteger('price_netto')->default(0);

            // Währung, falls du international wirst (ISO Code: EUR, USD)
            $table->string('currency', 3)->default('EUR');

            // Globaler Schalter: Ist das Produkt überhaupt noch kaufbar?
            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
