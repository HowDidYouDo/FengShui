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
        Schema::table('customers', function (Blueprint $table) {
            $table->text('birth_date')->nullable()->change();
            $table->text('birth_place')->nullable()->change();
            $table->text('billing_street')->nullable()->change();
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->text('birth_date')->nullable()->change();
            $table->text('birth_place')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->change();
            $table->string('birth_place')->nullable()->change();
            $table->string('billing_street')->nullable()->change();
        });

        Schema::table('family_members', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->change();
            $table->string('birth_place')->nullable()->change();
        });
    }
};
