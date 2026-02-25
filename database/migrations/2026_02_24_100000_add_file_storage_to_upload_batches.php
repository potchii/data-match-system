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
        Schema::table('upload_batches', function (Blueprint $table) {
            $table->string('file_hash', 64)->nullable()->after('file_name');
            $table->string('stored_file_path', 500)->nullable()->after('file_hash');
            $table->bigInteger('file_size')->nullable()->after('stored_file_path');
            
            $table->index('file_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('upload_batches', function (Blueprint $table) {
            $table->dropIndex(['file_hash']);
            $table->dropColumn(['file_hash', 'stored_file_path', 'file_size']);
        });
    }
};
