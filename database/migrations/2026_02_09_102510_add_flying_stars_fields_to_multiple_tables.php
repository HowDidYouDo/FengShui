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
        // 1. Projects Erweiterung
        Schema::table('projects', function (Blueprint $table) {
            $table->string('facing_mountain')->nullable()->after('period');
            $table->boolean('is_replacement_chart')->default(false)->after('facing_mountain');
            $table->string('special_chart_type')->nullable()->after('is_replacement_chart');
        });

        // 2. Bagua Notes Erweiterung (Sektoren)
        Schema::table('bagua_notes', function (Blueprint $table) {
            $table->unsignedTinyInteger('mountain_star')->nullable()->after('gua_number');
            $table->unsignedTinyInteger('water_star')->nullable()->after('mountain_star');
            $table->unsignedTinyInteger('base_star')->nullable()->after('water_star');
            $table->unsignedTinyInteger('yearly_star')->nullable()->after('base_star');
            $table->unsignedTinyInteger('monthly_star')->nullable()->after('yearly_star');
            $table->string('room_type')->nullable()->after('monthly_star');
            $table->text('stars_analysis')->nullable()->after('content');
        });

        // 3. Customers Erweiterung (Life Gua)
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedTinyInteger('life_gua')->nullable()->after('gender');
            $table->string('kua_group')->nullable()->after('life_gua'); // 'east' or 'west'
        });

        // 4. Family Members Erweiterung (Life Gua)
        Schema::table('family_members', function (Blueprint $table) {
            $table->unsignedTinyInteger('life_gua')->nullable()->after('gender');
            $table->string('kua_group')->nullable()->after('life_gua'); // 'east' or 'west'
        });

        // 5. Room Assignments Erweiterung
        Schema::table('room_assignments', function (Blueprint $table) {
            $table->string('usage_type')->nullable()->after('family_member_id');
            $table->decimal('person_facing_direction', 5, 2)->nullable()->after('usage_type');
            $table->unsignedTinyInteger('suitability_rating')->nullable()->after('person_facing_direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_assignments', function (Blueprint $table) {
            $table->dropColumn(['usage_type', 'person_facing_direction', 'suitability_rating']);
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->dropColumn(['life_gua', 'kua_group']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['life_gua', 'kua_group']);
        });

        Schema::table('bagua_notes', function (Blueprint $table) {
            $table->dropColumn([
                'mountain_star',
                'water_star',
                'base_star',
                'yearly_star',
                'monthly_star',
                'room_type',
                'stars_analysis'
            ]);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['facing_mountain', 'is_replacement_chart', 'special_chart_type']);
        });
    }
};
