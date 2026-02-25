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
        Schema::create('template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')
                ->constrained('column_mapping_templates')
                ->onDelete('cascade');
            $table->string('field_name', 255);
            $table->enum('field_type', ['string', 'integer', 'date', 'boolean', 'decimal']);
            $table->boolean('is_required')->default(false);
            $table->timestamps();
            
            // Unique constraint on template_id and field_name
            $table->unique(['template_id', 'field_name'], 'unique_template_field');
            
            // Index on template_id for faster lookups
            $table->index('template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_fields');
    }
};
