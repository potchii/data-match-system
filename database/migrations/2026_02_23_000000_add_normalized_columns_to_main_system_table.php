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
        Schema::table('main_system', function (Blueprint $table) {
            $table->string('last_name_normalized')->nullable()->after('last_name')->index();
            $table->string('first_name_normalized')->nullable()->after('first_name')->index();
            $table->string('middle_name_normalized')->nullable()->after('middle_name')->index();
        });
        
        // Backfill normalized columns for existing records
        DB::statement("
            UPDATE main_system 
            SET 
                last_name_normalized = LOWER(TRIM(last_name)),
                first_name_normalized = LOWER(TRIM(first_name)),
                middle_name_normalized = LOWER(TRIM(middle_name))
            WHERE last_name_normalized IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_system', function (Blueprint $table) {
            $table->dropColumn(['last_name_normalized', 'first_name_normalized', 'middle_name_normalized']);
        });
    }
};

