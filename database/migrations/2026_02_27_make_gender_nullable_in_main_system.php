<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('main_system', function (Blueprint $table) {
            $table->string('gender')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('main_system', function (Blueprint $table) {
            $table->string('gender')->nullable(false)->change();
        });
    }
};
