<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. CUSTOMERS (Kunden / Mandanten)
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Der Consultant

            // Stammdaten
            $table->string('name');
            $table->string('email')->nullable();

            // Rechnungsanschrift
            $table->string('billing_street')->nullable();
            $table->string('billing_zip')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_country')->nullable();

            // Metaphysik-Daten (Person)
            $table->date('birth_date')->nullable();
            $table->time('birth_time')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('gender', 1)->nullable(); // 'm', 'f'

            $table->text('notes')->nullable();
            $table->boolean('is_self_profile')->default(false);
            $table->timestamps();
        });

        // 2. FAMILY MEMBERS (Bewohner)
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('relationship')->nullable();

            // Metaphysik-Daten
            $table->date('birth_date')->nullable();
            $table->time('birth_time')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('gender', 1)->nullable();

            $table->timestamps();
        });

        // 3. PROJECTS (Das Haus / Objekt)
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');

            // Gebäude-Daten
            $table->integer('settled_year')->nullable();
            $table->decimal('facing_direction', 5, 2)->nullable(); // 0.00 bis 359.99
            $table->decimal('ventilation_direction', 5, 2)->nullable(); // 0.00 bis 359.99
            $table->integer('period')->nullable(); // Feng Shui Periode (1-9)

            $table->timestamps();
        });

        // 4. FLOOR PLANS (Grundrisse)
        Schema::create('floor_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            $table->string('title')->nullable();
            $table->integer('sort_order')->default(0);

            // JSON Daten für Canvas/Koordinaten
            $table->json('outer_bounds')->nullable();
            $table->json('room_data')->nullable();

            $table->timestamps();
        });

        // 5. BAGUA NOTES (Analysen pro Sektor)
        Schema::create('bagua_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_plan_id')->constrained('floor_plans')->cascadeOnDelete();

            $table->unsignedTinyInteger('gua_number'); // 1-9
            $table->text('content')->nullable();

            $table->timestamps();
            $table->unique(['floor_plan_id', 'gua_number']);
        });

        // 6. Raumzuweisung für BAGUA NOTES und Familienmitglieder
        Schema::create('room_assignments', function (Blueprint $table) {
            $table->id();

            // Link to the specific sector on a floor plan
            $table->foreignId('bagua_note_id')->constrained()->cascadeOnDelete();

            // The Person (either a main customer OR a family member)
            // We ensure in code that only ONE of these is set
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('family_member_id')->nullable()->constrained()->cascadeOnDelete();

            $table->timestamps();

            // Optional: Prevent duplicates (Person can only be assigned once to the same room)
            // $table->unique(['bagua_note_id', 'family_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_assignments');
        Schema::dropIfExists('bagua_notes');
        Schema::dropIfExists('floor_plans');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('family_members');
        Schema::dropIfExists('customers');
    }
};
