<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Convert optional string fields from nullable to default empty string.
     * This simplifies comparisons and avoids NULL-related edge cases.
     * 
     * Fields changed:
     * - middle_name, suffix, civil_status (personal info)
     * - address, barangay (location fields)
     * 
     * Fields kept nullable:
     * - birthday (date type - NULL means truly unknown)
     * - gender (can be truly unknown, not just empty)
     */
    public function up(): void
    {
        $stringFields = [
            'middle_name',
            'suffix',
            'civil_status',
            'address',
            'barangay',
        ];

        // First, update existing NULL values to empty strings for all fields
        foreach ($stringFields as $field) {
            DB::table('main_system')
                ->whereNull($field)
                ->update([$field => '']);
        }

        // Then modify all columns to be non-nullable with default empty string
        Schema::table('main_system', function (Blueprint $table) use ($stringFields) {
            foreach ($stringFields as $field) {
                $table->string($field)->default('')->nullable(false)->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $stringFields = [
            'middle_name',
            'suffix',
            'civil_status',
            'address',
            'barangay',
        ];

        // Revert all fields to nullable
        Schema::table('main_system', function (Blueprint $table) use ($stringFields) {
            foreach ($stringFields as $field) {
                $table->string($field)->nullable()->change();
            }
        });
    }
};
