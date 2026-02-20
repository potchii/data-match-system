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
            $table->foreignId('origin_batch_id')->nullable()->after('uid')->constrained('upload_batches')->onDelete('set null');
            $table->unsignedBigInteger('origin_match_result_id')->nullable()->after('origin_batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_system', function (Blueprint $table) {
            $table->dropForeign(['origin_batch_id']);
            $table->dropColumn(['origin_batch_id', 'origin_match_result_id']);
        });
    }
};
