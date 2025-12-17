<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
// 1. Heavenly Stems (Die 10 Himmelsstämme)
        Schema::create('heavenly_stems', function (Blueprint $table) {
            $table->id();
            $table->string('name_en'); // z.B. 'Jia'
            $table->string('name_zh'); // z.B. '甲'
            $table->enum('yin_yang', ['yin', 'yang']);
            $table->enum('element', ['wood', 'fire', 'earth', 'metal', 'water']);
            $table->timestamps();
        });

// 2. Earthly Branches (Die 12 Erdzweige / Tiere)
        Schema::create('earthly_branches', function (Blueprint $table) {
            $table->id();
            $table->string('name_en'); // z.B. 'Zi' (Rat)
            $table->string('name_zh'); // z.B. '子'
            $table->string('animal_en'); // z.B. 'Rat'
            $table->enum('yin_yang', ['yin', 'yang']);
            $table->enum('element', ['wood', 'fire', 'earth', 'metal', 'water']);
            $table->integer('reference_hour_start'); // 23 (für 23:00 - 01:00)
            $table->timestamps();
        });

// 3. The 60 Jia Zi (Die 60 Säulen)
        Schema::create('jia_zi', function (Blueprint $table) {
            $table->id(); // 1 bis 60
            $table->foreignId('stem_id')->constrained('heavenly_stems');
            $table->foreignId('branch_id')->constrained('earthly_branches');
            $table->string('name_en'); // z.B. 'Jia Zi'
            $table->string('name_zh'); // z.B. '甲子'
            $table->string('na_yin_element'); // Das Melodische Element (z.B. 'Sea Metal')
            $table->timestamps();
        });

// 4. Solar Terms (Für den Kalender - Jie Qi)
// Da die exakte Minute astronomisch berechnet wird, speichern wir Referenzdaten
// für die Jahre 1900 - 2100 per Seeder.
        Schema::create('solar_terms', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string('name_en'); // z.B. 'Start of Spring' (Li Chun)
            $table->integer('term_index'); // 1-24
            $table->dateTime('utc_time'); // Der exakte astronomische Zeitpunkt
            $table->timestamps();

// Index für schnelle Suche
            $table->index(['year', 'term_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solar_terms');
        Schema::dropIfExists('jia_zi');
        Schema::dropIfExists('earthly_branches');
        Schema::dropIfExists('heavenly_stems');
    }
};
