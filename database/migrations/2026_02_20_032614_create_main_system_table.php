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
            $table->date('birthday');
            $table->string('gender');
            $table->string('civil_status')->nullable();
            $table->string('street_no')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('barangay')->nullable();
            $table->timestamps();
            
            // Indexes for faster matching
            $table->index(['last_name', 'first_name', 'birthday']);
            $table->index('uid');
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
