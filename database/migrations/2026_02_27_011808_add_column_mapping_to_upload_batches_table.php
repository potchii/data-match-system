<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds column_mapping JSON field to store mapping summary
     * for display in results view.
     */
    public function up(): void
    {
        Schema::table('upload_batches', function (Blueprint $table) {
            $table->json('column_mapping')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('upload_batches', function (Blueprint $table) {
            $table->dropColumn('column_mapping');
        });
    }
};
