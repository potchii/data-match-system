<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Removes the additional_attributes JSON column from main_system table
     * as part of migration to proper database columns and template fields.
     */
    public function up(): void
    {
        Schema::table('main_system', function (Blueprint $table) {
            $table->dropColumn('additional_attributes');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Restores the additional_attributes column for rollback capability.
     */
    public function down(): void
    {
        Schema::table('main_system', function (Blueprint $table) {
            $table->json('additional_attributes')->nullable()->after('barangay');
        });
    }
};
