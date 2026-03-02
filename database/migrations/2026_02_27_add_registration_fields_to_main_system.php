<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('main_system', function (Blueprint $table) {
            $table->string('regs_no')->nullable()->after('uid');
            $table->date('registration_date')->nullable()->after('regs_no');
            $table->string('id_type')->nullable()->after('registration_date');
            $table->string('status')->default('active')->after('id_type');
            $table->string('category')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('main_system', function (Blueprint $table) {
            $table->dropColumn(['regs_no', 'registration_date', 'id_type', 'status', 'category']);
        });
    }
};
