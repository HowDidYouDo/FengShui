<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->renameColumn('facing_direction', 'compass_direction');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('sitting_direction', 8, 4)->nullable()->after('compass_direction');
        });

        // Migrate existing data: Sitting = (Compass + 180) % 360
        // Use raw SQL for compatibility
        DB::statement("UPDATE projects SET sitting_direction = MOD(compass_direction + 180, 360) WHERE compass_direction IS NOT NULL");
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('sitting_direction');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->renameColumn('compass_direction', 'facing_direction');
        });
    }
};
