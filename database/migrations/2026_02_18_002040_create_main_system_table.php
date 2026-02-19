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
        Schema::create('main_system', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('suffix')->nullable();
            $table->date('birthday')->nullable();
            $table->string('gender');
            $table->string('civil_status');
            $table->string('street_no');
            $table->string('street');
            $table->string('city');
            $table->string('province');
            $table->date('registration_date')->nullable();
            $table->text('full_name_meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_system');
    }
};
