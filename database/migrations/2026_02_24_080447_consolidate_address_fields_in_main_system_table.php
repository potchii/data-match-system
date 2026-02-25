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
        // Step 1: Add new address column
        Schema::table('main_system', function (Blueprint $table) {
            $table->string('address', 500)->nullable()->after('civil_status');
        });

        // Step 2: Migrate existing data - combine street_no, street, city, province into address
        // Use different syntax for SQLite vs MySQL
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite doesn't have CONCAT_WS, use || operator
            DB::statement("
                UPDATE main_system 
                SET address = TRIM(
                    COALESCE(NULLIF(street_no, ''), '') || 
                    CASE WHEN NULLIF(street_no, '') IS NOT NULL AND NULLIF(street, '') IS NOT NULL THEN ', ' ELSE '' END ||
                    COALESCE(NULLIF(street, ''), '') ||
                    CASE WHEN (NULLIF(street_no, '') IS NOT NULL OR NULLIF(street, '') IS NOT NULL) AND NULLIF(city, '') IS NOT NULL THEN ', ' ELSE '' END ||
                    COALESCE(NULLIF(city, ''), '') ||
                    CASE WHEN (NULLIF(street_no, '') IS NOT NULL OR NULLIF(street, '') IS NOT NULL OR NULLIF(city, '') IS NOT NULL) AND NULLIF(province, '') IS NOT NULL THEN ', ' ELSE '' END ||
                    COALESCE(NULLIF(province, ''), '')
                )
                WHERE street_no IS NOT NULL 
                   OR street IS NOT NULL 
                   OR city IS NOT NULL 
                   OR province IS NOT NULL
            ");
        } else {
            // MySQL/MariaDB
            DB::statement("
                UPDATE main_system 
                SET address = TRIM(CONCAT_WS(', ',
                    NULLIF(street_no, ''),
                    NULLIF(street, ''),
                    NULLIF(city, ''),
                    NULLIF(province, '')
                ))
                WHERE street_no IS NOT NULL 
                   OR street IS NOT NULL 
                   OR city IS NOT NULL 
                   OR province IS NOT NULL
            ");
        }

        // Step 3: Drop old columns
        Schema::table('main_system', function (Blueprint $table) {
            $table->dropColumn(['street_no', 'street', 'city', 'province']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Re-add the old columns
        Schema::table('main_system', function (Blueprint $table) {
            $table->string('street_no', 50)->nullable()->after('civil_status');
            $table->string('street', 255)->nullable()->after('street_no');
            $table->string('city', 100)->nullable()->after('street');
            $table->string('province', 100)->nullable()->after('city');
        });

        // Step 2: Try to split address back (best effort - won't be perfect)
        DB::statement("
            UPDATE main_system 
            SET street = address
            WHERE address IS NOT NULL
        ");

        // Step 3: Drop the address column
        Schema::table('main_system', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
