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
        Schema::table('features', function (Blueprint $table) {
            // Defines the purchase type: 'lifetime' for one-time purchases, 'subscription' for recurring.
            $table->string('purchase_type')->default('lifetime')->after('currency');

            // For subscriptions, defines the renewal period, e.g., 'yearly', 'monthly'. Null for lifetime products.
            $table->string('renewal_period')->nullable()->after('purchase_type');

            // Default quota assigned when this feature is licensed (e.g., number of family members).
            $table->unsignedInteger('default_quota')->nullable()->after('renewal_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn(['purchase_type', 'renewal_period', 'default_quota']);
        });
    }
};
