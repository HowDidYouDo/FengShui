<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // NEU: Verweis auf die Features Tabelle statt freiem String
            // Wir verknüpfen über die ID, das ist am performantesten.
            $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();

            $table->integer('quota')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Ein User kann ein Feature-Produkt nur einmal "besitzen"
            $table->unique(['user_id', 'feature_id']);
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_features');
    }
};
