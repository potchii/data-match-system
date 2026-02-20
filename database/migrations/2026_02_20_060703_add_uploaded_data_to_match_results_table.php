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
        Schema::table('match_results', function (Blueprint $table) {
            $table->string('uploaded_last_name')->nullable()->after('uploaded_record_id');
            $table->string('uploaded_first_name')->nullable()->after('uploaded_last_name');
            $table->string('uploaded_middle_name')->nullable()->after('uploaded_first_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('match_results', function (Blueprint $table) {
            $table->dropColumn(['uploaded_last_name', 'uploaded_first_name', 'uploaded_middle_name']);
        });
    }
};
